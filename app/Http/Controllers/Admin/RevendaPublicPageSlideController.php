<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeCarouselSlide;
use App\Support\RevendaPublicPageContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RevendaPublicPageSlideController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $empresa = $user ? RevendaPublicPageContext::resolveTargetEmpresa($user) : null;
        $setupError = null;
        $slides = collect();

        if (! $empresa) {
            $setupError = 'Nenhuma revenda ativa foi definida para gerenciar os slides.';
        } elseif (! RevendaPublicPageContext::canEdit($user)) {
            $setupError = 'A personalizacao dos slides desta revenda ainda nao foi liberada pelo admin padrao.';
        } elseif (! Schema::hasTable('home_carousel_slides')) {
            $setupError = 'A tabela de slides ainda nao existe. Execute as migrations pelo Sail.';
        } else {
            $slides = HomeCarouselSlide::query()
                ->where('empresa_id', $empresa->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        return view('admin.revenda-public-page-slides.index', [
            'empresa' => $empresa,
            'slides' => $slides,
            'setupError' => $setupError,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $empresa = $this->authorizedEmpresa();

        if (! $empresa) {
            return redirect()->route('admin.revenda-public-page-slides.index');
        }

        return view('admin.revenda-public-page-slides.create', [
            'empresa' => $empresa,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = $this->authorizedEmpresa();

        if (! $empresa) {
            return redirect()->route('admin.revenda-public-page-slides.index');
        }

        $validated = $this->validateSlide($request);

        HomeCarouselSlide::create($this->buildPayload($request, $validated, $empresa->id));

        return redirect()
            ->route('admin.revenda-public-page-slides.index')
            ->with('status', 'Slide da revenda criado com sucesso.');
    }

    public function edit(HomeCarouselSlide $revendaPublicPageSlide): View|RedirectResponse
    {
        $empresa = $this->authorizedEmpresa();

        if (! $empresa) {
            return redirect()->route('admin.revenda-public-page-slides.index');
        }

        $this->authorizeSlide($revendaPublicPageSlide, $empresa->id);

        return view('admin.revenda-public-page-slides.edit', [
            'empresa' => $empresa,
            'slide' => $revendaPublicPageSlide,
        ]);
    }

    public function update(Request $request, HomeCarouselSlide $revendaPublicPageSlide): RedirectResponse
    {
        $empresa = $this->authorizedEmpresa();

        if (! $empresa) {
            return redirect()->route('admin.revenda-public-page-slides.index');
        }

        $this->authorizeSlide($revendaPublicPageSlide, $empresa->id);

        $validated = $this->validateSlide($request, true, $revendaPublicPageSlide);

        $revendaPublicPageSlide->update($this->buildPayload($request, $validated, $empresa->id, $revendaPublicPageSlide));

        return redirect()
            ->route('admin.revenda-public-page-slides.index')
            ->with('status', 'Slide da revenda atualizado com sucesso.');
    }

    public function destroy(HomeCarouselSlide $revendaPublicPageSlide): RedirectResponse
    {
        $empresa = $this->authorizedEmpresa();

        if (! $empresa) {
            return redirect()->route('admin.revenda-public-page-slides.index');
        }

        $this->authorizeSlide($revendaPublicPageSlide, $empresa->id);

        if ($revendaPublicPageSlide->image_source_type === 'upload' && $revendaPublicPageSlide->image_path) {
            Storage::disk('public')->delete($revendaPublicPageSlide->image_path);
        }

        $revendaPublicPageSlide->delete();

        return redirect()
            ->route('admin.revenda-public-page-slides.index')
            ->with('status', 'Slide da revenda removido com sucesso.');
    }

    private function authorizedEmpresa()
    {
        $user = Auth::user();
        $empresa = $user ? RevendaPublicPageContext::resolveTargetEmpresa($user) : null;

        if (! $empresa || ! RevendaPublicPageContext::canEdit($user)) {
            return null;
        }

        if (! Schema::hasTable('home_carousel_slides')) {
            return null;
        }

        return $empresa;
    }

    private function validateSlide(Request $request, bool $isUpdate = false, ?HomeCarouselSlide $slide = null): array
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'button_label' => ['nullable', 'string', 'max:60'],
            'button_link' => ['nullable', 'url', 'max:1000'],
            'image_source_type' => ['required', Rule::in(['upload', 'link'])],
            'image_url' => ['nullable', 'url', 'max:1000'],
            'image_file' => [$isUpdate ? 'nullable' : 'required_without:image_url', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (($validated['image_source_type'] ?? null) === 'link' && empty($validated['image_url'])) {
            throw ValidationException::withMessages([
                'image_url' => 'Informe uma URL da imagem quando a origem for link.',
            ]);
        }

        if (($validated['image_source_type'] ?? null) === 'upload'
            && ! $request->hasFile('image_file')
            && ! ($slide?->image_path)
        ) {
            throw ValidationException::withMessages([
                'image_file' => 'Envie uma imagem quando a origem for upload.',
            ]);
        }

        return $validated;
    }

    private function buildPayload(Request $request, array $validated, int $empresaId, ?HomeCarouselSlide $slide = null): array
    {
        $payload = [
            'empresa_id' => $empresaId,
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'button_label' => $validated['button_label'] ?? null,
            'button_link' => $validated['button_link'] ?? null,
            'image_source_type' => $validated['image_source_type'],
            'image_url' => null,
            'sort_order' => (int) $validated['sort_order'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if (($validated['image_source_type'] ?? null) === 'link') {
            $payload['image_url'] = $validated['image_url'] ?? null;
            $payload['image_path'] = null;

            if ($slide && $slide->image_source_type === 'upload' && $slide->image_path) {
                Storage::disk('public')->delete($slide->image_path);
            }

            return $payload;
        }

        $payload['image_path'] = $slide?->image_path;

        if ($request->hasFile('image_file')) {
            if ($slide && $slide->image_path) {
                Storage::disk('public')->delete($slide->image_path);
            }

            $payload['image_path'] = $request->file('image_file')->store('home-carousel', 'public');
        }

        return $payload;
    }

    private function authorizeSlide(HomeCarouselSlide $slide, int $empresaId): void
    {
        abort_unless((int) ($slide->empresa_id ?? 0) === $empresaId, 403);
    }
}