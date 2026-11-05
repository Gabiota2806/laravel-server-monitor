<?php

use App\Http\Controllers\ServerController;
use Illuminate\Support\Facades\Route;

Route::get('/metrics', [ServerController::class, 'api'])
    ->middleware('throttle:60,1'); // máximo 60 pedidos por minuto

