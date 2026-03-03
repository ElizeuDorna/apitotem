<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Template;
use App\Models\TemplateItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $query = Template::query()->withCount('items')->orderByDesc('id');

        if (! $user->isDefaultAdmin()) {
            $query->where('empresa_id', $user->empresa_id);
        }

        return view('admin.templates.index', [
            'templates' => $query->paginate(15),
        ]);
    }

    public function create(): View
    {
        $user = Auth::user();

        return view('admin.templates.create', [
            'layouts' => Template::LAYOUTS,
            'isDefaultAdmin' => $user->isDefaultAdmin(),
            'empresas' => $user->isDefaultAdmin()
                ? Empresa::query()->orderBy('NOME')->get(['id', 'NOME', 'CNPJ_CPF'])
                : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'tipo_layout' => ['required', Rule::in(Template::LAYOUTS)],
            'empresa_id' => ['nullable', 'integer', 'exists:empresa,id'],
        ]);

        $empresaId = $user->isDefaultAdmin()
            ? ($validated['empresa_id'] ?? null)
            : $user->empresa_id;

        if (! $empresaId) {
            return redirect()->back()->withInput()->withErrors([
                'empresa_id' => 'Selecione uma empresa para criar o template.',
            ]);
        }

        $template = Template::create([
            'empresa_id' => $empresaId,
            'nome' => $validated['nome'],
            'tipo_layout' => $validated['tipo_layout'],
        ]);

        return redirect()->route('admin.templates.edit', $template)
            ->with('success', 'Template criado com sucesso.');
    }

    public function edit(Template $template): View
    {
        $this->authorizeTemplate($template);

        return view('admin.templates.edit', [
            'template' => $template,
            'layouts' => Template::LAYOUTS,
            'tipos' => TemplateItem::TIPOS,
            'items' => $template->items()->orderBy('ordem')->get(),
        ]);
    }

    public function update(Request $request, Template $template): RedirectResponse
    {
        $this->authorizeTemplate($template);

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'tipo_layout' => ['required', Rule::in(Template::LAYOUTS)],
        ]);

        $template->update($validated);

        return redirect()->route('admin.templates.edit', $template)
            ->with('success', 'Template atualizado.');
    }

    public function destroy(Template $template): RedirectResponse
    {
        $this->authorizeTemplate($template);

        $template->delete();

        return redirect()->route('admin.templates.index')
            ->with('success', 'Template removido.');
    }

    public function addItem(Request $request, Template $template): RedirectResponse
    {
        $this->authorizeTemplate($template);

        $validated = $request->validate([
            'tipo' => ['required', Rule::in(TemplateItem::TIPOS)],
            'ordem' => ['required', 'integer', 'min:1'],
            'conteudo' => ['nullable', 'string'],
            'tempo_exibicao' => ['nullable', 'integer', 'min:1', 'max:600'],
            'tamanho' => ['nullable', 'string', 'max:50'],
        ]);

        TemplateItem::create([
            'template_id' => $template->id,
            'tipo' => $validated['tipo'],
            'ordem' => $validated['ordem'],
            'conteudo' => $validated['conteudo'] ?? null,
            'config_json' => array_filter([
                'tempo_exibicao' => $validated['tempo_exibicao'] ?? null,
                'tamanho' => $validated['tamanho'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
        ]);

        return redirect()->route('admin.templates.edit', $template)
            ->with('success', 'Bloco adicionado ao template.');
    }

    public function deleteItem(Template $template, TemplateItem $item): RedirectResponse
    {
        $this->authorizeTemplate($template);
        abort_unless((int) $item->template_id === (int) $template->id, 404);

        $item->delete();

        return redirect()->route('admin.templates.edit', $template)
            ->with('success', 'Bloco removido.');
    }

    private function authorizeTemplate(Template $template): void
    {
        $user = Auth::user();

        if ($user->isDefaultAdmin()) {
            return;
        }

        abort_unless((int) $user->empresa_id === (int) $template->empresa_id, 403);
    }
}
