<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// OAuth callback registered in TikTok Partner Center (TIKTOK_REDIRECT_URI)
Route::get('tiktok/oauth/callback', [AuthController::class, 'getAuthorizedShops'])->name('tiktok.oauth.callback');


Route::get('tiktok/auth', function () {
    return [
        'auth_base_url' => env('TIKTOK_AUTH_BASE_URL'),
        'api_base_url' => env('TIKTOK_API_BASE_URL'),
    ];
});