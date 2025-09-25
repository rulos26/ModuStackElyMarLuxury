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

            <!-- Configuración del Footer -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-window-minimize"></i> Configuración del Footer
                    </h3>
                </div>
                <div class="card-body">
                    @php
                        $footerService = app(\App\Services\FooterService::class);
                        $footerConfig = $footerService->getFooterConfig();
                    @endphp

                    <!-- Tipo de Footer -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Tipo de Footer:</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="footer_type" id="footer_traditional"
                                           value="traditional" {{ !$footerConfig['use_custom_html'] ? 'checked' : '' }} onchange="toggleFooterType()">
                                    <label class="form-check-label" for="footer_traditional">
                                        Footer Tradicional (configuración por campos)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="footer_type" id="footer_custom"
                                           value="custom" {{ $footerConfig['use_custom_html'] ? 'checked' : '' }} onchange="toggleFooterType()">
                                    <label class="form-check-label" for="footer_custom">
                                        Footer Personalizado (HTML personalizado)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración Tradicional -->
                    <div id="footer-traditional-config">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Información de la Empresa:</h5>
                                <div class="form-group">
                                    <label for="footer_company_name">Nombre de la empresa:</label>
                                    <input type="text" class="form-control" id="footer_company_name" name="footer_company_name"
                                           value="{{ old('footer_company_name', $footerConfig['company_name']) }}"
                                           placeholder="Ely Mar Luxury">
                                </div>
                                <div class="form-group">
                                    <label for="footer_company_url">URL de la empresa:</label>
                                    <input type="url" class="form-control" id="footer_company_url" name="footer_company_url"
                                           value="{{ old('footer_company_url', $footerConfig['company_url']) }}"
                                           placeholder="https://www.ejemplo.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>Configuración de Visualización:</h5>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="footer_show_copyright" name="footer_show_copyright"
                                           {{ $footerConfig['show_copyright'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="footer_show_copyright">
                                        Mostrar copyright
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="footer_show_version" name="footer_show_version"
                                           {{ $footerConfig['show_version'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="footer_show_version">
                                        Mostrar versión
                                    </label>
                                </div>
                                <div class="form-group mt-3">
                                    <label for="footer_version_text">Texto de versión:</label>
                                    <input type="text" class="form-control" id="footer_version_text" name="footer_version_text"
                                           value="{{ old('footer_version_text', $footerConfig['version_text']) }}"
                                           placeholder="1.0.0">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="footer_left_text">Texto izquierda (opcional):</label>
                                    <input type="text" class="form-control" id="footer_left_text" name="footer_left_text"
                                           value="{{ old('footer_left_text', $footerConfig['left_text']) }}"
                                           placeholder="Texto adicional en la izquierda">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Layout del footer:</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="footer_layout" id="footer_layout_traditional"
                                               value="traditional" {{ !$footerConfig['show_center_text'] ? 'checked' : '' }}>
                                        <label class="form-check-label" for="footer_layout_traditional">
                                            Tradicional (izquierda/derecha)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="footer_layout" id="footer_layout_center"
                                               value="center" {{ $footerConfig['show_center_text'] ? 'checked' : '' }}>
                                        <label class="form-check-label" for="footer_layout_center">
                                            Centrado
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="footer-center-text" style="display: {{ $footerConfig['show_center_text'] ? 'block' : 'none' }};">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="footer_center_text">Texto centrado:</label>
                                    <input type="text" class="form-control" id="footer_center_text" name="footer_center_text"
                                           value="{{ old('footer_center_text', $footerConfig['center_text']) }}"
                                           placeholder="Texto centrado del footer">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración Personalizada -->
                    <div id="footer-custom-config" style="display: {{ $footerConfig['use_custom_html'] ? 'block' : 'none' }};">
                        <div class="form-group">
                            <label for="footer_custom_html">HTML Personalizado:</label>
                            <textarea class="form-control" id="footer_custom_html" name="footer_custom_html" rows="6"
                                      placeholder="<footer class='main-footer'>
    <div class='text-center'>
        <strong>Tu contenido personalizado aquí</strong>
    </div>
</footer>">{{ old('footer_custom_html', $footerConfig['custom_html']) }}</textarea>
                            <small class="form-text text-muted">
                                Puedes usar HTML completo para personalizar el footer. Asegúrate de incluir las clases CSS necesarias.
                            </small>
                        </div>
                    </div>

                    <!-- Vista previa del footer -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Vista previa del footer:</h5>
                            <div class="border rounded p-3 bg-light" id="footer-preview">
                                @if($footerConfig['use_custom_html'] && !empty($footerConfig['custom_html']))
                                    {!! $footerConfig['custom_html'] !!}
                                @else
                                    <footer class="main-footer">
                                        @if($footerConfig['show_center_text'] && !empty($footerConfig['center_text']))
                                            <div class="text-center">
                                                <strong>{{ $footerConfig['center_text'] }}</strong>
                                            </div>
                                        @else
                                            @if($footerConfig['show_copyright'])
                                                <div class="float-right d-none d-sm-inline">
                                                    <strong>Copyright &copy; {{ date('Y') }}
                                                        <a href="{{ $footerConfig['company_url'] !== '#' ? $footerConfig['company_url'] : '#' }}">
                                                            {{ $footerConfig['company_name'] }}
                                                        </a>.
                                                    </strong>
                                                    Todos los derechos reservados.
                                                </div>
                                            @endif

                                            <div class="float-left d-none d-sm-inline">
                                                @if($footerConfig['show_version'])
                                                    <strong>Versión</strong> {{ $footerConfig['version_text'] }}
                                                @endif
                                                @if(!empty($footerConfig['left_text']))
                                                    {!! $footerConfig['left_text'] !!}
                                                @endif
                                            </div>
                                        @endif
                                        <div class="clearfix"></div>
                                    </footer>
                                @endif
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

// Funciones para el footer
function toggleFooterType() {
    const traditionalRadio = document.getElementById('footer_traditional');
    const customRadio = document.getElementById('footer_custom');
    const traditionalConfig = document.getElementById('footer-traditional-config');
    const customConfig = document.getElementById('footer-custom-config');

    if (traditionalRadio.checked) {
        traditionalConfig.style.display = 'block';
        customConfig.style.display = 'none';
    } else {
        traditionalConfig.style.display = 'none';
        customConfig.style.display = 'block';
    }

    updateFooterPreview();
}

function toggleFooterLayout() {
    const traditionalLayout = document.getElementById('footer_layout_traditional');
    const centerTextDiv = document.getElementById('footer-center-text');

    if (traditionalLayout.checked) {
        centerTextDiv.style.display = 'none';
    } else {
        centerTextDiv.style.display = 'block';
    }

    updateFooterPreview();
}

function updateFooterPreview() {
    const footerType = document.querySelector('input[name="footer_type"]:checked').value;
    const preview = document.getElementById('footer-preview');

    if (footerType === 'custom') {
        const customHtml = document.getElementById('footer_custom_html').value;
        preview.innerHTML = customHtml || '<footer class="main-footer"><div class="text-center"><strong>Tu contenido personalizado aquí</strong></div></footer>';
    } else {
        const layout = document.querySelector('input[name="footer_layout"]:checked').value;
        const companyName = document.getElementById('footer_company_name').value || 'Ely Mar Luxury';
        const companyUrl = document.getElementById('footer_company_url').value || '#';
        const showCopyright = document.getElementById('footer_show_copyright').checked;
        const showVersion = document.getElementById('footer_show_version').checked;
        const versionText = document.getElementById('footer_version_text').value || '1.0.0';
        const leftText = document.getElementById('footer_left_text').value || '';
        const centerText = document.getElementById('footer_center_text').value || '';

        let html = '<footer class="main-footer">';

        if (layout === 'center' && centerText) {
            html += '<div class="text-center"><strong>' + centerText + '</strong></div>';
        } else {
            if (showCopyright) {
                html += '<div class="float-right d-none d-sm-inline">';
                html += '<strong>Copyright &copy; ' + new Date().getFullYear() + ' <a href="' + companyUrl + '">' + companyName + '</a>.</strong>';
                html += ' Todos los derechos reservados.';
                html += '</div>';
            }

            html += '<div class="float-left d-none d-sm-inline">';
            if (showVersion) {
                html += '<strong>Versión</strong> ' + versionText;
            }
            if (leftText) {
                html += ' ' + leftText;
            }
            html += '</div>';
        }

        html += '<div class="clearfix"></div></footer>';
        preview.innerHTML = html;
    }
}

// Event listeners
document.getElementById('app_title_prefix').addEventListener('input', updateTitlePreview);
document.getElementById('app_title_postfix').addEventListener('input', updateTitlePreview);
document.getElementById('theme_color').addEventListener('input', updateThemePreview);
document.getElementById('sidebar_style').addEventListener('change', updateThemePreview);

// Footer event listeners
document.querySelectorAll('input[name="footer_type"]').forEach(radio => {
    radio.addEventListener('change', toggleFooterType);
});

document.querySelectorAll('input[name="footer_layout"]').forEach(radio => {
    radio.addEventListener('change', toggleFooterLayout);
});

document.getElementById('footer_company_name').addEventListener('input', updateFooterPreview);
document.getElementById('footer_company_url').addEventListener('input', updateFooterPreview);
document.getElementById('footer_show_copyright').addEventListener('change', updateFooterPreview);
document.getElementById('footer_show_version').addEventListener('change', updateFooterPreview);
document.getElementById('footer_version_text').addEventListener('input', updateFooterPreview);
document.getElementById('footer_left_text').addEventListener('input', updateFooterPreview);
document.getElementById('footer_center_text').addEventListener('input', updateFooterPreview);
document.getElementById('footer_custom_html').addEventListener('input', updateFooterPreview);

// Inicializar vista previa
document.addEventListener('DOMContentLoaded', function() {
    updateTitlePreview();
    updateThemePreview();
    updateFooterPreview();
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


