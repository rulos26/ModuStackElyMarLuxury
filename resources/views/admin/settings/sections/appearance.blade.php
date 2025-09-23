@extends('adminlte::page')

@section('title', 'Configuración de Apariencia')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-palette"></i> Configuración de Apariencia
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
                    <a href="{{ route('admin.settings.section', 'appearance') }}" class="nav-link active">
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
        <form action="{{ route('admin.settings.update.section', 'appearance') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Logo de la aplicación -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-image"></i> Logo de la Aplicación
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Logo Actual:</label>
                                @php $currentLogo = $settings->where('key', 'app_logo')->first()->value ?? ''; @endphp
                                @if($currentLogo)
                                    <div class="text-center mb-3">
                                        <img src="{{ $currentLogo }}" alt="Logo Actual"
                                             style="max-width: 150px; max-height: 150px;"
                                             class="img-thumbnail" id="current-logo">
                                    </div>
                                @else
                                    <div class="alert alert-info text-center">
                                        <i class="fas fa-info-circle"></i> No hay logo configurado
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="logo_file">Subir nueva imagen:</label>
                                <input type="file" class="form-control @error('logo_file') is-invalid @enderror"
                                       id="logo_file" name="logo_file" accept="image/*" onchange="previewImage(this)">
                                @error('logo_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Formatos permitidos: JPEG, PNG, JPG, GIF, SVG. Tamaño máximo: 2MB
                                </small>
                            </div>

                            <div id="image-preview" class="mb-3" style="display: none;">
                                <label>Vista previa:</label>
                                <div class="text-center">
                                    <img id="preview-img" src="" alt="Vista previa"
                                         style="max-width: 150px; max-height: 150px;"
                                         class="img-thumbnail">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="app_logo">O pegar código base64/URL:</label>
                        <textarea class="form-control @error('app_logo') is-invalid @enderror"
                                  id="app_logo" name="app_logo" rows="3"
                                  placeholder="Pegar aquí el código base64 del logo o URL de la imagen">{{ old('app_logo', $settings->where('key', 'app_logo')->first()->value ?? '') }}</textarea>
                        @error('app_logo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Puede pegar un código base64 o una URL de imagen
                        </small>
                    </div>
                </div>
            </div>

            <!-- Icono de la aplicación -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-star"></i> Icono de la Aplicación
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="app_icon">Icono FontAwesome:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i id="icon-preview" class="fas fa-fw {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? 'fas fa-fw fa-cog') }}"></i>
                                        </span>
                                    </div>
                                    <input type="text" class="form-control @error('app_icon') is-invalid @enderror"
                                           id="app_icon" name="app_icon"
                                           value="{{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? 'fas fa-fw fa-cog') }}"
                                           onchange="updateIconPreview(this.value)">
                                    @error('app_icon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">
                                    Ejemplo: fas fa-fw fa-home, far fa-fw fa-star, fab fa-fw fa-github
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Vista previa del icono:</label>
                                <div class="text-center p-3 border rounded">
                                    <i id="icon-preview-large" class="fas fa-fw {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? 'fas fa-fw fa-cog') }}" style="font-size: 2rem; color: #007bff;"></i>
                                    <div class="mt-2">
                                        <small class="text-muted">Tamaño: 2rem</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Títulos de la aplicación -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-heading"></i> Títulos de la Aplicación
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="app_title_prefix">Prefijo del título:</label>
                                <input type="text" class="form-control @error('app_title_prefix') is-invalid @enderror"
                                       id="app_title_prefix" name="app_title_prefix"
                                       value="{{ old('app_title_prefix', $settings->where('key', 'app_title_prefix')->first()->value ?? '') }}"
                                       placeholder="Ej: [Admin]">
                                @error('app_title_prefix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Aparecerá antes del nombre de la página
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="app_title_postfix">Sufijo del título:</label>
                                <input type="text" class="form-control @error('app_title_postfix') is-invalid @enderror"
                                       id="app_title_postfix" name="app_title_postfix"
                                       value="{{ old('app_title_postfix', $settings->where('key', 'app_title_postfix')->first()->value ?? '') }}"
                                       placeholder="Ej: - Panel de Control">
                                @error('app_title_postfix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Aparecerá después del nombre de la página
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Vista previa del título:</label>
                        <div class="alert alert-light border">
                            <strong>Ejemplo:</strong>
                            <span id="title-preview">
                                {{ old('app_title_prefix', $settings->where('key', 'app_title_prefix')->first()->value ?? '') }}
                                Dashboard
                                {{ old('app_title_postfix', $settings->where('key', 'app_title_postfix')->first()->value ?? '') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tema y colores -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-paint-brush"></i> Tema y Colores
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="theme_color">Color del tema:</label>
                                <input type="color" class="form-control @error('theme_color') is-invalid @enderror"
                                       id="theme_color" name="theme_color"
                                       value="{{ old('theme_color', $settings->where('key', 'theme_color')->first()->value ?? '#007bff') }}">
                                @error('theme_color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Color principal del tema
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sidebar_style">Estilo del sidebar:</label>
                                <select class="form-control @error('sidebar_style') is-invalid @enderror"
                                        id="sidebar_style" name="sidebar_style">
                                    <option value="light" {{ old('sidebar_style', $settings->where('key', 'sidebar_style')->first()->value ?? 'light') == 'light' ? 'selected' : '' }}>
                                        Claro
                                    </option>
                                    <option value="dark" {{ old('sidebar_style', $settings->where('key', 'sidebar_style')->first()->value ?? 'light') == 'dark' ? 'selected' : '' }}>
                                        Oscuro
                                    </option>
                                </select>
                                @error('sidebar_style')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Estilo visual del menú lateral
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
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('image-preview').style.display = 'block';

            // Convertir a base64 automáticamente
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();
            img.onload = function() {
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
                const base64 = canvas.toDataURL('image/png');
                document.getElementById('app_logo').value = base64;
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function updateIconPreview(iconClass) {
    const preview = document.getElementById('icon-preview');
    const largePreview = document.getElementById('icon-preview-large');

    if (preview && largePreview) {
        preview.className = iconClass;
        largePreview.className = iconClass;
    }
}

function updateTitlePreview() {
    const prefix = document.getElementById('app_title_prefix').value;
    const postfix = document.getElementById('app_title_postfix').value;
    const preview = document.getElementById('title-preview');

    if (preview) {
        preview.textContent = prefix + ' Dashboard ' + postfix;
    }
}

// Event listeners
document.getElementById('app_title_prefix').addEventListener('input', updateTitlePreview);
document.getElementById('app_title_postfix').addEventListener('input', updateTitlePreview);

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

.img-thumbnail {
    border: 2px solid #dee2e6;
    border-radius: 0.5rem;
}

#image-preview {
    border: 1px dashed #007bff;
    border-radius: 0.5rem;
    padding: 1rem;
    background-color: #f8f9fa;
}

.form-control[type="color"] {
    height: 2.5rem;
    width: 100%;
}

.alert-light {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}
</style>
@stop
