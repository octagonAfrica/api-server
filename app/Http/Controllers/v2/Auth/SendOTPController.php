<?php

namespace App\Http\Controllers\v2\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Support\Facades\Mail;

class SendOTPController extends Controller
{
    public function sendOTPToPhone(Request $request)
{
    $identifier = $request['identifier'];

    // Africas Talking credentials
    $username = env('AFRICAS_TALKING_USERNAME');
    $apiKey = env('AFRICAS_TALKING_API_KEY');

    // Validate identifier
    if (!$identifier || empty($identifier)) {
        return response()->json([
            'status' => 400,
            'operation' => 'fail',
            'message' => 'Identifier required.',
        ], 400);
    }

    if (!is_numeric($identifier)) {
        return response()->json([
            'status' => 400,
            'operation' => 'fail',
            'message' => 'Identifier must be numeric.',
        ], 400);
    }

    $token = rand(100000, 999999);
    $dateCreated = date('Y-m-d H:i:s');
    $expiryDate = date('Y-m-d H:i:s', strtotime($dateCreated . ' + 1 days'));
    $trimmedNumber = trim($identifier);
    $noSpacesNumber = str_replace(' ', '', $trimmedNumber);
    $new_no = "+$noSpacesNumber";

    // Insert token into the database
    $phone_number = ltrim("+$identifier", '+');

    //sha 254 hashed token
    $hashedToken = hash('sha256', env('SALT') . $token);
    $insertToken = DB::connection('mydb_sqlsrv')
        ->insert('INSERT INTO api_tokens_tb(token_scheme_code, token_username,token_user_category,token_used,token_value,token_source,token_date) VALUES ( ?, ?, ?,?, ?, ?, ?)', ['TEST',$phone_number,'Normal',0,$hashedToken,'API',$dateCreated]);
    if ($insertToken) {
        $parameters = [
            'message' => "Hello Dear Esteemed Customer, Use the OTP below $token.",
            'sender_id' => 'OCTAGON',
            'recipient' => $new_no,
            'type' => 'plain',
        ];

        try {
            // Initialize the SDK
            $AT = new AfricasTalking($username, $apiKey);
            $sms = $AT->sms();

            // Send SMS
            $result = $sms->send([
                'to'      => [$new_no],
                'message' => $parameters['message'],
                'from'    => $parameters['sender_id'],
            ]);

            return response()->json([
                'status' => 200,
                'token' => $token,
                'operation' => 'success',
                'message' => "OTP sent to $new_no",
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'Unable to send OTP. Error: ' . $th->getMessage(),
            ], 400);
        }
    } else {
        return response()->json([
            'status' => 500,
            'operation' => 'fail',
            'message' => 'Failed to store OTP token.',
        ], 500);
    }
}
    public function sendOTPToEmail(Request $request){
        $identifier = $request['identifier'];

        // Validate identifier
        if (!$identifier || empty($identifier)) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'Identifier required.',
            ], 400);
        } else{
            $token = rand(100000, 999999);
            $datecreated = date('Y-m-d H:i:s');
            $expirydate = date('Y-m-d H:i:s', strtotime($datecreated.' + 1 days'));
            //$normalizedPhoneNumber = $this->normalizePhoneNumber($user_mobile,$user_country);
            //$phone_number = ltrim($normalizedPhoneNumber,'+');
            // time of token Crea
            $dateCreated = date('Y-m-d H:i:s');

            // hash the token with sha254 and system salt
            $hashedToken = hash('sha256', env('SALT') . $token);

            // insert the token into api_token_api
            $insert_token = DB::connection('mydb_sqlsrv')->insert('INSERT INTO api_tokens_tb(token_scheme_code, token_username,token_user_category,token_used,token_value,token_source,token_date) VALUES ( ?, ?, ?,?, ?, ?, ?)', ['TEST',$identifier,'Normal',0,$hashedToken,'API',$dateCreated]);

            // check if the user exists in the database
            $sql_user = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb where user_email = '$identifier'");

            if ($sql_user) {
                $user = $sql_user[0];
                $name = $user->user_full_names;
                $user_id = $user->user_id;
                $user_country = $user->user_country;
                $user_mobile = $user->user_mobile;
                if ($insert_token) {
                    $mailData = [
                        'token' => $token,
                        'name' => $name,
                    ];
                    try {
                        // Send  Email and message with token
                    Mail::to($identifier)->send(new PasswordResetMail($mailData));
                    return response()->json([
                        'status' => 200,
                        'token' => $token,
                        'operation' => 'success',
                        'message' => "OTP sent to $identifier successfully",
                        //'userexists'=> true,
                        //'userdata' => $user
                    ], 200);
                    } catch (\Throwable $th) {
                        return response()->json(
                            [
                                'status' => 400,
                                'operation' => 'fail',
                                //'message' => 'Unable to send OTP (Email) ',
                                'message' => 'Error: ' . $th->getMessage(),
                            ],
                            400
                        );
                    }
                }
            } else {
                if ($insert_token) {
                    $mailData = [
                        'token' => $token,
                        'name' => "Dear Esteemed Customer"
                    ];
                    try {
                        // Send  Email and message with token
                    Mail::to($identifier)->send(new PasswordResetMail($mailData));
                    return response()->json([
                        'status' => 200,
                        'token' => $token,
                        'operation' => 'success',
                        'message' => "OTP sent to $identifier successfully",
                        //'userexists'=> false,
                        //'userdata' => null
                    ], 200);
                    } catch (\Throwable $th) {
                        return response()->json(
                            [
                                'status' => 400,
                                'operation' => 'fail',
                                //'message' => 'Unable to send OTP (Email) ',
                                'message' => 'Error: ' . $th->getMessage(),
                            ],
                            400
                        );
                    }
                }
                }
            }
    }

     public function verifyEmailOTP(Request $request){
        $rules = [
            'identifier' => 'required',
            'otp' => 'required|min:6',
        ];

        // Validate request
        $validator = Validator::make($request->all(), $rules);
           // If validation fails, return a JSON response with the errors
        // if ($validator->fails()) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Validation failed',
        //         'errors' => $validator->errors()
        //     ], 422); // 422 Unprocessable Entity
        // } //look into this
        $data = $request->all();
        $identifier = $data['identifier'];
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
                [$identifier, $hashedOtp]
            );
            if (count($token_exist) > 0) {
                //check if the token has been used
                //die();
                $token_update = DB::connection('mydb_sqlsrv')->table('api_tokens_tb')->where('token_username',$identifier)->where('token_id',$token_exist[0]->token_id)->update(['token_used'=>1]);

                //check if user exists already
                $coreNumber = substr($identifier, -9); // Extracts the last 9 digits, assuming all numbers end with similar length

                // SQL Query that encompasses all countries
                // $query = DB::connection('mydb_sqlsrv')->select("
                //     SELECT TOP 1 * FROM members_tb
                //     WHERE m_email LIKE '%$identifier%'
                // ");

                $query = DB::connection('mydb_sqlsrv')->select("
                SELECT TOP 1 user_id as m_id,user_national_id as m_id_number,user_gender as m_gender,user_phone as m_phone,user_email as m_email,user_full_names as m_name,user_schemes as m_scheme_code,user_member_no as m_number,user_enc_pwd_link_exp_date AS m_doe,user_enc_pwd_link_exp_date AS m_doj,user_enc_pwd_link_exp_date AS m_dob  FROM sys_users_tb
                WHERE user_email = '$identifier'
            ");
                if (count($query) >0){
                    return response()->json(
                        [
                            'status' => 200,
                            'operation' => 'success',
                            'message' => 'Email Verified Successfully',
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
    public function verifyRegistrationOTP(Request $request)
    {
        $rules = [
            'identifier' => 'required',
            'otp' => 'required|min:6',
        ];

        // Validate request
        $validator = Validator::make($request->all(), $rules);
           // If validation fails, return a JSON response with the errors
        // if ($validator->fails()) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Validation failed',
        //         'errors' => $validator->errors()
        //     ], 422); // 422 Unprocessable Entity
        // } //look into this
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
}
