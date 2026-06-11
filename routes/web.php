<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Customer\IndexCustomer;
use App\Livewire\Customer\StoreCustomer;
use App\Livewire\Customer\UpdateCustomer;
use App\Livewire\Customer\ViewCustomer;
use App\Livewire\Administracion\IndexAdministracion;
use App\Livewire\Administracion\Interns\IndexInterns;
use App\Livewire\Administracion\Roles\IndexRole;
use App\Livewire\Administracion\Users\IndexUser;
use App\Livewire\CustomerReport;
use App\Livewire\TimeControl\IndexTimeControl;
use App\Livewire\TimeControl\MyProductivity;
use App\Livewire\TimeControl\Admin\AdminTimeDashboard;
use App\Livewire\TimeControl\Admin\CorrectTimeEntry;
use App\Livewire\TimeControl\Admin\OrganizationalProfiles;
use App\Models\User;
use App\Models\Role;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard/{customerId?}', function () {
        return view('dashboard');
    })->name('dashboard');

    //DASHBOARD
    Route::get('/dashboard/{customerId}/report', CustomerReport::class)->name('dashboard.customer.report');

    //ADMINISTRACION
    Route::get('/administracion', IndexAdministracion::class)->name('administracion.index');
    Route::get('/administracion/users', IndexUser::class)->name('administracion.section');

    //Users
    Route::get('/administracion/users/create', function(){
        return view('livewire.administracion.users.create');
    })->name('administracion.create.users');
    Route::get('/administracion/users/{user}/edit', function(User $user){
        return view('livewire.administracion.users.edit', compact('user'));
    })->name('administracion.edit.users');

    //Roles
    Route::get('/administracion/roles', IndexRole::class)->name('administracion.role');
    Route::get('/administracion/create/roles', function(){
        return view('livewire.administracion.roles.create');
    })->name('administracion.role.create');
    Route::get('/administracion/roles/{role}/edit', function(Role $role) {
        return view('livewire.administracion.roles.edit', compact('role'));
    })->name('administracion.role.edit');

    //Interns
    Route::get('/administracion/interns', IndexInterns::class)->name('administracion.interns');

    Route::get('/administracion/permissions', IndexAdministracion::class)->name('administracion.permissions');
    Route::get('/administracion/relationships', IndexAdministracion::class)->name('administracion.relationships');

    // COSTUMER
    Route::get('/customers', IndexCustomer::class)->name('customers.index');
    Route::get('/customers/create', StoreCustomer::class)->name('customers.create');
    Route::get('/customers/{customer}/edit', UpdateCustomer::class)->name('customers.edit');
    Route::get('/customers/{customer}/view', ViewCustomer::class)->name('customers.view');
    // Route::put('/customers/{customer}', UpdateCustomer::class)->name('customers.update');


    // CONTROL DE HORAS (operativo: Auxiliar/Coordinador/Contador)
    Route::get('/time', IndexTimeControl::class)->name('time.index');
    Route::get('/time/reports', MyProductivity::class)->name('time.reports');

    // CONTROL DE HORAS (administración: solo Administrador)
    Route::middleware('can:view-time-admin')->group(function () {
        Route::get('/time/admin', AdminTimeDashboard::class)->name('time.admin.dashboard');
        Route::get('/time/admin/profiles', OrganizationalProfiles::class)->name('time.admin.profiles');
    });
    Route::get('/time/admin/corrections', CorrectTimeEntry::class)
        ->middleware('can:correct-time-tracking')
        ->name('time.admin.corrections');
});
