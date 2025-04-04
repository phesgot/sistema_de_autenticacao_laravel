<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MainController;
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

    // new user confirmation
    Route::get('/new_user_confirmation/{token}', [AuthController::class, 'new_user_conformation'])->name('new_user_conformation');
});

// usuários autenticados
Route::middleware('auth')->group(function(){
    Route::get('/', [MainController::class, 'home'])->name('home');

    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});