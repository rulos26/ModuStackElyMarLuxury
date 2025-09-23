@extends('vendor.adminlte.page')

@section('title', 'Notificaciones')

@section('content_header')
    <h1>📢 Notificaciones</h1>
    <p>Gestionar notificaciones del sistema</p>
@stop

@section('content')
<div class="row">
    <!-- Estadísticas -->
    <div class="col-md-12">
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3 id="total-notifications">{{ $stats['total'] ?? 0 }}</h3>
                        <p>Total</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-bell"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3 id="unread-notifications">{{ $stats['unread'] ?? 0 }}</h3>
                        <p>No Leídas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3 id="read-notifications">{{ $stats['read'] ?? 0 }}</h3>
                        <p>Leídas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3 id="expired-notifications">{{ $stats['expired'] ?? 0 }}</h3>
                        <p>Expiradas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🔍 Filtros</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.notifications.index') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tipo</label>
                                <select name="type" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>Info</option>
                                    <option value="success" {{ request('type') == 'success' ? 'selected' : '' }}>Éxito</option>
                                    <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Advertencia</option>
                                    <option value="error" {{ request('type') == 'error' ? 'selected' : '' }}>Error</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Usuario</label>
                                <select name="user_id" class="form-control">
                                    <option value="">Todos</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Estado</label>
                                <div class="form-check">
                                    <input type="checkbox" name="unread_only" value="1" class="form-check-input" {{ request('unread_only') ? 'checked' : '' }}>
                                    <label class="form-check-label">Solo no leídas</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="expired_only" value="1" class="form-check-input" {{ request('expired_only') ? 'checked' : '' }}>
                                    <label class="form-check-label">Solo expiradas</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Limpiar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Acciones -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">⚡ Acciones Rápidas</h3>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.notifications.create') }}" class="btn btn-success">
                    <i class="fas fa-plus"></i> Nueva Notificación
                </a>
                <button type="button" class="btn btn-warning" onclick="deleteExpired()">
                    <i class="fas fa-trash"></i> Eliminar Expiradas
                </button>
                <button type="button" class="btn btn-info" onclick="showStats()">
                    <i class="fas fa-chart-bar"></i> Ver Estadísticas
                </button>
            </div>
        </div>
    </div>

    <!-- Lista de Notificaciones -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📋 Lista de Notificaciones</h3>
            </div>
            <div class="card-body">
                @if($notifications->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Título</th>
                                    <th>Usuario</th>
                                    <th>Estado</th>
                                    <th>Creada</th>
                                    <th>Expira</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($notifications as $notification)
                                    <tr class="{{ $notification->is_read ? '' : 'table-warning' }}">
                                        <td>{{ $notification->id }}</td>
                                        <td>
                                            <span class="badge badge-{{ $notification->type_badge }}">
                                                <i class="{{ $notification->default_icon }}"></i>
                                                {{ ucfirst($notification->type) }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>{{ $notification->title }}</strong>
                                            @if($notification->icon)
                                                <i class="{{ $notification->icon }}"></i>
                                            @endif
                                        </td>
                                        <td>
                                            @if($notification->user)
                                                <a href="{{ route('admin.users.show', $notification->user->id) }}">
                                                    {{ $notification->user->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">Global</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($notification->is_read)
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Leída
                                                </span>
                                            @else
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-exclamation"></i> No Leída
                                                </span>
                                            @endif
                                            @if($notification->is_push_sent)
                                                <span class="badge badge-info">
                                                    <i class="fas fa-paper-plane"></i> Push
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $notification->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @if($notification->expires_at)
                                                {{ $notification->expires_at->format('d/m/Y H:i') }}
                                                @if($notification->isExpired())
                                                    <span class="badge badge-danger">Expirada</span>
                                                @endif
                                            @else
                                                <span class="text-muted">Sin expiración</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.notifications.show', $notification->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if(!$notification->is_read)
                                                <button type="button" class="btn btn-sm btn-success" onclick="markAsRead({{ $notification->id }})">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteNotification({{ $notification->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{ $notifications->appends(request()->query())->links() }}
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        No hay notificaciones que coincidan con los filtros aplicados.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal de Estadísticas -->
<div class="modal fade" id="statsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📊 Estadísticas de Notificaciones</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="stats-content">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
function markAsRead(notificationId) {
    fetch(`/admin/notifications/${notificationId}/mark-read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function deleteNotification(notificationId) {
    if (confirm('¿Estás seguro de que quieres eliminar esta notificación?')) {
        fetch(`/admin/notifications/${notificationId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function deleteExpired() {
    if (confirm('¿Estás seguro de que quieres eliminar todas las notificaciones expiradas?')) {
        window.location.href = '{{ route("admin.notifications.delete-expired") }}';
    }
}

function showStats() {
    $('#statsModal').modal('show');

    fetch('/admin/notifications/stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>📊 Estadísticas Generales</h6>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Total</span>
                                    <span class="badge badge-primary">${data.data.total}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>No Leídas</span>
                                    <span class="badge badge-warning">${data.data.unread}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Leídas</span>
                                    <span class="badge badge-success">${data.data.read}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Pendientes Push</span>
                                    <span class="badge badge-info">${data.data.pending_push}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Expiradas</span>
                                    <span class="badge badge-danger">${data.data.expired}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>📈 Por Tipo</h6>
                            <ul class="list-group">
                `;

                for (let type in data.data.by_type) {
                    const count = data.data.by_type[type];
                    const badgeClass = type === 'error' ? 'danger' :
                                      type === 'warning' ? 'warning' :
                                      type === 'success' ? 'success' : 'info';
                    html += `
                        <li class="list-group-item d-flex justify-content-between">
                            <span>${type.charAt(0).toUpperCase() + type.slice(1)}</span>
                            <span class="badge badge-${badgeClass}">${count}</span>
                        </li>
                    `;
                }

                html += `
                            </ul>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h6>📅 Actividad Reciente</h6>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Últimas 24 horas</span>
                                    <span class="badge badge-primary">${data.data.last_24h || 0}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Última semana</span>
                                    <span class="badge badge-primary">${data.data.last_week || 0}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Último mes</span>
                                    <span class="badge badge-primary">${data.data.last_month || 0}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                `;

                document.getElementById('stats-content').innerHTML = html;
            }
        });
}
</script>
@stop
