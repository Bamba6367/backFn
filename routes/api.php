<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\verifyOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;




Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout',[AuthController::class, 'logout']);

  //route otp
  Route::post('/sendOtp', [verifyOtp::class, 'sendOtp']);

  //reoute reset password
  Route::post('/sendResetLink', [PasswordResetController::class, 'sendResetLink']);
Route::post('/resetPassword', [verifyOtp::class, 'resetPassword']);
// Route::middleware('auth:sanctum')->get('/profile', function (Request $request) {
  //     return $request->user();
  // });
  
  // Route::middlewere('auth:sanctum')->group(function () {
//  });