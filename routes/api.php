<?php

use App\Http\Controllers\AnalyticController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\FulfillmentController;
use App\Http\Controllers\LogisticsController;
use App\Http\Controllers\MemberEventController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'client.auth'])->prefix('litecard')->name('litecard.')->group(function () {
    Route::post('v1/litecard-event', [MemberEventController::class, 'store'])
        ->name('litecard-events.store');

});