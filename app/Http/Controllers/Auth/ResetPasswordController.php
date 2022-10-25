<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ResetPasswordController extends Controller
{
    public function resetPassword(Request $request)
    {
        $email = $request['email'];

        $token = openssl_random_pseudo_bytes(16);
        //Convert the binary data into hexadecimal representation.
        $token = bin2hex($token);

        $sql_user = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb where user_email = '$email'");

        $user = $sql_user[0];
        $name = $user->user_full_names;
        $user_id = $user->user_id;
        $datecreated = date('Y-m-d H:i:s');
        $expirydate = date('Y-m-d H:i:s', strtotime($datecreated . ' + 1 days'));

        $insert_token = DB::connection('mydb_sqlsrv')->insert('INSERT INTO tokens(token_key,user_id,expire_date,created_date) values (?,?,?,?)', [$token, $user_id, $expirydate, $datecreated]);
        if ($insert_token) {
            $mailData = [
                'token' => $token,
                'name' => $name
            ];
            try {
                // Send verification Email with token
                Mail::to($email)->send(new PasswordResetMail($mailData));
                return response()->json(
                    [
                        'operation' =>  'success',
                        'message' =>  "Password reset link for $name sent successfully to $email"
                    ],
                    200
                );
            } catch (\Throwable $th) {
                return response()->json(
                    [
                        'operation' =>  'fail',
                        'message' =>  'Unable to send password reset link'
                    ],
                    400
                );
            }
        }
    }

    // Update password
    public function newPassword(Request $request)
    {
        $code = $request['code'];
        $password = $request['password'];

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $check_code = DB::connection('mydb_sqlsrv')->select("SELECT * FROM tokens where token_key = '$code'");

        if (count($check_code) > 0) {
            $code_expiry = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM tokens where token_key = '$code' AND (DATEDIFF(second, expire_date, GETDATE()) / 3600.0)<=24");
            if (count($code_expiry)) {
                $data = $code_expiry[0];
                $userid = $data->user_id;
                $update_password = DB::connection('mydb_sqlsrv')->table('sys_users_tb')
                    ->where('user_id', $userid)
                    ->update(['user_enc_pwd' => $hashed_password]);

                if ($update_password) {
                    return response()->json([

                        'operation' => 'success',
                        'message' => 'Password reset succesfully'
                    ], 200);
                } else {
                    return response()->json([

                        'operation' => 'fail',
                        'message' => 'Failed to update Password. Check your post variables', 'error' => $update_password

                    ], 403);
                }
            } else {
                return response()->json([
                    'operation' =>  'fail',
                    'message' =>  'Password reset link has expired'
                ], 401);
            }
        } else {
            return response()->json([
                'operation' =>  'fail',
                'message' =>  'A record with that code does not exist in the database. Check your variables and try again'
            ], 402);
        }
    }
}
