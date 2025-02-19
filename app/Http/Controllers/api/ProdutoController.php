<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use  App\Models\Produto;

class ProdutoController extends Controller
{
    public function index()
    {
        return Produto::all();

    }

    
    public function store(Request $request)
    {
       /* $request->validate([
            'nome' => 'required',
            'preco' => 'required|numeric',
            // Add validation rules for other fields as needed
        ]);*/

        $product = Produto::create($request->all());
        return response()->json($product, 201);
       
    }

    public function show(string $id)
    {
        return Produto::findOrFail($id);
    }

    
    public function update(Request $request, string $id)
    {
        $produto = Produto::findOrFail($id);
        $produto->update($request->all());
        return $produto;
    }

  
    public function destroy(string $id)
    {
        $produto = Produto::findOrFail($id);
        $produto->delete();
        return response()->json(null, 204);
    }
}
