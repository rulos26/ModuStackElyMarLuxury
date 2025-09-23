@extends('vendor.adminlte.page')

@section('title', 'Crear Notificación')

@section('content_header')
    <h1>📢 Crear Notificación</h1>
    <p>Enviar nueva notificación a usuarios</p>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📝 Información de la Notificación</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.notifications.store') }}">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="title">Título *</label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror"
                                       id="title" name="title" value="{{ old('title') }}"
                                       placeholder="Título de la notificación" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type">Tipo *</label>
                                <select class="form-control @error('type') is-invalid @enderror"
                                        id="type" name="type" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option value="info" {{ old('type') == 'info' ? 'selected' : '' }}>ℹ️ Información</option>
                                    <option value="success" {{ old('type') == 'success' ? 'selected' : '' }}>✅ Éxito</option>
                                    <option value="warning" {{ old('type') == 'warning' ? 'selected' : '' }}>⚠️ Advertencia</option>
                                    <option value="error" {{ old('type') == 'error' ? 'selected' : '' }}>❌ Error</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="message">Mensaje *</label>
                        <textarea class="form-control @error('message') is-invalid @enderror"
                                  id="message" name="message" rows="4"
                                  placeholder="Contenido de la notificación" required>{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="icon">Icono</label>
                                <input type="text" class="form-control @error('icon') is-invalid @enderror"
                                       id="icon" name="icon" value="{{ old('icon') }}"
                                       placeholder="fas fa-bell">
                                <small class="form-text text-muted">
                                    Clase de icono de FontAwesome (ej: fas fa-bell)
                                </small>
                                @error('icon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="expires_in_hours">Expira en (horas)</label>
                                <input type="number" class="form-control @error('expires_in_hours') is-invalid @enderror"
                                       id="expires_in_hours" name="expires_in_hours" value="{{ old('expires_in_hours') }}"
                                       min="1" max="168" placeholder="24">
                                <small class="form-text text-muted">
                                    Dejar vacío para sin expiración (máximo 168 horas = 7 días)
                                </small>
                                @error('expires_in_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="url">URL de Acción</label>
                                <input type="url" class="form-control @error('url') is-invalid @enderror"
                                       id="url" name="url" value="{{ old('url') }}"
                                       placeholder="https://ejemplo.com">
                                <small class="form-text text-muted">
                                    URL a la que dirigir al usuario al hacer clic
                                </small>
                                @error('url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="action_text">Texto del Botón</label>
                                <input type="text" class="form-control @error('action_text') is-invalid @enderror"
                                       id="action_text" name="action_text" value="{{ old('action_text') }}"
                                       placeholder="Ver más">
                                <small class="form-text text-muted">
                                    Texto del botón de acción
                                </small>
                                @error('action_text')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="target_type">Destinatario *</label>
                        <select class="form-control @error('target_type') is-invalid @enderror"
                                id="target_type" name="target_type" required onchange="toggleUserSelect()">
                            <option value="">Seleccionar destinatario</option>
                            <option value="specific" {{ old('target_type') == 'specific' ? 'selected' : '' }}>👤 Usuario Específico</option>
                            <option value="global" {{ old('target_type') == 'global' ? 'selected' : '' }}>🌍 Todos los Usuarios</option>
                        </select>
                        @error('target_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group" id="user_select_group" style="display: none;">
                        <label for="user_id">Usuario *</label>
                        <select class="form-control @error('user_id') is-invalid @enderror"
                                id="user_id" name="user_id">
                            <option value="">Seleccionar usuario</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Enviar Notificación
                        </button>
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Acciones Rápidas -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">⚡ Acciones Rápidas</h3>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="fillWelcome()">
                        <i class="fas fa-heart"></i> Notificación de Bienvenida
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="fillSecurity()">
                        <i class="fas fa-shield-alt"></i> Alerta de Seguridad
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="fillSystem()">
                        <i class="fas fa-cog"></i> Notificación del Sistema
                    </button>
                </div>
            </div>
        </div>

        <!-- Vista Previa -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">👁️ Vista Previa</h3>
            </div>
            <div class="card-body">
                <div id="preview-notification" class="alert alert-info">
                    <div class="d-flex">
                        <div class="mr-3">
                            <i class="fas fa-bell fa-2x"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1" id="preview-title">Título de la notificación</h6>
                            <p class="mb-1" id="preview-message">Mensaje de la notificación</p>
                            <small class="text-muted" id="preview-time">Hace unos momentos</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Iconos Comunes -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🎨 Iconos Comunes</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <button type="button" class="btn btn-sm btn-outline-secondary mb-1" onclick="setIcon('fas fa-bell')">
                            <i class="fas fa-bell"></i> Bell
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary mb-1" onclick="setIcon('fas fa-info-circle')">
                            <i class="fas fa-info-circle"></i> Info
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary mb-1" onclick="setIcon('fas fa-check-circle')">
                            <i class="fas fa-check-circle"></i> Check
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary mb-1" onclick="setIcon('fas fa-exclamation-triangle')">
                            <i class="fas fa-exclamation-triangle"></i> Warning
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-sm btn-outline-secondary mb-1" onclick="setIcon('fas fa-times-circle')">
                            <i class="fas fa-times-circle"></i> Error
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary mb-1" onclick="setIcon('fas fa-heart')">
                            <i class="fas fa-heart"></i> Heart
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary mb-1" onclick="setIcon('fas fa-shield-alt')">
                            <i class="fas fa-shield-alt"></i> Shield
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary mb-1" onclick="setIcon('fas fa-cog')">
                            <i class="fas fa-cog"></i> Cog
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
function toggleUserSelect() {
    const targetType = document.getElementById('target_type').value;
    const userSelectGroup = document.getElementById('user_select_group');
    const userSelect = document.getElementById('user_id');

    if (targetType === 'specific') {
        userSelectGroup.style.display = 'block';
        userSelect.required = true;
    } else {
        userSelectGroup.style.display = 'none';
        userSelect.required = false;
    }
}

function setIcon(iconClass) {
    document.getElementById('icon').value = iconClass;
    updatePreview();
}

function updatePreview() {
    const title = document.getElementById('title').value || 'Título de la notificación';
    const message = document.getElementById('message').value || 'Mensaje de la notificación';
    const type = document.getElementById('type').value || 'info';
    const icon = document.getElementById('icon').value || 'fas fa-bell';

    const preview = document.getElementById('preview-notification');
    const previewTitle = document.getElementById('preview-title');
    const previewMessage = document.getElementById('preview-message');
    const previewIcon = preview.querySelector('i');

    // Actualizar contenido
    previewTitle.textContent = title;
    previewMessage.textContent = message;
    previewIcon.className = icon + ' fa-2x';

    // Actualizar colores según tipo
    preview.className = 'alert alert-' + getAlertClass(type);
}

function getAlertClass(type) {
    const classes = {
        'info': 'info',
        'success': 'success',
        'warning': 'warning',
        'error': 'danger'
    };
    return classes[type] || 'info';
}

function fillWelcome() {
    document.getElementById('title').value = '¡Bienvenido a ' + '{{ config("app.name") }}' + '!';
    document.getElementById('message').value = 'Hola, ¡bienvenido a nuestra plataforma! Esperamos que tengas una excelente experiencia.';
    document.getElementById('type').value = 'success';
    document.getElementById('icon').value = 'fas fa-heart';
    document.getElementById('action_text').value = 'Comenzar';
    updatePreview();
}

function fillSecurity() {
    document.getElementById('title').value = 'Alerta de Seguridad';
    document.getElementById('message').value = 'Se ha detectado actividad sospechosa en tu cuenta. Por favor, revisa tu perfil y cambia tu contraseña si es necesario.';
    document.getElementById('type').value = 'warning';
    document.getElementById('icon').value = 'fas fa-shield-alt';
    document.getElementById('action_text').value = 'Ver Perfil';
    updatePreview();
}

function fillSystem() {
    document.getElementById('title').value = 'Notificación del Sistema';
    document.getElementById('message').value = 'El sistema se actualizará en las próximas horas. Durante este tiempo, algunas funciones pueden no estar disponibles.';
    document.getElementById('type').value = 'info';
    document.getElementById('icon').value = 'fas fa-cog';
    document.getElementById('action_text').value = 'Ver Detalles';
    updatePreview();
}

// Actualizar vista previa en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    const inputs = ['title', 'message', 'type', 'icon'];
    inputs.forEach(inputId => {
        document.getElementById(inputId).addEventListener('input', updatePreview);
    });

    // Inicializar
    toggleUserSelect();
    updatePreview();
});
</script>
@stop
