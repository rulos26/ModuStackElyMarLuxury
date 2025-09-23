<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Rutas de administración
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    // Usuarios
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);

    // Roles
    Route::resource('roles', App\Http\Controllers\Admin\RoleController::class);

    // Permisos
    Route::resource('permissions', App\Http\Controllers\Admin\PermissionController::class);

    // Configuración - Dashboard modular
    Route::get('settings', [App\Http\Controllers\Admin\SettingsDashboardController::class, 'index'])->name('settings.dashboard');
    Route::get('settings/section/{section}', [App\Http\Controllers\Admin\SettingsDashboardController::class, 'section'])->name('settings.section');
    Route::put('settings/section/{section}', [App\Http\Controllers\Admin\SettingsDashboardController::class, 'updateSection'])->name('settings.update.section');

    // Configuración legacy (mantener compatibilidad)
    Route::get('settings/legacy', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.legacy');
    Route::put('settings/legacy', [App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update.legacy');
    Route::post('settings/reset', [App\Http\Controllers\Admin\SettingsController::class, 'reset'])->name('settings.reset');
});
