@extends('adminlte::page')

@section('template_title')
    {{ __('Crear') }} Subcategoría
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">

                <div class="card card-default">
                    <div class="card-header">
                        <span class="card-title">{{ __('Crear') }} Subcategoría</span>
                    </div>
                    <div class="card-body bg-white">
                        <form method="POST" action="{{ route('subcategories.store') }}"  role="form" enctype="multipart/form-data">
                            @csrf

                            @include('subcategory.form')

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
