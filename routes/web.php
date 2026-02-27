<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Corporate Travel API',
        'docs' => '/api/v1',
    ]);
});
