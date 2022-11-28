<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterContoller;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Claims\ClaimsController;
use App\Http\Controllers\AccountsController;
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
Route::post('/password_reset/link', [ResetPasswordController::class, 'resetPassword']);
Route::put('/new_password', [ResetPasswordController::class, 'updatePasswordWithCode']);
Route::put('/update_password', [ResetPasswordController::class, 'updatePassword']);
Route::get('/claims', [ClaimsController::class, 'getClaims']);
Route::get('/claims/client', [ClaimsController::class, 'clientClaims']);
Route::get('/accounts', [AccountsController::class, 'accounts']);
Route::get('/accounts/insurance/ipp', [AccountsController::class, 'individualIppAccount']);
Route::get('/accounts/insurance/easy_cover', [AccountsController::class, 'individualEasyCoverAccount']);
Route::get('/accounts/insurance/motor', [AccountsController::class, 'individualMotorAccount']);
Route::get('/accounts/pension', [AccountsController::class, 'individualPensionAccount']);
