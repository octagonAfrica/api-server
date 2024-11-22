<?php

namespace App\Http\Controllers;

use App\Mail\iCollectVerificationMail;
use App\Mail\CellulantVerificationMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Http;

class ContributionsController extends Controller
{
    // recieveContributions via MPESA
    public function recievePayments(Request $request)
    {
        date_default_timezone_set('Africa/Lusaka');
        $validator = Validator::make($request->all(), [
            'accountName' => 'required|string|max:255',
            'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'operation' => 'failure',
                'message' => 'Missing data/invalid data. Please try again.',
                'errors' => $validator->errors()->all(),
            ], 400);
        } else {
            $accountName = $request->input('accountName');
            $parts = explode(":", $accountName);
            $schemeCode = $parts[0];
            $memberNumber = $parts[1];
            $amount = $request->input('amount');
            $date = date('Y-m-d');
            $currentDate = Carbon::now();
            // Format the current date to display the month in words
            $currentMonthInWords = $currentDate->formatLocalized('%B');
            $memberDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  sys_users_tb su, members_tb m where  m.m_number = su.user_member_no and user_member_no ='$memberNumber' and user_schemes = '$schemeCode' ");
            if (count($memberDetails) > 0) {
                $member = $memberDetails[0];
                $user_full_names = $member->user_full_names;
                $user_email = $member->user_email;
                $user_phone = $member->user_phone;
                $user_payroll_number = $member->m_system_payroll;

                $sql = "SELECT sp.period_id, ss.sub_period_id
                    FROM scheme_periods_tb sp, scheme_sub_periods_tb ss
                    WHERE
                        sp.period_id = ss.scheme_period_id
                        and GETDATE() BETWEEN period_start_date AND period_end_date
                        and GETDATE() BETWEEN sub_period_start_date AND sub_period_end_date
                        and sp.period_active =	1
                        and period_scheme_code = '$schemeCode'";
                $periods = DB::connection('mydb_sqlsrv')->select($sql);

                if(count($periods)  == 0 ){
                    // periods not found
                    return response()->json([
                        'status' => 401,
                        'operation' => 'failure',
                        'message' => 'Periods not found',
                    ], 401);
                    exit();
                }

                $batch_type = 'Contributions';
                $batch_description = $currentMonthInWords.' Contributions';
                $date_paid = date('Y-m-d');
                $cont_scheme_code = $schemeCode;
                $cont_group_code = 'Scheme';
                $date_contribution_loaded = date('Y-m-d');
                $posted_on = date('Y-m-d');
                $posted_by = 'iCollect';
                $batched_by = 'iCollect';
                $date_batched = date('Y-m-d H:i:s');

                $periodData = $periods[0];
                $period_id = $periodData->period_id;
                $sub_period_id = $periodData->sub_period_id;
                // contributions batch
                $batch_id = DB::connection('mydb1')->table('contribution_batch')->insertGetId([
                    'period_id' => $period_id,
                    'sub_period_id' => $sub_period_id,
                    'batch_type' => $batch_type,
                    'batch_description' => $batch_description,
                    'date_paid' => $date_paid,
                    'cont_scheme_code' => $cont_scheme_code,
                    'cont_group_code' => $cont_group_code,
                    'date_contribution_loaded' => $date_contribution_loaded,
                    'posted_on' => $posted_on,
                    'posted_by' => $posted_by,
                    'batched_by' => $batched_by,
                    'date_batched' => $date_batched,
                ]);
                // batch contributions
                $member_code = $memberNumber;
                $member_name = $user_full_names;
                $payroll_number = $user_payroll_number;
                $schemeCode = 'OUPTF0002';
                if ($schemeCode === 'OUPTF0002') {
                    $ee_contributions = $amount;
                    $insertBatchContributions = DB::connection('mydb1')->insert('INSERT INTO batch_contributions(batch_id, member_code, member_name, payroll_number, ee_contribution)
                    values (?, ?, ?, ?, ?)', [$batch_id, $member_code, $member_name, $payroll_number, $ee_contributions]);
                } else {
                    $av_contributions = $amount;
                    $insertBatchContributions = DB::connection('mydb1')->insert('INSERT INTO batch_contributions(batch_id, member_code, member_name, payroll_number,  av_contribution)
                    values (?, ?, ?, ?, ?)', [$batch_id, $member_code, $member_name, $payroll_number,  $av_contributions]);
                }
                $subject = " Payment Confirmation  $batch_id";
                $mailData = [
                    'name' => $user_full_names,
                    'subject' => $subject,
                    'amount' => $amount,
                ];
                $email = $user_email;
                Mail::to($email)->send(new iCollectVerificationMail($mailData));
                // send notification email
                return response()->json([
                    'status' => 200,
                    'operation' => 'success',
                    'message' => 'Transaction has been recieved successfully',
                ], 200);
            } else {
                // error
                return response()->json([
                    'status' => 409,
                    'operation' => 'failure',
                    'message' => 'Client Not found/Does not exist',
                ], 409);
            }
        }
    }

    // make contributions via mpesa
    public function lipaNaMpesa(Request $request)
    {
        date_default_timezone_set('Africa/Nairobi');
        $validator = Validator::make($request->all(), [
            'accountName' => 'required|string|max:255',
            'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/|max:13',
            'phoneNumber' => 'required|regex:/^254\d{9}$/|max:12|min:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'operation' => 'failure',
                'message' => 'Missing data/invalid data. Please try again.',
                'errors' => $validator->errors()->all(),
            ], 400);
        } else { // fix passkey, paybill
            $accountName = $request->input('accountName');
            $amount = $request->input('amount');
            $phoneNumber = $request->input('phoneNumber');
            $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            $credentials = base64_encode('TJuxjM4bkaIzocyPsM9d1Qwbnoj1oaFh:WFaupM73VpOWYoFP');
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Basic '.$credentials]); // setting a custom header
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            $curl_response = curl_exec($curl);

            $token = json_decode($curl_response)->access_token;
            $url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'Authorization:Bearer '.$token]); // setting custom header
            $passkey = '95220447039eeaa364e3318e0104acb60b96d03bb0ea857890420acaa502669a';
            $timestamp = date('YmdHis');
            $password = base64_encode('4016955'.$passkey.$timestamp);
            $curl_post_data = [
                // Fill in the request parameters with valid values
                'BusinessShortCode' => '4016955',
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CommandID' => 'CheckIdentity',
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => $amount,
                'PartyA' => $phoneNumber,
                'PartyB' => '4016955',
                'PhoneNumber' => $phoneNumber,
                'CallBackURL' => 'https://vesencomputing.com/hj/callbackurl.php',
                'AccountReference' => $accountName,
                'TransactionDesc' => 'Payment for goods and services',
            ];
            $data_string = json_encode($curl_post_data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response);
            $merchantRequestID = $response->MerchantRequestID;
            $checkOutRequestID = $response->CheckoutRequestID;
            $responseCode = $response->ResponseCode;
            $responseDescription = $response->ResponseDescription;
            $customerMessage = $response->CustomerMessage;
            $createdAT = date('Y-m-d H:i:s');
            $transactionID = bin2hex(random_bytes(6));

            $data = [
                'stkresponse' => $response,
            ];

            $insertSTKResponse = DB::connection('mydb_sqlsrv')->insert('INSERT INTO stk_push_responses (transactionID, merchantRequestID, checkOutRequestID, responseCode, responseDescription, customerMessage, amount, phoneNumber, fullresponse, createdAT) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$transactionID, $merchantRequestID, $checkOutRequestID, $responseCode, $responseDescription, $customerMessage, $amount, $phoneNumber, json_encode($response), $createdAT]);
            if ($responseCode === '0') {
                return response()->json([
                    'status' => 200,
                    'operation' => 'success',
                    'message' => 'STK successfully sent',
                    'data' => $data,
                ], 200);
            } else {
                return response()->json([
                    'status' => 409,
                    'operation' => 'failure',
                    'message' => 'STK not sent, an error occurred',
                    'data' => $data,
                ], 409);
            }
        }
    }

    //make contribuitions via cellullant.
    public function cellulantDeposits(Request $request)
    {
        date_default_timezone_set('Africa/Nairobi');
        $validator = Validator::make($request->all(), [
            'accountName' => 'required|string|max:255',
            'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/|max:13',
            'phoneNumber' => 'required|regex:/^254\d{9}$/|max:12|min:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'operation' => 'failure',
                'message' => 'Missing data/invalid data. Please try again.',
                'errors' => $validator->errors()->all(),
            ], 400);
        } else { // add country to payload
            $accountName = $request->input('accountName');
            $amount = $request->input('amount');
            $phoneNumber = $request->input('phoneNumber');
            $createdAT = date('Y-m-d H:i:s');

            $parts = explode(":", $accountName);
            $schemeCode = $parts[0];
            $memberNumber = $parts[1];

            $memberCountry = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM members_tb, scheme_tb  WHERE m_number ='$memberNumber' and m_scheme_code = '$schemeCode' and scheme_code = '$schemeCode'");
            if(count($memberCountry)> 0 ){
                $memberData = $memberCountry[0];
                $schemeCountry = $memberData->scheme_country;
                $name = $memberData->m_name;
                $email = $memberData->m_email;
                $randomNumber =rand(100000, 999999);

               $data = $email . $phoneNumber . $name.$randomNumber.$createdAT;

               $randomChars = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
                $data .= $randomChars;
                $hexData = bin2hex($data);
                $merchantTransactionID = $hexData;

                $description = "Contribution to " . $memberData->m_scheme_code;
                $countryCode = '';
                if($schemeCountry == 'Kenya' ||$schemeCountry == 'kenya' ){
                    $countryCode = 'KE';
                }else if($schemeCountry == 'Uganda' ||$schemeCountry == 'uganda' ){
                    $countryCode = 'UG';
                }else if($schemeCountry == 'Zambia' ||$schemeCountry == 'zambia' ){
                    $countryCode = 'ZAM';
                }

                $cellulantData = [
                    'merchantTransactionID' => $merchantTransactionID,
                    'accountName' => $accountName,
                    'amount' => $amount,
                    'phoneNumber' => $phoneNumber,
                    'requestCountryCode' => $countryCode,
                    'name' => $name,
                    'email' => $email,
                    'description' => $description,
                ];

                try {
                    // Make the HTTP request here
                    $response = Http::asMultipart()->timeout(160)->post('https://cloud.octagonafrica.com/crm/portal/CheckOutEncryption.php', $cellulantData);

                    // Check for success and process the response
                    if ($response->successful()) {
                        $responseData = $response->json();

                        // Check if the response contains the checkout URL
                        if (isset($responseData['data'])) {
                            $checkOutUrl = $responseData['data'];
                            $insertCelullantContributions = DB::connection('mydb_sqlsrv')->insert('INSERT INTO cellulant_contributions (merchantTransactionID, accountName, amount, phoneNumber, requestCountryCode, name, email, responseMessage, createdAT) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [$merchantTransactionID, $accountName, $amount, $phoneNumber, $countryCode, $name, $email, json_encode($response->body()),  $createdAT]);
                            if ($insertCelullantContributions) {
                                return response()->json([
                                    'status' => 200,
                                    'operation' => 'success',
                                    'message' => 'Checkout URL Successfully Generated',
                                    'checkoutUrl' => $checkOutUrl,
                                ], 200);
                            } else {
                                return response()->json([
                                    'status' => 409,
                                    'operation' => 'failure',
                                    'message' => 'An error occurred while inserting data into the database',
                                ], 409);
                            }
                        } else {
                            // Response does not contain the checkout URL
                            return response()->json([
                                'status' => 500,
                                'error' => 'Response does not contain checkout URL',
                            ], 500);
                        }
                    } else {
                        // Failed request
                        return response()->json([
                            'status' => $response->status(),
                            'error' => 'Failed to send data to endpoint',
                            'message' => $response->body(),
                        ], $response->status());
                    }
                } catch (ConnectionException $e) {
                    // Handle connection exception
                    return response()->json([
                        'status' => 500,
                        'error' => 'Connection error: ' . $e->getMessage(),
                    ], 500);
                }

            }else {
                return response()->json([
                    'status' => 404,
                    'operation' => 'failure',
                    'message' => 'Member Not Found',
                ], 404);
            }
        }
    }

    public function cellulantCallBackURL(Request $request)
    {
        // Get the JSON data from the request
        $requestData = $request->json()->all();
        $requestStatusCode = $requestData['request_status_code'];
        $accountNumber = $requestData['account_number'];
        $currencyCode = $requestData['currency_code'];
        $checkoutRequestId = $requestData['checkout_request_id'];
        $requestAmount = $requestData['request_amount'];
        $amountPaid = $requestData['amount_paid'];
        $merchantTransactionId = $requestData['merchant_transaction_id'];
        $serviceCode = $requestData['service_code'];
        $requestDate = $requestData['request_date'];
        $requestStatusDescription = $requestData['request_status_description'];
        $msisdn = $requestData['msisdn'];
        // $shortUrl = $requestData['short_url'];
        $payments = $requestData['payments'];
        // $failed_payments= $requestData['failed_payments'];

        if($requestStatusCode ==178){
            $update_status = DB::connection('mydb_sqlsrv')->update("UPDATE cellulant_contributions SET paymentStatus ='PAID', requestStatusCode ='$requestStatusCode', payments = '".json_encode($payments)."', requestStatusDescription= '$requestStatusDescription' WHERE merchantTransactionID ='$merchantTransactionId'");
            $parts = explode(":", $accountNumber);
            $schemeCode = $parts[0];
            $memberNumber = $parts[1];
            $amount = $amountPaid;
            $date = date('Y-m-d');
            $currentDate = Carbon::now();
            // Format the current date to display the month in words
            $currentMonthInWords = $currentDate->formatLocalized('%B');
            $memberDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  sys_users_tb su, members_tb m, scheme_tb sm where  m.m_number = su.user_member_no and user_member_no ='$memberNumber' and user_schemes = '$schemeCode' and sm.scheme_code = '$schemeCode'");
            if (count($memberDetails) > 0) {
                $member = $memberDetails[0];
                $user_full_names = $member->user_full_names;
                $user_email = $member->user_email;
                $user_phone = $member->user_phone;
                $user_payroll_number = $member->m_system_payroll;
                $scheme_adminstration = $member->scheme_adminstration;

                $sql = "SELECT sp.period_id, ss.sub_period_id
                    FROM scheme_periods_tb sp, scheme_sub_periods_tb ss
                    WHERE
                        sp.period_id = ss.scheme_period_id
                        and GETDATE() BETWEEN period_start_date AND period_end_date
                        and GETDATE() BETWEEN sub_period_start_date AND sub_period_end_date
                        and sp.period_active =	1
                        and period_scheme_code = '$schemeCode'";
                $periods = DB::connection('mydb_sqlsrv')->select($sql);

                if(count($periods)  == 0 ){
                    // periods not found
                    return response()->json([
                        'status' => 401,
                        'operation' => 'failure',
                        'message' => 'Periods not found',
                    ], 401);
                    exit();
                }

                $batch_type = 'Contributions';
                $batch_description = $currentMonthInWords.' Contributions';
                $date_paid = date('Y-m-d');
                $cont_scheme_code = $schemeCode;
                $cont_group_code = 'Scheme';
                $date_contribution_loaded = date('Y-m-d');
                $posted_on = date('Y-m-d');
                $posted_by = 'iCollect';
                $batched_by = 'iCollect';
                $date_batched = date('Y-m-d H:i:s');

                $periodData = $periods[0];
                $period_id = $periodData->period_id;
                $sub_period_id = $periodData->sub_period_id;
                // contributions batch
                $batch_id = DB::connection('mydb1')->table('contribution_batch')->insertGetId([
                    'period_id' => $period_id,
                    'sub_period_id' => $sub_period_id,
                    'batch_type' => $batch_type,
                    'batch_description' => $batch_description,
                    'date_paid' => $date_paid,
                    'cont_scheme_code' => $cont_scheme_code,
                    'cont_group_code' => $cont_group_code,
                    'date_contribution_loaded' => $date_contribution_loaded,
                    'posted_on' => $posted_on,
                    'posted_by' => $posted_by,
                    'batched_by' => $batched_by,
                    'date_batched' => $date_batched,
                ]);
                // batch contributions
                $member_code = $memberNumber;
                $member_name = $user_full_names;
                $payroll_number = $user_payroll_number;

                if ($schemeCode === 'IPP' ) {
                    $ee_contributions = $amount;
                    $insertBatchContributions = DB::connection('mydb1')->insert('INSERT INTO batch_contributions(batch_id, member_code, member_name, payroll_number, ee_contribution)
                    values (?, ?, ?, ?, ?)', [$batch_id, $member_code, $member_name, $payroll_number, $ee_contributions]);
                } else {
                    $av_contributions = $amount;
                    $insertBatchContributions = DB::connection('mydb1')->insert('INSERT INTO batch_contributions(batch_id, member_code, member_name, payroll_number,  av_contribution)
                    values (?, ?, ?, ?, ?)', [$batch_id, $member_code, $member_name, $payroll_number,  $av_contributions]);
                }
                $subject = "Contribution Conformation  $batch_id";
                $mailData = [
                    'name' => $user_full_names,
                    'subject' => $subject,
                    'amount' => $amount,
                ];
                $email = $user_email;
                Mail::to($email)->send(new CellulantVerificationMail($mailData));
                // send notification email
                return response()->json([
                    'status' => 200,
                    'operation' => 'success',
                    'message' => 'Transaction has been recieved successfully',
                ], 200);
            } else {
                // error
                return response()->json([
                    'status' => 404,
                    'operation' => 'failure',
                    'message' => 'Client Not found/Does not exist',
                ], 404);
            }
        }
    }

}
