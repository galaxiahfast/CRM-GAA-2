<?php

namespace App\Services\Support;

use Carbon\CarbonImmutable;

/**
 * Guía en PDF del Panel General. Reutiliza por completo el formato membretado de
 * los Reportes de soporte (SupportGuidePdfService) y agrega, después de las
 * preguntas de uso del Centro de Ayuda, la sección del programador de tareas.
 */
class PanelGeneralGuidePdfService extends SupportGuidePdfService
{
    /**
     * @return array<string, mixed>|null
     */
    public function data(string $categoryKey, ?CarbonImmutable $now = null): ?array
    {
        $data = parent::data($categoryKey, $now);
        if ($data === null) {
            return null;
        }

        $data['items'] = [...$data['items'], ...$this->schedulerItems()];
        $data['summary'] = trim($data['summary'].' Al final de la guía se explica cómo está configurado el programador de tareas que mantiene en marcha los procesos automáticos del CRM.');

        return $data;
    }

    /**
     * @return array<int, array{question: string, answer: string, steps: array<int, string>}>
     */
    private function schedulerItems(): array
    {
        $autoClose = (string) config('time-control.auto_close_at', '21:00');
        $autoCloseZone = (string) config('time-control.timezone', 'America/Mexico_City');
        $chatZone = (string) config('support.timezone', 'America/Mexico_City');

        return [
            [
                'question' => '¿Qué es el programador de tareas y por qué debe estar activo?',
                'answer' => 'El programador ejecuta por sí solo los procesos automáticos del CRM: la sincronización del checador, el cierre de cronómetros, la limpieza de sesiones y del chat de soporte, la actualización del índice de respaldos y el correo de clientes incompletos. Si deja de ejecutarse, esos procesos se detienen aunque el CRM siga abierto.',
                'steps' => [
                    'Windows lo activa cada minuto con el archivo cronjob.bat que está en la carpeta del proyecto.',
                    'En cada llamada, Laravel revisa qué tareas corresponden a ese minuto y ejecuta solo esas.',
                    'El programador no procesa la cola de trabajos: ese proceso (queue:work) se mantiene aparte.',
                ],
            ],
            [
                'question' => '¿Cómo está configurado el programador en el servidor?',
                'answer' => 'Una tarea del Programador de tareas de Windows ejecuta cada minuto el archivo cronjob.bat. Ese archivo entra a la carpeta del CRM y ejecuta “php artisan schedule:run”.',
                'steps' => [
                    'Abre el Programador de tareas de Windows en el servidor y crea una tarea nueva.',
                    'En Acciones elige “Iniciar un programa” y selecciona el archivo cronjob.bat de la carpeta del proyecto (por ejemplo, C:\\xampp\\htdocs\\crm-gaa-2\\cronjob.bat).',
                    'En Desencadenadores crea uno que se repita cada 1 minuto de forma indefinida y que se inicie con el equipo.',
                    'En General usa la cuenta del servidor y marca “Ejecutar tanto si el usuario inició sesión como si no”.',
                    'Confirma que cronjob.bat apunte a la carpeta del proyecto y a C:\\xampp\\php\\php.exe; si el CRM está en otra ruta, ajústalas.',
                    'En un equipo de desarrollo se logra lo mismo dejando abierta una terminal con “php artisan schedule:work”.',
                ],
            ],
            [
                'question' => '¿Qué tareas ejecuta el programador y cuándo?',
                'answer' => 'Estas son las tareas automáticas que el programador lanza actualmente. Los horarios sin zona indicada siguen la hora del servidor.',
                'steps' => [
                    'Cada minuto: sincroniza las marcas del checador biométrico.',
                    'Cada minuto: actualiza el índice de respaldos de CONTPAQi del día (solo lectura).',
                    'Todos los días a las 23:00: mantenimiento del checador; valida los últimos 30 días y depura inconsistencias.',
                    "Todos los días a las {$autoClose} (hora {$autoCloseZone}): cierra los cronómetros que sigan activos.",
                    'Cada 30 minutos: elimina las sesiones vencidas.',
                    "Todos los días a las 00:05 (hora {$chatZone}): borra los mensajes de días anteriores del chat de soporte.",
                    'Los días 5, 17 y 28 de cada mes a las 08:00: envía el correo “Clientes Incompletos - Reporte Semanal”.',
                ],
            ],
            [
                'question' => '¿Cómo compruebo que el programador funciona?',
                'answer' => 'Revisa las tareas desde la carpeta del proyecto y confirma que la tarea de Windows termina sin errores.',
                'steps' => [
                    'En una terminal dentro de la carpeta del proyecto ejecuta “php artisan schedule:list” para ver las tareas y su próxima ejecución.',
                    'En el Programador de tareas de Windows revisa que el resultado de la última ejecución indique que terminó correctamente.',
                    'Si cambiaste el archivo .env, ejecuta “php artisan optimize:clear” para que el programador use los valores nuevos.',
                    'Si una tarea no corre, ejecuta “php artisan schedule:run” a mano y revisa el mensaje de error que muestre.',
                ],
            ],
        ];
    }
}
