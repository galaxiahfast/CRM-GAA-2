<?php

namespace App\Livewire\Administracion\Users;

use Livewire\Component;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\UserInterns;
use Illuminate\Support\Facades\DB;

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
            $this->mode = 'edit';
        }
        
        $this->roles = Role::all();

        $this->isAuxiliar = $isAuxiliar;
        if ($isAuxiliar) {
            if (!in_array($role, ['Administrador', 'Coordinador', 'Contador'])) {
                abort(403, 'No tienes permisos para crear interns.');
            }
            $this->roles = $this->roles->where('role', 'Auxiliar');
        } else {
            if (!in_array($role, ['Administrador', 'Coordinador'])) {
                abort(403, 'No tienes permisos para crear usuarios.');
            }
        }
    }

    public function save(Request $request)
    {
        $rules = [
            'name' => 'bail|required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'password' => 'bail|required|string|max:255|min:8|confirmed',
            'password_confirmation' => 'bail|required_with:password|same:password',
            'email' => 'bail|required|email|max:255|unique:users,email' . ($this->user ? ',' . $this->user->id : ''),
            'role_id' => 'bail|required|exists:roles,id',
        ];
        if ($this->mode === 'edit') {
            if (!$this->password) {
                unset($rules['password']);
                unset($rules['password_confirmation']);
            }
        }
            $data = $this->validate($rules);
        
        try {
            if ($this->mode === 'create') {
                $data['password'] = bcrypt($this->password);
                $user = User::create($data);
                if ($data['role_id'] == 4) {
                    UserInterns::create([
                        'intern_id' => $user->id,
                        'created_by' => auth()->id()
                    ]);
                }
            } elseif ($this->mode === 'edit' && $this->user) {
                if ($this->password) {
                    $data['password'] = bcrypt($this->password);
                } else {
                    unset($data['password']);
                }
                $this->user->update($data);
            }
            session()->flash('success', 'Usuario guardado exitosamente.');

            return redirect()->to("/administracion/" . ($this->isAuxiliar ? 'interns' : 'users'));
        } catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error al guardar el usuario: ' . $e->getMessage());
            return;
        }
    }

    public function cancel()
    {
        return redirect()->to('/administracion/users');
    }
    public function render()
    {
        return view('livewire.administracion.users.form');
    }
}
