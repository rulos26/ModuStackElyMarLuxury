@extends('adminlte::page')

@section('title', 'Configuración Avanzada')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-cogs"></i> Configuración Avanzada
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
                    <a href="{{ route('admin.settings.section', 'notifications') }}" class="nav-link">
                        <i class="fas fa-bell"></i> Notificaciones
                    </a>
                    <a href="{{ route('admin.settings.section', 'advanced') }}" class="nav-link active">
                        <i class="fas fa-cogs"></i> Avanzado
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="col-md-9">
        <form action="{{ route('admin.settings.update.section', 'advanced') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Configuración de debug y mantenimiento -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bug"></i> Debug y Mantenimiento
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="debug_mode" name="debug_mode"
                                           {{ old('debug_mode', $settings->where('key', 'debug_mode')->first()->value ?? false) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="debug_mode">
                                        Modo debug
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Mostrar errores detallados (solo para desarrollo)
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="maintenance_mode" name="maintenance_mode">
                                    <label class="custom-control-label" for="maintenance_mode">
                                        Modo mantenimiento
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Bloquear acceso público durante mantenimiento
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="log_level">Nivel de logs:</label>
                        <select class="form-control @error('log_level') is-invalid @enderror"
                                id="log_level" name="log_level">
                            <option value="debug" {{ old('log_level', $settings->where('key', 'log_level')->first()->value ?? 'info') == 'debug' ? 'selected' : '' }}>
                                Debug (más detallado)
                            </option>
                            <option value="info" {{ old('log_level', $settings->where('key', 'log_level')->first()->value ?? 'info') == 'info' ? 'selected' : '' }}>
                                Info (informativo)
                            </option>
                            <option value="warning" {{ old('log_level', $settings->where('key', 'log_level')->first()->value ?? 'info') == 'warning' ? 'selected' : '' }}>
                                Warning (advertencias)
                            </option>
                            <option value="error" {{ old('log_level', $settings->where('key', 'log_level')->first()->value ?? 'info') == 'error' ? 'selected' : '' }}>
                                Error (solo errores)
                            </option>
                        </select>
                        @error('log_level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Configuración de caché -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-memory"></i> Configuración de Caché
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cache_driver">Driver de caché:</label>
                                <select class="form-control @error('cache_driver') is-invalid @enderror"
                                        id="cache_driver" name="cache_driver">
                                    <option value="file" {{ old('cache_driver', $settings->where('key', 'cache_driver')->first()->value ?? 'file') == 'file' ? 'selected' : '' }}>
                                        Archivo (File)
                                    </option>
                                    <option value="redis" {{ old('cache_driver', $settings->where('key', 'cache_driver')->first()->value ?? 'file') == 'redis' ? 'selected' : '' }}>
                                        Redis
                                    </option>
                                    <option value="memcached" {{ old('cache_driver', $settings->where('key', 'cache_driver')->first()->value ?? 'file') == 'memcached' ? 'selected' : '' }}>
                                        Memcached
                                    </option>
                                </select>
                                @error('cache_driver')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cache_ttl">TTL de caché (minutos):</label>
                                <input type="number" class="form-control @error('cache_ttl') is-invalid @enderror"
                                       id="cache_ttl" name="cache_ttl" min="1" max="1440"
                                       value="{{ old('cache_ttl', $settings->where('key', 'cache_ttl')->first()->value ?? '60') }}">
                                @error('cache_ttl')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Tiempo de vida del caché en minutos (1-1440)
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn btn-warning" onclick="clearCache()">
                            <i class="fas fa-trash"></i> Limpiar Caché
                        </button>
                        <small class="form-text text-muted">
                            Limpia todo el caché del sistema
                        </small>
                    </div>
                </div>
            </div>

            <!-- Configuración de colas -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tasks"></i> Configuración de Colas
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="queue_driver">Driver de colas:</label>
                                <select class="form-control @error('queue_driver') is-invalid @enderror"
                                        id="queue_driver" name="queue_driver">
                                    <option value="sync" {{ old('queue_driver', $settings->where('key', 'queue_driver')->first()->value ?? 'sync') == 'sync' ? 'selected' : '' }}>
                                        Síncrono (Sync)
                                    </option>
                                    <option value="database" {{ old('queue_driver', $settings->where('key', 'queue_driver')->first()->value ?? 'sync') == 'database' ? 'selected' : '' }}>
                                        Base de datos
                                    </option>
                                    <option value="redis" {{ old('queue_driver', $settings->where('key', 'queue_driver')->first()->value ?? 'sync') == 'redis' ? 'selected' : '' }}>
                                        Redis
                                    </option>
                                </select>
                                @error('queue_driver')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="queue_max_attempts">Máximo intentos:</label>
                                <input type="number" class="form-control @error('queue_max_attempts') is-invalid @enderror"
                                       id="queue_max_attempts" name="queue_max_attempts" min="1" max="10"
                                       value="{{ old('queue_max_attempts', $settings->where('key', 'queue_max_attempts')->first()->value ?? '3') }}">
                                @error('queue_max_attempts')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Número máximo de intentos para trabajos fallidos
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración de respaldos -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-database"></i> Configuración de Respaldos
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="backup_frequency">Frecuencia de respaldos:</label>
                                <select class="form-control @error('backup_frequency') is-invalid @enderror"
                                        id="backup_frequency" name="backup_frequency">
                                    <option value="disabled" {{ old('backup_frequency', $settings->where('key', 'backup_frequency')->first()->value ?? 'disabled') == 'disabled' ? 'selected' : '' }}>
                                        Deshabilitado
                                    </option>
                                    <option value="daily" {{ old('backup_frequency', $settings->where('key', 'backup_frequency')->first()->value ?? 'disabled') == 'daily' ? 'selected' : '' }}>
                                        Diario
                                    </option>
                                    <option value="weekly" {{ old('backup_frequency', $settings->where('key', 'backup_frequency')->first()->value ?? 'disabled') == 'weekly' ? 'selected' : '' }}>
                                        Semanal
                                    </option>
                                    <option value="monthly" {{ old('backup_frequency', $settings->where('key', 'backup_frequency')->first()->value ?? 'disabled') == 'monthly' ? 'selected' : '' }}>
                                        Mensual
                                    </option>
                                </select>
                                @error('backup_frequency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="backup_retention">Retención de respaldos (días):</label>
                                <input type="number" class="form-control @error('backup_retention') is-invalid @enderror"
                                       id="backup_retention" name="backup_retention" min="1" max="365"
                                       value="{{ old('backup_retention', $settings->where('key', 'backup_retention')->first()->value ?? '30') }}">
                                @error('backup_retention')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Días para mantener los respaldos (1-365)
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn btn-success" onclick="createBackup()">
                            <i class="fas fa-save"></i> Crear Respaldo Ahora
                        </button>
                        <small class="form-text text-muted">
                            Crea un respaldo manual de la base de datos
                        </small>
                    </div>
                </div>
            </div>

            <!-- Configuración de API -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-code"></i> Configuración de API
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="api_enabled" name="api_enabled">
                                    <label class="custom-control-label" for="api_enabled">
                                        Habilitar API
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Permitir acceso a la API REST
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="api_rate_limit">Límite de tasa API (req/min):</label>
                                <input type="number" class="form-control @error('api_rate_limit') is-invalid @enderror"
                                       id="api_rate_limit" name="api_rate_limit" min="10" max="1000"
                                       value="{{ old('api_rate_limit', $settings->where('key', 'api_rate_limit')->first()->value ?? '60') }}">
                                @error('api_rate_limit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                            <button type="submit" class="btn btn-danger btn-lg">
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
function clearCache() {
    if (confirm('¿Está seguro de que desea limpiar todo el caché?')) {
        // Aquí se implementaría la lógica para limpiar el caché
        alert('Caché limpiado exitosamente');
    }
}

function createBackup() {
    if (confirm('¿Crear un respaldo de la base de datos ahora?')) {
        // Aquí se implementaría la lógica para crear un respaldo
        alert('Respaldo creado exitosamente');
    }
}

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
