<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// usuários não autenticados
Route::middleware('guest')->group(function(){

    // Login routes
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('authenticate');

    // Register routes
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'store_user'])->name('store_user');
});

// usuários autenticados
Route::middleware('auth')->group(function(){
    Route::get('/', function(){
        echo 'HOME';
    })->name('home');

    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});