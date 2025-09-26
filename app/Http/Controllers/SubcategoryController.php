<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Requests\SubcategoryRequest;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class SubcategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $subcategories = Subcategory::with('category')->paginate();

        return view('subcategory.index', compact('subcategories'))
            ->with('i', ($request->input('page', 1) - 1) * $subcategories->perPage());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $subcategory = new Subcategory();

        return view('subcategory.create', compact('subcategory'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SubcategoryRequest $request): RedirectResponse
    {
        Subcategory::create($request->validated());

        return Redirect::route('subcategories.index')
            ->with('success', 'Subcategoría creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id): View
    {
        $subcategory = Subcategory::find($id);

        return view('subcategory.show', compact('subcategory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): View
    {
        $subcategory = Subcategory::find($id);

        return view('subcategory.edit', compact('subcategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SubcategoryRequest $request, Subcategory $subcategory): RedirectResponse
    {
        $subcategory->update($request->validated());

        return Redirect::route('subcategories.index')
            ->with('success', 'Subcategoría actualizada exitosamente');
    }

    public function destroy($id): RedirectResponse
    {
        Subcategory::find($id)->delete();

        return Redirect::route('subcategories.index')
            ->with('success', 'Subcategoría eliminada exitosamente');
    }
}
