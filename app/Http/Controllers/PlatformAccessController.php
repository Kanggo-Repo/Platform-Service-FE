<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PlatformAccessController extends Controller
{
    public function pending(): View
    {
        return view('auth.access-pending');
    }
}
