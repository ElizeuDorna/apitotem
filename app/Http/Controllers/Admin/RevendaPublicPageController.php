<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmpresaPublicPage;
use App\Support\RevendaPublicPageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class RevendaPublicPageController extends Controller
{
    public function edit(): View
    {
        $user = Auth::user();
        $empresa = $user ? RevendaPublicPageContext::resolveTargetEmpresa($user) : null;
        $setupError = null;
        $accessBlocked = false;
        $page = null;

        if (! $empresa) {
            $setupError = $user && $user->isDefaultAdmin()
                ? 'Selecione uma revenda ativa em Empresas para editar a frente publica.'
                : 'A frente publica personalizada esta disponivel apenas para usuarios vinculados a uma revenda.';
        } elseif (! $user->isDefaultAdmin() && ! (bool) ($empresa->public_page_enabled ?? false)) {
            $accessBlocked = true;
            $setupError = 'A personalizacao da frente publica ainda nao foi liberada pelo admin padrao para esta revenda.';
        } else {
            try {
                if (! Schema::hasTable('empresa_public_pages')) {
                    $setupError = 'A tabela da frente publica da revenda ainda nao existe. Execute as migrations pelo Sail.';
                } else {
                    $page = EmpresaPublicPage::query()->firstOrCreate(
                        ['empresa_id' => $empresa->id],
                        $this->defaultPayload($empresa)
                    );
                }
            } catch (Throwable) {
                $setupError = 'Nao foi possivel carregar os dados da frente publica da revenda neste momento.';
            }
        }

        return view('admin.revenda-public-page.edit', [
            'empresa' => $empresa,
            'page' => $page,
            'setupError' => $setupError,
            'accessBlocked' => $accessBlocked,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $empresa = $user ? RevendaPublicPageContext::resolveTargetEmpresa($user) : null;

        if (! $empresa || ! RevendaPublicPageContext::canEdit($user)) {
            return redirect()
                ->route('admin.revenda-public-page.edit')
                ->with('error', 'Voce nao tem permissao para editar esta frente publica.');
        }

        if (! Schema::hasTable('empresa_public_pages')) {
            return redirect()
                ->route('admin.revenda-public-page.edit')
                ->with('error', 'Execute as migrations para habilitar a frente publica da revenda.');
        }

        $validated = $request->validate([
            'hero_title' => ['nullable', 'string', 'max:180'],
            'hero_subtitle' => ['nullable', 'string', 'max:1000'],
            'about_title' => ['nullable', 'string', 'max:180'],
            'about_content' => ['nullable', 'string', 'max:5000'],
            'contact_title' => ['nullable', 'string', 'max:180'],
            'contact_content' => ['nullable', 'string', 'max:5000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_whatsapp' => ['nullable', 'string', 'max:50'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_link' => ['nullable', 'url', 'max:1000'],
        ]);

        $page = EmpresaPublicPage::query()->firstOrCreate(
            ['empresa_id' => $empresa->id],
            $this->defaultPayload($empresa)
        );

        $page->update($validated);

        return redirect()
            ->route('admin.revenda-public-page.edit')
            ->with('status', 'Frente publica da revenda atualizada com sucesso.');
    }

    private function defaultPayload($empresa): array
    {
        return [
            'hero_title' => $empresa->nome,
            'hero_subtitle' => 'Apresente sua revenda com identidade propria, carrossel exclusivo e paginas publicas personalizadas.',
            'about_title' => 'Sobre a nossa revenda',
            'about_content' => 'Use este espaco para apresentar sua empresa, sua proposta comercial e os diferenciais que voce quer destacar ao cliente.',
            'contact_title' => 'Fale conosco',
            'contact_content' => 'Entre em contato para solicitar uma demonstracao, receber uma proposta ou conhecer melhor a frente publica da sua revenda.',
            'contact_email' => $empresa->email,
            'contact_phone' => $empresa->fone,
            'contact_whatsapp' => $empresa->fone,
            'cta_label' => 'Falar com a revenda',
            'cta_link' => null,
        ];
    }
}