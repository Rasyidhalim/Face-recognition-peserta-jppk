<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\FaceRecognitionController;
use App\Http\Controllers\LoginController;

Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);
// Route untuk menampilkan halaman Vue (Main.vue)
Route::get('/', function () {
    return Inertia::render('Main'); 
});

// Route untuk menerima kiriman foto dari Vue ke Python
// withoutMiddleware digunakan agar tidak kena Error 419 CSRF
Route::post('/api/recognize', [FaceRecognitionController::class, 'recognize'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);