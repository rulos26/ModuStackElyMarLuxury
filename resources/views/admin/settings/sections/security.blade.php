@extends('adminlte::page')

@section('title', 'Configuración de Seguridad')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-shield-alt"></i> Configuración de Seguridad
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
                    <a href="{{ route('admin.settings.section', 'security') }}" class="nav-link active">
                        <i class="fas fa-shield-alt"></i> Seguridad
                    </a>
                    <a href="{{ route('admin.settings.section', 'notifications') }}" class="nav-link">
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
        <form action="{{ route('admin.settings.update.section', 'security') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Configuración de sesiones -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock"></i> Configuración de Sesiones
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="session_timeout">Tiempo de sesión (minutos):</label>
                                <input type="number" class="form-control @error('session_timeout') is-invalid @enderror"
                                       id="session_timeout" name="session_timeout" min="5" max="480"
                                       value="{{ old('session_timeout', $settings->where('key', 'session_timeout')->first()->value ?? '120') }}">
                                @error('session_timeout')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Tiempo en minutos antes de que expire la sesión (5-480 min)
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="max_login_attempts">Máximo intentos de login:</label>
                                <input type="number" class="form-control @error('max_login_attempts') is-invalid @enderror"
                                       id="max_login_attempts" name="max_login_attempts" min="3" max="10"
                                       value="{{ old('max_login_attempts', $settings->where('key', 'max_login_attempts')->first()->value ?? '5') }}">
                                @error('max_login_attempts')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Número máximo de intentos antes de bloquear (3-10)
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Política de contraseñas -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-lock"></i> Política de Contraseñas
                    </h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="password_min_length">Longitud mínima de contraseña:</label>
                        <input type="number" class="form-control @error('password_min_length') is-invalid @enderror"
                               id="password_min_length" name="password_min_length" min="6" max="20"
                               value="{{ old('password_min_length', $settings->where('key', 'password_min_length')->first()->value ?? '8') }}">
                        @error('password_min_length')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Longitud mínima requerida para las contraseñas (6-20 caracteres)
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="require_uppercase" name="require_uppercase">
                            <label class="custom-control-label" for="require_uppercase">
                                Requerir al menos una letra mayúscula
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="require_lowercase" name="require_lowercase">
                            <label class="custom-control-label" for="require_lowercase">
                                Requerir al menos una letra minúscula
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="require_numbers" name="require_numbers">
                            <label class="custom-control-label" for="require_numbers">
                                Requerir al menos un número
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="require_symbols" name="require_symbols">
                            <label class="custom-control-label" for="require_symbols">
                                Requerir al menos un símbolo especial
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Autenticación de dos factores -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-key"></i> Autenticación de Dos Factores
                    </h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="require_2fa" name="require_2fa"
                                   {{ old('require_2fa', $settings->where('key', 'require_2fa')->first()->value ?? false) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="require_2fa">
                                Requerir autenticación de dos factores
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Los usuarios deberán configurar 2FA en sus cuentas
                        </small>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Nota:</strong> La autenticación de dos factores requiere que los usuarios configuren una aplicación autenticadora como Google Authenticator o Authy.
                    </div>
                </div>
            </div>

            <!-- Configuración de registro -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-plus"></i> Configuración de Registro
                    </h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="allow_registration" name="allow_registration"
                                   {{ old('allow_registration', $settings->where('key', 'allow_registration')->first()->value ?? true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="allow_registration">
                                Permitir registro de nuevos usuarios
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Si está deshabilitado, solo los administradores pueden crear cuentas
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="email_verification_required" name="email_verification_required">
                            <label class="custom-control-label" for="email_verification_required">
                                Requerir verificación de email
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Los usuarios deben verificar su email antes de poder acceder
                        </small>
                    </div>
                </div>
            </div>

            <!-- Configuración de IP -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-network-wired"></i> Control de Acceso por IP
                    </h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="ip_whitelist_enabled" name="ip_whitelist_enabled">
                            <label class="custom-control-label" for="ip_whitelist_enabled">
                                Habilitar lista blanca de IPs
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Solo las IPs en la lista blanca podrán acceder al sistema
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="allowed_ips">IPs permitidas:</label>
                        <textarea class="form-control @error('allowed_ips') is-invalid @enderror"
                                  id="allowed_ips" name="allowed_ips" rows="4"
                                  placeholder="Ejemplo:&#10;192.168.1.1&#10;10.0.0.0/8&#10;203.0.113.0/24">{{ old('allowed_ips', $settings->where('key', 'allowed_ips')->first()->value ?? '') }}</textarea>
                        @error('allowed_ips')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Una IP o rango por línea. Ejemplo: 192.168.1.1, 10.0.0.0/8
                        </small>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-warning btn-lg">
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

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}
</style>
@stop
