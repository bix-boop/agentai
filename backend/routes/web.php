<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

// Minimal built-in app (no Node build required)
Route::get('/app', function () {
    return view('app');
});

// Serve SPA build (CRA) from backend/public/app if exists
Route::get('/{any}', function () {
    $spaIndex = public_path('app/index.html');
    if (File::exists($spaIndex)) {
        return response()->file($spaIndex);
    }
    return view('welcome');
})->where('any', '^(?!api).*$');
