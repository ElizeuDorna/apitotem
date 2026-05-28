<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialMediaTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $activeTab = $request->routeIs('admin.social-media.whatsapp.index')
            ? 'whatsapp'
            : 'social';

        $user = $request->user();

        return view('admin.social-media.index', [
            'activeTab' => $activeTab,
            'canAccessMetaTab' => (bool) $user?->hasMenuAccess(User::MENU_REDE_SOCIAL_META),
            'canAccessWhatsAppTab' => (bool) $user?->hasMenuAccess(User::MENU_REDE_SOCIAL_WHATSAPP),
        ]);
    }
}