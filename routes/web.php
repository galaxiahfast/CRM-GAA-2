<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Customer\IndexCustomer;
use App\Livewire\Customer\StoreCustomer;
use App\Livewire\Customer\UpdateCustomer;
use App\Livewire\Customer\ViewCustomer;
use App\Livewire\Administracion\IndexAdministracion;
use App\Livewire\Administracion\Interns\IndexInterns;
use App\Livewire\Administracion\Relationship\IndexRelationship;
use App\Livewire\Administracion\Roles\IndexRole;
use App\Livewire\Administracion\Users\IndexUser;
use App\Livewire\CustomerReport;
use App\Models\Customer;
use App\Models\User;
use App\Models\Role;


use Illuminate\Support\Facades\Mail;
use App\Mail\ReporteSemanal;


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

    //ADMINISTRACION INDEX
    Route::middleware(['auth', 'role:Administrador,Coordinador,Contador'])->group(function () {
        Route::get('/administracion', IndexAdministracion::class)->name('administracion.index');
    });
    Route::middleware(['auth', 'role:Administrador,Coordinador'])->group(function () {
        Route::get('/administracion/users', IndexUser::class)->name('administracion.section');
    });

    //ADMINISTRACION Users
    Route::middleware(['auth', 'role:Administrador,Coordinador,Contador'])->group(function () {
        Route::get('/administracion/users/create', function(){
            return view('livewire.administracion.users.create');
        })->name('administracion.create.users');
        Route::get('/administracion/users/{user}/edit', function(User $user){
            return view('livewire.administracion.users.edit', compact('user'));
        })->name('administracion.edit.users');
    });


    //ADMINISTRACION Roles
    Route::middleware(['auth', 'role:Administrador'])->group(function () {
        Route::get('/administracion/roles', IndexRole::class)->name('administracion.role');
        Route::get('/administracion/create/roles', function(){
            return view('livewire.administracion.roles.create');
        })->name('administracion.role.create');
        Route::get('/administracion/roles/{role}/edit', function(Role $role) {
            return view('livewire.administracion.roles.edit', compact('role'));
        })->name('administracion.role.edit');
    });


    //ADMINISTRACION Interns
    Route::middleware(['auth', 'role:Administrador,Coordinador,Contador'])->group(function () {
        Route::get('/administracion/interns', IndexInterns::class)->name('administracion.interns');
    });
    
    //ADMINISTRADOR Permissions
    Route::middleware(['auth', 'role:Administrador'])->group(function () {
        Route::get('/administracion/permissions', IndexAdministracion::class)->name('administracion.permissions');
    });

    //ADMINISTRADOR Relationship
    Route::middleware(['auth', 'role:Administrador,Coordinador,Contador'])->group(function () {
        Route::get('/administracion/relationships', IndexRelationship::class)->name('administracion.relationships');
    });

    // COSTUMER
    Route::get('/customers', IndexCustomer::class)->name('customers.index');
    
    Route::middleware(['auth', 'role:Administrador,Coordinador'])->group(function () {
        Route::get('/customers/create', StoreCustomer::class)->name('customers.create');
        Route::get('/customers/{customer}/edit', UpdateCustomer::class)->name('customers.edit');
    });
    
    Route::get('/customers/{customer}/view', ViewCustomer::class)->name('customers.view');

    Route::middleware(['auth', 'role:Administrador'])->group(function () {
        Route::get('/test-email', function() {
            Mail::to('prueba@datamid.com.mx')->send(new ReporteSemanal());
            return "Correo enviado con exito";
        });
    });

    // TIME CONTROL
    Route::get('/time', function () {
        return view('components.maintenance');
    })->name('time.index');
});
