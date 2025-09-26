@extends('adminlte::page')

@section('template_title')
    Piezas
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('Piezas') }}
                            </span>

                             <div class="float-right">
                                <a href="{{ route('pieces.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                  {{ __('Crear Nueva') }}
                                </a>
                              </div>
                        </div>
                    </div>
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success m-4">
                            <p>{{ $message }}</p>
                        </div>
                    @endif

                    <div class="card-body bg-white">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead">
                                    <tr>
                                        <th>No</th>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Subcategoría</th>
                                        <th>Precio Venta</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pieces as $piece)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            <td>{{ $piece->code }}</td>
                                            <td>{{ $piece->name }}</td>
                                            <td>{{ $piece->category->name ?? 'Sin categoría' }}</td>
                                            <td>{{ $piece->subcategory->name ?? 'Sin subcategoría' }}</td>
                                            <td>${{ number_format($piece->sale_price, 2) }}</td>
                                            <td>
                                                <span class="badge badge-{{ $piece->status === 'disponible' ? 'success' : ($piece->status === 'vendido' ? 'danger' : 'warning') }}">
                                                    {{ ucfirst($piece->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <form action="{{ route('pieces.destroy', $piece->id) }}" method="POST">
                                                    <a class="btn btn-sm btn-primary " href="{{ route('pieces.show', $piece->id) }}"><i class="fa fa-fw fa-eye"></i> {{ __('Ver') }}</a>
                                                    <a class="btn btn-sm btn-success" href="{{ route('pieces.edit', $piece->id) }}"><i class="fa fa-fw fa-edit"></i> {{ __('Editar') }}</a>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="event.preventDefault(); confirm('¿Estás seguro de eliminar?') ? this.closest('form').submit() : false;"><i class="fa fa-fw fa-trash"></i> {{ __('Eliminar') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                {!! $pieces->withQueryString()->links() !!}
            </div>
        </div>
    </div>
@endsection
