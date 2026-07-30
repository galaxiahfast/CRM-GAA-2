<?php

namespace App\Livewire\Administracion\Users;

use App\Models\JobPosition;
use App\Models\PhysicalArea;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInterns;
use App\Models\UserOrganizationalProfile;
use App\Services\Administracion\OrganizationChartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public bool $isSelectedAuxiliar = false;

    public string $managementTab = 'crear';

    public ?int $managementUserId = null;

    public string $deleteConfirmationName = '';

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
    ];

    public function mount($user = null, $isAuxiliar = false)
    {
        $authUser = auth()->user();
        $role = $authUser->role->role ?? null;

        if ($user && $user->exists) {
            $this->user = $user;
            $this->name = $user->name;
            $this->last_name = $user->last_name;
            $this->email = $user->email;
            $this->role_id = $user->role_id;
            $this->employee_id = $user->employee_id; // 👈 Carga el ID de Hikvision
            $this->mode = 'edit';
            $this->managementTab = 'editar';

            // 👈 Carga los valores monetarios actuales del perfil activo
            $profile = $user->activeOrganizationalProfile;
            if ($profile) {
                $this->hourly_rate = $profile->hourly_rate;
                $this->food_allowance = $profile->food_allowance;
                $this->job_position_id = $profile->job_position_id;
                $this->physical_area_id = $profile->physical_area_id;
            }
        }

        $this->roles = Role::all();

        $this->isAuxiliar = $isAuxiliar;
        if ($isAuxiliar) {
            if (! in_array($role, ['Administrador', 'Coordinador', 'Contador'])) {
                abort(403, 'No tienes permisos para crear interns.');
            }
            $this->roles = $this->roles->where('role', 'Auxiliar');
        } else {
            if (! in_array($role, ['Administrador', 'Coordinador'])) {
                abort(403, 'No tienes permisos para crear usuarios.');
            }
        }

        $this->isSelectedAuxiliar = $isAuxiliar || $this->isAuxiliarRole($this->role_id);
    }

    public function setManagementTab(string $tab)
    {
        abort_unless(in_array($tab, ['crear', 'editar', 'eliminar'], true), 404);

        if ($tab === 'crear' && $this->mode === 'edit') {
            return redirect()->route('administracion.create.users');
        }

        $this->managementTab = $tab;
        $this->managementUserId = null;
        $this->deleteConfirmationName = '';
        $this->resetValidation(['managementUserId', 'deleteConfirmationName']);
    }

    public function editManagedUser()
    {
        $data = $this->validate([
            'managementUserId' => ['required', 'integer', 'exists:users,id'],
        ]);

        return redirect()->route('administracion.edit.users', $data['managementUserId']);
    }

    public function deleteManagedUser(OrganizationChartService $chartService)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $this->validate([
            'managementUserId' => ['required', 'integer', 'exists:users,id'],
            'deleteConfirmationName' => ['required', 'string'],
        ]);

        $user = User::findOrFail($data['managementUserId']);
        $fullName = trim("{$user->name} {$user->last_name}");

        if (mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $data['deleteConfirmationName']))) !== mb_strtolower($fullName)) {
            $this->addError('deleteConfirmationName', 'Debes escribir el nombre completo exacto del usuario para eliminarlo.');

            return;
        }

        abort_if($user->id === auth()->id(), 422, 'No puedes eliminar tu propio usuario.');

        DB::transaction(function () use ($user, $chartService): void {
            $chartService->detachAllRelationsForUser($user->id);
            UserInterns::where('intern_id', $user->id)->delete();
            $user->delete();
        });

        session()->flash('success', 'Usuario eliminado correctamente.');

        return redirect()->route('administracion.create.users');
    }

    public function updatedRoleId(): void
    {
        $this->isSelectedAuxiliar = $this->isAuxiliarRole($this->role_id);
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

        $rules['hourly_rate'] = $this->isAuxiliarRole($this->role_id) ? 'required|numeric|min:0' : 'nullable|numeric|min:0';
        $rules['food_allowance'] = $this->isAuxiliarRole($this->role_id) ? 'required|numeric|min:0' : 'nullable|numeric|min:0';

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
        // como NOT NULL. Fuera del rol Auxiliar se persisten en cero.
        $hourlyRate = $isAuxiliarRole ? ($data['hourly_rate'] ?? 0) : 0;
        $foodAllowance = $isAuxiliarRole ? ($data['food_allowance'] ?? 0) : 0;
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

            session()->flash('success', 'Usuario guardado y posicionado exitosamente.');

            return redirect()->to('/administracion/'.($this->isAuxiliar ? 'interns' : 'users'));

        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al guardar el usuario: '.$e->getMessage());

            return;
        }
    }

    public function cancel()
    {
        return redirect()->route('administracion.index');
    }

    public function render()
    {
        return view('livewire.administracion.users.form', [
            'jobPositions' => JobPosition::orderBy('name')->get(['id', 'name']),
            'physicalAreas' => PhysicalArea::orderBy('name')->get(['id', 'name']),
            'employeeIdSuggestions' => DB::table('control_de_horas')
                ->select('employeeID')
                ->selectRaw('MAX(personName) as personName')
                ->whereNotNull('employeeID')
                ->where('employeeID', '<>', '')
                ->groupBy('employeeID')
                ->orderBy('employeeID')
                ->get(),
            'manageableUsers' => User::query()
                ->orderBy('name')
                ->orderBy('last_name')
                ->get(['id', 'name', 'last_name', 'email']),
        ]);
    }

    private function isAuxiliarRole($roleId): bool
    {
        return Role::whereKey($roleId)->where('role', 'Auxiliar')->exists();
    }
}
