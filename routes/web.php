<?php

use App\Http\Controllers\FichajeController;
use App\Http\Controllers\FichajesDiariosController;
use App\Http\Controllers\GroupAssignmentController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\RrhhDocumentosController;
use App\Http\Controllers\UserBuscadorController;
use App\Http\Controllers\UserCronosController;
use App\Http\Controllers\UserSemillasController;
use App\Http\Controllers\UserStoreController;
use App\Http\Controllers\UserZeusController;
use App\Http\Controllers\WorkerBuscadorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\UserPlutonController;
use App\Http\Controllers\TacografoController;

// 🔐 Redirige '/' directamente a /gestoria (modo admin)
Route::redirect('/', '/gestoria');

// 🔐 Rutas para login (solo para invitados)
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')
        ->name('register.store'); // 👈 AÑADE ESTO
});

// 🔒 Rutas privadas (solo para administradores logueados)
Route::middleware('auth')->group(function () {

    // 📌 Página principal
    Route::get('/gestoria', function () { return view('gestor.gestoria'); })->name('gestor.gestoria');

    // 🧑‍🔧 TRABAJADORES (Polifonía)
    Route::get('/trabajadores/{id}/edit', [TrabajadorController::class, 'edit'])->name('trabajadores.edit');
    Route::put('/trabajadores/{id}', [TrabajadorController::class, 'update'])->name('trabajadores.update');

    // 👤 USUARIOS (base principal)
    Route::resource('usuarios', UsuarioController::class)->only(['index', 'edit', 'update']);
    Route::get('/usuarios/unificado/{email}', [UsuarioController::class, 'editUnificado'])->name('usuarios.edit.unificado');
    Route::get('/usuarios/unificado/uuid/{uuid}', [UsuarioController::class, 'editByUuid'])->name('usuarios.edit.uuid');
    Route::get('/usuarios/vincular', [UsuarioController::class, 'vincular'])->name('usuarios.vincular');
    Route::post('/usuarios/vincular', [UsuarioController::class, 'vincularStore'])->name('usuarios.vincular.store');

    Route::get('/usuarios/vincular/{vinculo}/edit', [UsuarioController::class, 'vincularEdit'])->name('usuarios.vincular.edit');
    Route::put('/usuarios/vincular/{vinculo}', [UsuarioController::class, 'vincularUpdate'])->name('usuarios.vincular.update');


    // 👤 USUARIOS Buscador
    Route::get('/buscador/user/{id}/edit', [UserBuscadorController::class, 'edit'])->name('buscador.user.edit');
    Route::put('/buscador/user/{id}', [UserBuscadorController::class, 'update'])->name('buscador.user.update');

    Route::get('/buscador/worker/{id}/edit', [WorkerBuscadorController::class, 'edit'])->name('buscador.worker.edit');
    Route::put('/buscador/worker/{id}', [WorkerBuscadorController::class, 'update'])->name('buscador.worker.update');

    // 👤 USUARIOS Cronos
    Route::get('/cronos/user/{id}/edit', [UserCronosController::class, 'edit'])->name('cronos.user.edit');
    Route::put('/cronos/user/{id}', [UserCronosController::class, 'update'])->name('cronos.user.update');

    // 👤 USUARIOS Semillas
    Route::get('/semillas/user/{id}/edit', [UserSemillasController::class, 'edit'])->name('semillas.user.edit');
    Route::put('/semillas/user/{id}', [UserSemillasController::class, 'update'])->name('semillas.user.update');

    // 👤 USUARIOS Store
    Route::get('/store/user/{id}/edit', [UserStoreController::class, 'edit'])->name('store.user.edit');
    Route::put('/store/user/{id}', [UserStoreController::class, 'update'])->name('store.user.update');

    // 👤 USUARIOS Zeus
    Route::get('/zeus/user/{id}/edit', [UserZeusController::class, 'edit'])->name('zeus.user.edit');
    Route::put('/zeus/user/{id}', [UserZeusController::class, 'update'])->name('zeus.user.update');

    // 🛰️ USUARIOS PLUTÓN
    Route::put('/pluton/{pluton}', [UserPlutonController::class, 'update'])->name('pluton.update');
    Route::get('/pluton/{pluton}/edit', [UserPlutonController::class, 'edit'])->name('pluton.edit');

    Route::get('/fichajes/{trabajador}/edit', [FichajeController::class, 'edit'])->name('fichajes.edit');
    Route::put('/fichajes/{trabajador}', [FichajeController::class, 'update'])->name('fichajes.update');
    Route::get('/usuarios/{trabajador}/fichajes-unificado', [FichajeController::class, 'fichajesUnificado'])
        ->name('usuarios.fichajes.unificado');
    Route::get('/usuarios/onboarding', [OnboardingController::class, 'onboardingCreate'])
        ->name('usuarios.onboarding.create');

    Route::post('/usuarios/onboarding/send', [OnboardingController::class, 'onboardingSend'])
        ->name('usuarios.onboarding.send');
    // opcional modal historial
    Route::get('/fichajes/{trabajador}/historial', [FichajeController::class, 'getFichajes'])->name('fichajes.historial');

    Route::get('/fichajes-diarios/export', [FichajesDiariosController::class, 'export'])
        ->name('fichajes.diarios.export');

    // routes/web.php
    Route::get('/fichajes/diarios', [\App\Http\Controllers\FichajesDiariosController::class, 'index'])
        ->name('fichajes.diarios.index');

    // Export CSV (lo que estás viendo con filtros/orden)
    Route::get('/trabajadores/export', [UsuarioController::class, 'export'])->name('trabajadores.export');
    Route::get('/trabajadores/{trabajador}/fichajes', [TrabajadorController::class, 'getFichajes'])
        ->name('trabajadores.fichajes.get');
    // Toggle activo rápido
    Route::post('/trabajadores/{id}/toggle-activo', [TrabajadorController::class, 'toggleActivo'])->name('trabajadores.toggleActivo');
    Route::get('/usuarios/export/excel', [UsuarioController::class, 'exportExcel'])
        ->name('usuarios.export.excel');

    Route::post('/trabajadores/{trabajador}/dias', [UsuarioController::class, 'storeDays'])
        ->name('trabajadores.dias.store');

    Route::get('/trabajadores/{trabajador}/dias', [UsuarioController::class, 'getDays'])
        ->name('trabajadores.dias.get');

    Route::get('/trabajadores/{trabajador}/vacaciones/pdf', [UsuarioController::class, 'vacaciones'])
        ->name('trabajadores.vacaciones.pdf');

    Route::get('/trabajadores/{trabajador}/permisos/pdf', [UsuarioController::class, 'permisos'])
        ->name('trabajadores.permisos.pdf');

    Route::get('/trabajadores/{trabajador}/bajas/pdf', [UsuarioController::class, 'bajas'])
        ->name('trabajadores.bajas.pdf');

    Route::get('/groups/asignar', [GroupAssignmentController::class, 'create'])->name('groups.assign.create');
    Route::post('/groups/asignar', [GroupAssignmentController::class, 'store'])->name('groups.assign.store');
    Route::delete('/groups/asignar', [GroupAssignmentController::class, 'detach'])->name('groups.assign.detach');

    //RRHH
    Route::get('/rrhh/documentos', [RrhhDocumentosController::class, 'index'])
        ->name('rrhh.documentos.index');

    Route::post('/rrhh/documentos/pdf', [RrhhDocumentosController::class, 'pdf'])
        ->name('rrhh.documentos.pdf');

    Route::post('/rrhh/documentos/zip', [RrhhDocumentosController::class, 'zip'])
        ->name('rrhh.documentos.zip');

    Route::resource('tacografo', TacografoController::class);
    Route::post('/tacografo/{tacografo}/toggle-activo',
        [TacografoController::class, 'toggleActivo']
    )->name('tacografo.toggle');
    Route::post('/tacografo/{tacografo}/fecha', [TacografoController::class, 'updateFecha'])
        ->name('tacografo.updateFecha');
    Route::get('/tacografo/create', [TacografoController::class, 'create'])->name('tacografo.create');
    Route::post('/tacografo', [TacografoController::class, 'store'])->name('tacografo.store');
    // 🚪 Cerrar sesión
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
