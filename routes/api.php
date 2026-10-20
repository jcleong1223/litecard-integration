<?php

use App\Http\Controllers\AnalyticController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\FulfillmentController;
use App\Http\Controllers\LogisticsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('')->name('no-auth.')->group(function () {
    /****** Public Authentication Routes (no auth required) ******/
    Route::get('authorizeApp', [AuthController::class, 'authorizeApp'])->name('authorize-app');
    Route::get('getAuthorizedShops', [AuthController::class, 'getAuthorizedShops'])->name('get-authorized-shops');
});

/****** Shop-scoped routes: target shop comes from the X-Shop-Id header ******/
Route::prefix('')->name('shop.')->middleware('shop')->group(function () {



});


Route::middleware(['api', 'client.auth'])->prefix('litecard')->name('litecard.')->group(function () {
    Route::post('v1/webhook-events', [MemberEventController::class, 'store'])
        ->name('webhook-events.store');

});