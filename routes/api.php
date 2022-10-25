<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterContoller;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PhpParser\Node\Stmt\Return_;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/register', [RegisterContoller::class, 'registerUser']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/login/verification', [LoginController::class, 'sendLoginVerification']);
Route::post('/login/otp', [LoginController::class, 'otp']);
Route::post('/password_reset', [ResetPasswordController::class, 'resetPassword']);
