@extends('adminlte::page')

@section('title', 'Ver Permiso')

@section('content_header')
    <h1>Detalles del Permiso: {{ $permission->name }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Información del Permiso</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">ID</th>
                            <td>{{ $permission->id }}</td>
                        </tr>
                        <tr>
                            <th>Nombre</th>
                            <td><span class="badge badge-success badge-lg">{{ $permission->name }}</span></td>
                        </tr>
                        <tr>
                            <th>Fecha de Creación</th>
                            <td>{{ $permission->created_at->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>Última Actualización</th>
                            <td>{{ $permission->updated_at->format('d/m/Y H:i:s') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Roles que Tienen este Permiso</h3>
                </div>
                <div class="card-body">
                    @if($permission->roles->count() > 0)
                        @foreach($permission->roles as $role)
                            <span class="badge badge-primary badge-sm mb-1">{{ $role->name }}</span><br>
                        @endforeach
                    @else
                        <p class="text-muted">Ningún rol tiene este permiso</p>
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
                    <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Editar Permiso
                    </a>
                    <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a la Lista
                    </a>
                </div>
            </div>
        </div>
    </div>
@stop
