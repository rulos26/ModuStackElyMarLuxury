<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiDocumentationController extends Controller
{
    /**
     * Documentación completa de la API
     */
    public function index(): JsonResponse
    {
        $documentation = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'ModuStack ElyMar Luxury API',
                'version' => '1.0.0',
                'description' => 'API completa para administración del sistema ModuStack ElyMar Luxury',
                'contact' => [
                    'name' => 'Soporte API',
                    'email' => 'support@modustack.com'
                ]
            ],
            'servers' => [
                [
                    'url' => url('/api'),
                    'description' => 'Servidor de producción'
                ]
            ],
            'security' => [
                [
                    'BearerAuth' => []
                ],
                [
                    'ApiKeyAuth' => []
                ]
            ],
            'paths' => $this->getApiPaths(),
            'components' => [
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT'
                    ],
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key'
                    ]
                ],
                'schemas' => $this->getApiSchemas()
            ]
        ];

        return response()->json($documentation);
    }

    /**
     * Obtiene las rutas de la API
     */
    protected function getApiPaths(): array
    {
        return [
            '/info' => [
                'get' => [
                    'tags' => ['General'],
                    'summary' => 'Información de la API',
                    'description' => 'Obtiene información general sobre la API',
                    'responses' => [
                        '200' => [
                            'description' => 'Información de la API',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/ApiInfo'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/system/status' => [
                'get' => [
                    'tags' => ['Sistema'],
                    'summary' => 'Estado del sistema',
                    'description' => 'Obtiene el estado actual del sistema',
                    'security' => [['BearerAuth' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'Estado del sistema',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/SystemStatus'
                                    ]
                                ]
                            ]
                        ],
                        '401' => [
                            'description' => 'No autorizado',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/Error'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/drivers' => [
                'get' => [
                    'tags' => ['Drivers'],
                    'summary' => 'Gestión de drivers',
                    'description' => 'Gestiona los drivers del sistema',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'action',
                            'in' => 'query',
                            'description' => 'Acción a realizar',
                            'required' => true,
                            'schema' => [
                                'type' => 'string',
                                'enum' => ['status', 'change', 'validate', 'restore', 'restart']
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Operación exitosa',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/DriverResponse'
                                    ]
                                ]
                            ]
                        ],
                        '400' => [
                            'description' => 'Solicitud inválida',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/Error'
                                    ]
                                ]
                            ]
                        ],
                        '401' => [
                            'description' => 'No autorizado',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/Error'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'post' => [
                    'tags' => ['Drivers'],
                    'summary' => 'Cambiar driver',
                    'description' => 'Cambia el driver de un servicio',
                    'security' => [['BearerAuth' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/DriverChangeRequest'
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Driver cambiado exitosamente',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/SuccessResponse'
                                    ]
                                ]
                            ]
                        ],
                        '422' => [
                            'description' => 'Datos de entrada inválidos',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/ValidationError'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/backups' => [
                'get' => [
                    'tags' => ['Respaldos'],
                    'summary' => 'Listar respaldos',
                    'description' => 'Obtiene la lista de respaldos disponibles',
                    'security' => [['BearerAuth' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'Lista de respaldos',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'data' => [
                                                'type' => 'array',
                                                'items' => [
                                                    '$ref' => '#/components/schemas/Backup'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'post' => [
                    'tags' => ['Respaldos'],
                    'summary' => 'Crear respaldo',
                    'description' => 'Crea un nuevo respaldo del sistema',
                    'security' => [['BearerAuth' => []]],
                    'requestBody' => [
                        'required' => false,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/BackupCreateRequest'
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Respaldo creado exitosamente',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/BackupResponse'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/notifications' => [
                'get' => [
                    'tags' => ['Notificaciones'],
                    'summary' => 'Listar notificaciones',
                    'description' => 'Obtiene la lista de notificaciones',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'per_page',
                            'in' => 'query',
                            'description' => 'Número de elementos por página',
                            'schema' => ['type' => 'integer', 'default' => 15]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Lista de notificaciones',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'data' => [
                                                'type' => 'array',
                                                'items' => [
                                                    '$ref' => '#/components/schemas/Notification'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'post' => [
                    'tags' => ['Notificaciones'],
                    'summary' => 'Enviar notificación',
                    'description' => 'Envía una nueva notificación',
                    'security' => [['BearerAuth' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/NotificationRequest'
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Notificación enviada exitosamente',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/SuccessResponse'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/settings' => [
                'get' => [
                    'tags' => ['Configuración'],
                    'summary' => 'Obtener configuración',
                    'description' => 'Obtiene la configuración del sistema',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'category',
                            'in' => 'query',
                            'description' => 'Categoría de configuración',
                            'schema' => ['type' => 'string', 'default' => 'all']
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Configuración del sistema',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'data' => [
                                                'type' => 'array',
                                                'items' => [
                                                    '$ref' => '#/components/schemas/AppSetting'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'post' => [
                    'tags' => ['Configuración'],
                    'summary' => 'Actualizar configuración',
                    'description' => 'Actualiza la configuración del sistema',
                    'security' => [['BearerAuth' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/SettingsUpdateRequest'
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Configuración actualizada exitosamente',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/SuccessResponse'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/users' => [
                'get' => [
                    'tags' => ['Usuarios'],
                    'summary' => 'Listar usuarios',
                    'description' => 'Obtiene la lista de usuarios',
                    'security' => [['BearerAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'per_page',
                            'in' => 'query',
                            'description' => 'Número de elementos por página',
                            'schema' => ['type' => 'integer', 'default' => 15]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Lista de usuarios',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'data' => [
                                                'type' => 'array',
                                                'items' => [
                                                    '$ref' => '#/components/schemas/User'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'post' => [
                    'tags' => ['Usuarios'],
                    'summary' => 'Crear usuario',
                    'description' => 'Crea un nuevo usuario',
                    'security' => [['BearerAuth' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/UserCreateRequest'
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Usuario creado exitosamente',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/UserResponse'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Obtiene los esquemas de la API
     */
    protected function getApiSchemas(): array
    {
        return [
            'ApiInfo' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'data' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'version' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'endpoints' => ['type' => 'object'],
                            'authentication' => ['type' => 'string'],
                            'rate_limit' => ['type' => 'string']
                        ]
                    ]
                ]
            ],
            'SystemStatus' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'data' => [
                        'type' => 'object',
                        'properties' => [
                            'system' => [
                                'type' => 'object',
                                'properties' => [
                                    'status' => ['type' => 'string'],
                                    'uptime' => ['type' => 'string'],
                                    'memory_usage' => ['type' => 'integer'],
                                    'memory_peak' => ['type' => 'integer'],
                                    'php_version' => ['type' => 'string'],
                                    'laravel_version' => ['type' => 'string']
                                ]
                            ],
                            'drivers' => ['type' => 'object'],
                            'database' => ['type' => 'object'],
                            'cache' => ['type' => 'object'],
                            'storage' => ['type' => 'object']
                        ]
                    ]
                ]
            ],
            'DriverResponse' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'data' => ['type' => 'object']
                ]
            ],
            'DriverChangeRequest' => [
                'type' => 'object',
                'required' => ['service', 'driver'],
                'properties' => [
                    'service' => [
                        'type' => 'string',
                        'enum' => ['cache', 'session', 'queue', 'mail', 'database']
                    ],
                    'driver' => ['type' => 'string'],
                    'config' => ['type' => 'object']
                ]
            ],
            'Backup' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'filename' => ['type' => 'string'],
                    'size' => ['type' => 'integer'],
                    'status' => ['type' => 'string'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time']
                ]
            ],
            'BackupCreateRequest' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string', 'maxLength' => 255],
                    'description' => ['type' => 'string', 'maxLength' => 500]
                ]
            ],
            'BackupResponse' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'message' => ['type' => 'string'],
                    'data' => ['$ref' => '#/components/schemas/Backup']
                ]
            ],
            'Notification' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                    'type' => ['type' => 'string', 'enum' => ['info', 'warning', 'error', 'success']],
                    'is_read' => ['type' => 'boolean'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time']
                ]
            ],
            'NotificationRequest' => [
                'type' => 'object',
                'required' => ['title', 'message', 'type'],
                'properties' => [
                    'title' => ['type' => 'string', 'maxLength' => 255],
                    'message' => ['type' => 'string', 'maxLength' => 1000],
                    'type' => ['type' => 'string', 'enum' => ['info', 'warning', 'error', 'success']],
                    'user_id' => ['type' => 'integer']
                ]
            ],
            'AppSetting' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'key' => ['type' => 'string'],
                    'value' => ['type' => 'string'],
                    'category' => ['type' => 'string'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time']
                ]
            ],
            'SettingsUpdateRequest' => [
                'type' => 'object',
                'required' => ['settings'],
                'properties' => [
                    'settings' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'required' => ['key', 'value'],
                            'properties' => [
                                'key' => ['type' => 'string'],
                                'value' => ['type' => 'string']
                            ]
                        ]
                    ]
                ]
            ],
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time']
                ]
            ],
            'UserCreateRequest' => [
                'type' => 'object',
                'required' => ['name', 'email', 'password'],
                'properties' => [
                    'name' => ['type' => 'string', 'maxLength' => 255],
                    'email' => ['type' => 'string', 'format' => 'email', 'maxLength' => 255],
                    'password' => ['type' => 'string', 'minLength' => 8],
                    'role' => ['type' => 'string']
                ]
            ],
            'UserResponse' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'message' => ['type' => 'string'],
                    'data' => ['$ref' => '#/components/schemas/User']
                ]
            ],
            'SuccessResponse' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'message' => ['type' => 'string']
                ]
            ],
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'message' => ['type' => 'string'],
                    'error_code' => ['type' => 'string']
                ]
            ],
            'ValidationError' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'message' => ['type' => 'string'],
                    'errors' => ['type' => 'object']
                ]
            ]
        ];
    }

    /**
     * Documentación simplificada para desarrolladores
     */
    public function simple(): JsonResponse
    {
        return response()->json([
            'title' => 'ModuStack ElyMar Luxury API',
            'version' => '1.0.0',
            'base_url' => url('/api'),
            'authentication' => [
                'type' => 'Bearer Token',
                'header' => 'Authorization: Bearer {token}',
                'alternative' => 'X-API-Key: {api_key}'
            ],
            'rate_limits' => [
                'default' => '100 requests per minute',
                'drivers_change' => '10 requests per minute',
                'backups_create' => '5 requests per 5 minutes',
                'notifications_send' => '20 requests per minute'
            ],
            'endpoints' => [
                'GET /info' => 'Información de la API',
                'GET /system/status' => 'Estado del sistema',
                'GET /drivers?action=status' => 'Estado de drivers',
                'POST /drivers' => 'Cambiar driver',
                'GET /backups' => 'Listar respaldos',
                'POST /backups' => 'Crear respaldo',
                'GET /notifications' => 'Listar notificaciones',
                'POST /notifications' => 'Enviar notificación',
                'GET /settings' => 'Obtener configuración',
                'POST /settings' => 'Actualizar configuración',
                'GET /users' => 'Listar usuarios',
                'POST /users' => 'Crear usuario'
            ],
            'examples' => [
                'change_driver' => [
                    'url' => '/api/drivers',
                    'method' => 'POST',
                    'headers' => [
                        'Authorization' => 'Bearer {token}',
                        'Content-Type' => 'application/json'
                    ],
                    'body' => [
                        'service' => 'cache',
                        'driver' => 'redis',
                        'config' => [
                            'host' => '127.0.0.1',
                            'port' => 6379
                        ]
                    ]
                ],
                'create_backup' => [
                    'url' => '/api/backups',
                    'method' => 'POST',
                    'headers' => [
                        'Authorization' => 'Bearer {token}',
                        'Content-Type' => 'application/json'
                    ],
                    'body' => [
                        'name' => 'Backup Manual',
                        'description' => 'Respaldo creado manualmente'
                    ]
                ]
            ]
        ]);
    }
}

