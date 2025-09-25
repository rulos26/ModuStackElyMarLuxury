<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DynamicDriverService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DynamicDriverController extends Controller
{
    protected $dynamicDriverService;

    public function __construct(DynamicDriverService $dynamicDriverService)
    {
        $this->dynamicDriverService = $dynamicDriverService;
    }

    /**
     * Muestra la página principal de administración de drivers
     */
    public function index()
    {
        $driversStatus = $this->dynamicDriverService->getAllDriversStatus();

        return view('admin.drivers.index', compact('driversStatus'));
    }

    /**
     * Obtiene el estado de todos los drivers
     */
    public function status(): JsonResponse
    {
        try {
            $status = $this->dynamicDriverService->getAllDriversStatus();

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estado de drivers', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado de drivers'
            ], 500);
        }
    }

    /**
     * Obtiene los drivers soportados para un servicio
     */
    public function supportedDrivers(string $service): JsonResponse
    {
        try {
            $drivers = $this->dynamicDriverService->getSupportedDrivers($service);

            return response()->json([
                'success' => true,
                'data' => $drivers
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener drivers soportados', [
                'service' => $service,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener drivers soportados'
            ], 500);
        }
    }

    /**
     * Cambia el driver de un servicio
     */
    public function changeDriver(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service' => 'required|string|in:cache,session,queue,mail,database',
            'driver' => 'required|string',
            'config' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $service = $request->input('service');
            $driver = $request->input('driver');
            $config = $request->input('config', []);

            // Validar configuración del driver
            $validationErrors = $this->dynamicDriverService->validateDriverConfig($service, $driver, $config);

            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración inválida',
                    'errors' => $validationErrors
                ], 422);
            }

            // Cambiar el driver
            $success = $this->dynamicDriverService->changeDriver($service, $driver, $config);

            if ($success) {
                // Reiniciar servicios relacionados
                $this->dynamicDriverService->restartServices([$service]);

                return response()->json([
                    'success' => true,
                    'message' => "Driver cambiado exitosamente a {$driver} para {$service}",
                    'data' => [
                        'service' => $service,
                        'driver' => $driver,
                        'config' => $config
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al cambiar el driver'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error al cambiar driver', [
                'service' => $request->input('service'),
                'driver' => $request->input('driver'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtiene la configuración actual de un driver
     */
    public function getDriverConfig(string $service): JsonResponse
    {
        try {
            $config = $this->dynamicDriverService->getDriverConfig($service);
            $currentDriver = $this->dynamicDriverService->getCurrentDriver($service);

            return response()->json([
                'success' => true,
                'data' => [
                    'current_driver' => $currentDriver,
                    'config' => $config
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener configuración del driver', [
                'service' => $service,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuración del driver'
            ], 500);
        }
    }

    /**
     * Restaura la configuración de un driver desde la base de datos
     */
    public function restoreDriver(string $service): JsonResponse
    {
        try {
            $success = $this->dynamicDriverService->restoreDriverConfig($service);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => "Configuración restaurada para {$service}"
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró configuración guardada'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Error al restaurar driver', [
                'service' => $service,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar configuración del driver'
            ], 500);
        }
    }

    /**
     * Reinicia servicios específicos
     */
    public function restartServices(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'services' => 'sometimes|array',
            'services.*' => 'string|in:cache,session,queue,mail,database'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Servicios inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $services = $request->input('services', []);
            $success = $this->dynamicDriverService->restartServices($services);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Servicios reiniciados exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al reiniciar servicios'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error al reiniciar servicios', [
                'services' => $request->input('services'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Valida la configuración de un driver
     */
    public function validateConfig(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service' => 'required|string|in:cache,session,queue,mail,database',
            'driver' => 'required|string',
            'config' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $service = $request->input('service');
            $driver = $request->input('driver');
            $config = $request->input('config');

            $errors = $this->dynamicDriverService->validateDriverConfig($service, $driver, $config);

            return response()->json([
                'success' => true,
                'valid' => empty($errors),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Error al validar configuración', [
                'service' => $request->input('service'),
                'driver' => $request->input('driver'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al validar configuración'
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de uso de drivers
     */
    public function statistics(): JsonResponse
    {
        try {
            $status = $this->dynamicDriverService->getAllDriversStatus();
            $statistics = [];

            foreach ($status as $service => $data) {
                $statistics[$service] = [
                    'current_driver' => $data['current'],
                    'supported_count' => count($data['supported']),
                    'has_config' => !empty($data['config']),
                    'last_updated' => $data['config']['updated_at'] ?? null
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ], 500);
        }
    }
}

