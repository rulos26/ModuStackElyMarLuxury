@extends('adminlte::page')

@section('title', 'Acceso Denegado')

@section('content_header')
    <h1 class="m-0">
        <i class="fas fa-ban"></i> Acceso Denegado
    </h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-4">
                    <i class="fas fa-ban fa-5x text-danger"></i>
                </div>

                <h3 class="text-danger">Acceso Denegado</h3>

                <p class="lead">
                    {{ $message ?? 'Tu dirección IP no está autorizada para acceder a este sistema.' }}
                </p>

                <div class="mt-4">
                    <p class="text-muted">
                        Si crees que esto es un error, por favor contacta al administrador del sistema.
                    </p>
                </div>

                <div class="mt-4">
                    <a href="{{ url()->previous() }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@stop



