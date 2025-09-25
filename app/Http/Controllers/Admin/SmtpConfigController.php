<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmtpConfig;
use App\Services\SmtpConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SmtpConfigController extends Controller
{
    protected $smtpConfigService;

    public function __construct(SmtpConfigService $smtpConfigService)
    {
        $this->smtpConfigService = $smtpConfigService;
    }

    /**
     * Mostrar lista de configuraciones SMTP
     */
    public function index()
    {
        $configs = SmtpConfig::with('creator')->latest()->paginate(10);

        return view('admin.smtp-configs.index', compact('configs'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $predefinedConfigs = SmtpConfig::getPredefinedConfigs();

        return view('admin.smtp-configs.create', compact('predefinedConfigs'));
    }

    /**
     * Crear nueva configuración SMTP
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:smtp_configs,name',
            'mailer' => 'required|in:smtp,sendmail,mailgun,ses,postmark,resend',
            'host' => 'required_if:mailer,smtp|nullable|string|max:255',
            'port' => 'required_if:mailer,smtp|nullable|integer|min:1|max:65535',
            'encryption' => 'nullable|in:tls,ssl',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string',
            'timeout' => 'nullable|integer|min:1|max:300',
            'local_domain' => 'nullable|string|max:255',
            'from_address' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $config = $this->smtpConfigService->createFromForm($request->all(), Auth::id());

            // Si se marca como por defecto
            if ($request->boolean('is_default')) {
                $this->smtpConfigService->setAsDefault($config);
            }

            return redirect()->route('admin.smtp-configs.index')
                ->with('success', 'Configuración SMTP creada exitosamente.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creando configuración SMTP: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Crear configuración predefinida
     */
    public function storePredefined(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:gmail,outlook,yahoo,mailtrap,sendmail',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'from_address' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $credentials = [
                'username' => $request->username,
                'password' => $request->password,
                'from_address' => $request->from_address,
                'from_name' => $request->from_name
            ];

            $config = $this->smtpConfigService->createPredefinedConfiguration(
                $request->type,
                $credentials,
                Auth::id()
            );

            // Si se marca como por defecto
            if ($request->boolean('is_default')) {
                $this->smtpConfigService->setAsDefault($config);
            }

            return redirect()->route('admin.smtp-configs.index')
                ->with('success', 'Configuración SMTP predefinida creada exitosamente.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creando configuración SMTP predefinida: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar configuración específica
     */
    public function show(SmtpConfig $smtpConfig)
    {
        return view('admin.smtp-configs.show', compact('smtpConfig'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(SmtpConfig $smtpConfig)
    {
        return view('admin.smtp-configs.edit', compact('smtpConfig'));
    }

    /**
     * Actualizar configuración SMTP
     */
    public function update(Request $request, SmtpConfig $smtpConfig)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:smtp_configs,name,' . $smtpConfig->id,
            'mailer' => 'required|in:smtp,sendmail,mailgun,ses,postmark,resend',
            'host' => 'required_if:mailer,smtp|nullable|string|max:255',
            'port' => 'required_if:mailer,smtp|nullable|integer|min:1|max:65535',
            'encryption' => 'nullable|in:tls,ssl',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string',
            'timeout' => 'nullable|integer|min:1|max:300',
            'local_domain' => 'nullable|string|max:255',
            'from_address' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $success = $this->smtpConfigService->updateConfiguration($smtpConfig, $request->all());

            if ($success) {
                return redirect()->route('admin.smtp-configs.index')
                    ->with('success', 'Configuración SMTP actualizada exitosamente.');
            } else {
                return redirect()->back()
                    ->with('error', 'Error actualizando configuración SMTP.')
                    ->withInput();
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error actualizando configuración SMTP: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Eliminar configuración SMTP
     */
    public function destroy(SmtpConfig $smtpConfig)
    {
        try {
            $success = $this->smtpConfigService->deleteConfiguration($smtpConfig);

            if ($success) {
                return redirect()->route('admin.smtp-configs.index')
                    ->with('success', 'Configuración SMTP eliminada exitosamente.');
            } else {
                return redirect()->back()
                    ->with('error', 'Error eliminando configuración SMTP.');
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error eliminando configuración SMTP: ' . $e->getMessage());
        }
    }

    /**
     * Establecer como configuración por defecto
     */
    public function setDefault(SmtpConfig $smtpConfig)
    {
        try {
            $success = $this->smtpConfigService->setAsDefault($smtpConfig);

            if ($success) {
                return redirect()->route('admin.smtp-configs.index')
                    ->with('success', 'Configuración SMTP establecida como por defecto.');
            } else {
                return redirect()->back()
                    ->with('error', 'Error estableciendo configuración por defecto.');
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error estableciendo configuración por defecto: ' . $e->getMessage());
        }
    }

    /**
     * Activar/desactivar configuración
     */
    public function toggleActive(SmtpConfig $smtpConfig)
    {
        try {
            $success = $this->smtpConfigService->toggleActive($smtpConfig);

            if ($success) {
                $status = $smtpConfig->fresh()->is_active ? 'activada' : 'desactivada';
                return redirect()->route('admin.smtp-configs.index')
                    ->with('success', "Configuración SMTP {$status} exitosamente.");
            } else {
                return redirect()->back()
                    ->with('error', 'Error cambiando estado de configuración SMTP.');
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error cambiando estado de configuración: ' . $e->getMessage());
        }
    }

    /**
     * Probar configuración SMTP
     */
    public function test(SmtpConfig $smtpConfig)
    {
        try {
            $result = $this->smtpConfigService->testConfiguration($smtpConfig);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error probando configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Migrar configuración desde .env
     */
    public function migrateFromEnv()
    {
        try {
            $config = $this->smtpConfigService->migrateFromEnv();

            if ($config) {
                return redirect()->route('admin.smtp-configs.index')
                    ->with('success', 'Configuración SMTP migrada desde .env exitosamente.');
            } else {
                return redirect()->route('admin.smtp-configs.index')
                    ->with('warning', 'No se encontró configuración válida en .env para migrar.');
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error migrando configuración desde .env: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas del sistema
     */
    public function statistics()
    {
        $stats = $this->smtpConfigService->getSystemStatistics();

        return response()->json($stats);
    }

    /**
     * Obtener configuraciones disponibles (API)
     */
    public function available()
    {
        $configs = $this->smtpConfigService->getAvailableConfigurations();

        return response()->json($configs);
    }

    /**
     * Validar configuración
     */
    public function validate(SmtpConfig $smtpConfig)
    {
        try {
            $validation = $this->smtpConfigService->validateConfiguration($smtpConfig);

            return response()->json($validation);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'errors' => ['Error validando configuración: ' . $e->getMessage()],
                'warnings' => []
            ], 500);
        }
    }
}



