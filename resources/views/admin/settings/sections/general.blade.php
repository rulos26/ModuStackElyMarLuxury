@extends('adminlte::page')

@section('title', 'Configuración General')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-globe"></i> Configuración General
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
                    <a href="{{ route('admin.settings.section', 'general') }}" class="nav-link active">
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
                    <a href="{{ route('admin.settings.section', 'advanced') }}" class="nav-link">
                        <i class="fas fa-cogs"></i> Avanzado
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="col-md-9">
        <form action="{{ route('admin.settings.update.section', 'general') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Información básica de la aplicación -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Información Básica
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="app_name">Nombre de la aplicación <span class="text-danger">*</span>:</label>
                                <input type="text" class="form-control @error('app_name') is-invalid @enderror"
                                       id="app_name" name="app_name" required
                                       value="{{ old('app_name', $settings->where('key', 'app_name')->first()->value ?? 'AdminLTE 3') }}"
                                       placeholder="Ej: Mi Aplicación">
                                @error('app_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Nombre que aparecerá en el título y menús
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="app_version">Versión:</label>
                                <input type="text" class="form-control @error('app_version') is-invalid @enderror"
                                       id="app_version" name="app_version"
                                       value="{{ old('app_version', $settings->where('key', 'app_version')->first()->value ?? '1.0.0') }}"
                                       placeholder="Ej: 1.0.0">
                                @error('app_version')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Versión actual de la aplicación
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="app_description">Descripción:</label>
                        <textarea class="form-control @error('app_description') is-invalid @enderror"
                                  id="app_description" name="app_description" rows="3"
                                  placeholder="Descripción breve de la aplicación">{{ old('app_description', $settings->where('key', 'app_description')->first()->value ?? '') }}</textarea>
                        @error('app_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Descripción que aparecerá en metadatos y documentación
                        </small>
                    </div>
                </div>
            </div>

            <!-- Información del desarrollador -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-tie"></i> Información del Desarrollador
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="app_author">Autor/Desarrollador:</label>
                                <input type="text" class="form-control @error('app_author') is-invalid @enderror"
                                       id="app_author" name="app_author"
                                       value="{{ old('app_author', $settings->where('key', 'app_author')->first()->value ?? '') }}"
                                       placeholder="Ej: Tu Empresa">
                                @error('app_author')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Nombre del autor o empresa desarrolladora
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="app_url">URL de la aplicación:</label>
                                <input type="url" class="form-control @error('app_url') is-invalid @enderror"
                                       id="app_url" name="app_url"
                                       value="{{ old('app_url', $settings->where('key', 'app_url')->first()->value ?? '') }}"
                                       placeholder="https://miaplicacion.com">
                                @error('app_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    URL principal de la aplicación
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vista previa de la información -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-eye"></i> Vista Previa
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Información del Sistema:</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td id="preview-name">{{ old('app_name', $settings->where('key', 'app_name')->first()->value ?? 'AdminLTE 3') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Versión:</strong></td>
                                    <td id="preview-version">{{ old('app_version', $settings->where('key', 'app_version')->first()->value ?? '1.0.0') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Autor:</strong></td>
                                    <td id="preview-author">{{ old('app_author', $settings->where('key', 'app_author')->first()->value ?? '') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>URL:</strong></td>
                                    <td id="preview-url">{{ old('app_url', $settings->where('key', 'app_url')->first()->value ?? '') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Descripción:</h5>
                            <div class="alert alert-light border" id="preview-description">
                                {{ old('app_description', $settings->where('key', 'app_description')->first()->value ?? 'Sin descripción') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración de idioma y región -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-globe-americas"></i> Idioma y Región
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="app_locale">Idioma predeterminado:</label>
                                <select class="form-control @error('app_locale') is-invalid @enderror"
                                        id="app_locale" name="app_locale">
                                    <option value="es" {{ old('app_locale', $settings->where('key', 'app_locale')->first()->value ?? 'es') == 'es' ? 'selected' : '' }}>
                                        Español
                                    </option>
                                    <option value="en" {{ old('app_locale', $settings->where('key', 'app_locale')->first()->value ?? 'es') == 'en' ? 'selected' : '' }}>
                                        English
                                    </option>
                                    <option value="fr" {{ old('app_locale', $settings->where('key', 'app_locale')->first()->value ?? 'es') == 'fr' ? 'selected' : '' }}>
                                        Français
                                    </option>
                                    <option value="de" {{ old('app_locale', $settings->where('key', 'app_locale')->first()->value ?? 'es') == 'de' ? 'selected' : '' }}>
                                        Deutsch
                                    </option>
                                </select>
                                @error('app_locale')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Idioma predeterminado de la interfaz
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="app_timezone">Zona horaria:</label>
                                <select class="form-control @error('app_timezone') is-invalid @enderror"
                                        id="app_timezone" name="app_timezone">
                                    <option value="America/Mexico_City" {{ old('app_timezone', $settings->where('key', 'app_timezone')->first()->value ?? 'America/Mexico_City') == 'America/Mexico_City' ? 'selected' : '' }}>
                                        América/México (GMT-6)
                                    </option>
                                    <option value="America/New_York" {{ old('app_timezone', $settings->where('key', 'app_timezone')->first()->value ?? 'America/Mexico_City') == 'America/New_York' ? 'selected' : '' }}>
                                        América/New York (GMT-5)
                                    </option>
                                    <option value="Europe/Madrid" {{ old('app_timezone', $settings->where('key', 'app_timezone')->first()->value ?? 'America/Mexico_City') == 'Europe/Madrid' ? 'selected' : '' }}>
                                        Europa/Madrid (GMT+1)
                                    </option>
                                    <option value="UTC" {{ old('app_timezone', $settings->where('key', 'app_timezone')->first()->value ?? 'America/Mexico_City') == 'UTC' ? 'selected' : '' }}>
                                        UTC (GMT+0)
                                    </option>
                                </select>
                                @error('app_timezone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Zona horaria predeterminada del sistema
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
                            <button type="submit" class="btn btn-success btn-lg">
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
// Actualizar vista previa en tiempo real
function updatePreview() {
    document.getElementById('preview-name').textContent = document.getElementById('app_name').value || 'Sin nombre';
    document.getElementById('preview-version').textContent = document.getElementById('app_version').value || 'Sin versión';
    document.getElementById('preview-author').textContent = document.getElementById('app_author').value || 'Sin autor';
    document.getElementById('preview-url').textContent = document.getElementById('app_url').value || 'Sin URL';
    document.getElementById('preview-description').textContent = document.getElementById('app_description').value || 'Sin descripción';
}

// Event listeners para actualizar vista previa
document.getElementById('app_name').addEventListener('input', updatePreview);
document.getElementById('app_version').addEventListener('input', updatePreview);
document.getElementById('app_author').addEventListener('input', updatePreview);
document.getElementById('app_url').addEventListener('input', updatePreview);
document.getElementById('app_description').addEventListener('input', updatePreview);

// Auto-hide alerts
$(document).ready(function() {
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Prevenir URLs con hash
    $('a[href="#"]').on('click', function(e) {
        e.preventDefault();
        console.log('Enlace con # prevenido:', this);
    });

    // Limpiar hash de la URL si existe
    if (window.location.hash) {
        window.history.replaceState('', document.title, window.location.pathname);
    }
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

.table td {
    border-top: 1px solid #dee2e6;
    padding: 0.5rem;
}

.alert-light {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    margin-bottom: 0;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
</style>
@stop
