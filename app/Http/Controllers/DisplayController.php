<?php

namespace App\Http\Controllers;

use App\Support\QueueSnapshot;

class DisplayController extends Controller
{
    public function index()
    {
        return view('display.index', ['snapshot' => QueueSnapshot::get()]);
    }

    public function data()
    {
        return response()->json(QueueSnapshot::get());
    }
}
