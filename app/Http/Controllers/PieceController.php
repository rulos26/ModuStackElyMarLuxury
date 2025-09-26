<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Requests\PieceRequest;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PieceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $pieces = Piece::with(['category', 'subcategory'])->paginate();

        return view('piece.index', compact('pieces'))
            ->with('i', ($request->input('page', 1) - 1) * $pieces->perPage());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $piece = new Piece();

        return view('piece.create', compact('piece'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PieceRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();

        // Log para debug
        Log::info('Datos validados para crear pieza:', $validatedData);

        $piece = Piece::create($validatedData);

        // Log para verificar que se creó correctamente
        Log::info('Pieza creada con ID:', ['id' => $piece->id, 'data' => $piece->toArray()]);

        return Redirect::route('pieces.index')
            ->with('success', 'Pieza creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id): View
    {
        $piece = Piece::find($id);

        return view('piece.show', compact('piece'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): View
    {
        $piece = Piece::find($id);

        return view('piece.edit', compact('piece'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PieceRequest $request, Piece $piece): RedirectResponse
    {
        $validatedData = $request->validated();

        // Log para debug
        Log::info('Datos validados para actualizar pieza:', $validatedData);
        Log::info('Pieza antes de actualizar:', $piece->toArray());

        $piece->update($validatedData);

        // Log para verificar que se actualizó correctamente
        Log::info('Pieza después de actualizar:', $piece->fresh()->toArray());

        return Redirect::route('pieces.index')
            ->with('success', 'Pieza actualizada exitosamente');
    }

    public function destroy($id): RedirectResponse
    {
        Piece::find($id)->delete();

        return Redirect::route('pieces.index')
            ->with('success', 'Pieza eliminada exitosamente');
    }
}
