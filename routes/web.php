<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/index',[ProductController::class,'index']);
Route::post('/checkout',[ProductController::class,'checkout'])->name('checkout');
Route::get('/success',[ProductController::class, 'success'])->name('checkout.success');
Route::get('/cancel',[ProductController::class, 'cancel'])->name('checkout.cancel');

Route::post('/webhook',[ProductController::class, 'webhook'])->name('checkout.webhook');