<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        $empresa = $this->resolveEmpresaForUser($request);

        $empresas = $user->isDefaultAdmin()
            ? Empresa::query()->orderBy('NOME')->get(['id', 'NOME', 'CNPJ_CPF', 'api_token'])
            : collect();

        return view('admin.api-token', [
            'empresa' => $empresa,
            'token' => $empresa->api_token,
            'empresas' => $empresas,
            'isDefaultAdmin' => $user->isDefaultAdmin(),
        ]);
    }

    public function regenerate(Request $request): RedirectResponse
    {
        $empresa = $this->resolveEmpresaForUser($request);

        $empresa->api_token = Str::random(60);
        $empresa->save();

        return redirect()
            ->route('admin.api-token.index', ['empresa_id' => $empresa->id])
            ->with('success', 'Token da API gerado com sucesso.');
    }

    private function resolveEmpresaForUser(Request $request): Empresa
    {
        $user = Auth::user();

        if ($user->isDefaultAdmin()) {
            $empresaId = $request->input('empresa_id', $request->query('empresa_id'));

            if ($empresaId) {
                $empresa = Empresa::find($empresaId);

                abort_unless($empresa, 404, 'Empresa selecionada não encontrada.');

                return $empresa;
            }

            $empresa = Empresa::query()->orderBy('id')->first();

            abort_unless($empresa, 404, 'Nenhuma empresa cadastrada para gerar token.');

            return $empresa;
        }

        abort_unless($user->empresa_id, 403, 'Usuário sem empresa vinculada.');

        $empresa = Empresa::find($user->empresa_id);

        abort_unless($empresa, 404, 'Empresa vinculada não encontrada.');

        return $empresa;
    }
}
