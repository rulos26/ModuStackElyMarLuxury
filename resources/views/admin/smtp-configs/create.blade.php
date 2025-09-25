@extends('adminlte::page')

@section('title', 'Crear Configuración SMTP')

@section('content_header')
    <h1 class="m-0 text-dark">
        <i class="fas fa-plus"></i> Crear Configuración SMTP
        <small class="text-muted">Nueva configuración de email</small>
    </h1>
@stop

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-server"></i> Configuración SMTP
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.smtp-configs.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            <!-- Tabs -->
            <div class="card-header p-0">
                <ul class="nav nav-tabs" id="configTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="manual-tab" data-toggle="tab" href="#manual" role="tab">
                            <i class="fas fa-cog"></i> Configuración Manual
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="predefined-tab" data-toggle="tab" href="#predefined" role="tab">
                            <i class="fas fa-magic"></i> Configuraciones Predefinidas
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="configTabsContent">
                    <!-- Configuración Manual -->
                    <div class="tab-pane fade show active" id="manual" role="tabpanel">
                        <form action="{{ route('admin.smtp-configs.store') }}" method="POST">
                            @csrf

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Nombre de la Configuración *</label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                               id="name" name="name" value="{{ old('name') }}"
                                               placeholder="ej: Mi Configuración SMTP" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Nombre único para identificar esta configuración</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mailer">Tipo de Mailer *</label>
                                        <select class="form-control @error('mailer') is-invalid @enderror"
                                                id="mailer" name="mailer" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="smtp" {{ old('mailer') == 'smtp' ? 'selected' : '' }}>SMTP</option>
                                            <option value="sendmail" {{ old('mailer') == 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                            <option value="mailgun" {{ old('mailer') == 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                                            <option value="ses" {{ old('mailer') == 'ses' ? 'selected' : '' }}>Amazon SES</option>
                                            <option value="postmark" {{ old('mailer') == 'postmark' ? 'selected' : '' }}>Postmark</option>
                                            <option value="resend" {{ old('mailer') == 'resend' ? 'selected' : '' }}>Resend</option>
                                        </select>
                                        @error('mailer')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Configuración SMTP (mostrar solo si mailer es smtp) -->
                            <div id="smtp-config" style="display: none;">
                                <h5><i class="fas fa-server"></i> Configuración SMTP</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="host">Host SMTP *</label>
                                            <input type="text" class="form-control @error('host') is-invalid @enderror"
                                                   id="host" name="host" value="{{ old('host') }}"
                                                   placeholder="ej: smtp.gmail.com">
                                            @error('host')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="port">Puerto *</label>
                                            <input type="number" class="form-control @error('port') is-invalid @enderror"
                                                   id="port" name="port" value="{{ old('port') }}"
                                                   placeholder="587" min="1" max="65535">
                                            @error('port')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="encryption">Encriptación</label>
                                            <select class="form-control @error('encryption') is-invalid @enderror"
                                                    id="encryption" name="encryption">
                                                <option value="">Ninguna</option>
                                                <option value="tls" {{ old('encryption') == 'tls' ? 'selected' : '' }}>TLS</option>
                                                <option value="ssl" {{ old('encryption') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                            </select>
                                            @error('encryption')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="username">Usuario SMTP</label>
                                            <input type="text" class="form-control @error('username') is-invalid @enderror"
                                                   id="username" name="username" value="{{ old('username') }}"
                                                   placeholder="tu_email@gmail.com">
                                            @error('username')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password">Contraseña SMTP</label>
                                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                                   id="password" name="password"
                                                   placeholder="Tu contraseña o contraseña de aplicación">
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">Para Gmail, usa una contraseña de aplicación</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="timeout">Timeout (segundos)</label>
                                            <input type="number" class="form-control @error('timeout') is-invalid @enderror"
                                                   id="timeout" name="timeout" value="{{ old('timeout', 30) }}"
                                                   min="1" max="300">
                                            @error('timeout')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="local_domain">Dominio Local</label>
                                            <input type="text" class="form-control @error('local_domain') is-invalid @enderror"
                                                   id="local_domain" name="local_domain" value="{{ old('local_domain') }}"
                                                   placeholder="localhost">
                                            @error('local_domain')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Información del Remitente -->
                            <h5><i class="fas fa-user"></i> Información del Remitente</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="from_address">Email del Remitente *</label>
                                        <input type="email" class="form-control @error('from_address') is-invalid @enderror"
                                               id="from_address" name="from_address" value="{{ old('from_address') }}"
                                               placeholder="noreply@midominio.com" required>
                                        @error('from_address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="from_name">Nombre del Remitente *</label>
                                        <input type="text" class="form-control @error('from_name') is-invalid @enderror"
                                               id="from_name" name="from_name" value="{{ old('from_name') }}"
                                               placeholder="Mi Aplicación" required>
                                        @error('from_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="description">Descripción</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          id="description" name="description" rows="3"
                                          placeholder="Descripción opcional de esta configuración">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="is_default" name="is_default" value="1"
                                           {{ old('is_default') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_default">
                                        Establecer como configuración por defecto
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Crear Configuración
                                </button>
                                <a href="{{ route('admin.smtp-configs.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Configuraciones Predefinidas -->
                    <div class="tab-pane fade" id="predefined" role="tabpanel">
                        <div class="row">
                            @foreach($predefinedConfigs as $key => $config)
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-{{ $key === 'gmail' ? 'envelope' : ($key === 'mailtrap' ? 'test-tube' : 'server') }}"></i>
                                                {{ $config['name'] }}
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">{{ $config['description'] }}</p>
                                            <div class="mb-3">
                                                <strong>Configuración:</strong>
                                                <ul class="list-unstyled mt-1">
                                                    <li><strong>Host:</strong> <code>{{ $config['host'] }}</code></li>
                                                    <li><strong>Puerto:</strong> <code>{{ $config['port'] }}</code></li>
                                                    <li><strong>Encriptación:</strong> <code>{{ $config['encryption'] ? strtoupper($config['encryption']) : 'Ninguna' }}</code></li>
                                                </ul>
                                            </div>
                                            <button type="button" class="btn btn-primary btn-sm"
                                                    onclick="createPredefined('{{ $key }}')">
                                                <i class="fas fa-plus"></i> Crear Configuración
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Configuración Predefinida -->
<div class="modal fade" id="predefinedModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="predefinedModalTitle">Crear Configuración Predefinida</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="predefinedForm" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="predefined_type" name="type">

                    <div class="form-group">
                        <label for="predefined_username">Usuario/Email *</label>
                        <input type="text" class="form-control" id="predefined_username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="predefined_password">Contraseña *</label>
                        <input type="password" class="form-control" id="predefined_password" name="password" required>
                        <small class="form-text text-muted">Para Gmail, usa una contraseña de aplicación</small>
                    </div>

                    <div class="form-group">
                        <label for="predefined_from_address">Email del Remitente *</label>
                        <input type="email" class="form-control" id="predefined_from_address" name="from_address" required>
                    </div>

                    <div class="form-group">
                        <label for="predefined_from_name">Nombre del Remitente *</label>
                        <input type="text" class="form-control" id="predefined_from_name" name="from_name" required>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="predefined_is_default" name="is_default" value="1">
                            <label class="custom-control-label" for="predefined_is_default">
                                Establecer como configuración por defecto
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Configuración</button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Mostrar/ocultar configuración SMTP según el mailer seleccionado
    $('#mailer').change(function() {
        if ($(this).val() === 'smtp') {
            $('#smtp-config').show();
            $('#host, #port').prop('required', true);
        } else {
            $('#smtp-config').hide();
            $('#host, #port').prop('required', false);
        }
    });

    // Inicializar estado
    $('#mailer').trigger('change');
});

function createPredefined(type) {
    const configs = @json($predefinedConfigs);
    const config = configs[type];

    $('#predefinedModalTitle').text(`Crear Configuración - ${config.name}`);
    $('#predefined_type').val(type);
    $('#predefinedForm').attr('action', '{{ route("admin.smtp-configs.store-predefined") }}');

    // Limpiar formulario
    $('#predefinedForm')[0].reset();
    $('#predefined_type').val(type);

    $('#predefinedModal').modal('show');
}
</script>
@stop



