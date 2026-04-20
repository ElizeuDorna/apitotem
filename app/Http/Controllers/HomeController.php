<?php

namespace App\Http\Controllers;

use App\Models\HomeCarouselSlide;
use Throwable;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        try {
            $slides = HomeCarouselSlide::query()
                ->whereNull('empresa_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        } catch (Throwable) {
            $slides = collect();
        }

        return view('welcome', [
            'slides' => $slides,
        ]);
    }
}