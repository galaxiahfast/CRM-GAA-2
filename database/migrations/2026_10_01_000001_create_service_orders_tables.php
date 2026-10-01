<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_servicio', function (Blueprint $table): void {
            $table->id();
            $table->string('folio', 32)->unique();
            $table->uuid('request_token')->unique();
            $table->string('tipo', 30)->index();
            $table->foreignId('cliente_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->unsignedBigInteger('contacto_id')->nullable();
            $table->string('cliente_nombre', 120);
            $table->string('contacto', 120)->default('');
            $table->string('quien_entrega', 120)->default('Julián Emiliano Ortiz Rivero');
            $table->string('tipo_equipo', 80);
            $table->string('equipo_marca', 100)->default('');
            $table->string('equipo_modelo', 120);
            $table->string('equipo_serie', 100)->default('');
            $table->json('accesorios')->nullable();
            $table->string('estado_fisico', 30)->default('bueno');
            $table->text('falla_reportada')->nullable();
            $table->text('diagnostico')->nullable();
            $table->text('reparacion_realizada')->nullable();
            $table->string('estado', 30)->index();
            $table->dateTime('fecha_recepcion')->nullable();
            $table->dateTime('fecha_entrega_prometida')->nullable();
            $table->dateTime('fecha_entrega_real')->nullable();
            $table->dateTime('fecha_limite_devolucion')->nullable();
            $table->dateTime('fecha_devolucion')->nullable();
            $table->boolean('firma_recepcion')->default(false);
            $table->boolean('firma_entrega')->default(false);
            $table->text('observaciones')->nullable();
            $table->decimal('precio', 12, 2)->nullable();
            $table->string('forma_pago', 60)->nullable();
            $table->string('garantia', 160)->nullable();
            $table->string('foto_path')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('legacy_report_id')->nullable()->unique()->constrained('equipment_delivery_reports')->nullOnDelete();
            $table->timestamps();

            $table->index(['cliente_nombre', 'estado'], 'ordenes_cliente_estado_index');
            $table->index('equipo_serie', 'ordenes_equipo_serie_index');
        });

        Schema::create('movimientos_orden', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('orden_id')->constrained('ordenes_servicio')->cascadeOnDelete();
            $table->string('tipo_movimiento', 30)->index();
            $table->dateTime('fecha');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->json('evidencia')->nullable();
            $table->timestamps();

            $table->index(['orden_id', 'fecha'], 'movimientos_orden_fecha_index');
        });

        $this->migrateLegacyReports();
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_orden');
        Schema::dropIfExists('ordenes_servicio');
    }

    private function migrateLegacyReports(): void
    {
        if (! Schema::hasTable('equipment_delivery_reports')) {
            return;
        }

        DB::table('equipment_delivery_reports')->orderBy('id')->chunkById(100, function ($reports): void {
            foreach ($reports as $report) {
                $tipo = (string) $report->movement_type;
                $estado = match ($tipo) {
                    'recepcion' => 'recibido',
                    'entrega' => 'entregado',
                    'prestamo' => 'prestado',
                    'compra' => 'vendido',
                    default => 'recibido',
                };
                $accessories = array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/u', (string) $report->accessories) ?: [])));

                if ($tipo === 'entrega' && ($reception = $this->findPendingReception($report))) {
                    DB::table('ordenes_servicio')->where('id', $reception->id)->update([
                        'estado' => 'entregado',
                        'fecha_entrega_real' => $report->created_at,
                        'reparacion_realizada' => $report->observations ?: $reception->reparacion_realizada,
                        'observaciones' => $report->observations ?: $reception->observaciones,
                        'foto_path' => $report->photo_path ?: $reception->foto_path,
                        'updated_at' => $report->updated_at,
                    ]);
                    DB::table('movimientos_orden')->insert([
                        'orden_id' => $reception->id,
                        'tipo_movimiento' => 'entrega',
                        'fecha' => $report->created_at,
                        'usuario_id' => $report->created_by,
                        'notas' => $report->observations ?? '',
                        'evidencia' => $report->photo_path ? json_encode([$report->photo_path]) : null,
                        'created_at' => $report->created_at,
                        'updated_at' => $report->updated_at,
                    ]);

                    continue;
                }

                $orderId = DB::table('ordenes_servicio')->insertGetId([
                    'folio' => $report->folio,
                    'request_token' => $report->request_token ?: (string) Str::uuid(),
                    'tipo' => $tipo,
                    'cliente_id' => $report->customer_id ?? null,
                    'contacto_id' => null,
                    'cliente_nombre' => $report->customer_name,
                    'contacto' => $report->customer_contact ?? '',
                    'quien_entrega' => $report->delivered_by ?? 'Julián Emiliano Ortiz Rivero',
                    'tipo_equipo' => $report->equipment_type,
                    'equipo_marca' => $report->brand ?? '',
                    'equipo_modelo' => $report->model,
                    'equipo_serie' => $report->serial_number ?: 'SIN SERIE',
                    'accesorios' => json_encode($accessories, JSON_UNESCAPED_UNICODE),
                    'estado_fisico' => $report->physical_condition,
                    'falla_reportada' => $tipo === 'recepcion' ? $report->observations : null,
                    'diagnostico' => null,
                    'reparacion_realizada' => $tipo === 'entrega' ? $report->observations : null,
                    'estado' => $estado,
                    'fecha_recepcion' => $tipo === 'recepcion' ? $report->created_at : null,
                    'fecha_entrega_prometida' => null,
                    'fecha_entrega_real' => in_array($tipo, ['entrega', 'compra'], true) ? $report->created_at : null,
                    'fecha_limite_devolucion' => null,
                    'fecha_devolucion' => null,
                    'firma_recepcion' => false,
                    'firma_entrega' => false,
                    'observaciones' => $report->observations ?? '',
                    'precio' => null,
                    'forma_pago' => null,
                    'garantia' => null,
                    'foto_path' => $report->photo_path,
                    'creado_por' => $report->created_by,
                    'legacy_report_id' => $report->id,
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                ]);

                DB::table('movimientos_orden')->insert([
                    'orden_id' => $orderId,
                    'tipo_movimiento' => $tipo,
                    'fecha' => $report->created_at,
                    'usuario_id' => $report->created_by,
                    'notas' => $report->observations ?? '',
                    'evidencia' => $report->photo_path ? json_encode([$report->photo_path]) : null,
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                ]);
            }
        }, 'id');
    }

    private function findPendingReception(object $report): ?object
    {
        $query = DB::table('ordenes_servicio')
            ->where('tipo', 'recepcion')
            ->whereNotIn('estado', ['entregado', 'cancelado']);

        $serial = trim((string) ($report->serial_number ?? ''));
        if ($serial !== '' && mb_strtoupper($serial) !== 'SIN SERIE') {
            $match = (clone $query)->where('equipo_serie', $serial)->latest('id')->first();
            if ($match) {
                return $match;
            }
        }

        return $query
            ->where('cliente_nombre', (string) $report->customer_name)
            ->where('equipo_modelo', (string) $report->model)
            ->latest('id')
            ->first();
    }
};
