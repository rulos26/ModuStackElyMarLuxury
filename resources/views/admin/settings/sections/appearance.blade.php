@extends('vendor.adminlte.page')

@section('title', 'Configuración de Apariencia')

@section('content_header')
    <h1>
        <i class="fas fa-palette"></i> Configuración de Apariencia
    </h1>
    <p>Personalice el aspecto visual de la aplicación</p>
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
                    <a href="{{ route('admin.settings.section', 'general') }}"
                       class="nav-link {{ request()->route('section') == 'general' ? 'active' : '' }}">
                        <i class="fas fa-globe"></i> General
                    </a>
                    <a href="{{ route('admin.settings.section', 'appearance') }}"
                       class="nav-link {{ request()->route('section') == 'appearance' ? 'active' : '' }}">
                        <i class="fas fa-paint-brush"></i> Apariencia
                    </a>
                    <a href="{{ route('admin.settings.section', 'security') }}"
                       class="nav-link {{ request()->route('section') == 'security' ? 'active' : '' }}">
                        <i class="fas fa-shield-alt"></i> Seguridad
                    </a>
                    <a href="{{ route('admin.settings.section', 'notifications') }}"
                       class="nav-link {{ request()->route('section') == 'notifications' ? 'active' : '' }}">
                        <i class="fas fa-bell"></i> Notificaciones
                    </a>
                    <a href="{{ route('admin.settings.section', 'advanced') }}"
                       class="nav-link {{ request()->route('section') == 'advanced' ? 'active' : '' }}">
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
                                @php
                                    $currentLogo = \App\Helpers\ViewHelper::getLogoForView();
                                    $logoInfo = \App\Services\LogoService::getLogoInfo();
                                @endphp
                                @if($currentLogo && $logoInfo['exists'])
                                    <div class="text-center mb-3">
                                        <img src="{{ $currentLogo }}" alt="Logo Actual"
                                             style="max-width: 150px; max-height: 150px;"
                                             class="img-thumbnail" id="current-logo">
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-file"></i> {{ basename($currentLogo) }}
                                                @if($logoInfo['size'])
                                                    ({{ number_format($logoInfo['size'] / 1024, 1) }} KB)
                                                @endif
                                            </small>
                                        </div>
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
                                <label for="logo_file">Nuevo Logo:</label>
                                <input type="file" class="form-control @error('logo_file') is-invalid @enderror"
                                       id="logo_file" name="logo_file" accept="image/*" onchange="previewImage(this)">
                                @error('logo_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Formatos soportados: JPG, PNG, GIF, SVG (máx. 2MB)
                                </small>
                            </div>
                        </div>
                    </div>

                    <div id="image-preview" class="text-center" style="display: none;">
                        <h5>Vista previa:</h5>
                        <img id="preview-img" style="max-width: 200px; max-height: 200px;" class="img-thumbnail">
                    </div>
                </div>
            </div>

            <!-- Favicon de la aplicación -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-star"></i> Favicon de la Aplicación
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Favicons Actuales:</label>
                                @php
                                    $faviconInfo = \App\Services\FaviconService::getFaviconInfo();
                                @endphp
                                @if(file_exists(public_path('favicons/favicon.ico')))
                                    <div class="text-center mb-3">
                                        <div class="border rounded p-3">
                                            <img src="/favicons/favicon.ico" alt="Favicon Actual"
                                                 style="max-width: 32px; max-height: 32px;"
                                                 class="img-thumbnail">
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-file"></i> favicon.ico
                                                    @if(file_exists(public_path('favicons/favicon.ico')))
                                                        ({{ number_format(filesize(public_path('favicons/favicon.ico')) / 1024, 1) }} KB)
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-info text-center">
                                        <i class="fas fa-info-circle"></i> No hay favicon configurado
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="favicon_file">Nuevo Favicon:</label>
                                <input type="file" class="form-control @error('favicon_file') is-invalid @enderror"
                                       id="favicon_file" name="favicon_file" accept=".ico,.jpg,.jpeg,.png,.gif" onchange="previewFavicon(this)">
                                @error('favicon_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Formatos soportados: JPG, PNG, GIF, ICO (máx. 5MB, mín. 180x180px)
                                </small>
                            </div>
                        </div>
                    </div>

                    <div id="favicon-preview" class="text-center" style="display: none;">
                        <h5>Vista previa:</h5>
                        <img id="preview-favicon" style="max-width: 64px; max-height: 64px;" class="img-thumbnail">
                        <div class="mt-2">
                            <small class="text-muted">Las imágenes se convertirán automáticamente a favicon.ico</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Icono y títulos -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tag"></i> Icono y Títulos
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="app_icon">Icono de la aplicación:</label>
                                <input type="text" class="form-control @error('app_icon') is-invalid @enderror"
                                       id="app_icon" name="app_icon"
                                       value="{{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? 'fas fa-cube') }}"
                                       placeholder="fas fa-cube">
                                @error('app_icon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Clase de icono de Font Awesome
                                </small>
                                <div class="mt-2">
                                    <label>Vista previa:</label>
                                    <div class="d-flex align-items-center">
                                        <i id="icon-preview" class="fas fa-cube fa-2x text-primary mr-2"></i>
                                        <i id="icon-preview-large" class="fas fa-cube fa-3x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="app_title_prefix">Prefijo del título:</label>
                                <input type="text" class="form-control @error('app_title_prefix') is-invalid @enderror"
                                       id="app_title_prefix" name="app_title_prefix"
                                       value="{{ old('app_title_prefix', $settings->where('key', 'app_title_prefix')->first()->value ?? '') }}"
                                       placeholder="Mi">
                                @error('app_title_prefix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="app_title_postfix">Sufijo del título:</label>
                                <input type="text" class="form-control @error('app_title_postfix') is-invalid @enderror"
                                       id="app_title_postfix" name="app_title_postfix"
                                       value="{{ old('app_title_postfix', $settings->where('key', 'app_title_postfix')->first()->value ?? '') }}"
                                       placeholder="App">
                                @error('app_title_postfix')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label>Vista previa del título:</label>
                                <div class="alert alert-light">
                                    <strong id="title-preview">Dashboard</strong>
                                </div>
                            </div>
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

                    <!-- Vista previa en tiempo real -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Vista previa en tiempo real:</h5>
                            <div class="border rounded p-3" id="theme-preview">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary text-white px-3 py-2 rounded mr-3" id="preview-header">
                                        <i class="fas fa-home"></i> Dashboard
                                    </div>
                                    <div class="bg-light border px-3 py-2 rounded" id="preview-sidebar">
                                        <i class="fas fa-bars"></i> Menú
                                    </div>
                                </div>
                                <div class="text-muted">
                                    <small>Los cambios se aplicarán automáticamente al guardar</small>
                                </div>
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
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                            <a href="{{ route('admin.settings.dashboard') }}" class="btn btn-secondary btn-lg ml-2">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="button" class="btn btn-outline-danger" onclick="resetForm()">
                                <i class="fas fa-undo"></i> Restablecer
                            </button>
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
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewFavicon(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-favicon').src = e.target.result;
            document.getElementById('favicon-preview').style.display = 'block';
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

// Actualizar vista previa del tema en tiempo real
function updateThemePreview() {
    const themeColor = document.getElementById('theme_color').value;
    const sidebarStyle = document.getElementById('sidebar_style').value;

    // Aplicar color del tema
    document.getElementById('preview-header').style.backgroundColor = themeColor;

    // Aplicar estilo del sidebar
    const sidebar = document.getElementById('preview-sidebar');
    if (sidebarStyle === 'dark') {
        sidebar.className = 'bg-dark text-white px-3 py-2 rounded';
    } else {
        sidebar.className = 'bg-light border px-3 py-2 rounded';
    }
}

function resetForm() {
    if (confirm('¿Estás seguro de que quieres restablecer todos los cambios?')) {
        document.querySelector('form').reset();
        updateTitlePreview();
        updateThemePreview();
    }
}

// Event listeners
document.getElementById('app_title_prefix').addEventListener('input', updateTitlePreview);
document.getElementById('app_title_postfix').addEventListener('input', updateTitlePreview);
document.getElementById('theme_color').addEventListener('input', updateThemePreview);
document.getElementById('sidebar_style').addEventListener('change', updateThemePreview);

// Inicializar vista previa
document.addEventListener('DOMContentLoaded', function() {
    updateTitlePreview();
    updateThemePreview();
});

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

#theme-preview {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}
</style>
@stop


