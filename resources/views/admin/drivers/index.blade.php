@extends('adminlte::page')

@section('title', 'Drivers Dinámicos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-cogs"></i> Drivers Dinámicos</h1>
        <div>
            <button type="button" class="btn btn-info btn-sm" onclick="refreshStatus()">
                <i class="fas fa-sync-alt"></i> Actualizar
            </button>
            <button type="button" class="btn btn-success btn-sm" onclick="restartAllServices()">
                <i class="fas fa-redo"></i> Reiniciar Servicios
            </button>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <!-- Alertas -->
            <div id="alert-container"></div>

            <!-- Estadísticas generales -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-database"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Base de Datos</span>
                            <span class="info-box-number" id="db-driver">-</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-envelope"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Correo</span>
                            <span class="info-box-number" id="mail-driver">-</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-tachometer-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Cache</span>
                            <span class="info-box-number" id="cache-driver">-</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-tasks"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Colas</span>
                            <span class="info-box-number" id="queue-driver">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración de drivers -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-sliders-h"></i> Configuración de Drivers
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach(['database' => 'Base de Datos', 'mail' => 'Correo', 'cache' => 'Cache', 'session' => 'Sesión', 'queue' => 'Colas'] as $service => $label)
                        <div class="col-md-6 mb-4">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-{{ $service === 'database' ? 'database' : ($service === 'mail' ? 'envelope' : ($service === 'cache' ? 'tachometer-alt' : ($service === 'session' ? 'user-clock' : 'tasks'))) }}"></i>
                                        {{ $label }}
                                    </h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" onclick="toggleDriverConfig('{{ $service }}')">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body" id="driver-config-{{ $service }}" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Driver Actual:</label>
                                                <span class="badge badge-info" id="current-driver-{{ $service }}">-</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Cambiar a:</label>
                                                <select class="form-control" id="new-driver-{{ $service }}" onchange="loadDriverConfig('{{ $service }}')">
                                                    <option value="">Seleccionar driver...</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Configuración específica del driver -->
                                    <div id="driver-config-form-{{ $service }}" style="display: none;">
                                        <hr>
                                        <h5>Configuración del Driver</h5>
                                        <div id="config-fields-{{ $service }}"></div>
                                        <div class="mt-3">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="changeDriver('{{ $service }}')">
                                                <i class="fas fa-save"></i> Aplicar Cambio
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="restoreDriver('{{ $service }}')">
                                                <i class="fas fa-undo"></i> Restaurar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Log de cambios -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i> Historial de Cambios
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="changes-log">
                            <thead>
                                <tr>
                                    <th>Servicio</th>
                                    <th>Driver Anterior</th>
                                    <th>Driver Nuevo</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Se llena dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .info-box {
            display: block;
            min-height: 90px;
            background: #fff;
            width: 100%;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
            border-radius: 2px;
            margin-bottom: 15px;
        }
        .info-box-icon {
            border-top-left-radius: 2px;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 2px;
            display: block;
            float: left;
            height: 90px;
            width: 90px;
            text-align: center;
            font-size: 45px;
            line-height: 90px;
            background: rgba(0,0,0,0.2);
        }
        .info-box-content {
            padding: 5px 10px;
            margin-left: 90px;
        }
        .info-box-text {
            text-transform: uppercase;
            font-weight: bold;
            font-size: 14px;
        }
        .info-box-number {
            display: block;
            font-weight: bold;
            font-size: 18px;
        }
        .driver-status {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
@stop

@section('js')
<script>
let driversStatus = {};

$(document).ready(function() {
    loadDriversStatus();
    setInterval(loadDriversStatus, 30000); // Actualizar cada 30 segundos
});

function loadDriversStatus() {
    $.ajax({
        url: '{{ route("admin.drivers.status") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                driversStatus = response.data;
                updateDriversDisplay();
                loadSupportedDrivers();
            }
        },
        error: function() {
            showAlert('Error al cargar estado de drivers', 'danger');
        }
    });
}

function updateDriversDisplay() {
    // Actualizar estadísticas generales
    $('#db-driver').text(driversStatus.database?.current || '-');
    $('#mail-driver').text(driversStatus.mail?.current || '-');
    $('#cache-driver').text(driversStatus.cache?.current || '-');
    $('#queue-driver').text(driversStatus.queue?.current || '-');

    // Actualizar drivers actuales
    Object.keys(driversStatus).forEach(service => {
        const currentDriver = driversStatus[service].current;
        $(`#current-driver-${service}`).text(currentDriver || '-');
    });
}

function loadSupportedDrivers() {
    Object.keys(driversStatus).forEach(service => {
        const supportedDrivers = driversStatus[service].supported || [];
        const select = $(`#new-driver-${service}`);
        select.empty().append('<option value="">Seleccionar driver...</option>');

        supportedDrivers.forEach(driver => {
            select.append(`<option value="${driver}">${driver}</option>`);
        });
    });
}

function loadDriverConfig(service) {
    const selectedDriver = $(`#new-driver-${service}`).val();
    const configForm = $(`#driver-config-form-${service}`);
    const configFields = $(`#config-fields-${service}`);

    if (!selectedDriver) {
        configForm.hide();
        return;
    }

    // Mostrar formulario de configuración
    configForm.show();

    // Generar campos de configuración según el driver
    let configHtml = '';

    switch (service) {
        case 'mail':
            if (selectedDriver === 'smtp') {
                configHtml = `
                    <div class="form-group">
                        <label>Host SMTP:</label>
                        <input type="text" class="form-control" name="host" placeholder="smtp.gmail.com">
                    </div>
                    <div class="form-group">
                        <label>Puerto:</label>
                        <input type="number" class="form-control" name="port" placeholder="587">
                    </div>
                    <div class="form-group">
                        <label>Usuario:</label>
                        <input type="text" class="form-control" name="username">
                    </div>
                    <div class="form-group">
                        <label>Contraseña:</label>
                        <input type="password" class="form-control" name="password">
                    </div>
                    <div class="form-group">
                        <label>Encriptación:</label>
                        <select class="form-control" name="encryption">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                        </select>
                    </div>
                `;
            }
            break;

        case 'database':
            configHtml = `
                <div class="form-group">
                    <label>Host:</label>
                    <input type="text" class="form-control" name="host" placeholder="localhost">
                </div>
                <div class="form-group">
                    <label>Puerto:</label>
                    <input type="number" class="form-control" name="port" placeholder="3306">
                </div>
                <div class="form-group">
                    <label>Base de Datos:</label>
                    <input type="text" class="form-control" name="database">
                </div>
                <div class="form-group">
                    <label>Usuario:</label>
                    <input type="text" class="form-control" name="username">
                </div>
                <div class="form-group">
                    <label>Contraseña:</label>
                    <input type="password" class="form-control" name="password">
                </div>
            `;
            break;

        case 'cache':
        case 'session':
            if (selectedDriver === 'redis') {
                configHtml = `
                    <div class="form-group">
                        <label>Host Redis:</label>
                        <input type="text" class="form-control" name="host" placeholder="127.0.0.1">
                    </div>
                    <div class="form-group">
                        <label>Puerto Redis:</label>
                        <input type="number" class="form-control" name="port" placeholder="6379">
                    </div>
                    <div class="form-group">
                        <label>Base de Datos Redis:</label>
                        <input type="number" class="form-control" name="database" placeholder="0">
                    </div>
                `;
            }
            break;
    }

    configFields.html(configHtml);
}

function changeDriver(service) {
    const selectedDriver = $(`#new-driver-${service}`).val();
    const configData = {};

    // Recopilar datos de configuración
    $(`#config-fields-${service} input, #config-fields-${service} select`).each(function() {
        const name = $(this).attr('name');
        const value = $(this).val();
        if (name && value) {
            configData[name] = value;
        }
    });

    // Validar configuración
    $.ajax({
        url: '{{ route("admin.drivers.validate") }}',
        method: 'POST',
        data: {
            service: service,
            driver: selectedDriver,
            config: configData,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success && response.valid) {
                // Aplicar cambio
                applyDriverChange(service, selectedDriver, configData);
            } else {
                showAlert('Configuración inválida: ' + response.errors.join(', '), 'warning');
            }
        },
        error: function() {
            showAlert('Error al validar configuración', 'danger');
        }
    });
}

function applyDriverChange(service, driver, config) {
    $.ajax({
        url: '{{ route("admin.drivers.change") }}',
        method: 'POST',
        data: {
            service: service,
            driver: driver,
            config: config,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                loadDriversStatus();
                addToChangesLog(service, driversStatus[service].current, driver);
            } else {
                showAlert(response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Error al cambiar driver', 'danger');
        }
    });
}

function restoreDriver(service) {
    $.ajax({
        url: `{{ route("admin.drivers.restore", ":service") }}`.replace(':service', service),
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                loadDriversStatus();
            } else {
                showAlert(response.message, 'warning');
            }
        },
        error: function() {
            showAlert('Error al restaurar driver', 'danger');
        }
    });
}

function restartAllServices() {
    $.ajax({
        url: '{{ route("admin.drivers.restart") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
            } else {
                showAlert(response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Error al reiniciar servicios', 'danger');
        }
    });
}

function toggleDriverConfig(service) {
    $(`#driver-config-${service}`).slideToggle();
}

function refreshStatus() {
    loadDriversStatus();
    showAlert('Estado actualizado', 'info');
}

function addToChangesLog(service, oldDriver, newDriver) {
    const logTable = $('#changes-log tbody');
    const now = new Date().toLocaleString();

    logTable.prepend(`
        <tr>
            <td>${service}</td>
            <td><span class="badge badge-secondary">${oldDriver}</span></td>
            <td><span class="badge badge-success">${newDriver}</span></td>
            <td>${now}</td>
            <td><span class="badge badge-success">Exitoso</span></td>
        </tr>
    `);
}

function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;

    $('#alert-container').html(alertHtml);

    // Auto-ocultar después de 5 segundos
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@stop

