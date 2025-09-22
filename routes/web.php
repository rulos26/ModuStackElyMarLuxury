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

    // Configuración
    Route::get('settings', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/reset', [App\Http\Controllers\Admin\SettingsController::class, 'reset'])->name('settings.reset');
});
