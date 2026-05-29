<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SkillPageController extends Controller
{
    public function index(): View
    {
        return view('skills.index');
    }
}
