<?php

namespace App\Livewire\TimeControl;

use App\Models\Service;
use App\Models\SubService;
use App\Services\Authorization\PermissionAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class ActivityCatalogManager extends Component
{
    public bool $showModal = false;

    public string $activeTab = 'crear';

    public ?int $selectedActivityId = null;

    public ?int $serviceId = null;

    public string $name = '';

    public string $description = '';

    public ?string $notice = null;

    public function mount(): void
    {
        $this->ensureCanManageActivities();
    }

    public function openModal(): void
    {
        $this->ensureCanManageActivities();
        $this->activeTab = 'crear';
        $this->resetManagementState();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->activeTab = 'crear';
        $this->resetManagementState();
    }

    public function setActiveTab(string $tab): void
    {
        $this->ensureCanManageActivities();
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar'], true), 404);

        $this->activeTab = $tab;
        $this->resetManagementState();
    }

    public function updatedSelectedActivityId($activityId): void
    {
        $this->ensureCanManageActivities();
        $this->selectedActivityId = filled($activityId) ? (int) $activityId : null;
        $this->resetActivityFields();
        $this->notice = null;
        $this->resetValidation();

        if (! $this->selectedActivityId || $this->activeTab !== 'editar') {
            return;
        }

        $activity = SubService::query()->findOrFail($this->selectedActivityId);
        $this->name = (string) $activity->sub_service;
        $this->serviceId = (int) $activity->service_id;
        $this->description = (string) ($activity->description ?? '');
    }

    public function save(): void
    {
        match ($this->activeTab) {
            'crear' => $this->createActivity(),
            'editar' => $this->updateActivity(),
            'eliminar' => $this->deleteActivity(),
            default => abort(404),
        };
    }

    public function createActivity(): void
    {
        $this->ensureCanManageActivities();
        $this->normalizeFields();
        $data = $this->validate($this->activityRules(), $this->validationMessages());

        try {
            $activity = DB::transaction(fn (): SubService => SubService::create([
                'sub_service' => $data['name'],
                'service_id' => (int) $data['serviceId'],
                'description' => $this->nullIfEmpty($data['description'] ?? null),
                'unique_key' => 'activity_'.Str::lower((string) Str::uuid()),
            ]));
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('catalog', 'No fue posible crear la actividad. Inténtalo nuevamente.');

            return;
        }

        $this->resetManagementState();
        $this->notice = 'Actividad creada correctamente.';
        $this->dispatch('activity-catalog-updated', activityId: $activity->id, action: 'created');
    }

    public function updateActivity(): void
    {
        $this->ensureCanManageActivities();
        $this->normalizeFields();
        $data = $this->validate(
            ['selectedActivityId' => ['required', 'integer', 'exists:sub_services,id']] + $this->activityRules(true),
            $this->validationMessages(),
        );

        try {
            $blockedMessage = DB::transaction(function () use ($data): ?string {
                $activity = SubService::query()
                    ->lockForUpdate()
                    ->findOrFail((int) $data['selectedActivityId']);

                if ($activity->isProtectedCatalogEntry()
                    && (int) $activity->service_id !== (int) $data['serviceId']) {
                    return 'La categoría de esta actividad base no puede modificarse porque otros procesos dependen de ella.';
                }

                $activity->update([
                    'sub_service' => $data['name'],
                    'service_id' => (int) $data['serviceId'],
                    'description' => $this->nullIfEmpty($data['description'] ?? null),
                ]);

                return null;
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('catalog', 'No fue posible actualizar la actividad. Inténtalo nuevamente.');

            return;
        }

        if ($blockedMessage) {
            $this->addError('serviceId', $blockedMessage);

            return;
        }

        $this->notice = 'Actividad actualizada correctamente.';
        $this->dispatch('activity-catalog-updated', activityId: (int) $data['selectedActivityId'], action: 'updated');
    }

    public function deleteActivity(): void
    {
        $this->ensureCanManageActivities();
        $data = $this->validate([
            'selectedActivityId' => ['required', 'integer', 'exists:sub_services,id'],
        ]);

        try {
            $blockedMessage = DB::transaction(function () use ($data): ?string {
                $activityId = (int) $data['selectedActivityId'];
                $activity = SubService::query()->lockForUpdate()->findOrFail($activityId);

                if ($activity->isProtectedCatalogEntry()) {
                    return 'No se puede eliminar esta actividad base porque otros procesos del sistema dependen de ella.';
                }

                if (DB::table('time_entries')->where('sub_service_id', $activityId)->exists()) {
                    return 'No se puede eliminar esta actividad porque forma parte del historial de horas.';
                }

                if (DB::table('customer_files')->where('sub_service_id', $activityId)->exists()) {
                    return 'No se puede eliminar esta actividad porque tiene archivos de clientes asociados.';
                }

                DB::table('customer_services')->where('sub_service_id', $activityId)->delete();
                $activity->delete();

                return null;
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('catalog', 'No fue posible eliminar la actividad. Inténtalo nuevamente.');

            return;
        }

        if ($blockedMessage) {
            $this->addError('selectedActivityId', $blockedMessage);

            return;
        }

        $deletedActivityId = (int) $data['selectedActivityId'];
        $this->resetManagementState();
        $this->notice = 'Actividad eliminada correctamente.';
        $this->dispatch('activity-catalog-updated', activityId: $deletedActivityId, action: 'deleted');
    }

    public function render()
    {
        $this->ensureCanManageActivities();

        $activities = SubService::query()
            ->with('service:id,service')
            ->orderBy('sub_service')
            ->get();

        return view('livewire.time-control.activity-catalog-manager', [
            'activities' => $activities,
            'services' => Service::query()->orderBy('service')->get(['id', 'service']),
            'selectedActivity' => $this->selectedActivityId
                ? $activities->firstWhere('id', $this->selectedActivityId)
                : null,
        ]);
    }

    private function activityRules(bool $ignoreSelectedActivity = false): array
    {
        $uniqueName = Rule::unique('sub_services', 'sub_service');

        if ($ignoreSelectedActivity && $this->selectedActivityId) {
            $uniqueName->ignore($this->selectedActivityId);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $uniqueName,
            ],
            'serviceId' => ['required', 'integer', 'exists:services,id'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function validationMessages(): array
    {
        return [
            'name.required' => 'El nombre de la actividad es obligatorio.',
            'name.unique' => 'Ya existe una actividad con este nombre.',
            'serviceId.required' => 'Selecciona la categoría de servicio.',
            'serviceId.exists' => 'La categoría seleccionada ya no está disponible.',
        ];
    }

    private function normalizeFields(): void
    {
        $this->name = Str::squish($this->name);
        $this->description = trim($this->description);
    }

    private function resetManagementState(): void
    {
        $this->selectedActivityId = null;
        $this->notice = null;
        $this->resetActivityFields();
        $this->resetValidation();
    }

    private function resetActivityFields(): void
    {
        $this->serviceId = null;
        $this->name = '';
        $this->description = '';
    }

    private function nullIfEmpty(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function ensureCanManageActivities(): void
    {
        $user = auth()->user();

        abort_unless(
            $user?->isAdmin()
                && app(PermissionAccessService::class)->allows($user, 'administration.organization.manage'),
            403,
        );
    }
}
