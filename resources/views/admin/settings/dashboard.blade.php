@extends('adminlte::page')

@section('title', 'Dashboard de Configuración')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-cogs"></i> Dashboard de Configuración
        </h1>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetAllSettings()">
                <i class="fas fa-undo"></i> Restaurar Todo
            </button>
        </div>
    </div>
@stop

@section('content')
<div class="row">
    <!-- Navegación lateral de secciones -->
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
                        <small class="text-muted d-block">Información básica</small>
                    </a>
                    <a href="{{ route('admin.settings.section', 'appearance') }}"
                       class="nav-link {{ request()->route('section') == 'appearance' ? 'active' : '' }}">
                        <i class="fas fa-palette"></i> Apariencia
                        <small class="text-muted d-block">Logo, colores, tema</small>
                    </a>
                    <a href="{{ route('admin.settings.section', 'security') }}"
                       class="nav-link {{ request()->route('section') == 'security' ? 'active' : '' }}">
                        <i class="fas fa-shield-alt"></i> Seguridad
                        <small class="text-muted d-block">Contraseñas, sesiones</small>
                    </a>
                    <a href="{{ route('admin.settings.section', 'notifications') }}"
                       class="nav-link {{ request()->route('section') == 'notifications' ? 'active' : '' }}">
                        <i class="fas fa-bell"></i> Notificaciones
                        <small class="text-muted d-block">Email, push, alertas</small>
                    </a>
                    <a href="{{ route('admin.settings.section', 'advanced') }}"
                       class="nav-link {{ request()->route('section') == 'advanced' ? 'active' : '' }}">
                        <i class="fas fa-cogs"></i> Avanzado
                        <small class="text-muted d-block">Debug, caché, logs</small>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Resumen rápido -->
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle"></i> Resumen
                </h3>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-right">
                            <h4 class="text-primary mb-0">{{ $settings->count() }}</h4>
                            <small class="text-muted">Configuraciones</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-0">{{ $settings->where('value', '!=', '')->count() }}</h4>
                        <small class="text-muted">Configuradas</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="col-md-9">
        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $settings->where('key', 'like', '%app_%')->count() }}</h3>
                        <p>Config. Aplicación</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $settings->where('key', 'like', '%email_%')->count() }}</h3>
                        <p>Config. Email</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $settings->where('key', 'like', '%security_%')->count() }}</h3>
                        <p>Config. Seguridad</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $settings->where('key', 'like', '%debug_%')->count() }}</h3>
                        <p>Config. Debug</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-bug"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de secciones -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-globe"></i> General
                        </h3>
                    </div>
                    <div class="card-body">
                        <p>Configure la información básica de la aplicación como nombre, descripción y versión.</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> Nombre de la aplicación</li>
                            <li><i class="fas fa-check text-success"></i> Descripción y versión</li>
                            <li><i class="fas fa-check text-success"></i> URL y autor</li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.settings.section', 'general') }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Configurar
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-palette"></i> Apariencia
                        </h3>
                    </div>
                    <div class="card-body">
                        <p>Personalice el logo, colores y tema visual de la aplicación.</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> Logo y favicon</li>
                            <li><i class="fas fa-check text-success"></i> Colores del tema</li>
                            <li><i class="fas fa-check text-success"></i> Estilo del sidebar</li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.settings.section', 'appearance') }}" class="btn btn-success">
                            <i class="fas fa-edit"></i> Configurar
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-shield-alt"></i> Seguridad
                        </h3>
                    </div>
                    <div class="card-body">
                        <p>Configure las opciones de seguridad y autenticación.</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> Tiempo de sesión</li>
                            <li><i class="fas fa-check text-success"></i> Política de contraseñas</li>
                            <li><i class="fas fa-check text-success"></i> Autenticación 2FA</li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.settings.section', 'security') }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Configurar
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bell"></i> Notificaciones
                        </h3>
                    </div>
                    <div class="card-body">
                        <p>Configure las notificaciones y servicios de email.</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> Notificaciones por email</li>
                            <li><i class="fas fa-check text-success"></i> Configuración SMTP</li>
                            <li><i class="fas fa-check text-success"></i> Alertas push</li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.settings.section', 'notifications') }}" class="btn btn-info">
                            <i class="fas fa-edit"></i> Configurar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuración avanzada -->
        <div class="row">
            <div class="col-12">
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cogs"></i> Avanzado
                        </h3>
                    </div>
                    <div class="card-body">
                        <p>Configuraciones avanzadas del sistema como debug, caché y logs.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success"></i> Modo debug</li>
                                    <li><i class="fas fa-check text-success"></i> Modo mantenimiento</li>
                                    <li><i class="fas fa-check text-success"></i> Drivers de caché</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success"></i> Colas de trabajo</li>
                                    <li><i class="fas fa-check text-success"></i> Respaldos automáticos</li>
                                    <li><i class="fas fa-check text-success"></i> Niveles de log</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('admin.settings.section', 'advanced') }}" class="btn btn-danger">
                            <i class="fas fa-edit"></i> Configurar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para reset -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning"></i> Confirmar Restauración
                </h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea restaurar todas las configuraciones a sus valores por defecto?</p>
                <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form action="{{ route('admin.settings.reset') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-undo"></i> Restaurar Todo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
function resetAllSettings() {
    $('#resetModal').modal('show');
}

// Auto-hide alerts after 5 seconds
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

.small-box .inner h3 {
    font-size: 2.2rem;
    font-weight: bold;
}

.card-primary .card-header {
    background-color: #007bff;
    border-bottom-color: #0056b3;
}

.card-success .card-header {
    background-color: #28a745;
    border-bottom-color: #1e7e34;
}

.card-warning .card-header {
    background-color: #ffc107;
    border-bottom-color: #d39e00;
}

.card-info .card-header {
    background-color: #17a2b8;
    border-bottom-color: #117a8b;
}

.card-danger .card-header {
    background-color: #dc3545;
    border-bottom-color: #bd2130;
}
</style>
@stop
