<?php

namespace App\Services\Support;

use App\Models\TechnicalSupportReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

class TechnicalSupportReportPdfService
{
    public function catalog(): array
    {
        return [
            'contpaqi_contabilidad_install' => ['title' => 'Instalación de CONTPAQi Contabilidad', 'description' => 'Instalación del sistema, componentes y validación inicial.'],
            'contpaqi_contabilidad_reinstall' => ['title' => 'Reinstalación de CONTPAQi Contabilidad', 'description' => 'Instalación limpia, componentes y validación inicial del sistema.'],
            'contpaqi_nominas_install' => ['title' => 'Instalación de CONTPAQi Nóminas', 'description' => 'Instalación del sistema, componentes y validación inicial.'],
            'contpaqi_nominas_reinstall' => ['title' => 'Reinstalación de CONTPAQi Nóminas', 'description' => 'Instalación limpia, componentes y validación inicial del sistema.'],
            'contpaqi_errors' => ['title' => 'Resolución de errores de CONTPAQi', 'description' => 'Diagnóstico y corrección de incidencias de operación o acceso.'],
            'contpaqi_contabilidad_update' => ['title' => 'Actualización de CONTPAQi Contabilidad', 'description' => 'Actualización normal en un equipo o terminal.'],
            'contpaqi_contabilidad_server_update' => ['title' => 'Actualización de servidor de CONTPAQi Contabilidad', 'description' => 'Actualización del servidor con registro de todas las versiones aplicadas.'],
            'contpaqi_nominas_update' => ['title' => 'Actualización de CONTPAQi Nóminas', 'description' => 'Actualización normal en un equipo o terminal.'],
            'contpaqi_nominas_server_update' => ['title' => 'Actualización de servidor de CONTPAQi Nóminas', 'description' => 'Actualización del servidor con registro de todas las versiones aplicadas.'],
            'contpaqi_backup_restore' => ['title' => 'Respaldo o restauración de CONTPAQi', 'description' => 'Generación, validación o recuperación de respaldos autorizados.'],
            'office_install' => ['title' => 'Instalación de Microsoft Office', 'description' => 'Instalación, activación autorizada y pruebas de las aplicaciones.'],
            'office_license_activation' => ['title' => 'Activación de licencia de Microsoft Office', 'description' => 'Activación y comprobación de la licencia de Microsoft Office en el equipo.'],
            'linked_spreadsheets_correction' => ['title' => 'Corrección de hojas de cálculo vinculadas', 'description' => 'Revisión y corrección de vínculos entre hojas de cálculo.'],
            'office_2013_uninstall' => ['title' => 'Desinstalación de Microsoft Office 2013', 'description' => 'Retiro completo de Microsoft Office 2013 antes de instalar la versión nueva.'],
            'office_2019_install' => ['title' => 'Instalación de Microsoft Office 2019', 'description' => 'Instalación y comprobación de las aplicaciones de Microsoft Office 2019.'],
            'office_2019_activation' => ['title' => 'Activación de Microsoft Office 2019 y corrección de errores de activación', 'description' => 'Activación de la licencia y corrección de los errores que impedían completarla.'],
            'excel_addin_persistence' => ['title' => 'Configuración del complemento de hoja de cálculo de Excel para mantenerlo activo', 'description' => 'Configuración de Excel para que el complemento de hoja de cálculo permanezca activo al abrir el programa.'],
            'report_linked_files_bug_fix' => ['title' => 'Corrección de error en los archivos enlazados del sistema de reportes', 'description' => 'Diagnóstico y corrección del error al enlazar archivos en el sistema de reportes.'],
            'microsoft_365_install' => ['title' => 'Instalación de Microsoft 365', 'description' => 'Instalación y comprobación de las aplicaciones de Microsoft 365.'],
            'microsoft_365_refresh' => ['title' => 'Desinstalación e instalación actualizada de Microsoft 365', 'description' => 'Retiro de la instalación anterior, instalación de la versión vigente y comprobación de funcionamiento.'],
            'claude_addin_setup' => ['title' => 'Configuración del complemento de Claude en Microsoft 365', 'description' => 'Instalación, configuración y validación del complemento de Claude.'],
            'claude_addin_activation' => ['title' => 'Activación del complemento de Claude en Microsoft 365', 'description' => 'Activación y comprobación del complemento de Claude en Microsoft 365.'],
            'audictamen_crystal_reports_install' => ['title' => 'Instalación del componente de Crystal Reports para AUDICTAMEN', 'description' => 'Instalación y comprobación del componente requerido para generar reportes en AUDICTAMEN.'],
            'microsoft_store_compatibility_update' => ['title' => 'Actualización de Windows para compatibilidad con Microsoft Store', 'description' => 'Actualización del equipo y comprobación de acceso a Microsoft Store para instalar Microsoft 365.'],
            'sql_server_2012_restore' => ['title' => 'Restauración de base de datos empresarial en SQL Server 2012', 'description' => 'Ejecución de comandos de restauración y validación de la base de datos empresarial.'],
            'sql_terminal_access_setup' => ['title' => 'Configuración de acceso SQL desde terminales', 'description' => 'Habilitación y comprobación del acceso a la base de datos desde las terminales autorizadas.'],
            'intern_email_creation' => ['title' => 'Creación de cuentas de correo para becarios', 'description' => 'Creación de las cuentas institucionales y preparación de sus credenciales de acceso.'],
            'email_web_access_limits' => ['title' => 'Configuración de accesos web y límites de correo', 'description' => 'Configuración de los accesos en Internet y aplicación de los límites correspondientes.'],
            'email_setup' => ['title' => 'Configuración de correo electrónico', 'description' => 'Alta y comprobación de cuentas en el equipo o dispositivo.'],
            'email_installation' => ['title' => 'Instalación de correo electrónico', 'description' => 'Instalación, alta y comprobación de la cuenta de correo en el equipo.'],
            'email_local_backup' => ['title' => 'Respaldo local de correo, configuración, contactos y calendario', 'description' => 'Resguardo de la información y parámetros de la cuenta antes del cambio de equipo.'],
            'email_data_migration' => ['title' => 'Migración de correo y datos al equipo nuevo', 'description' => 'Transferencia y validación del correo, contactos, calendario y configuración en el equipo destino.'],
            'email_pop_to_imap' => ['title' => 'Cambio de cuenta de correo de POP a IMAP y sincronización inicial', 'description' => 'Configuración de la cuenta mediante IMAP y comprobación de su sincronización inicial.'],
            'windows_startup_ini_removal' => ['title' => 'Eliminación de archivo .ini del inicio de Windows', 'description' => 'Retiro del archivo de configuración que se abría al iniciar o reiniciar el equipo.'],
            'computer_printer_connection' => ['title' => 'Conexión de computadora a impresora', 'description' => 'Vinculación del equipo con la impresora y comprobación de impresión.'],
            'computer_relocation' => ['title' => 'Reubicación y reconexión de equipo de cómputo', 'description' => 'Cambio físico de ubicación, reconexión y comprobación del equipo.'],
            'windows_contpaqi_compatibility_update' => ['title' => 'Actualización de Windows para compatibilidad con CONTPAQi', 'description' => 'Actualización del sistema operativo y validación de compatibilidad con CONTPAQi.'],
            'ssd_full_backup' => ['title' => 'Respaldo integral de información y configuraciones', 'description' => 'Resguardo de archivos, parámetros y configuraciones del equipo.'],
            'ssd_corrected_system_migration' => ['title' => 'Instalación de nueva unidad SSD y migración corregida del sistema', 'description' => 'Migración del sistema y la información a una nueva unidad SSD, corrigiendo errores detectados.'],
            'ssd_diagnosis_repair' => ['title' => 'Diagnóstico y reparación de unidad SSD anterior', 'description' => 'Revisión, corrección y comprobación de la unidad SSD reemplazada.'],
            'printer_setup' => ['title' => 'Configuración de impresora o escáner', 'description' => 'Instalación de controladores, conexión y pruebas de impresión.'],
            'network_setup' => ['title' => 'Configuración de red', 'description' => 'Ajuste de conectividad local, recursos compartidos o acceso a Internet.'],
            'windows_setup' => ['title' => 'Configuración de Windows', 'description' => 'Ajustes del sistema, usuarios, controladores y actualizaciones.'],
            'antivirus_install' => ['title' => 'Instalación o configuración de antivirus', 'description' => 'Instalación, actualización y comprobación de protección.'],
            'data_migration' => ['title' => 'Migración de información', 'description' => 'Transferencia controlada de archivos y validación en el equipo destino.'],
            'general_diagnosis' => ['title' => 'Diagnóstico general de equipo', 'description' => 'Revisión de hardware, sistema operativo y aplicaciones.'],
        ];
    }

    public function data(TechnicalSupportReport $report): array
    {
        $issuedAt = CarbonImmutable::instance($report->issued_at)
            ->timezone(config('support.timezone', 'America/Mexico_City'))
            ->locale('es');

        return [
            'serial' => $report->folio,
            'issuedAt' => $issuedAt,
            'issuedAtLong' => ucfirst($issuedAt->isoFormat('dddd, D [de] MMMM [de] YYYY, HH:mm:ss')).' '.$issuedAt->format('T'),
            'responsible' => $report->responsible,
            'serviceRecipient' => $report->service_recipient,
            'title' => 'Soporte técnico',
            'addressee' => 'Atención: A quién corresponda',
            'supports' => $report->supports ?? [],
            'details' => $report->details ?? [],
            'filename' => $this->filename($report),
        ];
    }

    public function generate(TechnicalSupportReport $report): string
    {
        File::ensureDirectoryExists(storage_path('fonts'));

        $data = $this->data($report);
        $pdf = Pdf::loadView('pdf.technical-support-report', [
            ...$data,
            'logo' => $this->dataUri(public_path('img/delivery-notes/datamid-logo.png')),
            'headerDecoration' => $this->dataUri(public_path('img/delivery-notes/datamid-header.png')),
            'footerDecoration' => $this->dataUri(public_path('img/delivery-notes/datamid-footer.png')),
            'signatureImage' => $this->dataUri(public_path('img/support/julian-emiliano-signature.png')),
        ])->setPaper('letter');

        $dompdf = $pdf->getDomPDF();
        $dompdf->add_info('Title', 'Reporte de soporte técnico '.$report->folio);
        $dompdf->add_info('Author', $report->responsible);
        $dompdf->add_info('Subject', 'Formato membretado de soporte técnico');

        $pdf->render();
        $headerPath = public_path('img/delivery-notes/datamid-header.png');
        $logoPath = public_path('img/delivery-notes/datamid-logo.png');
        $footerPath = public_path('img/delivery-notes/datamid-footer.png');

        $dompdf->getCanvas()->page_script(static function ($pageNumber, $pageCount, $canvas) use ($headerPath, $logoPath, $footerPath): void {
            if ($pageNumber === 1) {
                return;
            }

            $canvas->image($headerPath, 0, 0, 108, 107);
            $canvas->image($logoPath, 438, 28, 120, 36);
            $canvas->image($footerPath, 0, 611, 612, 181);
        });

        return $pdf->output();
    }

    private function filename(TechnicalSupportReport $report): string
    {
        $serviceNames = collect($report->supports ?? [])
            ->map(fn (array $support): string => match ($support['key'] ?? '') {
                'contpaqi_contabilidad_install' => 'inst-cont',
                'contpaqi_contabilidad_reinstall' => 'reinst-cont',
                'contpaqi_nominas_install' => 'inst-nom',
                'contpaqi_nominas_reinstall' => 'reinst-nom',
                'contpaqi_errors' => 'errores-contpaqi',
                'contpaqi_contabilidad_update' => 'act-cont',
                'contpaqi_contabilidad_server_update' => 'act-srv-cont',
                'contpaqi_nominas_update' => 'act-nom',
                'contpaqi_nominas_server_update' => 'act-srv-nom',
                'contpaqi_backup_restore' => 'respaldo-contpaqi',
                'office_install' => 'inst-office',
                'office_license_activation' => 'act-office',
                'linked_spreadsheets_correction' => 'hojas-vinculadas',
                'office_2013_uninstall' => 'desinst-office-2013',
                'office_2019_install' => 'inst-office-2019',
                'office_2019_activation' => 'act-office-2019',
                'excel_addin_persistence' => 'complemento-excel',
                'report_linked_files_bug_fix' => 'archivos-enlazados',
                'microsoft_365_install' => 'inst-m365',
                'microsoft_365_refresh' => 'm365',
                'claude_addin_setup' => 'claude',
                'claude_addin_activation' => 'act-claude',
                'audictamen_crystal_reports_install' => 'inst-crystal-audictamen',
                'microsoft_store_compatibility_update' => 'act-store',
                'sql_server_2012_restore' => 'restaurar-sql2012',
                'sql_terminal_access_setup' => 'acceso-sql',
                'intern_email_creation' => 'correos-becarios',
                'email_web_access_limits' => 'limites-correo',
                'email_setup' => 'config-correo',
                'email_installation' => 'inst-correo',
                'email_local_backup' => 'respaldo-correo',
                'email_data_migration' => 'migracion-correo',
                'email_pop_to_imap' => 'pop-imap',
                'windows_startup_ini_removal' => 'quitar-ini',
                'computer_printer_connection' => 'conexion-impresora',
                'computer_relocation' => 'reubicar-equipo',
                'windows_contpaqi_compatibility_update' => 'act-windows-contpaqi',
                'ssd_full_backup' => 'respaldo-ssd',
                'ssd_corrected_system_migration' => 'migracion-ssd',
                'ssd_diagnosis_repair' => 'reparar-ssd',
                'printer_setup' => 'config-impresora',
                'network_setup' => 'config-red',
                'windows_setup' => 'config-windows',
                'antivirus_install' => 'antivirus',
                'data_migration' => 'migracion-datos',
                'general_diagnosis' => 'diagnostico',
                default => 'soporte',
            })
            ->unique()
            ->implode('-');

        return $report->folio.($serviceNames !== '' ? '-'.$serviceNames : '').'.pdf';
    }

    private function dataUri(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
