<?php

    namespace App\Http\Controllers\v2\Auth;

    use App\Http\Controllers\Controller;
    use App\Mail\PasswordResetMail;
    use App\Mail\PasswordResetMailWithLink;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Mail;
    use Illuminate\Support\Facades\Validator;
    use AfricasTalking\SDK\AfricasTalking;
    use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
    use Illuminate\Validation\Rule;

    class ResetPasswordController extends Controller
    {
        public function resetPasswordWithLink(Request $request)
        {
            $email = $request['email'];
            if (!$email || empty($email)) {
                return response()->json([
                    'status' => 400,
                    'operation' => 'fail',
                    'message' => 'Email required.',
                ], 400);
            } else {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $token = openssl_random_pseudo_bytes(16);
                    // Convert the binary data into hexadecimal representation.
                    $token = bin2hex($token);

                    $sql_user = DB::connection('mydb_sqlsrv')
                        ->select("SELECT TOP 1 * FROM sys_users_tb where user_email = '$email'");
                    if ($sql_user) {
                        $user = $sql_user[0];
                        $name = $user->user_full_names;
                        $user_id = $user->user_id;
                        $datecreated = date('Y-m-d H:i:s');
                        $expirydate = date('Y-m-d H:i:s', strtotime($datecreated.' + 1 days'));

                        $insert_token = DB::connection('mydb_sqlsrv')
                            ->insert('INSERT INTO tokens(token_key,user_id,expire_date,created_date)
                values (?,?,?,?)', [$token, $user_id, $expirydate, $datecreated]);
                        if ($insert_token) {
                            $mailData = [
                                'token' => $token,
                                'name' => $name,
                            ];
                            try {
                                // Send verification Email with token
                                Mail::to($email)->send(new PasswordResetMailWithLink($mailData));

                                return response()->json(
                                    [
                                        'status' => 200,
                                        'operation' => 'success',
                                        'message' => "Password reset link sent successfully to $email",
                                    ],
                                    200
                                );
                            } catch (\Throwable $th) {
                                return response()->json(
                                    [
                                        'status' => 400,
                                        'operation' => 'fail',
                                        'message' => 'Unable to send reset link',
                                    ],
                                    400
                                );
                            }
                        }
                    } else {
                        return response()->json([
                            'status' => 400,
                            'operation' => 'fail',
                            'message' => 'Email not registered.',
                        ], 400);
                    }
                } else {
                    return response()->json([
                        'status' => 400,
                        'operation' => 'fail',
                        'message' => 'Enter a Valid Email.',
                    ], 400);
                }
            }
        }

        // /reset Password with OTP
        public function resetPasswordWithOtp(Request $request)
        {
            $username = env('AFRICAS_TALKING_USERNAME');
            $apiKey = env('AFRICAS_TALKING_API_KEY');
            $identifier = $request['identifier'];
            // $phone = $request['phone'];
            if (!$identifier || empty($identifier)) {
                return response()->json([
                    'status' => 400,
                    'operation' => 'fail',
                    'message' => 'identifier required.',
                ], 400);
            } else {
                if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                    $token = rand(100000, 999999);
                    $sql_user = DB::connection('mydb_sqlsrv')
                        ->select("SELECT TOP 1 * FROM sys_users_tb where user_email = '$identifier'");
                    if ($sql_user) {
                        $user = $sql_user[0];
                        $name = $user->user_full_names;
                        $user_id = $user->user_id;
                        $user_country = $user->user_country;
                        $user_mobile = $user->user_mobile;
                        $datecreated = date('Y-m-d H:i:s');
                        $expirydate = date('Y-m-d H:i:s', strtotime($datecreated.' + 1 days'));
                        $normalizedPhoneNumber = $this->normalizePhoneNumber($user_mobile,$user_country);
                        $phone_number = ltrim($normalizedPhoneNumber,'+');
                        // time of token Crea
                        $dateCreated = date('Y-m-d H:i:s');

                        // hash the token with sha254 and system salt
                        $hashedToken = hash('sha256', env('SALT') . $token);

                        // insert the token into api_token_api
                        $insert_token = DB::connection('mydb_sqlsrv')->insert('INSERT INTO api_tokens_tb(token_scheme_code, token_username,token_user_category,token_used,token_value,token_source,token_date) VALUES ( ?, ?, ?,?, ?, ?, ?)', ['TEST',$identifier,'Normal',0,$hashedToken,'API',$dateCreated]);

                        if ($insert_token) {
                            $mailData = [
                                'token' => $token,
                                'name' => $name,
                            ];
                            try {

                                // Send  Email and message with token
                                Mail::to($identifier)->send(new PasswordResetMail($mailData));
                                $string1 = (string) $token;

                                $parameters = [
                                    'message' => "Hello  $name,  Use the OTP below $string1.", // the actual message
                                    'sender_id' => 'OCTAGON', // please always maintain capital letters. possible value: OCTAGON, IPM, MOBIKEZA
                                    'recipient' => $normalizedPhoneNumber, // alwaynew_nos begin with country code. Let us know any country you need us to enable.
                                    'type' => 'plain', // possible value: plain, mms, voice, whatsapp, default plain
                                ];

                                try {
                                    // Initialize the SDK
                                    $AT = new AfricasTalking($username, $apiKey);
                                    $sms = $AT->sms();

                                    // Send SMS
                                    $result = $sms->send([
                                        'to'      => [$normalizedPhoneNumber],
                                        'message' => $parameters['message'],
                                        'from'    => $parameters['sender_id'],
                                    ]);

                                    //dd($result);

                                    return response()->json([
                                        'status' => 200,
                                        'token' => $token,
                                        'operation' => 'success',
                                        'message' => "OTP sent to $normalizedPhoneNumber and $identifier successfully",
                                    ], 200);
                                } catch (\Throwable $th) {
                                    return response()->json([
                                        'status' => 400,
                                        'operation' => 'fail',
                                        'message' => 'Unable to send OTP. Error: ' . $th->getMessage(),
                                    ], 400);
                                }
                            } catch (\Throwable $th) {
                                return response()->json(
                                    [
                                        'status' => 400,
                                        'operation' => 'fail',
                                        'message' => 'Unable to send OTP (Email) ',
                                    ],
                                    400
                                );
                            }
                        }
                    } else {
                        return response()->json([
                            'status' => 400,
                            'operation' => 'fail',
                            'message' => 'Email not registered.',
                        ], 400);
                    }
                } elseif (is_numeric($identifier)) {
                    $sql_user = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb where user_phone like '%$identifier%'");
                    if ($sql_user) {
                        $pattern = "/^(\+254|254|0|7)[1-9]\d{8}$/";
                        if (preg_match($pattern, $identifier, $matches)) {
                            if ($matches[1] === '254') {
                                $new_number = substr($identifier, 3);
                                $identifier = "0$new_number";
                            } elseif ($matches[1] === '+254') {
                                $new_number = substr($identifier, 4);
                                $identifier = "0$new_number";
                            } elseif ($matches[1] === '7') {
                                $identifier = "0$identifier";
                            }
                        }
                        $token = rand(100000, 999999);
                        $user = $sql_user[0];
                        $name = $user->user_full_names;
                        $user_id = $user->user_id;
                        $datecreated = date('Y-m-d H:i:s');
                        $expirydate = date('Y-m-d H:i:s', strtotime($datecreated.' + 1 days'));

                        $insert_token = DB::connection('mydb_sqlsrv')
                            ->insert('INSERT INTO tokens(token_key,user_id,expire_date,created_date)
                        values (?,?,?,?)', [$token, $user_id, $expirydate, $datecreated]);
                        if ($insert_token) {
                            $string1 = (string) $token;
                            $identifier = substr($identifier, 1);
                            $new_no = "+254$identifier";
                            $parameters = [
                                'message' => "Hello  $name,  Use the OTP below $string1.", // the actual message
                                'sender_id' => 'OCTAGON', // please always maintain capital letters. possible value: OCTAGON, IPM, MOBIKEZA
                                'recipient' => $new_no, // alwaynew_nos begin with country code. Let us know any country you need us to enable.
                                'type' => 'plain', // possible value: plain, mms, voice, whatsapp, default plain
                            ];
                            // $parameters = [
                            //     'message' => "Hello  $name,  Use the OTP below $string1.", // the actual message
                            //     'sender_id' => 'OCTAGON', // please always maintain capital letters. possible value: OCTAGON, IPM, MOBIKEZA
                            //     'recipient' => $new_no, // alwaynew_nos begin with country code. Let us know any country you need us to enable.
                            //     'type' => 'plain', // possible value: plain, mms, voice, whatsapp, default plain
                            // ];
                            try {
                                // $ch = curl_init();
                                // curl_setopt($ch, CURLOPT_URL, 'https://sms.octagonafrica.com/api/v3/sms/send');
                                // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
                                // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                                // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                                // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
                                // curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                //     'Authorization: Bearer 9|b2F8SetSBpEQQkmWgHF2uzb8S6ooN0Y8iQpJBy7V', // will be specific to each application we are building internally. Current token will be disabled after test.
                                //     'Content-Type: application/json',
                                //     'Accept: application/json',
                                // ]);

                                // $get_sms_status = curl_exec($ch);

                                // if (curl_errno($ch)) {
                                //     $get_sms_status = curl_error($ch);
                                // }

                                // curl_close($ch);
                                // Initialize the SDK
                                $AT = new AfricasTalking($username, $apiKey);
                                $sms = $AT->sms();

                                // Send SMS
                                $result = $sms->send([
                                    'to'      => [$new_no],
                                    'message' => $parameters['message'],
                                    'from'    => $parameters['sender_id'],
                                ]);


                                return response()->json(
                                    [
                                        'status' => 200,
                                        'token' => $token,
                                        'operation' => 'success',
                                        'message' => "OTP sent to $new_no phone number",
                                        // 'sms status' => $get_sms_status
                                    ],
                                    200
                                );
                            } catch (\Throwable $th) {
                                return response()->json(
                                    [
                                        'status' => 400,
                                        'operation' => 'fail',
                                        'message' => 'Unable to send OTP',
                                    ],
                                    400
                                );
                            }
                        }
                    } else {
                        return response()->json([
                            'status' => 400,
                            'operation' => 'fail',
                            'message' => 'Phone number not registered.',
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
                        $expirydate = date('Y-m-d H:i:s', strtotime($datecreated.' + 1 days'));
                        if ($phone) {
                            $token = rand(100000, 999999);
                            $pattern = "/^(\+254|254|0|7)[1-9]\d{8}$/";
                            if (preg_match($pattern, $phone, $matches)) {
                                if ($matches[1] === '254') {
                                    $new_number = substr($phone, 3);
                                    $phone = "0$new_number";
                                } elseif ($matches[1] === '+254') {
                                    $new_number = substr($phone, 4);
                                    $phone = "0$new_number";
                                } elseif ($matches[1] === '7') {
                                    $phone = "0$phone";
                                }
                            }
                            $insert_token = DB::connection('mydb_sqlsrv')
                                ->insert('INSERT INTO tokens(token_key,user_id,expire_date,created_date)
                                values (?,?,?,?)', [$token, $user_id, $expirydate, $datecreated]);
                            if ($insert_token) {
                                $string1 = (string) $token;

                                $phone = substr($phone, 1);
                                $new_no = "+254$phone";
                                $parameters = [
                                    'message' => "Hello  $name,  Use the OTP below to reset your password into the system $string1.", // the actual message
                                    'sender_id' => 'OCTAGON', // please always maintain capital letters. possible value: OCTAGON, IPM, MOBIKEZA
                                    'recipient' => "$new_no", // always begin with country code. Let us know any country you need us to enable.
                                    'type' => 'plain', // possible value: plain, mms, voice, whatsapp, default plain
                                ];
                                try {
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

                                    return response()->json(
                                        [
                                            'status' => 200,
                                            'operation' => 'success',
                                            'message' => "OTP sent to $new_no (username)",
                                            // 'sms status' => $get_sms_status
                                        ],
                                        200
                                    );
                                } catch (\Throwable $th) {
                                    return response()->json(
                                        [
                                            'status' => 400,
                                            'operation' => 'fail',
                                            'message' => 'Unable to send OTP',
                                        ],
                                        400
                                    );
                                }
                            } else {
                                return response()->json([
                                    'status' => 400,
                                    'operation' => 'fail',
                                    'message' => 'Internal server Error!! OTP not generated.',
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
                                    'name' => $name,
                                ];
                                try {
                                    // Send verification Email with token
                                    Mail::to($user_email)->send(new PasswordResetMail($mailData));

                                    return response()->json(
                                        [
                                            'status' => 200,
                                            'operation' => 'success',
                                            'message' => "OTP sent to $user_email default0",
                                        ],
                                        200
                                    );
                                } catch (\Throwable $th) {
                                    return response()->json(
                                        [
                                            'status' => 400,
                                            'operation' => 'fail',
                                            'message' => 'Unable to send OTP',
                                        ],
                                        400
                                    );
                                }
                            }
                        }
                        if (!$phone && $user_email) {
                            return response()->json([
                                'status' => 200,
                                'operation' => 'success',
                                'message' => "Your account doesn't have a registered email or phone. Kindly contact the administrator",
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'status' => 400,
                            'operation' => 'fail',
                            'message' => 'Username not found.',
                        ], 400);
                    }
                }
            }
        }

        // Update password
        public function updatePasswordWithCode(Request $request)
        {
            $code = $request['code'];
            $password = $request['password'];

            if (empty($code) || empty($password) || !$code || !$password) {
                return response()->json([
                    'status' => 400,
                    'operation' => 'fail',
                    'message' => 'Code and password not provided.',
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
                            'message' => 'Password reset succesfully',
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 404,
                            'operation' => 'fail',
                            'message' => 'Failed to update Password.',
                            'error' => $update_password,
                        ], 404);
                    }
                } else {
                    return response()->json([
                        'status' => 404,
                        'operation' => 'fail',
                        'message' => 'Password reset link/token has expired',
                    ], 404);
                }
            } else {
                return response()->json([
                    'status' => 404,
                    'operation' => 'fail',
                    'message' => 'Invalid Token/link.',
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
                    'message' => 'New Password/user_id not privded.',
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
                            'message' => 'Password updated successfully.',
                        ],
                        200
                    );
                } else {
                    return response()->json(
                        [
                            'status' => 404,
                            'success' => false,
                            'message' => 'User Not Found.',
                        ],
                        404
                    );
                }
            } catch (\Throwable $th) {
                return response()->json(
                    [
                        'status' => 400,
                        'success' => false,
                        'message' => $th,
                    ],
                    400
                );
            }
        }

        public function verifyOtp(Request $request)
        {
            $rules = [
                'identifier' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        $existsInMembers = DB::connection('mydb_sqlsrv')
                                             ->table('members_tb') // Check in `members_tb`
                                             ->where('m_phone', $value)
                                             ->exists();
            
                        $existsInUsers = DB::connection('mydb_sqlsrv')
                                           ->table('sys_users_tb') // Check in `users`
                                           ->where('user_phone', $value)
                                           ->orwhere('user_mobile', $value)
                                           ->exists();
            
                        if (!$existsInMembers || !$existsInUsers) {
                            $fail('No account is registered with this number ' . $attribute . '.');
                        }
                    },
                ],
                'otp' => 'required|min:6',
            ];

            // Validate request
            $validator = Validator::make($request->all(), $rules);
            //If validation fails, return a JSON response with the errors
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422); // 422 Unprocessable Entity
            } //look into this
            $data = $request->all();
            $identifier = $data['identifier'];
            $phone_number = str_replace('+', '', $identifier);
            //$phone_number = "'" . str_replace('+', '', $identifier) . "'";

            //$phone_number='254743126150';
            //die($phone_number);
            $otp = $data['otp'];

            $hashedOtp = hash('sha256',env('SALT') . $otp);
            if ($validator->fails()) {
                return response()->json(
                    [
                        'status' => 400,
                        'success' => false,
                        'message' => 'Invalid request. Please input Valid OTP.',
                    ], 400
                );
            } else {
                //$token_exist = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM  api_tokens_tb WHERE token_username = '$phone_number' AND token_value = '$otp' ORDER BY token_date DESC");
                $token_exist = DB::connection('mydb_sqlsrv')->select(
                    "SELECT TOP 1 * FROM api_tokens_tb WHERE token_username = ? AND token_value = ? and token_used=0 ORDER BY token_date DESC",
                    [$phone_number, $hashedOtp]
                );
                if (count($token_exist) > 0) {
                    //check if the token has been used
                    //die();
                    $token_update = DB::connection('mydb_sqlsrv')->table('api_tokens_tb')->where('token_username',$phone_number)->where('token_id',$token_exist[0]->token_id)->update(['token_used'=>1]);

                    //check if user exists already
                    $coreNumber = substr($identifier, -9); // Extracts the last 9 digits, assuming all numbers end with similar length

                    // SQL Query that encompasses all countries
                    $query = DB::connection('mydb_sqlsrv')->select("
                        SELECT TOP 1 user_id as m_id,user_national_id as m_id_number,user_gender as m_gender,user_phone as m_phone,user_email as m_email,user_full_names as m_name,user_schemes as m_scheme_code,user_member_no as m_number,user_enc_pwd_link_exp_date AS m_doe,user_enc_pwd_link_exp_date AS m_doj,user_enc_pwd_link_exp_date AS m_dob  FROM sys_users_tb
                        WHERE user_phone LIKE '%$coreNumber%'  -- Core number (matches all formats)
                        OR user_phone LIKE '%254$coreNumber%'  -- Kenya (without '+')
                        OR user_phone LIKE '%+254$coreNumber%' -- Kenya (with '+')
                        OR user_phone LIKE '%256$coreNumber%'  -- Uganda (without '+')
                        OR user_phone LIKE '%+256$coreNumber%' -- Uganda (with '+')
                        OR user_phone LIKE '%260$coreNumber%'  -- Zambia (without '+')
                        OR user_phone LIKE '%+260$coreNumber%' -- Zambia (with '+');
                    ");
                    if (count($query) >0){
                        return response()->json(
                            [
                                'status' => 200,
                                'operation' => 'success',
                                'message' => 'Phone Verified Successfully',
                                'user_exists' => true,
                                'user_data' => $query
                            ],
                            200
                        );
                    } else{
                        return response()->json(
                            [
                                'status' => 200,
                                'operation' => 'success',
                                'message' => 'Phone Verified Successfully',
                                'user_exists' => false
                            ],
                            200
                        );
                    }

                } else {
                    return response()->json(
                        ['status' => 400,
                            'success' => false,
                            'message' => 'Access Denied.Please Put a Valid OTP',

                        ],
                        400
                    );
                }
            }
        }

        function normalizePhoneNumber($phoneNumber, $country) {
            // Remove any non-digit characters from the input
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

            // Define country codes
            $countryCodes = [
                'kenya' => '+254',
                'uganda' => '+256',
                'zambia' => '+260'
            ];

            // Determine the country code using a ternary expression
            $country = strtolower($country);
            $countryCode = isset($countryCodes[$country]) ? $countryCodes[$country] : 'Invalid country';

            if ($countryCode === 'Invalid country') {
                return $countryCode;
            }

            // Handle different phone number formats
            if (substr($phoneNumber, 0, 1) === '0') {
                // If it starts with 0, remove it and add the country code
                $phoneNumber = $countryCode . substr($phoneNumber, 1);
            } elseif (substr($phoneNumber, 0, strlen($countryCode) - 1) === substr($countryCode, 1)) {
                // If it starts with the country code without '+', add the '+'
                $phoneNumber = '+' . $phoneNumber;
            } elseif (substr($phoneNumber, 0, strlen($countryCode)) !== $countryCode) {
                // If it doesn't start with the correct country code, add the country code
                $phoneNumber = $countryCode . $phoneNumber;
            }
            // If it starts with the correct country code and already has '+', leave as is
            // The else block is implicit here since the number is already correctly formatted

            return $phoneNumber;
        }

    }
