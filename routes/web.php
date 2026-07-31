<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrganizationChartController;
use App\Http\Controllers\TimeEntryController;
// Modelos
use App\Livewire\Administracion\IndexAdministracion;
use App\Livewire\Administracion\Interns\IndexInterns;
// Componentes Locales (Clientes y Administración)
use App\Livewire\Administracion\Relationship\IndexRelationship;
use App\Livewire\Administracion\Roles\IndexRole;
use App\Livewire\Administracion\Users\IndexUser;
use App\Livewire\Customer\IndexCustomer;
use App\Livewire\Customer\StoreCustomer;
use App\Livewire\Customer\UpdateCustomer;
use App\Livewire\Customer\ViewCustomer;
use App\Livewire\CustomerReport;
use App\Livewire\TimeControl\Admin\AdminTimeDashboard;
use App\Livewire\TimeControl\Admin\AttendanceManagement;
use App\Livewire\TimeControl\Admin\CorrectTimeEntry;
// Componentes Remotos (Control de Horas Complejo)
use App\Livewire\TimeControl\Admin\OrganizationalProfiles;
use App\Livewire\TimeControl\AttendanceClock;
use App\Livewire\TimeControl\IndexTimeControl;
use App\Livewire\TimeControl\MyProductivity;
use App\Mail\ReporteSemanal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard/pdf', [DashboardController::class, 'exportPdf'])->name('dashboard.pdf');
Route::post('/dashboard/pdf', [DashboardController::class, 'generatePdf'])->name('dashboard.pdf.generate');

Route::middleware(['auth', 'verified'])->group(function () {
    // Rutas del módulo checador (asistencia biométrica)
    Route::post('/control-horas/consultar', [TimeEntryController::class, 'consultarAsistencia'])->name('control-horas.consultar');
    Route::get('/control-horas/export/{format}', [TimeEntryController::class, 'exportarAsistencia'])->name('control-horas.export');
    Route::post('/control-horas/tarifas-generales', [TimeEntryController::class, 'guardarTarifasGenerales'])->name('control-horas.tarifas-generales');
    Route::post('/control-horas/ajuste-dia', [TimeEntryController::class, 'guardarAjusteDia'])->name('control-horas.ajuste-dia');
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard/client-activity-data', [DashboardController::class, 'getClientActivityData'])
    ->name('dashboard.client-activity-data');

Route::get('/time/activity-data', [DashboardController::class, 'getActivityData'])->name('time.activity-data');
Route::get('/time/client-data', [DashboardController::class, 'getClientData'])->name('time.client-data');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // ==========================================
    // DASHBOARD
    // ==========================================
    Route::get('/dashboard/{customerId?}', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/dashboard/{customerId}/report', CustomerReport::class)->name('dashboard.customer.report');

    // ==========================================
    // SECCIÓN ADMINISTRACIÓN (Seguridad Local Activa)
    // ==========================================

    // Index Administración
    Route::get('/administracion', IndexAdministracion::class)
        ->middleware('access.permission:administration.organization.manage')
        ->name('administracion.index');

    // Organigrama - Solo Administrador
    Route::prefix('administracion/org-chart')
        ->middleware('access.permission:administration.organization.manage')
        ->name('administracion.org-chart.')
        ->group(function () {
            Route::get('/', [OrganizationChartController::class, 'index'])->name('index');
            Route::post('/relations', [OrganizationChartController::class, 'store'])->name('store');
            Route::delete('/relations/{relationId}', [OrganizationChartController::class, 'destroy'])->name('destroy');
        });

    // ==========================================
    // GESTIÓN DE USUARIOS
    // ==========================================

    // Lista de usuarios - Administrador y Coordinador
    Route::get('/administracion/users', IndexUser::class)
        ->middleware('access.permission:administration.users.manage')
        ->name('administracion.section');

    // Crear usuario - Administrador, Coordinador y Contador
    Route::get('/administracion/users/create', function () {
        return view('livewire.administracion.users.create');
    })->middleware('access.permission:administration.users.manage')
        ->name('administracion.create.users');

    // Editar usuario - Administrador, Coordinador y Contador
    Route::get('/administracion/users/{user}/edit', function (User $user) {
        return view('livewire.administracion.users.edit', compact('user'));
    })->middleware('access.permission:administration.users.manage')
        ->name('administracion.edit.users');

    // ==========================================
    // GESTIÓN DE ROLES - Solo Administrador
    // ==========================================

    // Lista de roles
    Route::get('/administracion/roles', IndexRole::class)
        ->middleware('access.permission:administration.roles.manage')
        ->name('administracion.role');

    // Crear rol
    Route::get('/administracion/create/roles', function () {
        return view('livewire.administracion.roles.create');
    })->middleware('access.permission:administration.roles.manage')
        ->name('administracion.role.create');

    // Editar rol
    Route::get('/administracion/roles/{role}/edit', function (Role $role) {
        return view('livewire.administracion.roles.edit', compact('role'));
    })->middleware('access.permission:administration.roles.manage')
        ->name('administracion.role.edit');

    // ==========================================
    // INTERNS, RELATIONSHIPS & PERMISSIONS
    // ==========================================

    // Interns - Administrador, Coordinador y Contador
    Route::get('/administracion/interns', IndexInterns::class)
        ->middleware('access.permission:administration.assignments.manage')
        ->name('administracion.interns');

    // Relationships - Administrador, Coordinador y Contador
    Route::get('/administracion/relationships', IndexRelationship::class)
        ->middleware('access.permission:administration.assignments.manage')
        ->name('administracion.relationships');

    // Permissions - Solo Administrador
    Route::get('/administracion/permissions', IndexAdministracion::class)
        ->middleware('access.permission:administration.permissions.manage')
        ->name('administracion.permissions');

    // ==========================================
    // SECCIÓN CLIENTES (Customers)
    // ==========================================
    Route::get('/customers', IndexCustomer::class)
        ->middleware('access.permission:customers.view')
        ->name('customers.index');
    Route::get('/customers/{customer}/view', ViewCustomer::class)
        ->middleware('access.permission:customers.view')
        ->name('customers.view');

    Route::get('/customers/create', StoreCustomer::class)
        ->middleware('access.permission:customers.manage')
        ->name('customers.create');

    Route::get('/customers/{customer}/edit', UpdateCustomer::class)
        ->middleware('access.permission:customers.manage')
        ->name('customers.edit');

    // ==========================================
    // TEST DE EMAIL INSTITUCIONAL
    // ==========================================
    Route::get('/test-email', function () {
        Mail::to('prueba@datamid.com.mx')->send(new ReporteSemanal);

        return 'Correo enviado con exito';
    })->middleware('access.permission:administration.organization.manage');

    // ==========================================
    // SECCIÓN MÓDULO CONTROL DE HORAS COMPLEJO
    // ==========================================

    // Operativo (Auxiliar / Coordinador / Contador / Administrador)
    Route::get('/time', IndexTimeControl::class)
        ->middleware('access.permission:activities.manage')
        ->name('time.index');

    Route::get('/time/dashboard', [DashboardController::class, 'index'])
        ->middleware('access.permission:time-control.productivity.view')
        ->name('time.dashboard');

    Route::get('/time/reports', MyProductivity::class)
        ->middleware('access.permission:time-control.productivity.view')
        ->name('time.reports');

    Route::get('/time/attendance', AttendanceClock::class)
        ->middleware('access.permission:time-control.clock.use')
        ->name('time.attendance');

    // Administración del módulo (Solo usuarios con permisos avanzados)
    Route::middleware('access.permission:time-control.supervision.view')->group(function () {
        Route::get('/time/admin', AdminTimeDashboard::class)->name('time.admin.dashboard');
        Route::get('/time/admin/profiles', OrganizationalProfiles::class)->name('time.admin.profiles');
        Route::get('/time/admin/attendance', AttendanceManagement::class)->name('time.admin.attendance');
    });

    Route::get('/time/admin/corrections', CorrectTimeEntry::class)
        ->middleware('access.permission:time-control.supervision.view')
        ->name('time.admin.corrections');
});
