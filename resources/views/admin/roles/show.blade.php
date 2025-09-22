@extends('adminlte::page')

@section('title', 'Ver Rol')

@section('content_header')
    <h1>Detalles del Rol: {{ $role->name }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Información del Rol</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">ID</th>
                            <td>{{ $role->id }}</td>
                        </tr>
                        <tr>
                            <th>Nombre</th>
                            <td><span class="badge badge-primary badge-lg">{{ $role->name }}</span></td>
                        </tr>
                        <tr>
                            <th>Fecha de Creación</th>
                            <td>{{ $role->created_at->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>Última Actualización</th>
                            <td>{{ $role->updated_at->format('d/m/Y H:i:s') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Permisos Asignados</h3>
                </div>
                <div class="card-body">
                    @if($role->permissions->count() > 0)
                        @foreach($role->permissions as $permission)
                            <span class="badge badge-success badge-sm mb-1">{{ $permission->name }}</span><br>
                        @endforeach
                    @else
                        <p class="text-muted">No tiene permisos asignados</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Acciones</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Editar Rol
                    </a>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a la Lista
                    </a>
                </div>
            </div>
        </div>
    </div>
@stop
