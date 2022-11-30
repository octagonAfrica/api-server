<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Mail\PasswordResetMailWithLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;

class ResetPasswordController extends Controller
{
    public function resetPasswordWithLink(Request $request)
    {
        $email = $request['email'];
        if (!$email || empty($email)) {
            return response()->json([
                'status' => 400,
                'operation' =>  'fail',
                'message' =>  'Email required.'
            ], 400);
        } else {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $token = openssl_random_pseudo_bytes(16);
                //Convert the binary data into hexadecimal representation.
                $token = bin2hex($token);

                $sql_user = DB::connection('mydb_sqlsrv')
                    ->select("SELECT TOP 1 * FROM sys_users_tb where user_email = '$email'");
                if ($sql_user) {
                    $user = $sql_user[0];
                    $name = $user->user_full_names;
                    $user_id = $user->user_id;
                    $datecreated = date('Y-m-d H:i:s');
                    $expirydate = date('Y-m-d H:i:s', strtotime($datecreated . ' + 1 days'));

                    $insert_token = DB::connection('mydb_sqlsrv')
                        ->insert('INSERT INTO tokens(token_key,user_id,expire_date,created_date)
             values (?,?,?,?)', [$token, $user_id, $expirydate, $datecreated]);
                    if ($insert_token) {
                        $mailData = [
                            'token' => $token,
                            'name' => $name
                        ];
                        try {
                            // Send verification Email with token
                            Mail::to($email)->send(new PasswordResetMailWithLink($mailData));
                            return response()->json(
                                [
                                    'status' => 200,
                                    'operation' =>  'success',
                                    'message' =>  "Password reset link sent successfully to $email"
                                ],
                                200
                            );
                        } catch (\Throwable $th) {
                            return response()->json(
                                [
                                    'status' => 400,
                                    'operation' =>  'fail',
                                    'message' =>  'Unable to send reset link'
                                ],
                                400
                            );
                        }
                    }
                } else {
                    return response()->json([
                        'status' => 400,
                        'operation' =>  'fail',
                        'message' =>  'Email not registered.'
                    ], 400);
                }
            } else {
                return response()->json([
                    'status' => 400,
                    'operation' =>  'fail',
                    'message' =>  'Enter a Valid Email.'
                ], 400);
            }
        }
    }
    public function resetPasswordWithOtp(Request $request)
    {
        $identifier = $request['identifier'];
        // $phone = $request['phone'];
        if (!$identifier || empty($identifier)) {
            return response()->json([
                'status' => 400,
                'operation' =>  'fail',
                'message' =>  'identifier required.'
            ], 400);
        } else {
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                // $token = openssl_random_pseudo_bytes(16);
                //Convert the binary data into hexadecimal representation.
                // $token = bin2hex($token);

                $token = rand(100000, 999999);

                $sql_user = DB::connection('mydb_sqlsrv')
                    ->select("SELECT TOP 1 * FROM sys_users_tb where user_email = '$identifier'");
                if ($sql_user) {
                    $user = $sql_user[0];
                    $name = $user->user_full_names;
                    $user_id = $user->user_id;
                    $datecreated = date('Y-m-d H:i:s');
                    $expirydate = date('Y-m-d H:i:s', strtotime($datecreated . ' + 1 days'));

                    $insert_token = DB::connection('mydb_sqlsrv')
                        ->insert('INSERT INTO tokens(token_key,user_id,expire_date,created_date)
                 values (?,?,?,?)', [$token, $user_id, $expirydate, $datecreated]);
                    if ($insert_token) {
                        $mailData = [
                            'token' => $token,
                            'name' => $name
                        ];
                        try {
                            // Send verification Email with token
                            Mail::to($identifier)->send(new PasswordResetMail($mailData));
                            return response()->json(
                                [
                                    'status' => 200,
                                    'operation' =>  'success',
                                    'message' =>  "Password reset link sent successfully to $identifier"
                                ],
                                200
                            );
                        } catch (\Throwable $th) {
                            return response()->json(
                                [
                                    'status' => 400,
                                    'operation' =>  'fail',
                                    'message' =>  'Unable to send password reset link'
                                ],
                                400
                            );
                        }
                    }
                } else {
                    return response()->json([
                        'status' => 400,
                        'operation' =>  'fail',
                        'message' =>  'Email not registered.'
                    ], 400);
                }
            } elseif (is_numeric($identifier)) {
                $pattern = "/^(\+254|254|0|7)[1-9]\d{8}$/";
                if ((preg_match($pattern, $identifier, $matches))) {
                    if ($matches[1] === "254") {
                        $new_number = substr($identifier, 3);
                        $identifier = "0$new_number";
                    } elseif ($matches[1] === "+254") {
                        $new_number = substr($identifier, 4);
                        $identifier = "0$new_number";
                    } elseif ($matches[1] === "7") {
                        $identifier = "0$identifier";
                    }
                }
                $token = rand(100000, 999999);

                $sql_user = DB::connection('mydb_sqlsrv')
                    ->select("SELECT TOP 1 * FROM sys_users_tb where user_phone = '$identifier'");
                if ($sql_user) {
                    $user = $sql_user[0];
                    $name = $user->user_full_names;
                    $user_id = $user->user_id;
                    $datecreated = date('Y-m-d H:i:s');
                    $expirydate = date('Y-m-d H:i:s', strtotime($datecreated . ' + 1 days'));

                    $insert_token = DB::connection('mydb_sqlsrv')
                        ->insert('INSERT INTO tokens(token_key,user_id,expire_date,created_date)
                     values (?,?,?,?)', [$token, $user_id, $expirydate, $datecreated]);
                    if ($insert_token) {
                        $string1 = (string)$token;
                        $identifier = substr($identifier, 1);
                        $new_no = "+254$identifier";
                        $parameters = [
                            'message'   => "Hello  $name,  Use the OTP below to reset your password into the system $string1.", // the actual message
                            'sender_id' => 'OCTAGON', // please always maintain capital letters. possible value: OCTAGON, IPM, MOBIKEZA
                            'recipient' => $new_no, // alwaynew_nos begin with country code. Let us know any country you need us to enable.
                            'type'      => 'plain', // possible value: plain, mms, voice, whatsapp, default plain
                        ];
                        try {
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, 'https://sms.octagonafrica.com/api/v3/sms/send');
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                'Authorization: Bearer 9|b2F8SetSBpEQQkmWgHF2uzb8S6ooN0Y8iQpJBy7V', //will be specific to each application we are building internally. Current token will be disabled after test.
                                'Content-Type: application/json',
                                'Accept: application/json',
                            ]);

                            $get_sms_status = curl_exec($ch);

                            if (curl_errno($ch)) {
                                $get_sms_status = curl_error($ch);
                            }

                            curl_close($ch);

                            return response()->json(
                                [
                                    'status' => 200,
                                    'operation' =>  'success',
                                    'message' =>  "OTP sent to $new_no",
                                    // 'sms status' => $get_sms_status
                                ],
                                200
                            );
                        } catch (\Throwable $th) {
                            return response()->json(
                                [
                                    'status' => 400,
                                    'operation' =>  'fail',
                                    'message' =>  'Unable to send OTP'
                                ],
                                400
                            );
                        }
                    }
                } else {
                    return response()->json([
                        'status' => 400,
                        'operation' =>  'fail',
                        'message' =>  'Phone number not registered.'
                    ], 400);
                }
            } else {

                $sql_user = DB::connection('mydb_sqlsrv')
                    ->select("SELECT TOP 1 * FROM sys_users_tb where user_username = '$identifier'");
                if ($sql_user) {
                    $user = $sql_user[0];
                    $name = $user->user_full_names;
                    $user_id = $user->user_id;
                    $phone = $user->user_phone;
                    $user_email = $user->user_email;
                    $datecreated = date('Y-m-d H:i:s');
                    $expirydate = date('Y-m-d H:i:s', strtotime($datecreated . ' + 1 days'));
                    if ($phone) {
                        $token = rand(100000, 999999);
                        $pattern = "/^(\+254|254|0|7)[1-9]\d{8}$/";
                        if ((preg_match($pattern, $phone, $matches))) {
                            if ($matches[1] === "254") {
                                $new_number = substr($phone, 3);
                                $phone = "0$new_number";
                            } elseif ($matches[1] === "+254") {
                                $new_number = substr($phone, 4);
                                $phone = "0$new_number";
                            } elseif ($matches[1] === "7") {
                                $phone = "0$phone";
                            }
                        }
                        $insert_token = DB::connection('mydb_sqlsrv')
                            ->insert('INSERT INTO tokens(token_key,user_id,expire_date,created_date)
                             values (?,?,?,?)', [$token, $user_id, $expirydate, $datecreated]);
                        if ($insert_token) {
                            $string1 = (string)$token;

                            $phone = substr($phone, 1);
                            $new_no = "+254$phone";
                            $parameters = [
                                'message'   => "Hello  $name,  Use the OTP below to reset your password into the system $string1.", // the actual message
                                'sender_id' => 'OCTAGON', // please always maintain capital letters. possible value: OCTAGON, IPM, MOBIKEZA
                                'recipient' => "$new_no", // always begin with country code. Let us know any country you need us to enable.
                                'type'      => 'plain', // possible value: plain, mms, voice, whatsapp, default plain
                            ];
                            try {
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, 'https://sms.octagonafrica.com/api/v3/sms/send');
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
                                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
                                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                    'Authorization: Bearer 9|b2F8SetSBpEQQkmWgHF2uzb8S6ooN0Y8iQpJBy7V', //will be specific to each application we are building internally. Current token will be disabled after test.
                                    'Content-Type: application/json',
                                    'Accept: application/json',
                                ]);

                                $get_sms_status = curl_exec($ch);

                                if (curl_errno($ch)) {
                                    $get_sms_status = curl_error($ch);
                                }

                                curl_close($ch);

                                return response()->json(
                                    [
                                        'status' => 200,
                                        'operation' =>  'success',
                                        'message' =>  "OTP sent to $new_no",
                                        // 'sms status' => $get_sms_status
                                    ],
                                    200
                                );
                            } catch (\Throwable $th) {
                                return response()->json(
                                    [
                                        'status' => 400,
                                        'operation' =>  'fail',
                                        'message' =>  'Unable to send OTP'
                                    ],
                                    400
                                );
                            }
                        } else {
                            return response()->json([
                                'status' => 400,
                                'operation' =>  'fail',
                                'message' =>  'Internal server Error!! OTP not generated.'
                            ], 400);
                        }
                    }
                    if ($user_email) {

                        $token = rand(100000, 999999);
                        $insert_token = DB::connection('mydb_sqlsrv')
                            ->insert('INSERT INTO tokens(token_key,user_id,expire_date,created_date)
                             values (?,?,?,?)', [$token, $user_id, $expirydate, $datecreated]);
                        if ($insert_token) {
                            $mailData = [
                                'token' => $token,
                                'name' => $name
                            ];
                            try {
                                // Send verification Email with token
                                Mail::to($user_email)->send(new PasswordResetMail($mailData));
                                return response()->json(
                                    [
                                        'status' => 200,
                                        'operation' =>  'success',
                                        'message' =>  "OTP sent to $user_email"
                                    ],
                                    200
                                );
                            } catch (\Throwable $th) {
                                return response()->json(
                                    [
                                        'status' => 400,
                                        'operation' =>  'fail',
                                        'message' =>  'Unable to send OTP'
                                    ],
                                    400
                                );
                            }
                        }
                    }
                    if (!$phone && $user_email) {
                        return response()->json([
                            'status' => 200,
                            'operation' =>  'success',
                            'message' => "Your account doesn't have a registered email or phone. Kindly contact the administrator"
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'status' => 400,
                        'operation' =>  'fail',
                        'message' =>  'Username not found.'
                    ], 400);
                }
            }
        };
    }
    // Update password
    public function updatePasswordWithCode(Request $request)
    {
        $code = $request['code'];
        $password = $request['password'];

        if (empty($code) || empty($password) || !$code || !$password) {
            return response()->json([
                'status' => 400,
                'operation' =>  'fail',
                'message' =>  'Code and password not provided.'
            ], 400);
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $check_code = DB::connection('mydb_sqlsrv')->select("SELECT * FROM tokens where token_key = '$code'");

        if (count($check_code) > 0) {
            $code_expiry = DB::connection('mydb_sqlsrv')
                ->select("SELECT TOP 1 * FROM tokens where token_key = '$code' AND (DATEDIFF(second, expire_date, GETDATE()) / 3600.0)<=24");
            if (count($code_expiry)) {
                $data = $code_expiry[0];
                $userid = $data->user_id;
                $update_password = DB::connection('mydb_sqlsrv')->table('sys_users_tb')
                    ->where('user_id', $userid)
                    ->update(['user_enc_pwd' => $hashed_password]);

                if ($update_password) {
                    return response()->json([
                        'statu' => 200,
                        'operation' => 'success',
                        'message' => 'Password reset succesfully'
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 404,
                        'operation' => 'fail',
                        'message' => 'Failed to update Password.',
                        'error' => $update_password

                    ], 404);
                }
            } else {
                return response()->json([
                    'status' => 404,
                    'operation' =>  'fail',
                    'message' =>  'Password reset link/token has expired'
                ], 404);
            }
        } else {
            return response()->json([
                'status' => 404,
                'operation' =>  'fail',
                'message' =>  'Invalid Token/link.'
            ], 404);
        }
    }

    public function updatePassword(Request $request)
    {
        $password = $request['password'];
        $userid = $request['user_id'];

        if (!$password || empty($password) || !$userid || empty($userid)) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'New Password/user_id not privded.'
            ], 400);
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            $update_password = DB::connection('mydb_sqlsrv')->table('sys_users_tb')
                ->where('user_id', $userid)
                ->update(['user_enc_pwd' => $hashed_password]);
            if ($update_password) {
                return response()->json(
                    [
                        'status' => 200,
                        'success' => true,
                        'message' => 'Password updated successfully.'
                    ],
                    200
                );
            } else {
                return response()->json(
                    [
                        'status' => 404,
                        'success' => false,
                        'message' => 'User Not Found.'
                    ],
                    404
                );
            }
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'status' => 400,
                    'success' => false,
                    'message' => $th
                ],
                400
            );
        }
    }
    public function verifyOtp(Request $request)
    {
        $code = $request['code'];

        if (empty($code) || !$code) {
            return response()->json([
                'status' => 400,
                'operation' =>  'fail',
                'message' =>  'Code not provided.'
            ], 400);
        }

        $check_code = DB::connection('mydb_sqlsrv')->select("SELECT * FROM tokens where token_key = '$code'");

        if (count($check_code) > 0) {
            $code_expiry = DB::connection('mydb_sqlsrv')
                ->select("SELECT TOP 1 * FROM tokens where token_key = '$code' AND (DATEDIFF(second, expire_date, GETDATE()) / 3600.0)<=24");
            if (count($code_expiry)) {
                $data = $code_expiry[0];
                $userid = $data->user_id;

                return response()->json([
                    'statu' => 200,
                    'operation' => 'success',
                    'message' => 'Valid Token',
                    'user_id' => $userid
                ], 200);
            } else {
                return response()->json([
                    'status' => 404,
                    'operation' => 'fail',
                    'message' => 'Password reset token has expired'
                ], 404);
            }
        } else {
            return response()->json([
                'status' => 404,
                'operation' => 'fail',
                'message' => 'Invalid Token.'
            ], 404);
        }
    }
}
