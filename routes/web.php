<?php

use Illuminate\Support\Facades\Route;

// Aplicar middleware de intentos de login a las rutas de autenticación
Route::middleware(['login.attempts'])->group(function () {
    Auth::routes();
});

// Aplicar middleware de control de acceso por IP a todas las rutas protegidas
Route::middleware(['ip.access'])->group(function () {
    // Página principal
    Route::get('/', function () {
        return redirect()->route('login');
    });

    // Home después del login
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

// Configuraciones SMTP
Route::resource('smtp-configs', App\Http\Controllers\Admin\SmtpConfigController::class);
Route::post('smtp-configs/predefined', [App\Http\Controllers\Admin\SmtpConfigController::class, 'storePredefined'])->name('smtp-configs.store-predefined');
Route::post('smtp-configs/{smtpConfig}/set-default', [App\Http\Controllers\Admin\SmtpConfigController::class, 'setDefault'])->name('smtp-configs.set-default');
Route::post('smtp-configs/{smtpConfig}/toggle-active', [App\Http\Controllers\Admin\SmtpConfigController::class, 'toggleActive'])->name('smtp-configs.toggle-active');
Route::post('smtp-configs/{smtpConfig}/test', [App\Http\Controllers\Admin\SmtpConfigController::class, 'test'])->name('smtp-configs.test');
Route::post('smtp-configs/migrate-env', [App\Http\Controllers\Admin\SmtpConfigController::class, 'migrateFromEnv'])->name('smtp-configs.migrate-env');
Route::get('smtp-configs-stats/statistics', [App\Http\Controllers\Admin\SmtpConfigController::class, 'statistics'])->name('smtp-configs.statistics');
Route::get('smtp-configs/available', [App\Http\Controllers\Admin\SmtpConfigController::class, 'available'])->name('smtp-configs.available');
Route::get('smtp-configs/{smtpConfig}/validate', [App\Http\Controllers\Admin\SmtpConfigController::class, 'validateConfiguration'])->name('smtp-configs.validate');

// Notificaciones
Route::resource('notifications', App\Http\Controllers\Admin\NotificationController::class);
Route::post('notifications/{notification}/mark-read', [App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])->name('admin.notifications.mark-read');
Route::post('notifications/delete-expired', [App\Http\Controllers\Admin\NotificationController::class, 'deleteExpired'])->name('admin.notifications.delete-expired');
Route::get('notifications/stats', [App\Http\Controllers\Admin\NotificationController::class, 'stats'])->name('admin.notifications.stats');
Route::post('notifications/send-welcome', [App\Http\Controllers\Admin\NotificationController::class, 'sendWelcome'])->name('admin.notifications.send-welcome');
Route::post('notifications/send-security', [App\Http\Controllers\Admin\NotificationController::class, 'sendSecurityAlert'])->name('admin.notifications.send-security');
Route::post('notifications/send-system', [App\Http\Controllers\Admin\NotificationController::class, 'sendSystemAlert'])->name('admin.notifications.send-system');

        // Backups
        Route::resource('backups', App\Http\Controllers\Admin\BackupController::class);
        Route::get('backups/{backup}/download', [App\Http\Controllers\Admin\BackupController::class, 'download'])->name('backups.download');
        Route::post('backups/{backup}/restore', [App\Http\Controllers\Admin\BackupController::class, 'restore'])->name('backups.restore');
        Route::post('backups/{backup}/verify', [App\Http\Controllers\Admin\BackupController::class, 'verify'])->name('backups.verify');
        Route::get('backups-stats/statistics', [App\Http\Controllers\Admin\BackupController::class, 'stats'])->name('backups.stats');
        Route::post('backups/clean-expired', [App\Http\Controllers\Admin\BackupController::class, 'cleanExpired'])->name('backups.clean-expired');

        // Mantenimiento
        Route::get('maintenance', [App\Http\Controllers\Admin\MaintenanceController::class, 'index'])->name('maintenance.index');
        Route::post('maintenance/enable', [App\Http\Controllers\Admin\MaintenanceController::class, 'enable'])->name('maintenance.enable');
        Route::post('maintenance/disable', [App\Http\Controllers\Admin\MaintenanceController::class, 'disable'])->name('maintenance.disable');
        Route::post('maintenance/allow-user', [App\Http\Controllers\Admin\MaintenanceController::class, 'allowUser'])->name('maintenance.allow-user');
        Route::post('maintenance/remove-user', [App\Http\Controllers\Admin\MaintenanceController::class, 'removeUser'])->name('maintenance.remove-user');
        Route::post('maintenance/allow-ip', [App\Http\Controllers\Admin\MaintenanceController::class, 'allowIp'])->name('maintenance.allow-ip');
        Route::post('maintenance/remove-ip', [App\Http\Controllers\Admin\MaintenanceController::class, 'removeIp'])->name('maintenance.remove-ip');
        Route::post('maintenance/clear', [App\Http\Controllers\Admin\MaintenanceController::class, 'clear'])->name('maintenance.clear');
        Route::get('maintenance/status', [App\Http\Controllers\Admin\MaintenanceController::class, 'status'])->name('maintenance.status');
        Route::get('maintenance/search-users', [App\Http\Controllers\Admin\MaintenanceController::class, 'searchUsers'])->name('maintenance.search-users');

// Drivers Dinámicos
Route::prefix('drivers')->name('drivers.')->middleware(['system.integration', 'integrated.logging', 'performance.monitoring', 'integrated.security', 'dynamic.driver'])->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\DynamicDriverController::class, 'index'])->name('index');
            Route::get('/status', [App\Http\Controllers\Admin\DynamicDriverController::class, 'status'])->name('status');
            Route::get('/supported/{service}', [App\Http\Controllers\Admin\DynamicDriverController::class, 'supportedDrivers'])->name('supported');
            Route::post('/change', [App\Http\Controllers\Admin\DynamicDriverController::class, 'changeDriver'])->name('change');
            Route::get('/config/{service}', [App\Http\Controllers\Admin\DynamicDriverController::class, 'getDriverConfig'])->name('config');
            Route::post('/restore/{service}', [App\Http\Controllers\Admin\DynamicDriverController::class, 'restoreDriver'])->name('restore');
            Route::post('/restart', [App\Http\Controllers\Admin\DynamicDriverController::class, 'restartServices'])->name('restart');
            Route::post('/validate', [App\Http\Controllers\Admin\DynamicDriverController::class, 'validateConfig'])->name('validate');
            Route::get('/statistics', [App\Http\Controllers\Admin\DynamicDriverController::class, 'statistics'])->name('statistics');
        });
    });
});

// Rutas de categorías y subcategorías (solo para administradores)
Route::prefix('admin')->middleware(['auth', 'can:manage-categories'])->group(function () {
    Route::resource('categories', App\Http\Controllers\CategoryController::class);
    Route::resource('subcategories', App\Http\Controllers\SubcategoryController::class);
});
Auth::routes();
