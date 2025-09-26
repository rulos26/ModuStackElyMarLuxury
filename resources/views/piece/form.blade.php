<div class="row padding-1 p-1">
    <div class="col-md-12">

        <div class="form-group mb-2 mb20">
            <label for="code" class="form-label">{{ __('Código') }}</label>
            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $piece?->code) }}" id="code" placeholder="Código de la pieza">
            {!! $errors->first('code', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>

        <div class="form-group mb-2 mb20">
            <label for="name" class="form-label">{{ __('Nombre') }}</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $piece?->name) }}" id="name" placeholder="Nombre de la pieza">
            {!! $errors->first('name', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>

        <div class="form-group mb-2 mb20">
            <label for="description" class="form-label">{{ __('Descripción') }}</label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror" id="description" placeholder="Descripción de la pieza" rows="3">{{ old('description', $piece?->description) }}</textarea>
            {!! $errors->first('description', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>

        <div class="form-group mb-2 mb20">
            <label for="category_id" class="form-label">{{ __('Categoría') }}</label>
            <select name="category_id" class="form-control @error('category_id') is-invalid @enderror" id="category_id">
                <option value="">{{ __('Seleccionar Categoría') }}</option>
                @foreach(\App\Models\Category::all() as $category)
                    <option value="{{ $category->id }}" {{ old('category_id', $piece?->category_id) == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            {!! $errors->first('category_id', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>

        <div class="form-group mb-2 mb20">
            <label for="subcategory_id" class="form-label">{{ __('Subcategoría') }}</label>
            <select name="subcategory_id" class="form-control @error('subcategory_id') is-invalid @enderror" id="subcategory_id">
                <option value="">{{ __('Seleccionar Subcategoría') }}</option>
                @foreach(\App\Models\Subcategory::all() as $subcategory)
                    <option value="{{ $subcategory->id }}" {{ old('subcategory_id', $piece?->subcategory_id) == $subcategory->id ? 'selected' : '' }}>
                        {{ $subcategory->name }}
                    </option>
                @endforeach
            </select>
            {!! $errors->first('subcategory_id', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>

        <div class="form-group mb-2 mb20">
            <label for="weight" class="form-label">{{ __('Peso (kg)') }}</label>
            <input type="number" step="0.01" name="weight" class="form-control @error('weight') is-invalid @enderror" value="{{ old('weight', $piece?->weight) }}" id="weight" placeholder="Peso en kilogramos">
            {!! $errors->first('weight', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>

        <div class="form-group mb-2 mb20">
            <label for="cost_price" class="form-label">{{ __('Precio de Costo') }}</label>
            <input type="number" step="0.01" name="cost_price" class="form-control @error('cost_price') is-invalid @enderror" value="{{ old('cost_price', $piece?->cost_price) }}" id="cost_price" placeholder="Precio de costo">
            {!! $errors->first('cost_price', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>

        <div class="form-group mb-2 mb20">
            <label for="sale_price" class="form-label">{{ __('Precio de Venta') }}</label>
            <input type="number" step="0.01" name="sale_price" class="form-control @error('sale_price') is-invalid @enderror" value="{{ old('sale_price', $piece?->sale_price) }}" id="sale_price" placeholder="Precio de venta">
            {!! $errors->first('sale_price', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>

        <div class="form-group mb-2 mb20">
            <label for="status" class="form-label">{{ __('Estado') }}</label>
            <select name="status" class="form-control @error('status') is-invalid @enderror" id="status">
                <option value="disponible" {{ old('status', $piece?->status) == 'disponible' ? 'selected' : '' }}>{{ __('Disponible') }}</option>
                <option value="apartado" {{ old('status', $piece?->status) == 'apartado' ? 'selected' : '' }}>{{ __('Apartado') }}</option>
                <option value="vendido" {{ old('status', $piece?->status) == 'vendido' ? 'selected' : '' }}>{{ __('Vendido') }}</option>
                <option value="reparacion" {{ old('status', $piece?->status) == 'reparacion' ? 'selected' : '' }}>{{ __('Reparación') }}</option>
            </select>
            {!! $errors->first('status', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>

    </div>
    <div class="col-md-12 mt20 mt-2">
        <button type="submit" class="btn btn-primary">{{ __('Enviar') }}</button>
    </div>
</div>
