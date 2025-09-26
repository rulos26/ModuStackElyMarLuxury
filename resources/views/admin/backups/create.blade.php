@extends('adminlte::page')

@section('title', 'Crear Backup')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-plus"></i> Crear Nuevo Backup</h1>
        <a href="{{ route('admin.backups.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Configuración del Backup</h3>
                </div>
                <form method="POST" action="{{ route('admin.backups.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nombre del Backup <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type">Tipo de Backup <span class="text-danger">*</span></label>
                                    <select name="type" id="type" class="form-control @error('type') is-invalid @enderror" required>
                                        <option value="">Seleccionar tipo...</option>
                                        <option value="full" {{ old('type') == 'full' ? 'selected' : '' }}>
                                            Completo (Base de datos + Archivos)
                                        </option>
                                        <option value="database" {{ old('type') == 'database' ? 'selected' : '' }}>
                                            Solo Base de Datos
                                        </option>
                                        <option value="files" {{ old('type') == 'files' ? 'selected' : '' }}>
                                            Solo Archivos
                                        </option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Descripción</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                      rows="3" placeholder="Descripción opcional del backup...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="retention_days">Días de Retención</label>
                                    <input type="number" name="retention_days" id="retention_days"
                                           class="form-control @error('retention_days') is-invalid @enderror"
                                           value="{{ old('retention_days', 30) }}" min="1" max="365">
                                    <small class="form-text text-muted">El backup será eliminado automáticamente después de este período</small>
                                    @error('retention_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="compress" id="compress" class="form-check-input"
                                               {{ old('compress', true) ? 'checked' : '' }}>
                                        <label for="compress" class="form-check-label">
                                            <strong>Comprimir backup</strong>
                                            <br><small class="text-muted">Reduce el tamaño del archivo final</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="encrypt" id="encrypt" class="form-check-input"
                                               {{ old('encrypt') ? 'checked' : '' }}>
                                        <label for="encrypt" class="form-check-label">
                                            <strong>Encriptar backup</strong>
                                            <br><small class="text-muted">Protege el contenido del backup</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-database"></i> Crear Backup
                        </button>
                        <a href="{{ route('admin.backups.index') }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Información sobre tipos de backup -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tipos de Backup</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-database text-primary"></i> Completo</h6>
                        <p class="text-muted small">Incluye base de datos y archivos del sistema. Recomendado para respaldos completos.</p>
                    </div>

                    <div class="mb-3">
                        <h6><i class="fas fa-table text-info"></i> Base de Datos</h6>
                        <p class="text-muted small">Solo la base de datos. Ideal para respaldos rápidos de datos.</p>
                    </div>

                    <div class="mb-3">
                        <h6><i class="fas fa-folder text-success"></i> Archivos</h6>
                        <p class="text-muted small">Solo archivos del sistema. Útil para respaldar configuraciones y uploads.</p>
                    </div>
                </div>
            </div>

            <!-- Recomendaciones -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recomendaciones</h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check text-success"></i>
                            <small>Usa nombres descriptivos</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success"></i>
                            <small>Habilita la compresión para ahorrar espacio</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success"></i>
                            <small>Configura retención apropiada</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success"></i>
                            <small>Considera encriptación para datos sensibles</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        // Actualizar descripción según el tipo seleccionado
        document.getElementById('type').addEventListener('change', function() {
            const type = this.value;
            const nameInput = document.getElementById('name');
            const descriptionInput = document.getElementById('description');

            if (!nameInput.value || nameInput.value.includes('backup_')) {
                const timestamp = new Date().toISOString().split('T')[0];
                nameInput.value = `${type}_backup_${timestamp}`;
            }

            const descriptions = {
                'full': 'Backup completo del sistema incluyendo base de datos y archivos',
                'database': 'Backup de la base de datos únicamente',
                'files': 'Backup de archivos del sistema (storage, public, config)'
            };

            if (descriptions[type] && !descriptionInput.value) {
                descriptionInput.value = descriptions[type];
            }
        });
    </script>
@stop





