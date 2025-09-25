@extends('adminlte::page')

@section('title', 'Configuraciones SMTP')

@section('content_header')
    <h1 class="m-0 text-dark">
        <i class="fas fa-server"></i> Configuraciones SMTP
        <small class="text-muted">Gestión de configuraciones de email</small>
    </h1>
@stop

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> Lista de Configuraciones
                </h3>
                <div class="card-tools">
                    <div class="btn-group">
                        <a href="{{ route('admin.smtp-configs.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Nueva Configuración
                        </a>
                        <button type="button" class="btn btn-info btn-sm" onclick="migrateFromEnv()">
                            <i class="fas fa-download"></i> Migrar desde .env
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="showStatistics()">
                            <i class="fas fa-chart-bar"></i> Estadísticas
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if($configs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Mailer</th>
                                    <th>Host</th>
                                    <th>Remitente</th>
                                    <th>Estado</th>
                                    <th>Creado</th>
                                    <th width="200">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($configs as $config)
                                    <tr>
                                        <td>
                                            <strong>{{ $config->name }}</strong>
                                            @if($config->description)
                                                <br><small class="text-muted">{{ $config->description }}</small>
                                            @endif
                                        </td>
                                        <td>{!! $config->mailer_badge !!}</td>
                                        <td>
                                            @if($config->host)
                                                <code>{{ $config->host }}:{{ $config->port }}</code>
                                                @if($config->encryption)
                                                    <br><small class="text-info">{{ strtoupper($config->encryption) }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $config->from_name }}</strong><br>
                                            <small class="text-muted">{{ $config->from_address }}</small>
                                        </td>
                                        <td>{!! $config->status_badge !!}</td>
                                        <td>
                                            {{ $config->created_at->format('d/m/Y') }}
                                            @if($config->creator)
                                                <br><small class="text-muted">por {{ $config->creator->name }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.smtp-configs.show', $config) }}"
                                                   class="btn btn-info" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.smtp-configs.edit', $config) }}"
                                                   class="btn btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-success"
                                                        onclick="testConfig({{ $config->id }})" title="Probar">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                @if(!$config->is_default)
                                                    <button type="button" class="btn btn-primary"
                                                            onclick="setDefault({{ $config->id }})" title="Establecer por defecto">
                                                        <i class="fas fa-star"></i>
                                                    </button>
                                                @endif
                                                <button type="button" class="btn btn-secondary"
                                                        onclick="toggleActive({{ $config->id }})" title="Activar/Desactivar">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                                @if(!$config->is_default)
                                                    <button type="button" class="btn btn-danger"
                                                            onclick="deleteConfig({{ $config->id }})" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $configs->links() }}
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i>
                        No hay configuraciones SMTP creadas.
                        <a href="{{ route('admin.smtp-configs.create') }}" class="btn btn-primary btn-sm ml-2">
                            <i class="fas fa-plus"></i> Crear Primera Configuración
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal de Estadísticas -->
<div class="modal fade" id="statisticsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-bar"></i> Estadísticas del Sistema SMTP
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="statisticsContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Cargando estadísticas...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Prueba -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-play"></i> Probar Configuración SMTP
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="testContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Probando configuración...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
function showStatistics() {
    $('#statisticsModal').modal('show');

    $.get('{{ route("admin.smtp-configs.statistics") }}')
        .done(function(data) {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-server"></i> Configuraciones</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                Total: <span class="badge badge-primary">${data.smtp_configs.total_configs}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Activas: <span class="badge badge-success">${data.smtp_configs.active_configs}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Inactivas: <span class="badge badge-secondary">${data.smtp_configs.inactive_configs}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Por Defecto: <span class="badge badge-warning">${data.smtp_configs.default_config || 'Ninguna'}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle"></i> Sistema</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                Configuración Actual: <span class="badge badge-info">${data.current_config || 'Ninguna'}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                Cache: <span class="badge badge-${data.cache_status === 'Cached' ? 'success' : 'warning'}">${data.cache_status}</span>
                            </li>
                            <li class="list-group-item">
                                <strong>Tipos de Mailer:</strong><br>
                                ${data.smtp_configs.mailer_types.map(type => `<span class="badge badge-secondary mr-1">${type.toUpperCase()}</span>`).join('')}
                            </li>
                        </ul>
                    </div>
                </div>
            `;

            $('#statisticsContent').html(html);
        })
        .fail(function() {
            $('#statisticsContent').html('<div class="alert alert-danger">Error cargando estadísticas</div>');
        });
}

function testConfig(configId) {
    $('#testModal').modal('show');

    $.post(`/admin/smtp-configs/${configId}/test`)
        .done(function(data) {
            let html = '';

            if (data.success) {
                html = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <strong>¡Configuración válida!</strong>
                        <p class="mb-0">La configuración SMTP se conectó correctamente.</p>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Detalles de Conexión:</strong>
                            <ul class="list-unstyled mt-2">
                                <li><strong>Host:</strong> ${data.details.host}</li>
                                <li><strong>Puerto:</strong> ${data.details.port}</li>
                                <li><strong>Encriptación:</strong> ${data.details.encryption || 'Ninguna'}</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <strong>Información de Remitente:</strong>
                            <ul class="list-unstyled mt-2">
                                <li><strong>Email:</strong> ${data.details.from_address}</li>
                                <li><strong>Nombre:</strong> ${data.details.from_name}</li>
                            </ul>
                        </div>
                    </div>
                `;

                if (data.test_email_sent) {
                    html += `
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-envelope"></i> Email de prueba enviado exitosamente.
                        </div>
                    `;
                }
            } else {
                html = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <strong>Error en la configuración</strong>
                        <p class="mb-0">${data.error}</p>
                    </div>
                `;
            }

            $('#testContent').html(html);
        })
        .fail(function() {
            $('#testContent').html('<div class="alert alert-danger">Error probando configuración</div>');
        });
}

function setDefault(configId) {
    if (confirm('¿Establecer esta configuración como por defecto?')) {
        $.post(`/admin/smtp-configs/${configId}/set-default`)
            .done(function() {
                location.reload();
            })
            .fail(function() {
                alert('Error estableciendo configuración por defecto');
            });
    }
}

function toggleActive(configId) {
    $.post(`/admin/smtp-configs/${configId}/toggle-active`)
        .done(function() {
            location.reload();
        })
        .fail(function() {
            alert('Error cambiando estado de configuración');
        });
}

function deleteConfig(configId) {
    if (confirm('¿Eliminar esta configuración SMTP? Esta acción no se puede deshacer.')) {
        $.ajax({
            url: `/admin/smtp-configs/${configId}`,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done(function() {
            location.reload();
        })
        .fail(function() {
            alert('Error eliminando configuración');
        });
    }
}

function migrateFromEnv() {
    if (confirm('¿Migrar configuración SMTP desde archivo .env?')) {
        $.post('{{ route("admin.smtp-configs.migrate-env") }}')
            .done(function() {
                location.reload();
            })
            .fail(function() {
                alert('Error migrando configuración desde .env');
            });
    }
}
</script>
@stop



