<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Support\EmpresaContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConfiguracaoController extends Controller
{
    /**
     * Show the configuration form.
     */
    public function edit()
    {
        $empresaId = $this->resolveEmpresaId();

        $config = Configuracao::firstOrCreate([
            'empresa_id' => $empresaId,
        ], []);

        $config = $config->fresh();

        return view('admin.configuracao', compact('config'));
    }

    /**
     * Handle form submission and update configuration.
     */
    public function update(Request $request)
    {
        $empresaId = $this->resolveEmpresaId();

        $validated = $request->validate([
            'apiUrl' => 'nullable|string|max:500',
            'priceColor' => 'nullable|string|max:7',
            'offerColor' => 'nullable|string|max:7',
            'rowBackgroundColor' => 'nullable|string|max:9',
            'showBorder' => 'nullable|boolean',
            'borderColor' => 'nullable|string|max:7',
            'useGradient' => 'nullable|boolean',
            'gradientStartColor' => 'nullable|string|max:7',
            'gradientEndColor' => 'nullable|string|max:7',
            'gradientStop1' => 'nullable|numeric|min:0|max:1',
            'gradientStop2' => 'nullable|numeric|min:0|max:1',
            'appBackgroundColor' => 'nullable|string|max:9',
            'isMainBorderEnabled' => 'nullable|boolean',
            'mainBorderColor' => 'nullable|string|max:7',
            'showImage' => 'nullable|boolean',
            'imageSize' => 'nullable|integer|min:16|max:512',
            'isPaginationEnabled' => 'nullable|boolean',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'paginationInterval' => 'nullable|integer|min:1|max:60',
            'apiRefreshInterval' => 'nullable|integer|min:5|max:3600',
        ]);

        Configuracao::updateOrCreate(
            ['empresa_id' => $empresaId],
            $validated
        );

        return redirect()
            ->back()
            ->with('success', 'Configuração atualizada com sucesso.');
    }

    private function resolveEmpresaId(): ?int
    {
        $user = Auth::user();

        return (int) EmpresaContext::requireEmpresaId($user);
    }
}
