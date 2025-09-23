@extends('adminlte::page')

@section('title', 'Configuración de Notificaciones')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-bell"></i> Configuración de Notificaciones
        </h1>
        <div class="btn-group">
            <a href="{{ route('admin.settings.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="row">
    <!-- Navegación lateral -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> Secciones
                </h3>
            </div>
            <div class="card-body p-0">
                <nav class="nav nav-pills flex-column">
                    <a href="{{ route('admin.settings.section', 'general') }}" class="nav-link">
                        <i class="fas fa-globe"></i> General
                    </a>
                    <a href="{{ route('admin.settings.section', 'appearance') }}" class="nav-link">
                        <i class="fas fa-palette"></i> Apariencia
                    </a>
                    <a href="{{ route('admin.settings.section', 'security') }}" class="nav-link">
                        <i class="fas fa-shield-alt"></i> Seguridad
                    </a>
                    <a href="{{ route('admin.settings.section', 'notifications') }}" class="nav-link active">
                        <i class="fas fa-bell"></i> Notificaciones
                    </a>
                    <a href="{{ route('admin.settings.section', 'advanced') }}" class="nav-link">
                        <i class="fas fa-cogs"></i> Avanzado
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="col-md-9">
        <form action="{{ route('admin.settings.update.section', 'notifications') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Configuración de notificaciones -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bell"></i> Configuración de Notificaciones
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="email_notifications" name="email_notifications"
                                           {{ old('email_notifications', $settings->where('key', 'email_notifications')->first()->value ?? true) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="email_notifications">
                                        Notificaciones por email
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Enviar notificaciones importantes por email
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="push_notifications" name="push_notifications">
                                    <label class="custom-control-label" for="push_notifications">
                                        Notificaciones push
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Enviar notificaciones push al navegador
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="notification_sound" name="notification_sound"
                                   {{ old('notification_sound', $settings->where('key', 'notification_sound')->first()->value ?? true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="notification_sound">
                                Sonido de notificaciones
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Reproducir sonido cuando llegue una notificación
                        </small>
                    </div>
                </div>
            </div>

            <!-- Configuración SMTP -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-envelope"></i> Configuración SMTP
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email_smtp_host">Servidor SMTP:</label>
                                <input type="text" class="form-control @error('email_smtp_host') is-invalid @enderror"
                                       id="email_smtp_host" name="email_smtp_host"
                                       value="{{ old('email_smtp_host', $settings->where('key', 'email_smtp_host')->first()->value ?? '') }}"
                                       placeholder="smtp.gmail.com">
                                @error('email_smtp_host')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email_smtp_port">Puerto SMTP:</label>
                                <input type="number" class="form-control @error('email_smtp_port') is-invalid @enderror"
                                       id="email_smtp_port" name="email_smtp_port" min="1" max="65535"
                                       value="{{ old('email_smtp_port', $settings->where('key', 'email_smtp_port')->first()->value ?? '587') }}">
                                @error('email_smtp_port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email_smtp_user">Usuario SMTP:</label>
                                <input type="email" class="form-control @error('email_smtp_user') is-invalid @enderror"
                                       id="email_smtp_user" name="email_smtp_user"
                                       value="{{ old('email_smtp_user', $settings->where('key', 'email_smtp_user')->first()->value ?? '') }}"
                                       placeholder="usuario@gmail.com">
                                @error('email_smtp_user')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email_smtp_encryption">Encriptación:</label>
                                <select class="form-control @error('email_smtp_encryption') is-invalid @enderror"
                                        id="email_smtp_encryption" name="email_smtp_encryption">
                                    <option value="tls" {{ old('email_smtp_encryption', $settings->where('key', 'email_smtp_encryption')->first()->value ?? 'tls') == 'tls' ? 'selected' : '' }}>
                                        TLS
                                    </option>
                                    <option value="ssl" {{ old('email_smtp_encryption', $settings->where('key', 'email_smtp_encryption')->first()->value ?? 'tls') == 'ssl' ? 'selected' : '' }}>
                                        SSL
                                    </option>
                                    <option value="none" {{ old('email_smtp_encryption', $settings->where('key', 'email_smtp_encryption')->first()->value ?? 'tls') == 'none' ? 'selected' : '' }}>
                                        Sin encriptación
                                    </option>
                                </select>
                                @error('email_smtp_encryption')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email_from_name">Nombre del remitente:</label>
                        <input type="text" class="form-control @error('email_from_name') is-invalid @enderror"
                               id="email_from_name" name="email_from_name"
                               value="{{ old('email_from_name', $settings->where('key', 'email_from_name')->first()->value ?? '') }}"
                               placeholder="Mi Aplicación">
                        @error('email_from_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email_from_address">Email del remitente:</label>
                        <input type="email" class="form-control @error('email_from_address') is-invalid @enderror"
                               id="email_from_address" name="email_from_address"
                               value="{{ old('email_from_address', $settings->where('key', 'email_from_address')->first()->value ?? '') }}"
                               placeholder="noreply@miaplicacion.com">
                        @error('email_from_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Tipos de notificaciones -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Tipos de Notificaciones
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Notificaciones del Sistema</h6>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="notify_user_registration" name="notify_user_registration">
                                    <label class="custom-control-label" for="notify_user_registration">
                                        Nuevos usuarios registrados
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="notify_login_attempts" name="notify_login_attempts">
                                    <label class="custom-control-label" for="notify_login_attempts">
                                        Intentos de login fallidos
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="notify_system_errors" name="notify_system_errors">
                                    <label class="custom-control-label" for="notify_system_errors">
                                        Errores del sistema
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Notificaciones de Usuario</h6>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="notify_password_changes" name="notify_password_changes">
                                    <label class="custom-control-label" for="notify_password_changes">
                                        Cambios de contraseña
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="notify_profile_updates" name="notify_profile_updates">
                                    <label class="custom-control-label" for="notify_profile_updates">
                                        Actualizaciones de perfil
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="notify_newsletter" name="notify_newsletter">
                                    <label class="custom-control-label" for="notify_newsletter">
                                        Newsletter y promociones
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración de frecuencia -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock"></i> Frecuencia de Notificaciones
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="notification_frequency">Frecuencia de notificaciones:</label>
                                <select class="form-control @error('notification_frequency') is-invalid @enderror"
                                        id="notification_frequency" name="notification_frequency">
                                    <option value="immediate">Inmediato</option>
                                    <option value="hourly">Cada hora</option>
                                    <option value="daily">Diario</option>
                                    <option value="weekly">Semanal</option>
                                </select>
                                @error('notification_frequency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="notification_quiet_hours">Horas de silencio:</label>
                                <input type="text" class="form-control @error('notification_quiet_hours') is-invalid @enderror"
                                       id="notification_quiet_hours" name="notification_quiet_hours"
                                       placeholder="22:00 - 08:00">
                                @error('notification_quiet_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Horario en formato HH:MM - HH:MM donde no enviar notificaciones
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-info btn-lg">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="{{ route('admin.settings.dashboard') }}" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@section('js')
<script>
// Auto-hide alerts
$(document).ready(function() {
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
@stop

@section('css')
<style>
.nav-pills .nav-link {
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
}

.nav-pills .nav-link.active {
    background-color: #007bff;
}

.custom-switch .custom-control-label::before {
    border-radius: 0.5rem;
}

.custom-switch .custom-control-input:checked ~ .custom-control-label::after {
    background-color: #fff;
    border-radius: 0.5rem;
}
</style>
@stop
