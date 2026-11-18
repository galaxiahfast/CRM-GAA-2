<?php

namespace App\Services\Profile;

use App\Models\User;
use App\Services\Support\SupportGuidePdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Ficha de perfil de un colaborador en PDF, con el mismo formato membretado de las guías y los reportes de soporte:
 * folio, fecha, secciones con sus datos (personales, organizacionales y de actividad) y la descripción del perfil.
 */
class ProfilePdfService
{
    public function __construct(private readonly SupportGuidePdfService $guides) {}

    /** @return array<string, mixed> */
    public function data(User $profile, User $requestedBy, ?CarbonImmutable $now = null): array
    {
        $issuedAt = ($now ?? CarbonImmutable::now())
            ->timezone((string) config('support.timezone', 'America/Mexico_City'))
            ->locale('es');

        $organization = $profile->activeOrganizationalProfile()->with(['physicalArea', 'jobPosition'])->first();
        $supervisors = $profile->superiors()->get()->map(fn (User $user): string => trim($user->name.' '.$user->last_name))->filter()->values();
        $subordinates = $profile->subordinates()->count();
        $age = $profile->approximateAge();
        $fullName = trim($profile->name.' '.$profile->last_name);
        $serial = 'PF-'.$issuedAt->format('Ymd').'-'.str_pad((string) $profile->id, 4, '0', STR_PAD_LEFT);

        return [
            'serial' => $serial,
            'issuedAtLong' => ucfirst($issuedAt->isoFormat('dddd, D [de] MMMM [de] YYYY, HH:mm:ss')).' '.$issuedAt->format('T'),
            'title' => 'Ficha de perfil',
            'fullName' => $fullName,
            'requestedBy' => trim($requestedBy->name.' '.$requestedBy->last_name),
            'sections' => [
                'Datos personales' => [
                    'Nombre completo' => $fullName,
                    'Correo electrónico' => (string) $profile->email,
                    'Año de nacimiento' => $profile->birth_year ? $profile->birth_year.($age !== null ? " ({$age} años)" : '') : 'No registrado',
                    'Ubicación' => 'Mérida, Yucatán, México',
                ],
                'Información organizacional' => [
                    'Empresa' => 'González Alonzo y Asociados S.C.P.',
                    'Área' => $organization?->physicalArea?->name ?? 'Sin asignar',
                    'Puesto' => $organization?->jobPosition?->name ?? 'Sin asignar',
                    'Supervisor' => $supervisors->isEmpty() ? 'Sin supervisor' : $supervisors->join(', '),
                    'Colaboradores a su cargo' => $subordinates === 0 ? 'No tiene colaboradores' : (string) $subordinates,
                ],
                'Actividad en el CRM' => [
                    'Actividades registradas' => number_format($profile->timeEntries()->count()),
                    'Amigos' => number_format($profile->friends()->count()),
                    'Cuenta creada' => $profile->created_at ? ucfirst($profile->created_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY')) : 'Sin dato',
                    'Última actualización' => $profile->updated_at ? ucfirst($profile->updated_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY')) : 'Sin dato',
                ],
            ],
            'description' => trim((string) $profile->profile_description) !== '' ? trim((string) $profile->profile_description) : null,
            'filename' => $serial.'-'.Str::slug($fullName ?: 'perfil').'.pdf',
        ];
    }

    /** @return array{content: string, filename: string} */
    public function generate(User $profile, User $requestedBy, ?CarbonImmutable $now = null): array
    {
        $data = $this->data($profile, $requestedBy, $now);

        File::ensureDirectoryExists(storage_path('fonts'));

        $pdf = Pdf::loadView('pdf.profile-sheet', [
            ...$data,
            'pageDecoration' => $this->guides->pageDecorationDataUri(),
        ])->setPaper('letter');

        $dompdf = $pdf->getDomPDF();
        $dompdf->add_info('Title', 'Ficha de perfil: '.$data['fullName'].' '.$data['serial']);
        $dompdf->add_info('Author', 'González Alonzo y Asociados S.C.P.');
        $dompdf->add_info('Subject', 'Perfil de '.$data['fullName']);

        return ['content' => $pdf->output(), 'filename' => $data['filename']];
    }
}
