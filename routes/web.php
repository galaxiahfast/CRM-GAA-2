<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReporteSemanal;

// Modelos
use App\Models\Customer;
use App\Models\User;
use App\Models\Role;

// Componentes Locales (Clientes y Administración)
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

use App\Livewire\TimeControl\AttendanceClock;

// Componentes Remotos (Control de Horas Complejo)
use App\Livewire\TimeControl\IndexTimeControl;
use App\Livewire\TimeControl\MyProductivity;
use App\Livewire\TimeControl\Admin\AdminTimeDashboard;
use App\Livewire\TimeControl\Admin\CorrectTimeEntry;
use App\Livewire\TimeControl\Admin\OrganizationalProfiles;
use App\Livewire\TimeControl\Admin\AttendanceManagement; // 🆕 Componente importado para el checador

use App\Http\Controllers\TimeEntryController;


Route::middleware(['auth', 'verified'])->group(function () {
    // ... tus otras rutas ...
    
    // Rutas del módulo checador (asistencia biométrica)
    Route::post('/control-horas/consultar', [TimeEntryController::class, 'consultarAsistencia'])->name('control-horas.consultar');
    Route::get('/control-horas/export/{format}', [TimeEntryController::class, 'exportarAsistencia'])->name('control-horas.export');
    Route::post('/control-horas/tarifas-generales', [TimeEntryController::class, 'guardarTarifasGenerales'])->name('control-horas.tarifas-generales');
    Route::post('/control-horas/ajuste-dia', [TimeEntryController::class, 'guardarAjusteDia'])->name('control-horas.ajuste-dia');
});

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    
    // DASHBOARD
    Route::get('/dashboard/{customerId?}', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/dashboard/{customerId}/report', CustomerReport::class)->name('dashboard.customer.report');

    // ==========================================
    // SECCIÓN ADMINISTRACIÓN (Seguridad Local Activa)
    // ==========================================
    
    // Index Administración
    Route::middleware(['auth', 'role:Administrador,Coordinador,Contador'])->group(function () {
        Route::get('/administracion', IndexAdministracion::class)->name('administracion.index');
    });
    
    // Gestión de Usuarios
    Route::middleware(['auth', 'role:Administrador,Coordinador'])->group(function () {
        Route::get('/administracion/users', IndexUser::class)->name('administracion.section');
    });

    Route::middleware(['auth', 'role:Administrador,Coordinador,Contador'])->group(function () {
        Route::get('/administracion/users/create', function(){
            return view('livewire.administracion.users.create');
        })->name('administracion.create.users');
        
        Route::get('/administracion/users/{user}/edit', function(User $user){
            return view('livewire.administracion.users.edit', compact('user'));
        })->name('administracion.edit.users');
    });

    // Gestión de Roles
    Route::middleware(['auth', 'role:Administrador'])->group(function () {
        Route::get('/administracion/roles', IndexRole::class)->name('administracion.role');
        Route::get('/administracion/create/roles', function(){
            return view('livewire.administracion.roles.create');
        })->name('administracion.role.create');
        
        Route::get('/administracion/roles/{role}/edit', function(Role $role) {
            return view('livewire.administracion.roles.edit', compact('role'));
        })->name('administracion.role.edit');
    });

    // Interns, Permissions & Relationships
    Route::middleware(['auth', 'role:Administrador,Coordinador,Contador'])->group(function () {
        Route::get('/administracion/interns', IndexInterns::class)->name('administracion.interns');
        Route::get('/administracion/relationships', IndexRelationship::class)->name('administracion.relationships');
    });
    
    Route::middleware(['auth', 'role:Administrador'])->group(function () {
        Route::get('/administracion/permissions', IndexAdministracion::class)->name('administracion.permissions');
    });

    // ==========================================
    // SECCIÓN CLIENTES (Costumers)
    // ==========================================
    Route::get('/customers', IndexCustomer::class)->name('customers.index');
    Route::get('/customers/{customer}/view', ViewCustomer::class)->name('customers.view');
    
    Route::middleware(['auth', 'role:Administrador,Coordinador'])->group(function () {
        Route::get('/customers/create', StoreCustomer::class)->name('customers.create');
        Route::get('/customers/{customer}/edit', UpdateCustomer::class)->name('customers.edit');
    });

    // Test de Email institucional
    Route::middleware(['auth', 'role:Administrador'])->group(function () {
        Route::get('/test-email', function() {
            Mail::to('prueba@datamid.com.mx')->send(new ReporteSemanal());
            return "Correo enviado con exito";
        });
    });

    // ==========================================
    // SECCIÓN MÓDULO CONTROL DE HORAS COMPLEJO
    // ==========================================
    
    // Operativo (Auxiliar / Coordinador / Contador / Administrador)
    Route::get('/time', IndexTimeControl::class)
        ->middleware('can:operate-time-tracking')
        ->name('time.index');

    // 💡 SOLUCIÓN DEL 403: Forzamos la ruta a validar con el Gate unificado de Productividad
    Route::get('/time/reports', MyProductivity::class)
        ->middleware('can:view-time-productivity')
        ->name('time.reports');

    Route::get('/time/attendance', AttendanceClock::class)
        ->middleware('can:operate-time-tracking')
        ->name('time.attendance');

    // Administración del módulo (Solo usuarios con permisos avanzados)
    Route::middleware('can:view-time-admin')->group(function () {
        Route::get('/time/admin', AdminTimeDashboard::class)->name('time.admin.dashboard');
        Route::get('/time/admin/profiles', OrganizationalProfiles::class)->name('time.admin.profiles');
        
        // 🆕 Nueva Vista Registrada: Control de Asistencia Biométrico (Checador)
        Route::get('/time/admin/attendance', AttendanceManagement::class)->name('time.admin.attendance');
    });
    
    Route::get('/time/admin/corrections', CorrectTimeEntry::class)
        ->middleware('can:correct-time-tracking')
        ->name('time.admin.corrections');
});