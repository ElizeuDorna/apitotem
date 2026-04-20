<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeCarouselSlide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class HomeCarouselController extends Controller
{
    public function index(): View
    {
        $this->authorizeDefaultAdmin();

        $slides = collect();
        $setupError = null;

        try {
            if (! Schema::hasTable('home_carousel_slides')) {
                $setupError = 'A tabela do carrossel ainda não existe. Execute a migration para habilitar esta tela.';
            } else {
                $slides = HomeCarouselSlide::query()
                    ->whereNull('empresa_id')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();
            }
        } catch (Throwable) {
            $setupError = 'Nao foi possivel acessar o banco de dados para carregar o carrossel. Verifique se o ambiente Laravel/Sail esta ativo e rode a migration.';
        }

        return view('admin.home-carousel.index', [
            'slides' => $slides,
            'setupError' => $setupError,
        ]);
    }

    public function create(): View
    {
        $this->authorizeDefaultAdmin();

        if (! $this->carouselTableReady()) {
            return redirect()
                ->route('admin.home-carousel.index')
                ->with('error', 'O carrossel ainda nao esta pronto para edicao. Execute a migration primeiro.');
        }

        return view('admin.home-carousel.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeDefaultAdmin();

        if (! $this->carouselTableReady()) {
            return redirect()
                ->route('admin.home-carousel.index')
                ->with('error', 'O carrossel ainda nao esta pronto para salvar slides. Execute a migration primeiro.');
        }

        $validated = $this->validateSlide($request);

        HomeCarouselSlide::create($this->buildPayload($request, $validated));

        return redirect()
            ->route('admin.home-carousel.index')
            ->with('status', 'Slide criado com sucesso.');
    }

    public function edit(HomeCarouselSlide $homeCarousel): View
    {
        $this->authorizeDefaultAdmin();
        abort_unless($homeCarousel->empresa_id === null, 404);

        if (! $this->carouselTableReady()) {
            return redirect()
                ->route('admin.home-carousel.index')
                ->with('error', 'O carrossel ainda nao esta pronto para edicao. Execute a migration primeiro.');
        }

        return view('admin.home-carousel.edit', [
            'slide' => $homeCarousel,
        ]);
    }

    public function update(Request $request, HomeCarouselSlide $homeCarousel): RedirectResponse
    {
        $this->authorizeDefaultAdmin();
        abort_unless($homeCarousel->empresa_id === null, 404);

        if (! $this->carouselTableReady()) {
            return redirect()
                ->route('admin.home-carousel.index')
                ->with('error', 'O carrossel ainda nao esta pronto para atualizacao. Execute a migration primeiro.');
        }

        $validated = $this->validateSlide($request, true, $homeCarousel);

        $homeCarousel->update($this->buildPayload($request, $validated, $homeCarousel));

        return redirect()
            ->route('admin.home-carousel.index')
            ->with('status', 'Slide atualizado com sucesso.');
    }

    public function destroy(HomeCarouselSlide $homeCarousel): RedirectResponse
    {
        $this->authorizeDefaultAdmin();
        abort_unless($homeCarousel->empresa_id === null, 404);

        if (! $this->carouselTableReady()) {
            return redirect()
                ->route('admin.home-carousel.index')
                ->with('error', 'O carrossel ainda nao esta pronto para exclusao. Execute a migration primeiro.');
        }

        if ($homeCarousel->image_source_type === 'upload' && $homeCarousel->image_path) {
            Storage::disk('public')->delete($homeCarousel->image_path);
        }

        $homeCarousel->delete();

        return redirect()
            ->route('admin.home-carousel.index')
            ->with('status', 'Slide removido com sucesso.');
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
        ], [
            'image_file.required_without' => 'Envie uma imagem quando a origem for upload.',
            'image_url.url' => 'Informe uma URL válida para a imagem.',
            'button_link.url' => 'Informe uma URL válida para o botão.',
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

    private function buildPayload(Request $request, array $validated, ?HomeCarouselSlide $slide = null): array
    {
        $sourceType = (string) $validated['image_source_type'];
        $payload = [
            'empresa_id' => null,
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'button_label' => $validated['button_label'] ?? null,
            'button_link' => $validated['button_link'] ?? null,
            'image_source_type' => $sourceType,
            'image_url' => null,
            'sort_order' => (int) $validated['sort_order'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if ($sourceType === 'link') {
            $payload['image_url'] = $validated['image_url'] ?? null;

            if ($slide && $slide->image_source_type === 'upload' && $slide->image_path) {
                Storage::disk('public')->delete($slide->image_path);
            }

            $payload['image_path'] = null;

            return $payload;
        }

        $payload['image_url'] = null;
        $payload['image_path'] = $slide?->image_path;

        if ($request->hasFile('image_file')) {
            if ($slide && $slide->image_path) {
                Storage::disk('public')->delete($slide->image_path);
            }

            $payload['image_path'] = $request->file('image_file')->store('home-carousel', 'public');
        }

        return $payload;
    }

    private function authorizeDefaultAdmin(): void
    {
        abort_unless(Auth::user()?->isDefaultAdmin(), 403);
    }

    private function carouselTableReady(): bool
    {
        try {
            return Schema::hasTable('home_carousel_slides');
        } catch (Throwable) {
            return false;
        }
    }
}