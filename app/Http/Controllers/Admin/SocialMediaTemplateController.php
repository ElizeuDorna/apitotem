<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SocialMediaTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.social-media.index');
    }
}