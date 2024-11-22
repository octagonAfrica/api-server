<?php

use App\Http\Controllers\v2\Auth\LoginController;
use App\Http\Controllers\v2\Auth\RegisterContoller;
use App\Http\Controllers\v2\Auth\SendOTPController;
use App\Http\Controllers\v2\Auth\EmailVerificationController;
use App\Http\Controllers\v2\Auth\DeleteUserController;
use App\Http\Controllers\v2\Auth\ResetPasswordController;
use App\Http\Controllers\Claims\ClaimsController;
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\IncomeDrawDownController;
use App\Http\Controllers\MemberStatementController;
use App\Http\Controllers\StatementsController;
use App\Http\Controllers\TwoWaySMSController;
use App\Http\Controllers\ContributionsController;
use App\Http\Controllers\EventsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PhpParser\Node\Stmt\Return_;
use App\Http\Middleware\ResponseMetaData;

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
Route::post('/user/delete', [DeleteUserController::class, 'deleteUser']);
Route::post('/register', [RegisterContoller::class, 'registerUser']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/login/verification', [LoginController::class, 'sendLoginVerification']);
Route::post('/login/otp', [LoginController::class, 'otp']);
Route::post('/loginwithPhone', [LoginController::class, 'loginWithPhoneNumber']);

Route::post('/phone/sendotp', [SendOTPController::class, 'sendOTPToPhone'])->middleware(ResponseMetaData::class);
//Email OTP routes
Route::post('/email/sendotp', [SendOTPController::class, 'sendOTPToEmail']);
Route::post('/email/verifyotp', [SendOTPController::class, 'verifyEmailOTP']);

Route::post('/phone/verifyotp', [SendOTPController::class, 'verifyRegistrationOTP']);
Route::post('/register/verify_email', [EmailVerificationController::class, 'verifyEmail']);
Route::post('/password_reset', [ResetPasswordController::class, 'resetPasswordWithOtp']);
Route::post('/password_reset/link', [ResetPasswordController::class, 'resetPasswordWithLink']);
Route::put('/new_password', [ResetPasswordController::class, 'updatePasswordWithCode']);
Route::put('/update_password', [ResetPasswordController::class, 'updatePassword']);
Route::post('/verify_otp', [ResetPasswordController::class, 'verifyOtp']);
Route::post('/userprofile/update', [RegisterContoller::class, 'updateMemberProfile']);
Route::post('/userprofile/profile', [RegisterContoller::class, 'MemberProfile']);
Route::post('/userprofile/bio', [RegisterContoller::class, 'memberBioDetails']);
Route::post('/userprofile/updatebio', [RegisterContoller::class, 'updateMemberBioDetails']);
Route::post('/user/adduserratings', [RegisterContoller::class, 'userRatings']);
Route::post('/user/onboardnewclients', [RegisterContoller::class, 'memberOnBoarding']);
Route::post('/user/activity', [RegisterContoller::class, 'userlogs']);
Route::post('/claims/showMemberDetails', [ClaimsController::class, 'showMemberDetails']);
Route::post('/claims/verifyClaimsOTP', [ClaimsController::class, 'verifyClaimsOTP']);
Route::get('/claims', [ClaimsController::class, 'getClaims']);
Route::post('/claims/sendClaimsOTP', [ClaimsController::class, 'sendClaimOTP']);
Route::get('/claims/client', [ClaimsController::class, 'clientClaims']);
Route::post('/claims/addnewwithdrawal', [ClaimsController::class, 'addNewWithdrawal']);
Route::post('/claims/addnewclaim', [ClaimsController::class, 'addNewClaim']);
Route::get('/claims/banks', [ClaimsController::class, 'fetchBanks']);
Route::get('/claims/getclaims', [ClaimsController::class, 'getMemberClaims']);
Route::post('/claims/addbankdetails', [ClaimsController::class, 'addBankDetails']);
Route::post('/claims/showdocuments', [ClaimsController::class, 'showDocuments']);
Route::post('/claims/checkbankdetails', [ClaimsController::class, 'checkBankDetails']);
Route::post('/claims/listclaims', [ClaimsController::class, 'listClaims']);
Route::get('/accounts', [AccountsController::class, 'accounts']);
Route::get('/accounts/insurance/ipp', [AccountsController::class, 'individualIppAccount']);
Route::get('/accounts/insurance/easy_cover', [AccountsController::class, 'individualEasyCoverAccount']);
Route::get('/accounts/insurance/motor', [AccountsController::class, 'individualMotorAccount']);
Route::get('/accounts/pension', [AccountsController::class, 'individualPensionAccount']);
Route::get('/accounts/pension/periods', [AccountsController::class, 'periods']);
Route::get('/accounts/pension/periods/twoway', [AccountsController::class, 'twowayPeriods']);
Route::get('/accounts/pension/twoway/periodsid', [AccountsController::class, 'twowayPeriodID']);
Route::get('/accountsid', [AccountsController::class, 'showAccounts']);
Route::get('/accounts/pension/transactions', [AccountsController::class, 'individualPensionAccountTransactions']);
Route::get('/accounts/member_statement',[MemberStatementController::class, 'MemberStatement']);
Route::get('/accounts/memberstatement',[StatementsController::class, 'MemberStatements']);
Route::get('/twowaysms',[TwoWaySMSController::class, 'fetchUser']);
Route::post('/contributions/icollect',[ContributionsController::class, 'recievePayments']);
Route::post('/contributions/deposits', [ContributionsController::class, 'cellulantDeposits']);
Route::post('/contributions/callback', [ContributionsController::class, 'cellulantCallBackURL']);
Route::post('/contributions/lipanampesa', [ContributionsController::class, 'lipaNaMpesa']);
Route::get('/accounts/balance', [AccountsController::class, 'accountBalance']);
Route::get('/events/allevents', [EventsController::class, 'allEvents']);
Route::post('/events/like', [EventsController::class, 'likeEvent']);

Route::post('/iddf', [IncomeDrawDownController::class, 'IDDF']);
