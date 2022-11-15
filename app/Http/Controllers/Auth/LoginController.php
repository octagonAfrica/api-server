<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginVerificationMail;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function login(Request $request)
    {

        $character = '+';
        $rules = [
            'username' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);
        $data = $request->all();
        $username = $data['username'];
        $password = $data['password'];
        $pattern = "/^(\+254|254|0)[1-9]\d{8}$/";
        if ($validator->fails()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => "Login failed. Username/Email/Phone number required"
                ],
                401
            );
        } else {
            // Username is phone number
            if ((is_numeric($username) || strpos($username, $character) === 0) && $password) {

                if ((preg_match($pattern, $username, $matches))) {
                    if ($matches[1] === "254") {
                        $new_number = substr($username, 3);
                        $username = "0$new_number";
                    } elseif ($matches[1] === "+254") {
                        $new_number = substr($username, 4);
                        $username = "0$new_number";
                    }
                    $user_exist = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_phone = '$username' AND user_active = 1");
                    if (count($user_exist) > 0) {
                        $user = $user_exist[0];
                        $otp = rand(100000, 999999);

                        if (password_verify($password, trim($user->user_enc_pwd))) {
                            $data = [
                                'user_id' => $user->user_id,
                                'otp' => $otp,
                                'username' => $user->user_username,
                                'user_full_names' => $user->user_full_names,
                                'user_email' => $user->user_email,
                                'user_mobile' => $user->user_mobile,
                                'user_phone' => $user->user_phone,
                                'user_national_id' => $user->user_national_id,
                                'pension' => $user->pension,
                                'trust_fund' => $user->trust_fund,
                                'accounts' => $user->accounts,
                                'drawdown' => $user->drawdown,
                                'user_schemes' => $user->user_schemes,
                                'user_admin' => $user->user_admin,
                                'user_signature' => $user->user_signature,
                                'user_role_id' => $user->user_role_id,
                                'user_delagate_owner' => $user->user_delagate_owner,
                                "iss" => "https://cloud.octagonafrica.com",
                                "aud" => "https://cloud.octagonafrica.com",
                                "iat" => strtotime('now'),              //login @ timestamp... the jwt frameowrk has a bug that sets the iat at t-1.5 hours
                                "nbf" => strtotime('now'),              //token session starts now
                                "exp" => strtotime('+1 hour')          //token session is valid/expires in an hour

                            ];
                            $key = env('JWT_KEY');
                            $jwt = JWT::encode($data, $key, 'HS256');
                            // insert Audit Trail -> successful login
                            DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb(audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date("Y-m-d H:i:s"), "All", $user->user_username, $user->user_full_names, "Login", "Success"]);

                            // Insert OTP expiry
                            DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[otp_expiry](otp,user_id,is_expired,created_at) values (?,?,?,?)', [$otp, $user->user_id, 0, date("Y-m-d H:i:s")]);

                            return response()->json(
                                [
                                    'success' =>  true,
                                    'message' =>  'Successfull verification',
                                    'data' => $data,
                                    'token' => $jwt
                                ],
                                200
                            );
                        } else {
                            // insert Audit Trail -> Failed Password
                            DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb(audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date("Y-m-d H:i:s"), "All", $user->user_username, $user->user_full_names, "Login", "Failed"]);
                            //wrong password
                            return response()->json(
                                [
                                    'success' => false,
                                    'message' => "Authentication failed. Incorrect password or username. Access denied."
                                ],
                                401
                            );
                        }
                    } else {
                        return response()->json(
                            [
                                'success' => false,
                                'message' => "Authentication failed. Username provided was not found in the database"
                            ],
                            401
                        );
                    }
                } else {
                    return response()->json([
                        'operation' => false,
                        'message' => 'Invalid Phone number'
                    ]);
                }
            }
            // username is Emial
            else if (filter_var($username, FILTER_VALIDATE_EMAIL) && $password) {

                $user_exist = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_email = '$username' AND user_active = 1");
                if (count($user_exist) > 0) {
                    $user = $user_exist[0];
                    $otp = rand(100000, 999999);

                    if (password_verify($password, trim($user->user_enc_pwd))) {
                        $data = [
                            'user_id' => $user->user_id,
                            'otp' => $otp,
                            'username' => $user->user_username,
                            'user_full_names' => $user->user_full_names,
                            'user_email' => $user->user_email,
                            'user_mobile' => $user->user_mobile,
                            'user_phone' => $user->user_phone,
                            'user_national_id' => $user->user_national_id,
                            'pension' => $user->pension,
                            'trust_fund' => $user->trust_fund,
                            'accounts' => $user->accounts,
                            'drawdown' => $user->drawdown,
                            'user_schemes' => $user->user_schemes,
                            'user_admin' => $user->user_admin,
                            'user_signature' => $user->user_signature,
                            'user_role_id' => $user->user_role_id,
                            'user_delagate_owner' => $user->user_delagate_owner,
                            "iss" => "https://cloud.octagonafrica.com",
                            "aud" => "https://cloud.octagonafrica.com",
                            "iat" => strtotime('now'),              //login @ timestamp... the jwt frameowrk has a bug that sets the iat at t-1.5 hours
                            "nbf" => strtotime('now'),              //token session starts now
                            "exp" => strtotime('+1 hour')          //token session is valid/expires in an hour

                        ];
                        $key = env('JWT_KEY');
                        $jwt = JWT::encode($data, $key, 'HS256');
                        // insert Audit Trail -> successful login
                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb(audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date("Y-m-d H:i:s"), "All", $user->user_username, $user->user_full_names, "Login", "Success"]);

                        // Insert OTP expiry
                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[otp_expiry](otp,user_id,is_expired,created_at) values (?,?,?,?)', [$otp, $user->user_id, 0, date("Y-m-d H:i:s")]);

                        return response()->json(
                            [
                                'success' =>  true,
                                'message' =>  'Successfull verification',
                                'data' => $data,
                                'token' => $jwt
                            ],
                            200
                        );
                    } else {
                        // insert Audit Trail -> Failed Password
                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb(audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date("Y-m-d H:i:s"), "All", $user->user_username, $user->user_full_names, "Login", "Failed"]);
                        //wrong password
                        return response()->json(
                            [
                                'success' => false,
                                'message' => "Authentication failed. Incorrect password or username. Access denied."
                            ],
                            401
                        );
                    }
                } else {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => "Authentication failed. Username provided was not found in the database"
                        ],
                        401
                    );
                }
            }
            // Username is phone and no password
            else if ((is_numeric($username) || strpos($username, $character) === 0)  && empty($password)) {
                if ((preg_match($pattern, $username, $matches))) {
                    if ($matches[1] === "254") {
                        $new_number = substr($username, 3);
                        $username = "0$new_number";
                    } elseif ($matches[1] === "+254") {
                        $new_number = substr($username, 4);
                        $username = "0$new_number";
                    }
                    $user_exist = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_phone = '$username' AND user_active = 1");
                    if (count($user_exist) > 0) {
                        $user = $user_exist[0];
                        $name = $user->user_full_names;
                        $otp = rand(100000, 999999);
                        $string1 = (string)$otp;
                        $new_number = substr($username, 1);
                        $username = "+254$new_number";


                        $parameters = [
                            'message'   => "Hello  $name,  Use the OTP below to log in to the system $string1.", // the actual message
                            'sender_id' => 'OCTAGON', // please always maintain capital letters. possible value: OCTAGON, IPM, MOBIKEZA
                            'recipient' => $username, // always begin with country code. Let us know any country you need us to enable.
                            'type'      => 'plain', // possible value: plain, mms, voice, whatsapp, default plain
                        ];

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


                        if ($get_sms_status) {
                            $data = [
                                'user_id' => $user->user_id,
                                'otp' => $otp,
                                'username' => $user->user_username,
                                'user_full_names' => $user->user_full_names,
                                'user_email' => $user->user_email,
                                'user_mobile' => $user->user_mobile,
                                'user_phone' => $user->user_phone,
                                'user_national_id' => $user->user_national_id,
                                'pension' => $user->pension,
                                'trust_fund' => $user->trust_fund,
                                'accounts' => $user->accounts,
                                'drawdown' => $user->drawdown,
                                'user_schemes' => $user->user_schemes,
                                'user_admin' => $user->user_admin,
                                'user_signature' => $user->user_signature,
                                'user_role_id' => $user->user_role_id,
                                'user_delagate_owner' => $user->user_delagate_owner,
                                "iss" => "https://cloud.octagonafrica.com",
                                "aud" => "https://cloud.octagonafrica.com",
                                "iat" => strtotime('now'),              //login @ timestamp... the jwt frameowrk has a bug that sets the iat at t-1.5 hours
                                "nbf" => strtotime('now'),              //token session starts now
                                "exp" => strtotime('+1 hour')          //token session is valid/expires in an hour

                            ];
                            $key = env('JWT_KEY');
                            $jwt = JWT::encode($data, $key, 'HS256');
                            // insert Audit Trail -> successful login
                            DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb(audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date("Y-m-d H:i:s"), "All", $user->user_username, $user->user_full_names, "Login", "Success"]);

                            // Insert OTP expiry
                            DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[otp_expiry](otp,user_id,is_expired,created_at) values (?,?,?,?)', [$otp, $user->user_id, 0, date("Y-m-d H:i:s")]);

                            return response()->json(
                                [
                                    'success' =>  true,
                                    'message' =>  "Successfull verification, otp sent to $username",
                                    // 'data' => $data,
                                    'token' => $jwt,
                                    'status' => $get_sms_status
                                ],
                                200
                            );
                        } else {
                            // insert Audit Trail -> Failed Password
                            DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb(audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date("Y-m-d H:i:s"), "All", $user->user_username, $user->user_full_names, "Login", "Failed"]);
                            //wrong password
                            return response()->json(
                                [
                                    'success' => false,
                                    'message' => "Authentication failed. Incorrect password or username. Access denied."
                                ],
                                401
                            );
                        }
                    } else {
                        return response()->json(
                            [
                                'success' => false,
                                'message' => "Authentication failed. Username provided was not found in the database"
                            ],
                            401
                        );
                    }
                } else {
                    return response()->json([
                        'operation' => false,
                        'message' => 'Invalid Phone number'
                    ]);
                }
            }
            // username is username generated during registration
            else {
                $user_exist = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_username = '$username' AND user_active = 1");
                if (count($user_exist) > 0) {
                    $user = $user_exist[0];
                    $otp = rand(100000, 999999);

                    if (password_verify($password, trim($user->user_enc_pwd))) {
                        $data = [
                            'user_id' => $user->user_id,
                            'otp' => $otp,
                            'username' => $user->user_username,
                            'user_full_names' => $user->user_full_names,
                            'user_email' => $user->user_email,
                            'user_mobile' => $user->user_mobile,
                            'user_phone' => $user->user_phone,
                            'user_national_id' => $user->user_national_id,
                            'pension' => $user->pension,
                            'trust_fund' => $user->trust_fund,
                            'accounts' => $user->accounts,
                            'drawdown' => $user->drawdown,
                            'user_schemes' => $user->user_schemes,
                            'user_admin' => $user->user_admin,
                            'user_signature' => $user->user_signature,
                            'user_role_id' => $user->user_role_id,
                            'user_delagate_owner' => $user->user_delagate_owner,
                            "iss" => "https://cloud.octagonafrica.com",
                            "aud" => "https://cloud.octagonafrica.com",
                            "iat" => strtotime('now'),              //login @ timestamp... the jwt frameowrk has a bug that sets the iat at t-1.5 hours
                            "nbf" => strtotime('now'),              //token session starts now
                            "exp" => strtotime('+1 hour')          //token session is valid/expires in an hour

                        ];
                        $key = env('JWT_KEY');
                        $jwt = JWT::encode($data, $key, 'HS256');
                        // insert Audit Trail -> successful login
                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb(audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date("Y-m-d H:i:s"), "All", $user->user_username, $user->user_full_names, "Login", "Success"]);

                        // Insert OTP expiry
                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[otp_expiry](otp,user_id,is_expired,created_at) values (?,?,?,?)', [$otp, $user->user_id, 0, date("Y-m-d H:i:s")]);

                        return response()->json(
                            [
                                'success' =>  true,
                                'message' =>  'Successfull verification',
                                'data' => $data,
                                'token' => $jwt
                            ],
                            200
                        );
                    } else {
                        // insert Audit Trail -> Failed Password
                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb(audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date("Y-m-d H:i:s"), "All", $user->user_username, $user->user_full_names, "Login", "Failed"]);
                        //wrong password
                        return response()->json(
                            [
                                'success' => false,
                                'message' => "Authentication failed. Incorrect password or username. Access denied."
                            ],
                            401
                        );
                    }
                } else {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => "Authentication failed. Username provided was not found in the database"
                        ],
                        401
                    );
                }
            };
        }
    }

    public function sendLoginVerification(Request $request)
    {
        $data = $request->all();
        $email = $data['email'];
        $otp = $data['otp'];
        $phone = $data['phone'];
        $selected = $data['selected'];
        $string1 = (string)$otp;
        $sql_user = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb where user_email = '$email'");
        $user = $sql_user[0];


        $user_id = $user->user_id;
        $name = $user->user_full_names;
        if ($selected == 'email') {
            $mailData = [
                'otp' => $otp,
                'name' => $name
            ];
            try {
                // Send verification Email with OTP
                Mail::to($email)->send(new LoginVerificationMail($mailData));
                return response()->json(
                    [
                        'operation' =>  'success',
                        'message' =>  "OTP verification code sent to '$email'"
                    ],
                    200
                );
            } catch (\Throwable $th) {
                return response()->json(
                    [
                        'operation' =>  'false',
                        'message' =>  "OTP verification not sent"
                    ],
                    400
                );
            }
        } else {
            $parameters = [
                'message'   => "Hello  $name,  Use the OTP below to log in to the system $string1.", // the actual message
                'sender_id' => 'OCTAGON', // please always maintain capital letters. possible value: OCTAGON, IPM, MOBIKEZA
                'recipient' => $phone, // always begin with country code. Let us know any country you need us to enable.
                'type'      => 'plain', // possible value: plain, mms, voice, whatsapp, default plain
            ];

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
        }
        return response()->json(
            [
                'operation' =>  'success',
                'message' =>  "OTP verification code sent to '$phone'",
                'status' => $get_sms_status
            ],
        );
    }

    public function otp(Request $request)
    {

        $code = $request['code'];

        $sql_check_code = DB::connection('mydb_sqlsrv')->select("SELECT * FROM otp_expiry WHERE otp='$code' AND is_expired!=1 AND (DATEDIFF(second, created_at, GETDATE()) / 3600.0)<=24");
        if (empty($sql_check_code)) {
            return response()->json([

                'operation' =>  'fail',
                'message' =>  'Invalid otp'
            ], 400);
        }
        $user = $sql_check_code[0];
        $userid = $user->user_id;

        $sql_check_user = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb where user_id = '$userid'");
        $userData = $sql_check_user[0];

        // Update otp expiry
        DB::connection('mydb_sqlsrv')->table('otp_expiry')
            ->where('otp', $code)
            ->update(['is_expired' => 1]);

        $payload = [
            'user_id' =>  $userData->user_id,
            'username' =>  $userData->user_username,
            'user_full_names' =>  $userData->user_full_names,
            'user_email' =>  $userData->user_email,
            'user_mobile' =>  $userData->user_mobile,
            'user_phone' =>  $userData->user_phone,
            'user_role_id' =>  $userData->user_role_id
        ];
        return response([
            'operation' =>  'success',
            'message' =>  'Successful verification',
            'data' => $payload
        ], 200);
    }
}
