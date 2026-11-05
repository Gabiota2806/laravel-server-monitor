<?php

namespace App\Http\Controllers;

use App\Services\ServerMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ServerController extends Controller
{
    // Laravel crea el ServerMetrics y lo pasa solo (inyección de dependencias)
    public function index(ServerMetrics $metrics): View
    {
        return view('servidor', ['data' => $metrics->snapshot()]);
    }

    public function api(ServerMetrics $metrics): JsonResponse
    {
        return response()->json($metrics->snapshot());
    }
}

