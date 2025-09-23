<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Mostrar lista de backups
     */
    public function index(Request $request)
    {
        $query = Backup::with('creator')
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('status')) {
            $query->withStatus($request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $backups = $query->paginate(15);
        $stats = $this->backupService->getStats();

        return view('admin.backups.index', compact('backups', 'stats'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        return view('admin.backups.create');
    }

    /**
     * Crear nuevo backup
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:full,database,files',
            'description' => 'nullable|string|max:1000',
            'compress' => 'boolean',
            'encrypt' => 'boolean',
            'retention_days' => 'integer|min:1|max:365'
        ]);

        try {
            $options = [
                'compress' => $request->boolean('compress'),
                'encrypt' => $request->boolean('encrypt'),
                'retention_days' => $request->integer('retention_days', 30)
            ];

            switch ($request->type) {
                case 'full':
                    $backup = $this->backupService->createFullBackup($request->name, $options);
                    break;
                case 'database':
                    $backup = $this->backupService->createDatabaseBackup($request->name, $options);
                    break;
                case 'files':
                    $backup = $this->backupService->createFilesBackup($request->name, $options);
                    break;
            }

            return redirect()
                ->route('admin.backups.show', $backup)
                ->with('success', 'Backup creado exitosamente');

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Error creando backup: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalles del backup
     */
    public function show(Backup $backup)
    {
        $backup->load('creator');

        // Verificar integridad del archivo
        $isValid = $this->backupService->verifyBackup($backup);

        return view('admin.backups.show', compact('backup', 'isValid'));
    }

    /**
     * Descargar backup
     */
    public function download(Backup $backup)
    {
        if ($backup->status !== 'completed') {
            return back()->with('error', 'El backup no está completado');
        }

        if (!$backup->fileExists()) {
            return back()->with('error', 'El archivo de backup no existe');
        }

        return Storage::disk($backup->storage_type)->download($backup->file_path, $backup->file_name);
    }

    /**
     * Restaurar backup
     */
    public function restore(Backup $backup)
    {
        if ($backup->status !== 'completed') {
            return back()->with('error', 'El backup no está completado');
        }

        if ($backup->isExpired()) {
            return back()->with('error', 'El backup ha expirado');
        }

        try {
            $success = $this->backupService->restoreBackup($backup);

            if ($success) {
                return back()->with('success', 'Backup restaurado exitosamente');
            } else {
                return back()->with('error', 'Error restaurando el backup');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error restaurando backup: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar backup
     */
    public function destroy(Backup $backup)
    {
        try {
            // Eliminar archivo físico si existe
            if ($backup->fileExists()) {
                Storage::disk($backup->storage_type)->delete($backup->file_path);
            }

            // Eliminar registro de la base de datos
            $backup->delete();

            return redirect()
                ->route('admin.backups.index')
                ->with('success', 'Backup eliminado exitosamente');

        } catch (\Exception $e) {
            return back()->with('error', 'Error eliminando backup: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar estadísticas
     */
    public function stats(Request $request)
    {
        $type = $request->get('type');
        $days = $request->get('days', 30);

        $stats = $this->backupService->getStats($type, $days);
        $recentBackups = Backup::getRecent(10);

        return view('admin.backups.stats', compact('stats', 'recentBackups'));
    }

    /**
     * Limpiar backups expirados
     */
    public function cleanExpired()
    {
        try {
            $deletedCount = $this->backupService->cleanExpiredBackups();

            return back()->with('success', "Se eliminaron {$deletedCount} backups expirados");

        } catch (\Exception $e) {
            return back()->with('error', 'Error limpiando backups: ' . $e->getMessage());
        }
    }

    /**
     * Verificar integridad de backup
     */
    public function verify(Backup $backup)
    {
        try {
            $isValid = $this->backupService->verifyBackup($backup);

            if ($isValid) {
                return back()->with('success', 'El backup está íntegro');
            } else {
                return back()->with('error', 'El backup está corrupto o no existe');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error verificando backup: ' . $e->getMessage());
        }
    }
}
