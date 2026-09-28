<?php

namespace Lara\App\Http\Controllers\Front\Special;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * @return Application|Factory|View
     */
    public function show(Request $request)
    {

        if (Auth::check()) {
            $theme = (bool) Auth::user()->profile->dark_mode ? 'lara-theme-dark' : 'lara-theme-light';
        } else {
            $theme = 'lara-theme-light';
        }

        return json_encode(['theme' => $theme]);

    }
}
