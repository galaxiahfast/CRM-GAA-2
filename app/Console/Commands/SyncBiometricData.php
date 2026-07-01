<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncBiometricData extends Command
{
    /**
     * El nombre y firma del comando para la consola de comandos.
     */
    protected $signature = 'biometric:sync {--maintenance : Ejecuta la limpieza profunda de los últimos 30 días}';

    /**
     * La descripción del comando.
     */
    protected $description = 'Sincroniza marcas desde el biométrico Hikvision ISAPI hacia la base de datos local';

    // Ajustes de conexión del dispositivo
    private array $chConfig = [
        'IP'   => '192.168.2.239',
        'USER' => 'admin',
        'PASS' => 'Temporal1.',
    ];

    /**
     * Ejecuta el comando de consola.
     */
    public function handle(): int
    {
        $this->info("=======================================================");
        $this->info("   SISTEMA DE SINCRONIZACIÓN HIKVISION - MODO ESPEJO   ");
        $this->info("=======================================================");

        $ahora = Carbon::now();

        // Evaluar si se solicita mantenimiento de 30 días o consulta rápida de 1 minuto
        if ($this->option('maintenance')) {
            $this->info("\n[OPCIÓN]: MANTENIMIENTO INICIAL SELECCIONADO (30 DÍAS)");
            $inicioBusqueda = $ahora->copy()->subDays(30)->second(0)->microsecond(0);
            $ejecutarLimpieza = true;
        } else {
            $this->info("\n[OPCIÓN]: EJECUTANDO CONSULTA INCREMENTAL RÁPIDA");
            
            // Buscar el último registro guardado en la tabla control_de_horas
            $ultimoRegistroStr = DB::table('control_de_horas')->max('authDateTime');

            if ($ultimoRegistroStr) {
                $inicioBusqueda = Carbon::parse($ultimoRegistroStr)->second(0)->microsecond(0);
            } else {
                $inicioBusqueda = $ahora->copy()->subDay()->second(0)->microsecond(0);
            }
            $ejecutarLimpieza = false;
        }

        $finBusqueda = $ahora->copy()->second(0)->microsecond(0);

        // Disparar sincronizador
        [$nuevos, $borrados] = $this->sincronizar($inicioBusqueda, $finBusqueda, $ejecutarLimpieza);

        $this->info("\n[PROCESO TERMINADO]");
        $this->comment(">> Registros nuevos agregados: {$nuevos}");
        $this->comment(">> Registros eliminados por consistencia: {$borrados}\n");

        return Command::SUCCESS;
    }

    /**
     * Consulta el endpoint de ISAPI y procesa el lote de datos.
     */
    private function sincronizar(Carbon $inicio, Carbon $fin, bool $ejecutarLimpieza): array
    {
        $inicioStr = $inicio->format('Y-m-d\TH:i:00');
        $finStr = $fin->format('Y-m-d\TH:i:00');

        $url = "http://{$this->chConfig['IP']}/ISAPI/AccessControl/AcsEvent?format=json";
        $posicion = 0;
        $nuevosTotales = 0;
        $idsEnChecador = [];

        $this->line(">> Consultando al dispositivo desde {$inicioStr} hasta {$finStr}");

        while (true) {
            $payload = [
                'AcsEventCond' => [
                    'searchID'             => 'sync_task',
                    'searchResultPosition' => $posicion,
                    'maxResults'           => 100,
                    'major'                => 5,
                    'minor'                => 0,
                    'startTime'            => $inicioStr,
                    'endTime'              => $finStr
                ]
            ];

            try {
                // Petición HTTP usando Digest Authentication integrado de Laravel
                $response = Http::withDigestAuth($this->chConfig['USER'], $this->chConfig['PASS'])
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->timeout(15)
                    ->post($url, $payload);

                if ($response->failed()) {
                    $this->error("\n>> Error HTTP {$response->status()}: " . $response->body());
                    break;
                }

                $data = $response->json('AcsEvent', []);
                $bloque = $data['InfoList'] ?? [];
                $totalEnEquipo = $data['totalMatches'] ?? 0;

                if (empty($bloque)) {
                    break;
                }

                // Iniciar una transacción de base de datos para insertar masivamente de forma segura
                DB::transaction(function () use ($bloque, $ejecutarLimpieza, &$idsEnChecador, &$nuevosTotales) {
                    foreach ($bloque as $e) {
                        $nombre = $e['name'] ?? $e['employeeName'] ?? null;
                        $empId = $e['employeeNo'] ?? $e['employeeNoString'] ?? null;

                        if ($nombre && $empId) {
                            // Limpieza de formato de fecha 'T' de ISAPI
                            $fhRaw = $e['time'] ?? '';
                            $fh = substr(str_replace('T', ' ', $fhRaw), 0, 19);
                            $fechaSolo = explode(' ', $fh)[0];
                            $horaSolo = explode(' ', $fh)[1];

                            if ($ejecutarLimpieza) {
                                $idsEnChecador[] = [
                                    'id' => (string) $empId,
                                    'fh' => $fh
                                ];
                            }

                            $direction = ($e['attendanceStatus'] ?? '') === 'checkIn' ? 'IN' : 'OUT';

                            // Insertar omitiendo duplicados (Equivalente a INSERT IGNORE)
                            $inserted = DB::table('control_de_horas')->insertOrIgnore([
                                'employeeID'   => $empId,
                                'personName'   => $nombre,
                                'authDateTime' => $fh,
                                'authDate'     => $fechaSolo,
                                'authTime'     => $horaSolo,
                                'direction'    => $direction,
                                'deviceName'   => 'Checador'
                            ]);

                            if ($inserted > 0) {
                                $nuevosTotales++;
                            }
                        }
                    }
                });

                $posicion += count($bloque);
                $this->output->write("   [PROGRESO] Leídos {$posicion} de {$totalEnEquipo} eventos...\r");

                if ($posicion >= $totalEnEquipo) {
                    break;
                }

            } catch (\Exception $ex) {
                $this->error("\n>> ERROR CRÍTICO DURANTE TRANSFERENCIA: " . $ex->getMessage());
                break;
            }
        }

        // Limpieza de registros locales eliminados en el hardware físico
        $borrados = 0;
        if ($ejecutarLimpieza && !empty($idsEnChecador)) {
            $this->line("\n>> Iniciando limpieza de registros ausentes en el checador...");
            
            $registrosDb = DB::table('control_de_horas')
                ->whereBetween('authDateTime', [str_replace('T', ' ', $inicioStr), str_replace('T', ' ', $finStr)])
                ->get(['employeeID', 'authDateTime']);

            foreach ($registrosDb as $reg) {
                $existe = false;
                foreach ($idsEnChecador as $ic) {
                    if ($ic['id'] === (string)$reg->employeeID && $ic['fh'] === $reg->authDateTime) {
                        $existe = true;
                        break;
                    }
                }

                if (!$existe) {
                    DB::table('control_de_horas')
                        ->where('employeeID', $reg->employeeID)
                        ->where('authDateTime', $reg->authDateTime)
                        ->delete();
                    $borrados++;
                }
            }
        }

        return [$nuevosTotales, $borrados];
    }
}