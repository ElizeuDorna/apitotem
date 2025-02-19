<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use  App\Models\Departamento;
class DepartamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Departamento::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $Departamento = Departamento::create($request->all());
        return response()->json($Departamento, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Departamento::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $Departamento = Departamento::findOrFail($id);
        $Departamento->update($request->all());
        return $Departamento;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $Produto = Departamento::findOrFail($id);
        $Produto->delete();
        return response()->json(null, 204);
    }
}
