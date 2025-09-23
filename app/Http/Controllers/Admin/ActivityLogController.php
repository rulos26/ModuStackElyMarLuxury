<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    protected $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
        $this->middleware('auth');
    }

    /**
     * Mostrar lista de logs
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with(['causer', 'subject'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('log_name')) {
            $query->inLog($request->log_name);
        }

        if ($request->filled('level')) {
            $query->level($request->level);
        }

        if ($request->filled('event')) {
            $query->event($request->event);
        }

        if ($request->filled('ip_address')) {
            $query->fromIp($request->ip_address);
        }

        if ($request->filled('method')) {
            $query->httpMethod($request->method);
        }

        if ($request->filled('status_code')) {
            $query->statusCode($request->status_code);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('user_agent', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(50);
        $stats = $this->activityLogService->getStats();

        // Opciones para filtros
        $logNames = ActivityLog::distinct()->pluck('log_name')->filter();
        $levels = ActivityLog::distinct()->pluck('level')->filter();
        $events = ActivityLog::distinct()->pluck('event')->filter()->take(20);
        $methods = ActivityLog::distinct()->pluck('method')->filter();
        $statusCodes = ActivityLog::distinct()->pluck('status_code')->filter()->sort();

        return view('admin.activity-logs.index', compact(
            'logs',
            'stats',
            'logNames',
            'levels',
            'events',
            'methods',
            'statusCodes'
        ));
    }

    /**
     * Mostrar log específico
     */
    public function show(ActivityLog $activityLog)
    {
        $activityLog->load(['causer', 'subject']);

        return view('admin.activity-logs.show', compact('activityLog'));
    }

    /**
     * Eliminar log
     */
    public function destroy(ActivityLog $activityLog)
    {
        $activityLog->delete();

        return redirect()->route('admin.activity-logs.index')
            ->with('success', 'Log eliminado exitosamente');
    }

    /**
     * Eliminar logs antiguos
     */
    public function cleanOldLogs(Request $request)
    {
        $daysToKeep = $request->get('days', 30);

        $deletedCount = $this->activityLogService->cleanOldLogs($daysToKeep);

        return redirect()->route('admin.activity-logs.index')
            ->with('success', "{$deletedCount} logs antiguos eliminados (más de {$daysToKeep} días)");
    }

    /**
     * Obtener estadísticas
     */
    public function stats(Request $request): JsonResponse
    {
        $days = $request->get('days', 7);
        $logName = $request->get('log_name');

        $stats = $this->activityLogService->getStats($logName, $days);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Obtener logs recientes
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 20);
        $logName = $request->get('log_name');

        $logs = $this->activityLogService->getRecent($limit, $logName);

        return response()->json([
            'success' => true,
            'data' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'log_name' => $log->log_name,
                    'description' => $log->description,
                    'level' => $log->level,
                    'event' => $log->event,
                    'ip_address' => $log->ip_address,
                    'method' => $log->method,
                    'status_code' => $log->status_code,
                    'execution_time' => $log->formatted_execution_time,
                    'memory_usage' => $log->formatted_memory_usage,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'causer_name' => $log->causer?->name ?? 'Sistema',
                    'level_badge' => $log->level_badge,
                    'method_badge' => $log->method_badge,
                    'status_code_badge' => $log->status_code_badge
                ];
            })
        ]);
    }

    /**
     * Obtener logs por nivel
     */
    public function byLevel(Request $request): JsonResponse
    {
        $level = $request->get('level', 'error');
        $limit = $request->get('limit', 100);

        $logs = $this->activityLogService->getLogsByLevel($level, $limit);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Obtener logs por IP
     */
    public function byIp(Request $request): JsonResponse
    {
        $ip = $request->get('ip');
        $limit = $request->get('limit', 100);

        if (!$ip) {
            return response()->json([
                'success' => false,
                'message' => 'IP requerida'
            ], 400);
        }

        $logs = $this->activityLogService->getLogsByIp($ip, $limit);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Obtener logs lentos
     */
    public function slowLogs(Request $request): JsonResponse
    {
        $threshold = $request->get('threshold', 1000); // 1 segundo por defecto
        $limit = $request->get('limit', 100);

        $logs = $this->activityLogService->getSlowLogs($threshold, $limit);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Obtener logs de alto uso de memoria
     */
    public function highMemoryLogs(Request $request): JsonResponse
    {
        $threshold = $request->get('threshold', 134217728); // 128MB por defecto
        $limit = $request->get('limit', 100);

        $logs = $this->activityLogService->getHighMemoryLogs($threshold, $limit);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Exportar logs
     */
    public function export(Request $request)
    {
        $logName = $request->get('log_name');
        $days = $request->get('days', 7);

        $logs = $this->activityLogService->exportLogs($logName, $days);

        $filename = 'activity_logs_' . now()->format('Y-m-d_H-i-s') . '.json';

        return response()->json($logs)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Buscar logs
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $logName = $request->get('log_name');
        $limit = $request->get('limit', 100);

        if (!$search) {
            return response()->json([
                'success' => false,
                'message' => 'Término de búsqueda requerido'
            ], 400);
        }

        $logs = $this->activityLogService->searchLogs($search, $logName, $limit);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Obtener estadísticas en tiempo real
     */
    public function realTimeStats(): JsonResponse
    {
        $stats = $this->activityLogService->getStats(null, 1); // Último día

        // Agregar estadísticas adicionales
        $stats['recent_errors'] = $this->activityLogService->getLogsByLevel('error', 10)->count();
        $stats['recent_warnings'] = $this->activityLogService->getLogsByLevel('warning', 10)->count();
        $stats['slow_requests'] = $this->activityLogService->getSlowLogs(2000, 10)->count(); // Más de 2 segundos

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Obtener top IPs
     */
    public function topIps(Request $request): JsonResponse
    {
        $days = $request->get('days', 7);
        $limit = $request->get('limit', 20);

        $startDate = now()->subDays($days);
        $endDate = now();

        $topIps = ActivityLog::betweenDates($startDate, $endDate)
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->selectRaw('ip_address, count(*) as count, max(created_at) as last_seen')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $topIps
        ]);
    }
}
