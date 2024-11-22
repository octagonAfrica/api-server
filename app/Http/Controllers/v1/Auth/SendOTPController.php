<?php

namespace App\Http\Controllers\v1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SendOTPController extends Controller
{
    public function sendOTPToPhone(Request $request)
    {
        $identifier = $request['identifier'];
        // $phone = $request['phone'];
        if (!$identifier || empty($identifier)) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'identifier required.',
            ], 400);
        } else {
            if (is_numeric($identifier)) {
                $token = rand(100000, 999999);

                $datecreated = date('Y-m-d H:i:s');
                $expirydate = date('Y-m-d H:i:s', strtotime($datecreated.' + 1 days'));
                $trimmedNumber = trim($identifier);
                $noSpacesNumber = str_replace(' ', '', $trimmedNumber);
                $new_no = "+$noSpacesNumber";

                $phone_number = ltrim("+$identifier", '+');
                $insert_token = DB::connection('mydb_sqlsrv')
                    ->insert('INSERT INTO token_verification(token_key,identifier,expire_date,created_date)
                     values (?,?,?,?)', [$token, $phone_number, $expirydate, $datecreated]);
                if ($insert_token) {
                    $string1 = (string) $token;
                    $parameters = [
                        'message' => "Hello  Dear Esteemed Customer,  Use the OTP below $string1.", // the actual message
                        'sender_id' => 'OCTAGON', // please always maintain capital letters. possible value: OCTAGON, IPM, MOBIKEZA
                        'recipient' => $new_no, // alwaynew_nos begin with country code. Let us know any country you need us to enable.
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
                                'token' => $token,
                                'operation' => 'success',
                                'message' => "OTP sent to $new_no",
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
        $data = $request->all();
        $identifier = $data['identifier'];
        $phone_number = ltrim($identifier, '+');
        $otp = $data['otp'];
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 400,
                    'success' => false,
                    'message' => 'Invalid request. Please input Valid OTP.',
                ], 400
            );
        } else {
            $user_exist = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM token_verification WHERE identifier = '$phone_number' AND token = $otp ORDER BY created_date DESC");
            if (count($user_exist) > 0) {
                return response()->json(
                    [
                        'status' => 200,
                        'operation' => 'success',
                        'message' => 'Phone Verified Successfully',
                    ],
                    200
                );
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
