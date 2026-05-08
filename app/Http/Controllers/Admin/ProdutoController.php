<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Services\ProdutoService;
use Illuminate\Support\Facades\Auth;

class ProdutoController extends Controller
{
    public function index(ProdutoService $produtoService)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $produtoService->ensureIndexAccess($user);

        return view('admin.produtos.index');
    }

    public function edit(Produto $produto, ProdutoService $produtoService)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $produtoService->authorizeProdutoAccess($user, $produto);

        return view('admin.produtos.edit', compact('produto'));
    }
}
