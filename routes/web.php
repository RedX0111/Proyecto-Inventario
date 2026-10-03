<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\CheckRol;

// 1. Rutas Públicas (Login)
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 2. Rutas Protegidas (Requieren autenticación)
Route::middleware(['auth'])->group(function () {
    
    // Rutas exclusivas para ADMINISTRADOR (Dashboard y CRUD de Usuarios)
    Route::middleware([CheckRol::class . ':ADMINISTRADOR'])->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
        Route::post('/sincronizar-sheets', [AdminController::class, 'jalarDataDeSheets'])->name('admin.sincronizar');
        Route::post('/aprobar-lote', [AdminController::class, 'aprobarLote'])->name('admin.aprobarLote');
        Route::post('/dashboard/periodo/{id}/cerrar', [AdminController::class, 'cerrarPeriodo'])->name('admin.cerrarPeriodo');
        
        //Editar activos
        Route::put('/dashboard/activos/{codigo}', [AdminController::class, 'activosUpdate'])->name('admin.activos.update');

        // CRUD de Usuarios
        Route::get('/dashboard/usuarios', [AdminController::class, 'usuariosIndex'])->name('admin.usuarios.index');
        Route::post('/dashboard/usuarios', [AdminController::class, 'usuariosStore'])->name('admin.usuarios.store');
        Route::put('/dashboard/usuarios/{id}', [AdminController::class, 'usuariosUpdate'])->name('admin.usuarios.update');
        Route::delete('/dashboard/usuarios/{id}', [AdminController::class, 'usuariosDestroy'])->name('admin.usuarios.destroy');
    });

    // Rutas para AUDITOR_LIMPIEZA y ADMINISTRADOR
    Route::middleware([CheckRol::class . ':AUDITOR_LIMPIEZA,ADMINISTRADOR'])->group(function () {
        Route::get('/limpieza', [AuditoriaController::class, 'bandejaLimpieza'])->name('auditoria.limpieza');
        Route::post('/limpieza/conciliar', [AuditoriaController::class, 'conciliarCenso'])->name('auditoria.conciliar');
    });

    // Padrón Maestro (Consulta general)
    Route::get('/padron', [AuditoriaController::class, 'padronMaestro'])->name('auditoria.padron');
});