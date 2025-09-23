@extends('adminlte::page')

@section('title', 'Configuración de la Aplicación')

@section('content_header')
    <h1>Configuración de la Aplicación</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Configuración General</h3>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="app_name">Nombre de la Aplicación</label>
                            <input type="text" class="form-control @error('app_name') is-invalid @enderror"
                                   id="app_name" name="app_name"
                                   value="{{ old('app_name', $settings->where('key', 'app_name')->first()->value ?? '') }}"
                                   required>
                            @error('app_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="app_icon">Icono de la Aplicación</label>
                            <select class="form-control @error('app_icon') is-invalid @enderror"
                                    id="app_icon" name="app_icon" required>
                                <option value="">Seleccionar icono</option>
                                <option value="fas fa-fw fa-tachometer-alt" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-tachometer-alt' ? 'selected' : '' }}>Dashboard</option>
                                <option value="fas fa-fw fa-users" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-users' ? 'selected' : '' }}>Usuarios</option>
                                <option value="fas fa-fw fa-user-tag" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-user-tag' ? 'selected' : '' }}>Roles</option>
                                <option value="fas fa-fw fa-key" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-key' ? 'selected' : '' }}>Permisos</option>
                                <option value="fas fa-fw fa-cog" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-cog' ? 'selected' : '' }}>Configuración</option>
                                <option value="fas fa-fw fa-home" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-home' ? 'selected' : '' }}>Inicio</option>
                                <option value="fas fa-fw fa-chart-bar" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-chart-bar' ? 'selected' : '' }}>Gráficos</option>
                                <option value="fas fa-fw fa-file" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-file' ? 'selected' : '' }}>Archivos</option>
                                <option value="fas fa-fw fa-envelope" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-envelope' ? 'selected' : '' }}>Mensajes</option>
                                <option value="fas fa-fw fa-bell" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-bell' ? 'selected' : '' }}>Notificaciones</option>
                                <option value="fas fa-fw fa-search" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-search' ? 'selected' : '' }}>Búsqueda</option>
                                <option value="fas fa-fw fa-plus" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-plus' ? 'selected' : '' }}>Agregar</option>
                                <option value="fas fa-fw fa-edit" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-edit' ? 'selected' : '' }}>Editar</option>
                                <option value="fas fa-fw fa-trash" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-trash' ? 'selected' : '' }}>Eliminar</option>
                                <option value="fas fa-fw fa-eye" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-eye' ? 'selected' : '' }}>Ver</option>
                                <option value="fas fa-fw fa-save" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-save' ? 'selected' : '' }}>Guardar</option>
                                <option value="fas fa-fw fa-arrow-left" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-arrow-left' ? 'selected' : '' }}>Izquierda</option>
                                <option value="fas fa-fw fa-arrow-right" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-arrow-right' ? 'selected' : '' }}>Derecha</option>
                                <option value="fas fa-fw fa-chevron-down" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-chevron-down' ? 'selected' : '' }}>Abajo</option>
                                <option value="fas fa-fw fa-chevron-up" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-chevron-up' ? 'selected' : '' }}>Arriba</option>
                                <option value="fas fa-fw fa-bars" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-bars' ? 'selected' : '' }}>Menú</option>
                                <option value="fas fa-fw fa-times" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-times' ? 'selected' : '' }}>Cerrar</option>
                                <option value="fas fa-fw fa-check" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-check' ? 'selected' : '' }}>Verificar</option>
                                <option value="fas fa-fw fa-exclamation" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-exclamation' ? 'selected' : '' }}>Exclamación</option>
                                <option value="fas fa-fw fa-info" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-info' ? 'selected' : '' }}>Información</option>
                                <option value="fas fa-fw fa-warning" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-warning' ? 'selected' : '' }}>Advertencia</option>
                                <option value="fas fa-fw fa-question" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-question' ? 'selected' : '' }}>Pregunta</option>
                                <option value="fas fa-fw fa-star" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-star' ? 'selected' : '' }}>Estrella</option>
                                <option value="fas fa-fw fa-heart" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-heart' ? 'selected' : '' }}>Corazón</option>
                                <option value="fas fa-fw fa-thumbs-up" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-thumbs-up' ? 'selected' : '' }}>Pulgar arriba</option>
                                <option value="fas fa-fw fa-thumbs-down" {{ old('app_icon', $settings->where('key', 'app_icon')->first()->value ?? '') == 'fas fa-fw fa-thumbs-down' ? 'selected' : '' }}>Pulgar abajo</option>
                            </select>
                            @error('app_icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="app_title_prefix">Prefijo del Título</label>
                            <input type="text" class="form-control @error('app_title_prefix') is-invalid @enderror"
                                   id="app_title_prefix" name="app_title_prefix"
                                   value="{{ old('app_title_prefix', $settings->where('key', 'app_title_prefix')->first()->value ?? '') }}">
                            @error('app_title_prefix')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="app_title_postfix">Postfijo del Título</label>
                            <input type="text" class="form-control @error('app_title_postfix') is-invalid @enderror"
                                   id="app_title_postfix" name="app_title_postfix"
                                   value="{{ old('app_title_postfix', $settings->where('key', 'app_title_postfix')->first()->value ?? '') }}">
                            @error('app_title_postfix')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="app_logo">Logo de la Aplicación</label>

                    {{-- Vista previa del logo actual --}}
                    <div class="mb-3">
                        <label>Logo Actual:</label>
                        @php
                            $currentLogo = $settings->where('key', 'app_logo')->first()->value ?? '';
                        @endphp
                        @if($currentLogo)
                            <div class="text-center">
                                <img src="{{ $currentLogo }}" alt="Logo Actual" style="max-width: 150px; max-height: 150px;" class="img-thumbnail">
                            </div>
                        @else
                            <div class="alert alert-info">No hay logo configurado</div>
                        @endif
                    </div>

                    {{-- Opción 1: Subir archivo --}}
                    <div class="mb-3">
                        <label for="logo_file">Subir nueva imagen:</label>
                        <input type="file" class="form-control @error('logo_file') is-invalid @enderror"
                               id="logo_file" name="logo_file" accept="image/*" onchange="previewImage(this)">
                        @error('logo_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB
                        </small>
                    </div>

                    {{-- Vista previa de la nueva imagen --}}
                    <div id="image-preview" class="mb-3" style="display: none;">
                        <label>Vista previa:</label>
                        <div class="text-center">
                            <img id="preview-img" src="" alt="Vista previa" style="max-width: 150px; max-height: 150px;" class="img-thumbnail">
                        </div>
                    </div>

                    {{-- Opción 2: Pegar base64 o URL --}}
                    <div class="mb-3">
                        <label for="app_logo">O pegar código base64/URL:</label>
                        <textarea class="form-control @error('app_logo') is-invalid @enderror"
                                  id="app_logo" name="app_logo" rows="3"
                                  placeholder="Pegar aquí el código base64 del logo o URL de la imagen">{{ old('app_logo', $settings->where('key', 'app_logo')->first()->value ?? '') }}</textarea>
                        @error('app_logo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Puedes pegar un código base64 de una imagen o una URL de imagen.
                        </small>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Configuración
                    </button>
                    <a href="{{ route('admin.settings.reset') }}" class="btn btn-warning"
                       onclick="return confirm('¿Estás seguro de restaurar la configuración por defecto?')">
                        <i class="fas fa-undo"></i> Restaurar por Defecto
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Vista previa del icono -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Vista Previa del Icono</h3>
        </div>
        <div class="card-body">
            <div class="text-center">
                @php
                    $currentIcon = $settings->where('key', 'app_icon')->first()->value ?? 'fas fa-fw fa-tachometer-alt';
                @endphp
                <i class="{{ $currentIcon }} fa-3x text-primary"></i>
                <p class="mt-2">{{ $currentIcon }}</p>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('image-preview').style.display = 'block';

                    // Convertir a base64 y llenar el textarea
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
    </script>
@stop
