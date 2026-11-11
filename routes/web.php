<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Ciudadano
Route::get('/', [KioskController::class, 'index'])->name('kiosk');
Route::post('/turnos', [KioskController::class, 'store'])->middleware('throttle:20,1')->name('tickets.store');
Route::get('/turno/{ticket:token}', [KioskController::class, 'show'])->name('tickets.show');
Route::get('/turno/{ticket:token}/estado', [KioskController::class, 'status'])->name('tickets.status');

// Pantalla pública (TV de sala de espera)
Route::get('/pantalla', [DisplayController::class, 'index'])->name('display');
Route::get('/pantalla/datos', [DisplayController::class, 'data'])->name('display.data');

// Personal de ventanilla
Route::middleware('auth')->prefix('ventanilla')->name('agent.')->group(function () {
    Route::get('/', [AgentController::class, 'panel'])->name('panel');
    Route::get('/reportes', [ReportController::class, 'index'])->name('reports');
    Route::post('/siguiente', [AgentController::class, 'next'])->name('next');
    Route::post('/repetir', [AgentController::class, 'recall'])->name('recall');
    Route::post('/iniciar', [AgentController::class, 'start'])->name('start');
    Route::post('/finalizar', [AgentController::class, 'finish'])->name('finish');
    Route::post('/ausente', [AgentController::class, 'skip'])->name('skip');
});

Route::get('/dashboard', fn () => redirect()->route('agent.panel'))->middleware('auth')->name('dashboard');

require __DIR__ . '/auth.php'; // Laravel Breeze
