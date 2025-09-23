@extends('vendor.adminlte.page')

@section('title', 'Ver Notificación')

@section('content_header')
    <h1>📢 Ver Notificación</h1>
    <p>Detalles de la notificación #{{ $notification->id }}</p>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📝 Detalles de la Notificación</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Título:</strong>
                        <p class="text-muted">{{ $notification->title }}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Tipo:</strong>
                        <p>
                            <span class="badge badge-{{ $notification->type_badge }}">
                                <i class="{{ $notification->default_icon }}"></i>
                                {{ ucfirst($notification->type) }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <strong>Mensaje:</strong>
                        <p class="text-muted">{{ $notification->message }}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Icono:</strong>
                        <p>
                            @if($notification->icon)
                                <i class="{{ $notification->icon }} fa-2x"></i>
                                <code>{{ $notification->icon }}</code>
                            @else
                                <span class="text-muted">Sin icono</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($notification->url || $notification->action_text)
                    <div class="row">
                        <div class="col-md-6">
                            <strong>URL de Acción:</strong>
                            <p>
                                @if($notification->url)
                                    <a href="{{ $notification->url }}" target="_blank">{{ $notification->url }}</a>
                                @else
                                    <span class="text-muted">Sin URL</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <strong>Texto del Botón:</strong>
                            <p class="text-muted">{{ $notification->action_text ?: 'Sin texto' }}</p>
                        </div>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <strong>Estado:</strong>
                        <p>
                            @if($notification->is_read)
                                <span class="badge badge-success">
                                    <i class="fas fa-check"></i> Leída
                                </span>
                                @if($notification->read_at)
                                    <br><small class="text-muted">{{ $notification->read_at->format('d/m/Y H:i:s') }}</small>
                                @endif
                            @else
                                <span class="badge badge-warning">
                                    <i class="fas fa-exclamation"></i> No Leída
                                </span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <strong>Push Enviado:</strong>
                        <p>
                            @if($notification->is_push_sent)
                                <span class="badge badge-success">
                                    <i class="fas fa-paper-plane"></i> Sí
                                </span>
                            @else
                                <span class="badge badge-secondary">
                                    <i class="fas fa-paper-plane"></i> No
                                </span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <strong>Creada:</strong>
                        <p class="text-muted">{{ $notification->created_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                    <div class="col-md-6">
                        <strong>Expira:</strong>
                        <p>
                            @if($notification->expires_at)
                                @if($notification->isExpired())
                                    <span class="badge badge-danger">
                                        <i class="fas fa-clock"></i> Expirada
                                    </span>
                                    <br><small class="text-muted">{{ $notification->expires_at->format('d/m/Y H:i:s') }}</small>
                                @else
                                    <span class="badge badge-success">
                                        <i class="fas fa-clock"></i> Válida
                                    </span>
                                    <br><small class="text-muted">{{ $notification->expires_at->format('d/m/Y H:i:s') }}</small>
                                @endif
                            @else
                                <span class="text-muted">Sin expiración</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($notification->data)
                    <div class="row">
                        <div class="col-md-12">
                            <strong>Datos Adicionales:</strong>
                            <pre class="bg-light p-3 rounded"><code>{{ json_encode($notification->data, JSON_PRETTY_PRINT) }}</code></pre>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Información del Usuario -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">👤 Información del Usuario</h3>
            </div>
            <div class="card-body">
                @if($notification->user)
                    <p><strong>Destinatario:</strong></p>
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <img src="{{ $notification->user->avatar ?? '/img/default-avatar.png' }}"
                                 class="img-circle" width="40" height="40" alt="Avatar">
                        </div>
                        <div>
                            <strong>{{ $notification->user->name }}</strong><br>
                            <small class="text-muted">{{ $notification->user->email }}</small>
                        </div>
                    </div>
                @else
                    <p><strong>Destinatario:</strong></p>
                    <span class="badge badge-info">
                        <i class="fas fa-globe"></i> Notificación Global
                    </span>
                    <p class="text-muted mt-2">Esta notificación fue enviada a todos los usuarios del sistema.</p>
                @endif

                @if($notification->creator)
                    <hr>
                    <p><strong>Creada por:</strong></p>
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <img src="{{ $notification->creator->avatar ?? '/img/default-avatar.png' }}"
                                 class="img-circle" width="40" height="40" alt="Avatar">
                        </div>
                        <div>
                            <strong>{{ $notification->creator->name }}</strong><br>
                            <small class="text-muted">{{ $notification->creator->email }}</small>
                        </div>
                    </div>
                @else
                    <hr>
                    <p><strong>Creada por:</strong></p>
                    <span class="badge badge-secondary">
                        <i class="fas fa-robot"></i> Sistema
                    </span>
                @endif
            </div>
        </div>

        <!-- Acciones -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">⚡ Acciones</h3>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if(!$notification->is_read)
                        <button type="button" class="btn btn-success" onclick="markAsRead()">
                            <i class="fas fa-check"></i> Marcar como Leída
                        </button>
                    @endif

                    @if($notification->url)
                        <a href="{{ $notification->url }}" target="_blank" class="btn btn-primary">
                            <i class="fas fa-external-link-alt"></i> Ver URL de Acción
                        </a>
                    @endif

                    <button type="button" class="btn btn-danger" onclick="deleteNotification()">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>

                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a la Lista
                    </a>
                </div>
            </div>
        </div>

        <!-- Vista Previa -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">👁️ Vista Previa</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-{{ $notification->type === 'error' ? 'danger' : $notification->type }}">
                    <div class="d-flex">
                        <div class="mr-3">
                            <i class="{{ $notification->default_icon }} fa-2x"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">{{ $notification->title }}</h6>
                            <p class="mb-1">{{ $notification->message }}</p>
                            @if($notification->action_text && $notification->url)
                                <a href="{{ $notification->url }}" class="btn btn-sm btn-primary">
                                    {{ $notification->action_text }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
function markAsRead() {
    fetch(`{{ route('admin.notifications.mark-read', $notification->id) }}`, {
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

function deleteNotification() {
    if (confirm('¿Estás seguro de que quieres eliminar esta notificación?')) {
        fetch(`{{ route('admin.notifications.destroy', $notification->id) }}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '{{ route("admin.notifications.index") }}';
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>
@stop
