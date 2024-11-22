<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmailVerificationController extends Controller
{
    public function verifyEmail(Request $request)
    {
        $rules = [
            'email' => 'required', 
        ];
        // Validate request
        $validator = Validator::make($request->all(), $rules);
        $data = $request->all();
        $email = $data['email'];
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 400,
                    'success' => false,
                    'message' => "Invalid request. Please input your National Id and password."
                ], 400
            );
        } else {
            $user_exist = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_email = '$email' AND is_email_verified = 0");
            if (count($user_exist) > 0) {
                $user = $user_exist[0];
                    //where is_email_verified =1 maeans that the user has a verified Account
                    $update_status = DB::connection('mydb_sqlsrv')->update("UPDATE sys_users_tb SET is_email_verified =1 WHERE user_email ='$email'");
                    if ($update_status) {
                        return response()->json(
                            [
                                'status' => 200,
                                'operation' => 'success',
                                'message' => "User '$email' verified successfully."
                            ], 200
                        );
                    } else {
                        return response()->json(
                            [
                                'status' => 500,
                                'success' => false,
                                'message' => 'Internal Server Error'
                            ], 500
                        );
                    }   
            } else {
                return response()->json(
                    [
                        'success' => false,
                        'message' => "Verification failed. User not found/Email Already Verified."
                    ],
                    401
                );
            }
        }
    }
}
