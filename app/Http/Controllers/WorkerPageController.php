<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class WorkerPageController extends Controller
{
    public function index(): View
    {
        return view('workers.index');
    }
}
