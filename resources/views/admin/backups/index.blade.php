@extends('adminlte::page')

@section('title', 'Gestión de Backups')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-database"></i> Gestión de Backups</h1>
        <div>
            <a href="{{ route('admin.backups.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Backup
            </a>
            <a href="{{ route('admin.backups.stats') }}" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> Estadísticas
            </a>
        </div>
    </div>
@stop

@section('content')
    <!-- Estadísticas rápidas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['total'] }}</h3>
                    <p>Total Backups</p>
                </div>
                <div class="icon">
                    <i class="fas fa-database"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['completed'] }}</h3>
                    <p>Completados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $stats['failed'] }}</h3>
                    <p>Fallidos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-times"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['formatted_total_size'] }}</h3>
                    <p>Espacio Usado</p>
                </div>
                <div class="icon">
                    <i class="fas fa-hdd"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filtros</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.backups.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tipo</label>
                            <select name="type" class="form-control">
                                <option value="">Todos</option>
                                <option value="full" {{ request('type') == 'full' ? 'selected' : '' }}>Completo</option>
                                <option value="database" {{ request('type') == 'database' ? 'selected' : '' }}>Base de Datos</option>
                                <option value="files" {{ request('type') == 'files' ? 'selected' : '' }}>Archivos</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Estado</label>
                            <select name="status" class="form-control">
                                <option value="">Todos</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>En Progreso</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completado</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Fallido</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Buscar</label>
                            <input type="text" name="search" class="form-control"
                                   value="{{ request('search') }}"
                                   placeholder="Nombre o descripción...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                                <a href="{{ route('admin.backups.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de backups -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Lista de Backups</h3>
        </div>
        <div class="card-body">
            @if($backups->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Tamaño</th>
                                <th>Creado</th>
                                <th>Expira</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backups as $backup)
                                <tr>
                                    <td>{{ $backup->id }}</td>
                                    <td>
                                        <strong>{{ $backup->name }}</strong>
                                        @if($backup->description)
                                            <br><small class="text-muted">{{ Str::limit($backup->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $backup->type_badge }}">
                                            {{ ucfirst($backup->type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $backup->status_badge }}">
                                            {{ ucfirst(str_replace('_', ' ', $backup->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $backup->formatted_file_size }}</td>
                                    <td>
                                        {{ $backup->created_at->format('Y-m-d H:i') }}
                                        @if($backup->creator)
                                            <br><small class="text-muted">por {{ $backup->creator->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($backup->expires_at)
                                            @if($backup->isExpired())
                                                <span class="text-danger">{{ $backup->expires_at->format('Y-m-d') }}</span>
                                            @else
                                                {{ $backup->expires_at->format('Y-m-d') }}
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.backups.show', $backup) }}"
                                               class="btn btn-sm btn-info" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            @if($backup->status === 'completed')
                                                <a href="{{ route('admin.backups.download', $backup) }}"
                                                   class="btn btn-sm btn-success" title="Descargar">
                                                    <i class="fas fa-download"></i>
                                                </a>

                                                @if(!$backup->isExpired())
                                                    <form method="POST" action="{{ route('admin.backups.restore', $backup) }}"
                                                          style="display: inline;"
                                                          onsubmit="return confirm('¿Estás seguro de restaurar este backup?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-warning" title="Restaurar">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif

                                            <form method="POST" action="{{ route('admin.backups.destroy', $backup) }}"
                                                  style="display: inline;"
                                                  onsubmit="return confirm('¿Estás seguro de eliminar este backup?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="d-flex justify-content-center">
                    {{ $backups->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-database fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay backups disponibles</h5>
                    <p class="text-muted">Crea tu primer backup para comenzar</p>
                    <a href="{{ route('admin.backups.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crear Backup
                    </a>
                </div>
            @endif
        </div>
    </div>
@stop

@section('js')
    <script>
        // Auto-refresh para backups en progreso
        @if($backups->where('status', 'in_progress')->count() > 0)
            setTimeout(function() {
                location.reload();
            }, 5000);
        @endif
    </script>
@stop
