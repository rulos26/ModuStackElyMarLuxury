@extends('adminlte::page')

@section('title', 'Modo Mantenimiento')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-tools"></i> Gestión de Modo Mantenimiento</h1>
    </div>
@stop

@section('content')
    <!-- Estado actual -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card {{ $isActive ? 'card-danger' : 'card-success' }}">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-{{ $isActive ? 'exclamation-triangle' : 'check-circle' }}"></i>
                        Estado Actual: {{ $isActive ? 'ACTIVO' : 'INACTIVO' }}
                    </h3>
                </div>
                <div class="card-body">
                    @if($isActive)
                        <div class="alert alert-warning">
                            <strong>⚠️ Modo Mantenimiento ACTIVO</strong><br>
                            El sitio está actualmente en modo mantenimiento. Solo los usuarios autorizados pueden acceder.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <strong>Tiempo de reintento:</strong> {{ $retryAfter }} segundos<br>
                                @if($message)
                                    <strong>Mensaje personalizado:</strong> {{ $message }}
                                @endif
                            </div>
                            <div class="col-md-6">
                                @if(!empty($contactInfo))
                                    <strong>Información de contacto:</strong><br>
                                    @if(!empty($contactInfo['email']))
                                        📧 {{ $contactInfo['email'] }}<br>
                                    @endif
                                    @if(!empty($contactInfo['phone']))
                                        📞 {{ $contactInfo['phone'] }}<br>
                                    @endif
                                    @if(!empty($contactInfo['support_url']))
                                        🔗 <a href="{{ $contactInfo['support_url'] }}" target="_blank">Centro de Ayuda</a>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="alert alert-success">
                            <strong>✅ Modo Mantenimiento INACTIVO</strong><br>
                            El sitio está disponible normalmente para todos los usuarios.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Panel de Control -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cogs"></i> Panel de Control
                    </h3>
                </div>
                <div class="card-body">
                    @if(!$isActive)
                        <!-- Formulario para activar mantenimiento -->
                        <form method="POST" action="{{ route('admin.maintenance.enable') }}">
                            @csrf
                            <div class="form-group">
                                <label for="retry_after">Tiempo de Reintento (segundos)</label>
                                <input type="number" name="retry_after" id="retry_after"
                                       class="form-control" value="{{ $retryAfter }}"
                                       min="60" max="86400" required>
                                <small class="form-text text-muted">Tiempo estimado para que los usuarios puedan reintentar el acceso</small>
                            </div>

                            <div class="form-group">
                                <label for="message">Mensaje Personalizado (opcional)</label>
                                <textarea name="message" id="message" class="form-control" rows="3"
                                          placeholder="Mensaje que verán los usuarios durante el mantenimiento...">{{ $message }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contact_email">Email de Contacto</label>
                                        <input type="email" name="contact_email" id="contact_email"
                                               class="form-control" value="{{ $contactInfo['email'] ?? '' }}"
                                               placeholder="contacto@ejemplo.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="contact_phone">Teléfono de Contacto</label>
                                        <input type="text" name="contact_phone" id="contact_phone"
                                               class="form-control" value="{{ $contactInfo['phone'] ?? '' }}"
                                               placeholder="+1 234 567 8900">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="support_url">URL de Soporte</label>
                                <input type="url" name="support_url" id="support_url"
                                       class="form-control" value="{{ $contactInfo['support_url'] ?? '' }}"
                                       placeholder="https://soporte.ejemplo.com">
                            </div>

                            <button type="submit" class="btn btn-warning btn-block">
                                <i class="fas fa-tools"></i> Activar Modo Mantenimiento
                            </button>
                        </form>
                    @else
                        <!-- Botón para desactivar mantenimiento -->
                        <form method="POST" action="{{ route('admin.maintenance.disable') }}">
                            @csrf
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-check"></i> Desactivar Modo Mantenimiento
                            </button>
                        </form>

                        <hr>

                        <form method="POST" action="{{ route('admin.maintenance.clear') }}"
                              onsubmit="return confirm('¿Estás seguro de limpiar toda la configuración?')">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-block">
                                <i class="fas fa-trash"></i> Limpiar Configuración
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Usuarios Permitidos -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i> Usuarios Permitidos
                    </h3>
                </div>
                <div class="card-body">
                    @if($allowedUsersData->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Email</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allowedUsersData as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                <form method="POST" action="{{ route('admin.maintenance.remove-user') }}"
                                                      style="display: inline;"
                                                      onsubmit="return confirm('¿Remover usuario de la lista?')">
                                                    @csrf
                                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No hay usuarios permitidos</p>
                    @endif

                    <hr>

                    <!-- Agregar usuario -->
                    <form method="POST" action="{{ route('admin.maintenance.allow-user') }}">
                        @csrf
                        <div class="form-group">
                            <label for="user_id">Agregar Usuario</label>
                            <select name="user_id" id="user_id" class="form-control select2" required>
                                <option value="">Seleccionar usuario...</option>
                                @foreach(App\Models\User::whereNotIn('id', $allowedUsersData->pluck('id'))->get() as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Agregar Usuario
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- IPs Permitidas -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-network-wired"></i> IPs Permitidas
                    </h3>
                </div>
                <div class="card-body">
                    @if(count($allowedIps) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>IP</th>
                                        <th>Tipo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allowedIps as $ip)
                                        <tr>
                                            <td><code>{{ $ip }}</code></td>
                                            <td>
                                                @if(strpos($ip, '/') !== false)
                                                    <span class="badge badge-info">Rango CIDR</span>
                                                @else
                                                    <span class="badge badge-success">IP Individual</span>
                                                @endif
                                            </td>
                                            <td>
                                                <form method="POST" action="{{ route('admin.maintenance.remove-ip') }}"
                                                      style="display: inline;"
                                                      onsubmit="return confirm('¿Remover IP de la lista?')">
                                                    @csrf
                                                    <input type="hidden" name="ip" value="{{ $ip }}">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No hay IPs permitidas</p>
                    @endif

                    <hr>

                    <!-- Agregar IP -->
                    <form method="POST" action="{{ route('admin.maintenance.allow-ip') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="ip">Agregar IP</label>
                                    <input type="text" name="ip" id="ip" class="form-control"
                                           placeholder="192.168.1.1 o 192.168.1.0/24" required>
                                    <small class="form-text text-muted">Soporta IPs individuales y rangos CIDR</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Agregar IP
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Información
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><strong>Usuarios Autorizados:</strong></h6>
                            <ul>
                                <li>Administradores (roles: admin, super-admin)</li>
                                <li>Usuarios agregados manualmente a la lista</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><strong>IPs Permitidas:</strong></h6>
                            <ul>
                                <li>IPs individuales (ej: 192.168.1.100)</li>
                                <li>Rangos CIDR (ej: 192.168.1.0/24)</li>
                                <li>IP actual: <code>{{ request()->ip() }}</code></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        // Inicializar Select2
        $('.select2').select2({
            placeholder: 'Seleccionar usuario...',
            allowClear: true
        });

        // Auto-refresh cada 30 segundos si el modo mantenimiento está activo
        @if($isActive)
            setTimeout(function() {
                location.reload();
            }, 30000);
        @endif
    </script>
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inicializar Select2
            $('.select2').select2({
                placeholder: 'Seleccionar usuario...',
                allowClear: true
            });

            // Auto-refresh cada 30 segundos si el modo mantenimiento está activo
            @if($isActive)
                setTimeout(function() {
                    location.reload();
                }, 30000);
            @endif
        });
    </script>
@stop





