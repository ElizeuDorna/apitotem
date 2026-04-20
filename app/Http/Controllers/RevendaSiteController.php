<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\EmpresaPublicPage;
use App\Models\HomeCarouselSlide;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class RevendaSiteController extends Controller
{
    public function home(string $slug): View|Response
    {
        $empresa = $this->resolveEmpresa($slug);

        if (! $empresa) {
            abort(404);
        }

        return view('revenda-public.home', $this->buildPageData($empresa, $slug));
    }

    public function about(string $slug): View|Response
    {
        $empresa = $this->resolveEmpresa($slug);

        if (! $empresa) {
            abort(404);
        }

        return view('revenda-public.about', $this->buildPageData($empresa, $slug));
    }

    public function contact(string $slug): View|Response
    {
        $empresa = $this->resolveEmpresa($slug);

        if (! $empresa) {
            abort(404);
        }

        return view('revenda-public.contact', $this->buildPageData($empresa, $slug));
    }

    private function resolveEmpresa(string $slug): ?Empresa
    {
        try {
            if (! Schema::hasColumn('empresa', 'public_page_slug') || ! Schema::hasColumn('empresa', 'public_page_enabled')) {
                return null;
            }

            return Empresa::query()
                ->where('nivel_acesso', Empresa::NIVEL_REVENDA)
                ->where('public_page_enabled', true)
                ->where('public_page_slug', $slug)
                ->first();
        } catch (Throwable) {
            return null;
        }
    }

    private function buildPageData(Empresa $empresa, string $slug): array
    {
        $page = null;
        $slides = collect();

        try {
            if (Schema::hasTable('empresa_public_pages')) {
                $page = EmpresaPublicPage::query()->where('empresa_id', $empresa->id)->first();
            }

            if (Schema::hasTable('home_carousel_slides')) {
                $slides = HomeCarouselSlide::query()
                    ->where('empresa_id', $empresa->id)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();
            }
        } catch (Throwable) {
            $page = $page;
        }

        return [
            'empresa' => $empresa,
            'page' => $page,
            'slides' => $slides,
            'slug' => $slug,
        ];
    }
}