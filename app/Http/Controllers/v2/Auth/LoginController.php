<?php

namespace App\Http\Controllers\v2\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginVerificationMail;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $character = '+';
        $rules = [
            'username' => ['required'],
            'password' => 'required'
        ];

        //'regex:/^(\+254|254|0)[1-9]\d{8}$/'
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 400,
                    'success' => false,
                    //'message' => 'Login failed. Valid Username/Email/Phone number and Password required.',
                    'errors' => $validator->errors()
                ],
                400
            );
        } else {// Username is phone number
            $data = $request->only(['username', 'password']);
            $username = $data['username'];
            $password = $data['password'];
            if ((is_numeric($username) || strpos($username, $character) === 0) && $password) {
                // Get validated data
          
                $user_exist = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_phone = '$username' or user_username ='$username' AND user_active = 1");
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
                            'iss' => 'https://cloud.octagonafrica.com',
                            'aud' => 'https://cloud.octagonafrica.com',
                            'iat' => strtotime('now'),              // login @ timestamp... the jwt frameowrk has a bug that sets the iat at t-1.5 hours
                            'nbf' => strtotime('now'),              // token session starts now
                            'exp' => strtotime('+1 hour'),          // token session is valid/expires in an hour
                        ];
                        $key = env('JWT_KEY');
                         if (!is_string($key)) {
            throw new \Exception('JWT_KEY must be a string');
        }
                        $jwt = JWT::encode($data, $key, 'HS256');
                        // insert Audit Trail -> successful login

                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[audit_trail_app_tb](audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date('Y-m-d H:i:s'), 'All', $user->user_username, $user->user_full_names, 'Login', 'Success']);

                        // Insert OTP expiry
                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[otp_expiry](otp,user_id,is_expired,created_at) values (?,?,?,?)', [$otp, $user->user_id, 0, date('Y-m-d H:i:s')]);

                        return response()->json(
                            [
                                'success' => true,
                                'message' => 'Successfull verification',
                                'data' => $data,
                                'token' => $jwt,
                            ],
                            200
                        );
                    } else {
                        // insert Audit Trail -> Failed Password
                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[app_audit_trail_tb](audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date('Y-m-d H:i:s'), 'All', $user->user_username, $user->user_full_names, 'Login', 'Failed']);
                        // wrong password
                        return response()->json(
                            [
                                'success' => false,
                                'message' => 'Authentication failed. Incorrect password or username. Access denied.',
                                'field' => 'phone',
                            ],
                            401
                        );
                    }
                } else {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => 'Authentication failed. Username provided was not found in the database',
                        ],
                        401
                    );
                }
            } elseif (filter_var($username, FILTER_VALIDATE_EMAIL) && $password) {// username is Email
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
                            'iss' => 'https://cloud.octagonafrica.com',
                            'aud' => 'https://cloud.octagonafrica.com',
                            'iat' => strtotime('now'),              // login @ timestamp... the jwt frameowrk has a bug that sets the iat at t-1.5 hours
                            'nbf' => strtotime('now'),              // token session starts now
                            'exp' => strtotime('+1 hour'),          // token session is valid/expires in an hour
                        ];
                        $key = env('JWT_KEY');
                        $jwt = JWT::encode($data, $key, 'HS256');
                        // fetch device information.
                        // Parse the user agent string
                        $userAgent = $request->header('User-Agent');

                        $detect = new \Mobile_Detect();

                        if ($detect->isMobile()) {
                            if ($detect->is('iOS')) {
                                $device = $detect->mobileGrade(); // Get specific device model for iOS
                                $platform = 'iOS';
                            } elseif ($detect->is('AndroidOS')) {
                                $device = $detect->mobileGrade(); // Get specific device model for Android
                                $platform = 'Android';
                            } else {
                                $device = 'Unknown mobile device';
                                $platform = 'Unknown mobile platform';
                            }
                        } elseif ($detect->isTablet()) {
                            $device = $detect->mobileGrade(); // Get specific device model for tablets
                            $platform = 'Tablet';
                        } else {
                            $device = 'Desktop';
                            $platform = 'Unknown platform';
                        }
                        // Output the device and platform information
                        $deviceInformation = [
                            'device' => $device,
                            'platform' => $platform,
                        ];
                        // insert Audit Trail -> successful login
                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[audit_trail_app_tb](audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description,device,platform) values (?,?,?,?,?,?,?,?)', [date('Y-m-d H:i:s'), 'All', $user->user_username, $user->user_full_names, 'Login', 'Success', $device, $platform]);
                        // Insert OTP expiry
                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[otp_expiry](otp,user_id,is_expired,created_at) values (?,?,?,?)', [$otp, $user->user_id, 0, date('Y-m-d H:i:s')]);

                        return response()->json(
                            ['status' => 200,
                                'success' => true,
                                'message' => 'Successfull verification',
                                'data' => $data,
                                'token' => $jwt,
                                'deviceInformation' => $deviceInformation,
                            ],
                            200
                        );
                    } else {
                        // insert Audit Trail -> Failed Password
                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[audit_trail_app_tb](audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date('Y-m-d H:i:s'), 'All', $user->user_username, $user->user_full_names, 'Login', 'Failed']);
                        // wrong password
                        return response()->json(
                            ['status' => 400,
                                'success' => false,
                                'message' => 'Authentication failed. Incorrect password or username. Access denied.',
                                'field' => 'mail',
                            ],
                            400
                        );
                    }
                } else {
                    return response()->json(
                        ['status' => 400,
                            'success' => false,
                            'message' => 'Authentication failed. Username provided was not found in the database',
                        ],
                        400
                    );
                }
            } elseif ((is_numeric($username) || strpos($username, $character) === 0) && empty($password)) {// Username is phone and no password
                if (preg_match($pattern, $username, $matches)) {
                    if ($matches[1] === '254') {
                        $new_number = substr($username, 3);
                        $username = "0$new_number";
                    } elseif ($matches[1] === '+254') {
                        $new_number = substr($username, 4);
                        $username = "0$new_number";
                    }
                    $user_exist = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_phone = '$username' AND user_active = 1");
                    if (count($user_exist) > 0) {
                        $user = $user_exist[0];
                        $name = $user->user_full_names;
                        $otp = rand(100000, 999999);
                        $string1 = (string) $otp;
                        $new_number = substr($username, 1);
                        $username = "+254$new_number";

                        $parameters = [
                            'message' => "Hello  $name,  Use the OTP below to log in to the system $string1.", // the actual message
                            'sender_id' => 'OCTAGON', // please always maintain capital letters. possible value: OCTAGON, IPM, MOBIKEZA
                            'recipient' => $username, // always begin with country code. Let us know any country you need us to enable.
                            'type' => 'plain', // possible value: plain, mms, voice, whatsapp, default plain
                        ];

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, 'https://sms.octagonafrica.com/api/v3/sms/send');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Authorization: Bearer 9|b2F8SetSBpEQQkmWgHF2uzb8S6ooN0Y8iQpJBy7V', // will be specific to each application we are building internally. Current token will be disabled after test.
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
                                'iss' => 'https://cloud.octagonafrica.com',
                                'aud' => 'https://cloud.octagonafrica.com',
                                'iat' => strtotime('now'),              // login @ timestamp... the jwt frameowrk has a bug that sets the iat at t-1.5 hours
                                'nbf' => strtotime('now'),              // token session starts now
                                'exp' => strtotime('+1 hour'),          // token session is valid/expires in an hour
                            ];
                            $key = env('JWT_KEY');
                            $jwt = JWT::encode($data, $key, 'HS256');
                            // insert Audit Trail -> successful login
                            DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[audit_trail_app_tb](audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date('Y-m-d H:i:s'), 'All', $user->user_username, $user->user_full_names, 'Login', 'Success']);

                            // Insert OTP expiry
                            DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[otp_expiry](otp,user_id,is_expired,created_at) values (?,?,?,?)', [$otp, $user->user_id, 0, date('Y-m-d H:i:s')]);

                            return response()->json(
                                [
                                    'status' => 200,
                                    'success' => true,
                                    'message' => "Successfull verification, otp sent to $username",
                                    // 'data' => $data,
                                    'token' => $jwt,
                                    'sms status' => $get_sms_status,
                                ],
                                200
                            );
                        } else {
                            // insert Audit Trail -> Failed Password
                            DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[audit_trail_app_tb](audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date('Y-m-d H:i:s'), 'All', $user->user_username, $user->user_full_names, 'Login', 'Failed']);
                            // wrong password
                            return response()->json(
                                [
                                    'status' => 401,
                                    'success' => false,
                                    'message' => 'Authentication failed. Incorrect password or username. Access denied.',
                                ],
                                401
                            );
                        }
                    } else {
                        return response()->json(
                            [
                                'status' => 404,
                                'success' => false,
                                'message' => 'Authentication failed. Username provided was not found in the database',
                            ],
                            404
                        );
                    }
                } else {
                    return response()->json([
                        'status' => 400,
                        'operation' => false,
                        'message' => 'Invalid Phone number',
                    ], 400);
                }
            } else { // username is username generated during registration
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
                            'iss' => 'https://cloud.octagonafrica.com',
                            'aud' => 'https://cloud.octagonafrica.com',
                            'iat' => strtotime('now'),              // login @ timestamp... the jwt frameowrk has a bug that sets the iat at t-1.5 hours
                            'nbf' => strtotime('now'),              // token session starts now
                            'exp' => strtotime('+1 hour'),          // token session is valid/expires in an hour
                        ];
                        $key = env('JWT_KEY');
                        if (!is_string($key)) {
                            throw new \Exception('JWT_KEY must be a string. Current value: ' . var_export($key, true));
                        }
                        $jwt = JWT::encode($data, $key, 'HS256');
                        // insert Audit Trail -> successful login
                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[audit_trail_app_tb](audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date('Y-m-d H:i:s'), 'All', $user->user_username, $user->user_full_names, 'Login', 'Success']);

                        // Insert OTP expiry
                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[otp_expiry](otp,user_id,is_expired,created_at) values (?,?,?,?)', [$otp, $user->user_id, 0, date('Y-m-d H:i:s')]);

                        return response()->json(
                            [
                                'status' => 200,
                                'success' => true,
                                'message' => 'Successfull verification',
                                'data' => $data,
                                'token' => $jwt,
                            ],
                            200
                        );
                    } else {
                        // insert Audit Trail -> Failed Password
                        DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[audit_trail_app_tb](audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date('Y-m-d H:i:s'), 'All', $user->user_username, $user->user_full_names, 'Login', 'Failed']);
                        // wrong password
                        return response()->json(
                            [
                                'status' => 401,
                                'success' => false,
                                'message' => 'Authentication failed. Incorrect password or username. Access denied.',
                            ],
                            401
                        );
                    }
                } else {
                    return response()->json(
                        [
                            'status' => 401,
                            'success' => false,
                            'message' => 'Authentication failed. Username provided was not found in the database',
                        ],
                        401
                    );
                }
            }
        }
    }

    public function sendLoginVerification(Request $request)
    {
        $data = $request->all();
        $email = $data['email'];
        $otp = $data['otp'];
        $phone = $data['phone'];
        $selected = $data['selected'];
        $string1 = (string) $otp;
        $sql_user = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb where user_email = '$email'");
        $user = $sql_user[0];

        $user_id = $user->user_id;
        $name = $user->user_full_names;
        if ($selected == 'email') {
            $mailData = [
                'otp' => $otp,
                'name' => $name,
            ];
            try {
                // Send verification Email with OTP
                Mail::to($email)->send(new LoginVerificationMail($mailData));

                return response()->json(
                    [
                        'operation' => 'success',
                        'message' => "OTP verification code sent to '$email'",
                    ],
                    200
                );
            } catch (\Throwable $th) {
                return response()->json(
                    [
                        'status' => 400,
                        'operation' => 'false',
                        'message' => 'OTP verification not sent',
                    ],
                    400
                );
            }
        } else {
            $parameters = [
                'message' => "Hello  $name,  Use the OTP below to log in to the system $string1.", // the actual message
                'sender_id' => 'OCTAGON', // please always maintain capital letters. possible value: OCTAGON, IPM, MOBIKEZA
                'recipient' => $phone, // always begin with country code. Let us know any country you need us to enable.
                'type' => 'plain', // possible value: plain, mms, voice, whatsapp, default plain
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://sms.octagonafrica.com/api/v3/sms/send');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer 9|b2F8SetSBpEQQkmWgHF2uzb8S6ooN0Y8iQpJBy7V', // will be specific to each application we are building internally. Current token will be disabled after test.
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
                'status' => 200,
                'operation' => 'success',
                'message' => "OTP verification code sent to '$phone'",
                'sms status' => $get_sms_status,
            ], 200
        );
    }

    public function otp(Request $request)
    {
        $code = $request['code'];

        $sql_check_code = DB::connection('mydb_sqlsrv')->select("SELECT * FROM otp_expiry WHERE otp='$code' AND is_expired!=1 AND (DATEDIFF(second, created_at, GETDATE()) / 3600.0)<=24");
        if (empty($sql_check_code)) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'Invalid otp',
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
            'user_id' => $userData->user_id,
            'username' => $userData->user_username,
            'user_full_names' => $userData->user_full_names,
            'user_email' => $userData->user_email,
            'user_mobile' => $userData->user_mobile,
            'user_phone' => $userData->user_phone,
            'user_role_id' => $userData->user_role_id,
        ];

        return response([
            'status' => 200,
            'operation' => 'success',
            'message' => 'Successful verification',
            'data' => $payload,
        ], 200);
    }

    public function loginWithPhoneNumber(Request $request)
    {
        $rules = [
            'phoneNumber' => 'required',
            'password' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        $data = $request->all();
        $phoneNumber = $data['phoneNumber'];
        $password = $data['password'];
        // $pattern = "/^(\+254|254|0)[1-9]\d{8}$/";
        if ($validator->fails()) {
            return response()->json(
                ['status' => 400,
                    'success' => false,
                    'message' => 'Login failed. Username/Email/Phone number required',
                ],
                400
            );
        } else {
            $user_exist = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_phone = '$phoneNumber' AND user_active = 1");
            if (count($user_exist) > 0) {
                $user = $user_exist[0];
                $name = $user->user_full_names;
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
                        'iss' => 'https://cloud.octagonafrica.com',
                        'aud' => 'https://cloud.octagonafrica.com',
                        'iat' => strtotime('now'),              // login @ timestamp... the jwt frameowrk has a bug that sets the iat at t-1.5 hours
                        'nbf' => strtotime('now'),              // token session starts now
                        'exp' => strtotime('+1 hour'),          // token session is valid/expires in an hour
                    ];
                    $key = env('JWT_KEY');
                    $jwt = JWT::encode($data, $key, 'HS256');
                    // insert Audit Trail -> successful login
                    DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[audit_trail_app_tb](audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date('Y-m-d H:i:s'), 'All', $user->user_username, $user->user_full_names, 'Login', 'Success']);

                    // Insert OTP expiry
                    DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[otp_expiry](otp,user_id,is_expired,created_at) values (?,?,?,?)', [$otp, $user->user_id, 0, date('Y-m-d H:i:s')]);

                    return response()->json(
                        [
                            'status' => 200,
                            'success' => true,
                            'message' => 'Successfull verification',
                            'data' => $data,
                            'token' => $jwt,
                        ],
                        200
                    );
                } else {
                    // insert Audit Trail -> Failed Password
                    DB::connection('mydb_sqlsrv')->insert('INSERT INTO [MYDB].[dbo].[audit_trail_app_tb](audit_date_time,audit_scheme_code,audit_username,audit_fullnames,audit_activity,audit_description) values (?,?,?,?,?,?)', [date('Y-m-d H:i:s'), 'All', $user->user_username, $user->user_full_names, 'Login', 'Failed']);
                    // wrong password
                    return response()->json(
                        [
                            'status' => 401,
                            'success' => false,
                            'message' => 'Authentication failed. Incorrect password or username. Access denied.',
                        ],
                        401
                    );
                }
            } else {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Authentication failed. Username provided was not found in the database',
                    ],
                    401
                );
            }
        }
    }
}
