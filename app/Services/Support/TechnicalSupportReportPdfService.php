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
            'microsoft_365_refresh' => ['title' => 'Desinstalación e instalación actualizada de Microsoft 365', 'description' => 'Retiro de la instalación anterior, instalación de la versión vigente y comprobación de funcionamiento.'],
            'claude_addin_setup' => ['title' => 'Configuración del complemento de Claude en Microsoft 365', 'description' => 'Instalación, configuración y validación del complemento de Claude.'],
            'microsoft_store_compatibility_update' => ['title' => 'Actualización de Windows para compatibilidad con Microsoft Store', 'description' => 'Actualización del equipo y comprobación de acceso a Microsoft Store para instalar Microsoft 365.'],
            'email_setup' => ['title' => 'Configuración de correo electrónico', 'description' => 'Alta y comprobación de cuentas en el equipo o dispositivo.'],
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
                'microsoft_365_refresh' => 'm365',
                'claude_addin_setup' => 'claude',
                'microsoft_store_compatibility_update' => 'act-store',
                'email_setup' => 'config-correo',
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
