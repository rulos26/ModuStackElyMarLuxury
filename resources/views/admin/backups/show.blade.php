@extends('adminlte::page')

@section('title', 'Detalles del Backup')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-database"></i> Detalles del Backup</h1>
        <div>
            <a href="{{ route('admin.backups.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <!-- Información principal -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $backup->name }}</h3>
                    <div class="card-tools">
                        <span class="badge {{ $backup->type_badge }}">{{ ucfirst($backup->type) }}</span>
                        <span class="badge {{ $backup->status_badge }}">{{ ucfirst(str_replace('_', ' ', $backup->status)) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>ID:</strong></td>
                                    <td>{{ $backup->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Archivo:</strong></td>
                                    <td>{{ $backup->file_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tamaño:</strong></td>
                                    <td>{{ $backup->formatted_file_size }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Creado:</strong></td>
                                    <td>{{ $backup->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                @if($backup->creator)
                                <tr>
                                    <td><strong>Creado por:</strong></td>
                                    <td>{{ $backup->creator->name }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        <span class="badge {{ $backup->status_badge }}">
                                            {{ ucfirst(str_replace('_', ' ', $backup->status)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Iniciado:</strong></td>
                                    <td>{{ $backup->started_at ? $backup->started_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Completado:</strong></td>
                                    <td>{{ $backup->completed_at ? $backup->completed_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Duración:</strong></td>
                                    <td>{{ $backup->formatted_execution_time }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Expira:</strong></td>
                                    <td>
                                        @if($backup->expires_at)
                                            @if($backup->isExpired())
                                                <span class="text-danger">{{ $backup->expires_at->format('Y-m-d H:i:s') }} (Expirado)</span>
                                            @else
                                                {{ $backup->expires_at->format('Y-m-d H:i:s') }}
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($backup->description)
                        <div class="mt-3">
                            <h6><strong>Descripción:</strong></h6>
                            <p class="text-muted">{{ $backup->description }}</p>
                        </div>
                    @endif

                    @if($backup->error_message)
                        <div class="mt-3">
                            <h6><strong>Error:</strong></h6>
                            <div class="alert alert-danger">
                                {{ $backup->error_message }}
                            </div>
                        </div>
                    @endif

                    <!-- Hash de verificación -->
                    @if($backup->file_hash)
                        <div class="mt-3">
                            <h6><strong>Hash SHA256:</strong></h6>
                            <code class="text-muted">{{ $backup->file_hash }}</code>
                            @if($isValid !== null)
                                @if($isValid)
                                    <span class="badge badge-success ml-2">
                                        <i class="fas fa-check"></i> Íntegro
                                    </span>
                                @else
                                    <span class="badge badge-danger ml-2">
                                        <i class="fas fa-times"></i> Corrupto
                                    </span>
                                @endif
                            @endif
                        </div>
                    @endif

                    <!-- Opciones del backup -->
                    @if($backup->options)
                        <div class="mt-3">
                            <h6><strong>Opciones:</strong></h6>
                            <ul class="list-unstyled">
                                @if($backup->is_compressed)
                                    <li><i class="fas fa-check text-success"></i> Comprimido</li>
                                @endif
                                @if($backup->is_encrypted)
                                    <li><i class="fas fa-lock text-warning"></i> Encriptado</li>
                                @endif
                                <li><i class="fas fa-calendar text-info"></i> Retención: {{ $backup->retention_days }} días</li>
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Metadatos -->
            @if($backup->metadata)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Metadatos</h3>
                    </div>
                    <div class="card-body">
                        <pre class="bg-light p-3 rounded"><code>{{ json_encode($backup->metadata, JSON_PRETTY_PRINT) }}</code></pre>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <!-- Acciones -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Acciones</h3>
                </div>
                <div class="card-body">
                    @if($backup->status === 'completed')
                        <div class="d-grid gap-2">
                            <a href="{{ route('admin.backups.download', $backup) }}" class="btn btn-success">
                                <i class="fas fa-download"></i> Descargar Backup
                            </a>

                            @if(!$backup->isExpired())
                                <form method="POST" action="{{ route('admin.backups.restore', $backup) }}"
                                      onsubmit="return confirm('¿Estás seguro de restaurar este backup? Esta acción puede sobrescribir datos existentes.')">
                                    @csrf
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="fas fa-undo"></i> Restaurar Backup
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('admin.backups.verify', $backup) }}">
                                @csrf
                                <button type="submit" class="btn btn-info w-100">
                                    <i class="fas fa-check-circle"></i> Verificar Integridad
                                </button>
                            </form>
                        </div>
                    @elseif($backup->status === 'in_progress')
                        <div class="alert alert-warning">
                            <i class="fas fa-clock"></i> El backup está en progreso...
                        </div>
                    @elseif($backup->status === 'failed')
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> El backup falló
                        </div>
                    @elseif($backup->status === 'pending')
                        <div class="alert alert-info">
                            <i class="fas fa-hourglass-half"></i> El backup está pendiente
                        </div>
                    @endif

                    <!-- Eliminar backup -->
                    <hr>
                    <form method="POST" action="{{ route('admin.backups.destroy', $backup) }}"
                          onsubmit="return confirm('¿Estás seguro de eliminar este backup? Esta acción no se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-trash"></i> Eliminar Backup
                        </button>
                    </form>
                </div>
            </div>

            <!-- Información del archivo -->
            @if($backup->status === 'completed')
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Información del Archivo</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Existe:</strong></td>
                                <td>
                                    @if($backup->fileExists())
                                        <span class="badge badge-success">Sí</span>
                                    @else
                                        <span class="badge badge-danger">No</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Tipo de almacenamiento:</strong></td>
                                <td>{{ ucfirst($backup->storage_type) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Ruta:</strong></td>
                                <td><code class="small">{{ $backup->file_path }}</code></td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Historial -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Historial</h3>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <li>
                            <i class="fas fa-plus bg-primary"></i>
                            <div class="timeline-item">
                                <span class="time">{{ $backup->created_at->format('H:i') }}</span>
                                <h3 class="timeline-header">Backup creado</h3>
                            </div>
                        </li>

                        @if($backup->started_at)
                        <li>
                            <i class="fas fa-play bg-warning"></i>
                            <div class="timeline-item">
                                <span class="time">{{ $backup->started_at->format('H:i') }}</span>
                                <h3 class="timeline-header">Proceso iniciado</h3>
                            </div>
                        </li>
                        @endif

                        @if($backup->completed_at)
                        <li>
                            <i class="fas fa-check bg-success"></i>
                            <div class="timeline-item">
                                <span class="time">{{ $backup->completed_at->format('H:i') }}</span>
                                <h3 class="timeline-header">
                                    @if($backup->status === 'completed')
                                        Proceso completado
                                    @else
                                        Proceso finalizado
                                    @endif
                                </h3>
                            </div>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }

        .timeline > li {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline > li > i {
            position: absolute;
            left: -22px;
            top: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            color: white;
            font-size: 16px;
        }

        .timeline-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }

        .timeline-item .time {
            color: #999;
            font-size: 12px;
        }

        .timeline-item .timeline-header {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
        }
    </style>
@stop





