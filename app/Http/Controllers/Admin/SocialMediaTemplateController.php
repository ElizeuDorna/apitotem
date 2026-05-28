<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialMediaTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $activeTab = $request->routeIs('admin.social-media.whatsapp.index')
            ? 'whatsapp'
            : 'social';

        return view('admin.social-media.index', [
            'activeTab' => $activeTab,
        ]);
    }
}