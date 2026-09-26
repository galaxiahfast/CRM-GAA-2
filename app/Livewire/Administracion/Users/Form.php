<?php

namespace App\Livewire\Administracion\Users;

use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInterns;
use App\Models\UserOrganizationalProfile;
use App\Services\Administracion\OrganizationChartService;
use App\Services\ReferenceDataCache;
use App\Services\TimeControl\AttendanceSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class Form extends Component
{
    public $user;

    public $name;

    public $last_name;

    public $email;

    public $password;

    public $password_confirmation;

    public $roles;

    public $role_id = '';

    public $isAuxiliar = false;

    public $mode = 'create';

    // 🆕 Nuevas propiedades para enlazar el checador y la nómina
    public $employee_id;

    public $hourly_rate = 25.00;     // Por defecto $25 pesos por hora

    public $food_allowance = 50.00;  // Por defecto $50 pesos de comida

    public $job_position_id = '';

    public $physical_area_id = '';

    public bool $isHourlyPosition = false;

    public string $managementTab = 'crear';

    public ?int $managementUserId = null;

    public string $deleteConfirmationName = '';

    public string $deleteConfirmationEmail = '';

    public string $deleteConfirmationPhrase = '';

    public bool $embedded = false;

    protected $messages = [
        'name.required' => 'El nombre es obligatorio.',
        'name.string' => 'El nombre debe ser una cadena de texto.',
        'name.max' => 'El nombre no debe exceder los 255 caracteres.',
        'last_name.string' => 'El apellido debe ser una cadena de texto.',
        'last_name.max' => 'El apellido no debe exceder los 255 caracteres.',
        'email.required' => 'El correo electrónico es obligatorio.',
        'email.email' => 'El correo electrónico debe ser una dirección válida.',
        'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',
        'email.unique' => 'El correo electrónico ya está en uso.',
        'password.required' => 'La contraseña es obligatoria.',
        'password.string' => 'La contraseña debe ser una cadena de texto.',
        'password.max' => 'La contraseña no debe exceder los 255 caracteres.',
        'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        'role_id.required' => 'El rol es obligatorio.',
        'role_id.exists' => 'El rol seleccionado no es válido.',
        // Mensajes para los nuevos campos
        'employee_id.unique' => 'Este ID de checador ya está asignado a otro usuario.',
        'hourly_rate.numeric' => 'El precio por hora debe ser un número válido.',
        'hourly_rate.min' => 'El precio por hora no puede ser menor a 0.',
        'food_allowance.numeric' => 'El apoyo de comida debe ser un número válido.',
        'food_allowance.min' => 'El apoyo de comida no puede ser menor a 0.',
        'deleteConfirmationName.required' => 'Confirma el nombre completo del usuario.',
        'deleteConfirmationEmail.required' => 'Confirma el correo electrónico del usuario.',
        'deleteConfirmationEmail.email' => 'Escribe un correo electrónico válido.',
        'deleteConfirmationPhrase.required' => 'Escribe ELIMINAR para continuar.',
    ];

    public function mount($user = null, $isAuxiliar = false, bool $embedded = false, string $initialTab = 'crear', ?int $initialUserId = null)
    {
        $this->embedded = $embedded;
        $this->managementTab = in_array($initialTab, ['crear', 'editar', 'eliminar'], true) ? $initialTab : 'crear';
        $authUser = auth()->user();
        $canManageUsers = app(\App\Services\Authorization\PermissionAccessService::class)
            ->allows($authUser, 'administration.users.manage');

        if ($user && $user->exists) {
            $this->user = $user;
            $this->name = $user->name;
            $this->last_name = $user->last_name;
            $this->email = $user->email;
            $this->role_id = $user->role_id;
            $this->employee_id = $user->employee_id; // 👈 Carga el ID de Hikvision
            $this->mode = 'edit';
            $this->managementTab = 'editar';
            $this->managementUserId = (int) $user->id;

            // 👈 Carga los valores monetarios actuales del perfil activo
            $profile = $user->activeOrganizationalProfile;
            if ($profile) {
                $this->hourly_rate = $profile->hourly_rate;
                $this->food_allowance = $profile->food_allowance;
                $this->job_position_id = $profile->job_position_id;
                $this->physical_area_id = $profile->physical_area_id;
            }
        }

        $this->roles = app(ReferenceDataCache::class)->administration()['roles'];

        $this->isAuxiliar = $isAuxiliar;
        if ($isAuxiliar) {
            if (! $canManageUsers) {
                abort(403, 'No tienes permisos para crear interns.');
            }
            $this->roles = $this->roles->where('role', 'Auxiliar');
        } else {
            if (! $canManageUsers) {
                abort(403, 'No tienes permisos para crear usuarios.');
            }
        }

        if ($initialUserId !== null && $this->managementTab !== 'crear') {
            $managedUser = User::query()->with('activeOrganizationalProfile')->findOrFail($initialUserId);
            $this->managementUserId = (int) $managedUser->id;
            if ($this->managementTab === 'editar') {
                $this->fillFromManagedUser($managedUser);
            }
        }

        $this->isHourlyPosition = $this->isHourlyJobPosition($this->job_position_id);
        if ($this->mode === 'create') {
            $this->email = '';
            $this->generateRandomPassword();
        }
    }

    public function generateRandomPassword(): void
    {
        $this->password = Str::password(14, true, true, true, false);
        $this->password_confirmation = '';
        $this->resetValidation(['password', 'password_confirmation']);
    }

    public function setManagementTab(string $tab)
    {
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar'], true), 404);

        if ($tab === 'crear' && $this->mode === 'edit') {
            if (! $this->embedded) {
                return redirect()->route('administracion.create.users');
            }
        }

        if ($this->embedded) {
            $this->resetEmbeddedEditor();
        }

        $this->managementTab = $tab;
        $this->managementUserId = null;
        $this->deleteConfirmationName = '';
        $this->deleteConfirmationEmail = '';
        $this->deleteConfirmationPhrase = '';
        $this->resetValidation([
            'managementUserId',
            'deleteConfirmationName',
            'deleteConfirmationEmail',
            'deleteConfirmationPhrase',
        ]);
    }

    public function editManagedUser()
    {
        $data = $this->validate([
            'managementUserId' => ['required', 'integer', 'exists:users,id'],
        ]);

        if ($this->embedded) {
            $user = User::with('activeOrganizationalProfile')->findOrFail($data['managementUserId']);
            $this->user = $user;
            $this->name = $user->name;
            $this->last_name = $user->last_name;
            $this->email = $user->email;
            $this->role_id = $user->role_id;
            $this->employee_id = $user->employee_id;
            $this->mode = 'edit';
            $profile = $user->activeOrganizationalProfile;
            $this->hourly_rate = $profile?->hourly_rate ?? 0;
            $this->food_allowance = $profile?->food_allowance ?? 0;
            $this->job_position_id = $profile?->job_position_id ?? '';
            $this->physical_area_id = $profile?->physical_area_id ?? '';
            $this->isHourlyPosition = $this->isHourlyJobPosition($this->job_position_id);

            return;
        }

        return redirect()->route('administracion.edit.users', $data['managementUserId']);
    }

    public function updatedManagementUserId($userId): void
    {
        $this->deleteConfirmationName = '';
        $this->deleteConfirmationEmail = '';
        $this->deleteConfirmationPhrase = '';

        if ($this->managementTab !== 'editar' || ! filled($userId)) {
            return;
        }

        $user = User::query()->with('activeOrganizationalProfile')->find((int) $userId);
        if ($user) {
            $this->fillFromManagedUser($user);
        }
    }

    private function fillFromManagedUser(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->role_id = $user->role_id;
        $this->employee_id = $user->employee_id;
        $this->mode = 'edit';
        $profile = $user->activeOrganizationalProfile;
        $this->hourly_rate = $profile?->hourly_rate ?? 0;
        $this->food_allowance = $profile?->food_allowance ?? 0;
        $this->job_position_id = $profile?->job_position_id ?? '';
        $this->physical_area_id = $profile?->physical_area_id ?? '';
        $this->isHourlyPosition = $this->isHourlyJobPosition($this->job_position_id);
        $this->resetValidation();
    }

    public function deleteManagedUser(OrganizationChartService $chartService)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $this->validate([
            'managementUserId' => ['required', 'integer', 'exists:users,id'],
            'deleteConfirmationName' => ['required', 'string'],
            'deleteConfirmationEmail' => ['required', 'email'],
            'deleteConfirmationPhrase' => ['required', 'string'],
        ]);

        $user = User::findOrFail($data['managementUserId']);
        $fullName = trim("{$user->name} {$user->last_name}");

        if (mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $data['deleteConfirmationName']))) !== mb_strtolower($fullName)) {
            $this->addError('deleteConfirmationName', 'Debes escribir el nombre completo exacto del usuario para eliminarlo.');

            return;
        }

        if (mb_strtolower(trim($data['deleteConfirmationEmail'])) !== mb_strtolower(trim((string) $user->email))) {
            $this->addError('deleteConfirmationEmail', 'El correo no coincide exactamente con el usuario seleccionado.');

            return;
        }

        if (mb_strtoupper(trim($data['deleteConfirmationPhrase'])) !== 'ELIMINAR') {
            $this->addError('deleteConfirmationPhrase', 'Escribe ELIMINAR para confirmar la eliminación definitiva.');

            return;
        }

        abort_if($user->id === auth()->id(), 422, 'No puedes eliminar tu propio usuario.');

        DB::transaction(function () use ($user, $chartService): void {
            $chartService->detachAllRelationsForUser($user->id);
            UserInterns::where('intern_id', $user->id)->delete();
            $user->delete();
        });

        session()->flash('success', 'Usuario eliminado correctamente.');
        $references = app(ReferenceDataCache::class);
        $references->forgetManageableUsers();
        $references->forgetOrganizationDashboard();

        if ($this->embedded) {
            $this->dispatch('user-management-closed');

            return;
        }

        return redirect()->route('administracion.create.users');
    }

    public function updatedJobPositionId(): void
    {
        $this->isHourlyPosition = $this->isHourlyJobPosition($this->job_position_id);
    }

    public function save(Request $request)
    {
        $rules = [
            'name' => 'bail|required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'password' => 'bail|required|string|max:255|min:8|confirmed',
            'password_confirmation' => 'bail|required_with:password|same:password',
            'email' => 'bail|required|email|max:255|unique:users,email'.($this->user ? ','.$this->user->id : ''),
            'role_id' => 'bail|required|exists:roles,id',
            // 🆕 Reglas de validación añadidas
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id'.($this->user ? ','.$this->user->id : ''),
            'job_position_id' => 'bail|required|exists:job_positions,id',
            'physical_area_id' => 'bail|required|exists:physical_areas,id',
        ];

        $isHourlyPosition = $this->isHourlyJobPosition($this->job_position_id);
        $rules['hourly_rate'] = $isHourlyPosition ? 'required|numeric|min:0' : 'nullable|numeric|min:0';
        $rules['food_allowance'] = $isHourlyPosition ? 'required|numeric|min:0' : 'nullable|numeric|min:0';

        if ($this->mode === 'edit') {
            if (! $this->password) {
                unset($rules['password']);
                unset($rules['password_confirmation']);
            }
        }

        $data = $this->validate($rules);

        // Aislamos los datos exclusivos del perfil organizacional antes de guardar el usuario
        $isAuxiliarRole = $this->isAuxiliarRole($data['role_id']);
        // Las instalaciones existentes en SQL Server pueden tener estas columnas
        // como NOT NULL. En puestos de tiempo completo se persisten en cero.
        $hourlyRate = $isHourlyPosition ? ($data['hourly_rate'] ?? 0) : 0;
        $foodAllowance = $isHourlyPosition ? ($data['food_allowance'] ?? 0) : 0;
        $jobPositionId = $data['job_position_id'];
        $physicalAreaId = $data['physical_area_id'];
        unset($data['hourly_rate'], $data['food_allowance'], $data['job_position_id'], $data['physical_area_id']);

        try {
            DB::transaction(function () use ($data, $hourlyRate, $foodAllowance, $jobPositionId, $physicalAreaId, $isAuxiliarRole) {
                if ($this->mode === 'create') {
                    $data['password'] = bcrypt($this->password);
                    $user = User::create($data);

                    if ($isAuxiliarRole) {
                        UserInterns::create([
                            'intern_id' => $user->id,
                            'created_by' => auth()->id(),
                        ]);
                    }

                    // 🆕 Genera el perfil organizacional activo con sus montos por defecto
                    UserOrganizationalProfile::create([
                        'user_id' => $user->id,
                        'job_position_id' => $jobPositionId,
                        'physical_area_id' => $physicalAreaId,
                        'hourly_rate' => $hourlyRate,
                        'food_allowance' => $foodAllowance,
                        'valid_from' => now()->toDateString(),
                        'is_active' => true,
                    ]);

                } elseif ($this->mode === 'edit' && $this->user) {
                    if ($this->password) {
                        $data['password'] = bcrypt($this->password);
                    } else {
                        unset($data['password']);
                    }

                    $this->user->update($data);

                    // 🆕 Actualiza o crea el perfil organizacional si no existía uno previo
                    $this->user->activeOrganizationalProfile()->updateOrCreate(
                        ['user_id' => $this->user->id, 'is_active' => true],
                        [
                            'job_position_id' => $jobPositionId,
                            'physical_area_id' => $physicalAreaId,
                            'hourly_rate' => $hourlyRate,
                            'food_allowance' => $foodAllowance,
                        ]
                    );
                }
            });

            if (filled($data['employee_id'] ?? null)) {
                app(AttendanceSettingsService::class)->saveGeneral(
                    (string) $data['employee_id'],
                    (float) $hourlyRate,
                    (float) $foodAllowance,
                    preserveDayOverrides: true,
                );
            }

            session()->flash('success', 'Usuario guardado y posicionado exitosamente.');
            $references = app(ReferenceDataCache::class);
            $references->forgetManageableUsers();
            $references->forgetOrganizationDashboard();

            if ($this->embedded) {
                $this->dispatch('user-management-closed');

                return;
            }

            return redirect()->to('/administracion/'.($this->isAuxiliar ? 'interns' : 'users'));

        } catch (\Exception $e) {
            report($e);
            session()->flash('error', 'Ocurrió un error al guardar el usuario: '.$e->getMessage());

            return;
        }
    }

    public function cancel()
    {
        if ($this->embedded) {
            $this->dispatch('user-management-closed');

            return;
        }

        return redirect()->route('administracion.index');
    }

    public function render()
    {
        $references = app(ReferenceDataCache::class);
        $administration = $references->administration();
        $editingForm = in_array($this->managementTab, ['crear', 'editar'], true);
        $selectingUser = in_array($this->managementTab, ['editar', 'eliminar'], true);
        $manageableUsers = $selectingUser ? $references->manageableUsers() : collect();

        return view('livewire.administracion.users.form', [
            'jobPositions' => $editingForm ? $administration['jobPositions'] : collect(),
            'physicalAreas' => $editingForm ? $administration['physicalAreas'] : collect(),
            'employeeIdSuggestions' => $editingForm ? $references->employeeSuggestions() : collect(),
            'manageableUsers' => $manageableUsers,
        ]);
    }

    private function isAuxiliarRole($roleId): bool
    {
        return Role::whereKey($roleId)->where('role', 'Auxiliar')->exists();
    }

    private function isHourlyJobPosition($positionId): bool
    {
        return JobPosition::query()
            ->whereKey((int) $positionId)
            ->where('payment_type', JobPosition::PAYMENT_HOURLY)
            ->exists();
    }

    private function resetEmbeddedEditor(): void
    {
        $this->user = null;
        $this->mode = 'create';
        $this->name = '';
        $this->last_name = '';
        $this->email = '';
        $this->password = Str::password(14, true, true, true, false);
        $this->password_confirmation = '';
        $this->role_id = '';
        $this->employee_id = '';
        $this->hourly_rate = 25.00;
        $this->food_allowance = 50.00;
        $this->job_position_id = '';
        $this->physical_area_id = '';
        $this->isHourlyPosition = false;
        $this->deleteConfirmationName = '';
        $this->deleteConfirmationEmail = '';
        $this->deleteConfirmationPhrase = '';
    }
}
