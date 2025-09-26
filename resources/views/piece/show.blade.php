@extends('adminlte::page')

@section('template_title')
    {{ $piece->name ?? __('Ver') . " " . __('Pieza') }}
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="float-left">
                            <span class="card-title">{{ __('Ver') }} Pieza</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary btn-sm" href="{{ route('pieces.index') }}"> {{ __('Volver') }}</a>
                        </div>
                    </div>

                    <div class="card-body bg-white">

                                <div class="form-group mb-2 mb20">
                                    <strong>Código:</strong>
                                    {{ $piece->code }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Nombre:</strong>
                                    {{ $piece->name }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Descripción:</strong>
                                    {{ $piece->description }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Categoría:</strong>
                                    {{ $piece->category->name ?? 'Sin categoría' }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Subcategoría:</strong>
                                    {{ $piece->subcategory->name ?? 'Sin subcategoría' }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Peso:</strong>
                                    {{ $piece->weight ? $piece->weight . ' kg' : 'No especificado' }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Precio de Costo:</strong>
                                    ${{ number_format($piece->cost_price, 2) }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Precio de Venta:</strong>
                                    ${{ number_format($piece->sale_price, 2) }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Estado:</strong>
                                    <span class="badge badge-{{ $piece->status === 'disponible' ? 'success' : ($piece->status === 'vendido' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($piece->status) }}
                                    </span>
                                </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
