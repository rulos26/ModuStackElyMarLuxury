<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\ApiDocumentationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas públicas de la API
Route::get('/info', [ApiController::class, 'info'])->name('api.info');
Route::get('/docs', [ApiDocumentationController::class, 'index'])->name('api.docs');
Route::get('/docs/simple', [ApiDocumentationController::class, 'simple'])->name('api.docs.simple');

// Rutas protegidas de la API
Route::middleware(['api.rate.limit:100,60', 'api.auth'])->group(function () {

    // Información del sistema
    Route::get('/system/status', [ApiController::class, 'systemStatus'])->name('api.system.status');

    // Gestión de drivers
    Route::prefix('drivers')->name('api.drivers.')->group(function () {
        Route::get('/', [ApiController::class, 'drivers'])->name('index');
        Route::post('/', [ApiController::class, 'drivers'])->name('change');
    });

    // Gestión de respaldos
    Route::prefix('backups')->name('api.backups.')->group(function () {
        Route::get('/', [ApiController::class, 'backups'])->name('index');
        Route::post('/', [ApiController::class, 'backups'])->name('create');
    });

    // Gestión de notificaciones
    Route::prefix('notifications')->name('api.notifications.')->group(function () {
        Route::get('/', [ApiController::class, 'notifications'])->name('index');
        Route::post('/', [ApiController::class, 'notifications'])->name('send');
    });

    // Gestión de configuración
    Route::prefix('settings')->name('api.settings.')->group(function () {
        Route::get('/', [ApiController::class, 'settings'])->name('index');
        Route::post('/', [ApiController::class, 'settings'])->name('update');
    });

    // Gestión de usuarios
    Route::prefix('users')->name('api.users.')->group(function () {
        Route::get('/', [ApiController::class, 'users'])->name('index');
        Route::post('/', [ApiController::class, 'users'])->name('create');
    });
});

// Rutas con rate limiting específico
Route::middleware(['api.rate.limit:10,60', 'api.auth'])->group(function () {
    Route::post('/drivers', [ApiController::class, 'drivers'])->name('api.drivers.change.limited');
});

Route::middleware(['api.rate.limit:5,300', 'api.auth'])->group(function () {
    Route::post('/backups', [ApiController::class, 'backups'])->name('api.backups.create.limited');
});

Route::middleware(['api.rate.limit:20,60', 'api.auth'])->group(function () {
    Route::post('/notifications', [ApiController::class, 'notifications'])->name('api.notifications.send.limited');
});

Route::middleware(['api.rate.limit:15,60', 'api.auth'])->group(function () {
    Route::post('/settings', [ApiController::class, 'settings'])->name('api.settings.update.limited');
});

Route::middleware(['api.rate.limit:5,300', 'api.auth'])->group(function () {
    Route::post('/users', [ApiController::class, 'users'])->name('api.users.create.limited');
});

// Rutas de autenticación API
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8'
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('API Token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Autenticación exitosa',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Credenciales inválidas'
        ], 401);
    })->name('login');

    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente'
        ]);
    })->middleware('auth:sanctum')->name('logout');

    Route::get('/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    })->middleware('auth:sanctum')->name('me');
});

// Rutas de utilidades API
Route::prefix('utils')->name('api.utils.')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0'
        ]);
    })->name('health');

    Route::get('/version', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'api_version' => '1.0.0',
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'server_time' => now()->toISOString()
            ]
        ]);
    })->name('version');
});

// Manejo de errores 404 para API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint no encontrado',
        'error_code' => 'NOT_FOUND'
    ], 404);
});

