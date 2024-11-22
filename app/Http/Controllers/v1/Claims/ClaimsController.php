<?php

namespace App\Http\Controllers\Claims;

use App\Http\Controllers\Controller;
use App\Mail\ClaimsVerificationMail;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ClaimsController extends Controller
{
    // send otp to mail and phone number ** Kenya.
    public function sendClaimOTP(Request $request)
    {
        $userID = $request['userID'];
        if (!$userID) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'UserID name required.',
            ], 400);
        } else {
            $UserDetails = DB::connection('mydb_sqlsrv')->select("SELECT user_username, user_full_names, user_email, user_mobile from  sys_users_tb where  user_id like '%$userID%'");
            $user = $UserDetails[0];
            $name = $user->user_full_names;
            $email = $user->user_email;
            $phoneNumber = $user->user_mobile;
            $username = $user->user_username;
            // generate random text for OTP.
            $length = 6;
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = substr(str_shuffle($characters), 0, $length);

            if (is_numeric($phoneNumber)) {
                $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
                if (substr($phoneNumber, 0, 3) === '254') {
                    $phoneNumber = '0'.substr($phoneNumber, 3);
                } elseif (substr($phoneNumber, 0, 4) === '+254') {
                    $phoneNumber = substr($phoneNumber, 1);
                }
                if (substr($phoneNumber, 0, 1) === '7') {
                    $phoneNumber = '0'.$phoneNumber;
                }
                $phoneNumber = '+2547'.substr($phoneNumber, 1);
                $deleteToken = DB::connection('mydb1')->delete("DELETE FROM system_tokens WHERE token_username='$username'");
                $phoneNumber = substr($phoneNumber, 1);
                $new_no = $phoneNumber;
                $insert_token = DB::connection('mydb1')->insert('INSERT into system_tokens(token_username,access_code) VALUES (?,?)', [$username, $randomString]);
                if ($insert_token) {
                    $subject = 'OCTAGON BENEFITS WITHDRAWAL';
                    $mailData = [
                        'name' => $name,
                        'randomString' => $randomString,
                        'subject' => $subject,
                    ];
                    Mail::to($email)->send(new ClaimsVerificationMail($mailData));
                    $parameters = [
                        'message' => "Dear $name, \n Your member portal access token is $randomString ,\n Use it to claim your benefits.\n
                        Contact us at support@octagonafrica.com incase you experience any issues while attempting to log in.\n
                        Regards,\n
                        Octagon Pension Services Team.",
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
                                'randomString' => $randomString,
                                'username' => $username,
                                'operation' => 'success',
                                'message' => "OTP sent to $new_no and  Email send to $email",
                            ],
                            200
                        );
                    } catch (\Throwable $th) {
                        return response()->json(
                            [
                                'status' => 400,
                                'operation' => 'fail',
                                'message' => 'Unable to send  and Email',
                            ],
                            400
                        );
                    }
                }
            }
        }
    }

    // verify otp
    public function verifyClaimsOTP(Request $request)
    {
        $otp = $request['otp'];
        $username = $request['username'];
        if (!$username && !$otp) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'Username and OTP Required.',
            ], 400);
        } else {
            // INSERT into system_tokens(token_username,access_code)
            $verify = DB::connection('mydb1')->select("SELECT TOP (1) * FROM system_tokens WHERE token_username='$username' AND access_code='$otp'");
            if ($verify) {
                return response()->json([
                    'status' => 200,
                    'operation' => 'success',
                    'message' => 'Token Verified Successfully.',
                ], 200);
            } else {
                return response()->json([
                    'status' => 400,
                    'operation' => 'Failed',
                    'message' => 'Invalid Token.',
                ], 400);
            }
        }
    }

    // fetch member details from db
    public function showMemberDetails(Request $request)
    {
        $memberNo = $request['memberNo'];
        $memberSchemeCode = $request['memberSchemeCode'];
        if (!$memberNo && $memberSchemeCode) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'Member Number/Scheme Code required.',
            ], 400);
        } else {
            $memberDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  members_tb where  m_number ='$memberNo' and m_scheme_code = '$memberSchemeCode' ");
            if (count($memberDetails) > 0) {
                $member = $memberDetails[0];
                $memberID = $member->m_id;
                $memberSchemeCode = $member->m_scheme_code;
                $memberName = $member->m_name;
                $memberNumber = $member->m_number;
                $memberDOB = $member->m_dob;
                $memberDoE = $member->m_doe; // emplo
                $memberDoJ = $member->m_doj;
                $memberNationalID = $member->m_id_number;
                $memberKRAPIN = $member->m_pin;
                $memberEmail = $member->m_email;
                $memberPhone = $member->m_phone;
                $memberPhyAddress = $member->m_physical_address;
                $memberPostAdress = $member->m_address;
                $memberKinsName = $member->m_kin_name;
                $memberKinsPhone = $member->m_kin_phone;
                $memberAccountName = $member->m_account_name;
                $memberAccountNo = $member->m_account_no;
                $memberBranchName = $member->m_branch_name;
                $memberBankName = $member->m_bank_name;
                $memberGender = $member->m_gender;
                $memberMarital = $member->m_marital;
                $memberNationality = $member->m_nationality;

                $data = [
                    'memberID' => $memberID,
                    'memberSchemeCode' => $memberSchemeCode,
                    'memberName' => $memberName,
                    'memberNumber' => $memberNumber,
                    'memberNationalID' => $memberNationalID,
                    'memberGender' => $memberGender,
                    'memberDOB' => $memberDOB,
                    'memberMarital' => $memberMarital,
                    'memberNationality' => $memberNationality,
                    'memberKRAPIN' => $memberKRAPIN,
                    'memberEmail' => $memberEmail,
                    'memberPhone' => $memberPhone,
                    'memberPhyAddress' => $memberPhyAddress,
                    'memberPostAdress' => $memberPostAdress,
                    'memberAccountName' => $memberAccountName,
                    'memberAccountNo' => $memberAccountNo,
                    'memberBankName' => $memberBankName,
                    'memberBranchName' => $memberBankName,
                ];

                return response()->json([
                    'status' => 200,
                    'operation' => 'Succces',
                    'message' => 'Member Details Fetched Successfully',
                    'data' => $data,
                ], 200);
            } else {
                return response()->json([
                    'status' => 400,
                    'operation' => 'fail',
                    'message' => 'Member Number Not Found/Invalid.',
                ], 400);
            }
        }
    }

    //     Claims Endpoints Error codes and meanings
    // 404, //details not found
    // 200, //claim done successfully
    // 400, //claim was not made
    // 402, //copying files failed
    // 403,  //profile provided but not approved by admin or Scheme HR
    // 202, //message to client sent successfully to update details
    // 405, //onboarding link not sent
    // 401, //endpoint did not return the expected result
    // 404  //member still active
    // 406, //invalid member number
    // 407, //member does not have an account
    // 411, //member has already posted a claim
    public function addNewClaim(Request $request)
    {
        $memberNo = $request['memberNo'];
        $memberSchemeCode = $request['memberSchemeCode'];
        $m_reason_for_exit = $request['reasonforExit'];
        $m_amount = $request['amount'];
        $dateOfExit = $request['dateOfExit'];
        if (!$memberNo || !$memberSchemeCode || !$m_reason_for_exit || !$m_amount || !$dateOfExit) {
            return response()->json([
                'status' => 404, // details not found
                'operation' => 'fail',
                'message' => 'Member Number/Scheme Code required.',
            ], 404);
        } else {
            date_default_timezone_set('Africa/Nairobi');

            $LogDetails = DB::connection('mydb_sqlsrv')->select("SELECT su.user_username, su.user_full_names, m.m_scheme_code from sys_users_tb su , members_tb m where su.user_national_id= m.m_id_number and m.m_number = '$memberNo' and m.m_scheme_code = '$memberSchemeCode'");
            if (count($LogDetails) > 0) {
                $logData = $LogDetails[0];
                $audit_username = $logData->user_username;
                $audit_fullnames = $logData->user_full_names;
                $audit_date_time = date('Y-m-d H:i:s');
                $audit_scheme_code = $logData->m_scheme_code;
                $memberActive = DB::connection('mydb_sqlsrv')->select("SELECT top 1 * from members_tb where m_number = '$memberNo' and m_scheme_code = '$memberSchemeCode' and m_status = 'Active'");
                if (count($memberActive) > 0) {
                    $memberData = $memberActive[0];
                    $memberName = $memberData->m_name;
                    $audit_activity = 'Making Claim';
                    $audit_description = 'Making Claim Attempt failed with error:Member Still Active';
                    $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                    VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                    return response()->json([
                        'status' => 405, // details not found
                        'operation' => 'fail',
                        'message' => "Dear $memberName you are not allowed to make a claim because you are still active please contact your HR or call 0709986000",
                    ], 405);
                }
                $pending_withdrawals = false;
                $results = DB::connection('mydb_sqlsrv') // Add your database connection here
                    ->table('withdrawals')
                    ->select('posted', 'flag')
                    ->where('use_scheme_code', 'like', "%$memberSchemeCode%")
                    ->where('use_member_no', 'like', "%$memberNo%")
                    ->where('posted', '!=', 1)
                    ->whereIn('flag', [1, 2, 3])
                    ->union(
                        DB::connection('mydb_sqlsrv') // Add your database connection here
                        ->table('withdrawals_new_tb')
                        ->select('posted', 'flag')
                        ->where('scheme_code', 'like', "%$memberSchemeCode%")
                        ->where('member_number', 'like', "%$memberNo%")
                        ->whereNull('posted')
                        ->whereIn('flag', [1, 2, 3])
                    )
                    ->union(
                        DB::connection('mydb_sqlsrv') // Add your database connection here
                        ->table('withdrawals_new_tb')
                        ->select('posted', 'flag')
                        ->where('scheme_code', 'like', "%$memberSchemeCode%")
                        ->where('member_number', 'like', "%$memberNo%")
                        ->where('posted', '!=', 1)
                        ->whereIn('flag', [1, 2, 3])
                    )
                    ->get();

                if ($results->count() > 0) {
                    $pending_withdrawals = true;
                }
                // Rest of your code
                if (!$pending_withdrawals) {
                    $memberDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  members_tb where  m_number ='$memberNo' and m_scheme_code = '$memberSchemeCode' ");
                    if (count($memberDetails) > 0) {
                        $member = $memberDetails[0];
                        $memberSchemeCode = $member->m_scheme_code;
                        $memberName = $member->m_name;
                        $memberNumber = $member->m_number;
                        $memberDOB = $member->m_dob;
                        $memberDoE = $member->m_doe; // emplo
                        $memberDoJ = $member->m_doj;
                        $memberDeS = $dateOfExit;
                        // $memberLCD = $member->m_lcd;
                        $memberNationalID = $member->m_id_number;
                        $memberKRAPIN = $member->m_pin;
                        $memberEmail = $member->m_email;
                        $memberPhone = $member->m_phone;
                        $memberPhyAddress = $member->m_physical_address;
                        $memberPostAdress = $member->m_address;
                        $memberKinsName = $member->m_kin_name;
                        $memberKinsPhone = $member->m_kin_phone;
                        $schemeDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  scheme_tb where  scheme_code = '$memberSchemeCode'");
                        $schemeData = $schemeDetails[0];
                        $schemeName = $schemeData->scheme_name;
                        $schemeCountry = $schemeData->scheme_country;
                        $p_hash = Hash::make(time().$memberSchemeCode.$memberNumber.uniqid());
                        $p_date = Carbon::now()->toDateString();
                        $p_scheme_code = $memberSchemeCode;
                        $p_member_number = $memberNumber;
                        $p_stage = 1;
                        $p_scheme_name = $schemeName;
                        $p_completed = 0;
                        $memberDOB = Carbon::createFromFormat('Y-m-d', $memberDOB);
                        $memberCurrentAge = $memberDOB->diffInYears();

                        $memberDocumentsDetails = DB::connection('mydb_macros_sqlsrv')->select("SELECT TOP (1) * from  member_profile_changes where  scheme_code = '$memberSchemeCode' and member_number ='$memberNo' and posted_status = 1 order by profile_change_id DESC ");
                        if (count($memberDocumentsDetails) > 0) {
                            $memberDocumentsData = $memberDocumentsDetails[0];
                            $scheme_name = $memberDocumentsData->scheme_name;
                            $scheme_code = $memberDocumentsData->scheme_code;
                            $member_name = $memberDocumentsData->member_name;
                            $member_number = $memberDocumentsData->member_number;
                            $amendments_file = json_decode($memberDocumentsData->amendments_file, true);
                            $posted_status = $memberDocumentsData->posted_status;
                            // check if the details are approved by the respective scheme HR
                            if ($posted_status == 1) {
                                $documentsDetails = [
                                    'schemeCode' => $scheme_code,
                                    'amendments_file' => $amendments_file,
                                ];

                                // Convert the array to JSON format
                                $memberDocumentsData = json_encode($documentsDetails);
                                // Create a new Guzzle HTTP client instance
                                $documentsClient = new Client();
                                $documentsResponse = $documentsClient->post('https://cloud.octagonafrica.com/crm/portal/moveclaimdocuments.php', [
                                    'headers' => [
                                        'Content-Type' => 'application/json',
                                    ],
                                    'body' => $memberDocumentsData,
                                ]);

                                // Get the response body
                                $documentsResponseData = $documentsResponse->getBody()->getContents();
                                // Decode the JSON response
                                $decodedDocumentsResponse = json_decode($documentsResponseData, true);
                                if ($decodedDocumentsResponse['status'] === 200 && $decodedDocumentsResponse['operation'] === 'Success') {
                                    // Show the success message
                                    $successMessage = $decodedDocumentsResponse['message'];
                                    $IDDocument = $decodedDocumentsResponse['ID Document'];
                                    $TaxIDDocument = $decodedDocumentsResponse['TAX ID DOCUMENT'];
                                    $benefitsElection = $decodedDocumentsResponse[' BENEFICIARIES NOMINATION FORM'];
                                    $bankProof = $decodedDocumentsResponse[' PROOF OF BANK DETAILS'];
                                    $supportingDocs = $decodedDocumentsResponse['supporting_docs'];

                                    try {
                                        // calculations

                                        $calculationsData = [
                                            'amount' => $m_amount,
                                            'schemeCode' => $memberSchemeCode,
                                            'memberNumber' => $memberNo,
                                            'memberReasonForExit' => $m_reason_for_exit,
                                            'memberAge' => $memberCurrentAge,
                                        ];
                                        $calculationsData = json_encode($calculationsData);
                                        $calculationsClient = new Client();
                                        $calculationsResponse = $calculationsClient->post('https://cloud.octagonafrica.com/crm/portal/calculations.php', [
                                            'headers' => [
                                                'Content-Type' => 'application/json',
                                            ],
                                            'body' => $calculationsData,
                                        ]);

                                        $statusCode = $calculationsResponse->getStatusCode();
                                        $responseBody = $calculationsResponse->getBody()->getContents();
                                        $decodedCalculationsResponse = json_decode($responseBody, true);

                                        if ($statusCode === 200) {
                                            $successMessage = $decodedCalculationsResponse['message'];
                                            $member_full_balance = $decodedCalculationsResponse['data']['member_full_balance'];
                                            $cash_total_percentage = $decodedCalculationsResponse['data']['cash_total_percentage'];
                                            $transfer_cash_percentage = $decodedCalculationsResponse['data']['transfer_cash_percentage'];
                                            // Key
                                            // per - percentage
                                            // ee - employee
                                            // er - employer
                                            // avc - ... additional volun contributions
                                            // te - tax exempt
                                            // nte - non-tax exempt
                                            // c - cash
                                            // t - transfer
                                            // d - deferred
                                            // cash
                                            $per_ee_te_c = $decodedCalculationsResponse['data']['cash']['per_ee_te_c'];
                                            $per_ee_nte_c = $decodedCalculationsResponse['data']['cash']['per_ee_nte_c'];
                                            $per_avc_te_c = $decodedCalculationsResponse['data']['cash']['per_avc_te_c'];
                                            $per_avc_nte_c = $decodedCalculationsResponse['data']['cash']['per_avc_nte_c'];
                                            $per_er_te_c = $decodedCalculationsResponse['data']['cash']['per_er_te_c'];
                                            $per_er_nte_c = $decodedCalculationsResponse['data']['cash']['per_er_nte_c'];

                                            // transfer
                                            $per_ee_te_t = $decodedCalculationsResponse['data']['transfer']['per_ee_te_t'];
                                            $per_ee_nte_t = $decodedCalculationsResponse['data']['transfer']['per_ee_nte_t'];
                                            $per_avc_te_t = $decodedCalculationsResponse['data']['transfer']['per_avc_te_t'];
                                            $per_avc_nte_t = $decodedCalculationsResponse['data']['transfer']['per_avc_nte_t'];
                                            $per_er_te_t = $decodedCalculationsResponse['data']['transfer']['per_er_te_t'];
                                            $per_er_nte_t = $decodedCalculationsResponse['data']['transfer']['per_er_nte_t'];

                                            // deffered
                                            $per_ee_te_d = $decodedCalculationsResponse['data']['deffered']['per_ee_te_d'];
                                            $per_ee_nte_d = $decodedCalculationsResponse['data']['deffered']['per_ee_nte_d'];
                                            $per_avc_te_d = $decodedCalculationsResponse['data']['deffered']['per_avc_te_d'];
                                            $per_avc_nte_d = $decodedCalculationsResponse['data']['deffered']['per_avc_nte_d'];
                                            $per_er_te_d = $decodedCalculationsResponse['data']['deffered']['per_er_te_d'];
                                            $per_er_nte_d = $decodedCalculationsResponse['data']['deffered']['per_er_nte_d'];

                                            // AVC Summary
                                            $summary_avc_cash = $decodedCalculationsResponse['data']['avc summary']['summary_avc_cash'];
                                            $summary_avc_transfer = $decodedCalculationsResponse['data']['avc summary']['summary_avc_transfer'];
                                            $summary_avc_deferred = $decodedCalculationsResponse['data']['avc summary']['summary_avc_deferred'];

                                            // employee Summary
                                            $summary_ee_cash = $decodedCalculationsResponse['data']['employee summary']['summary_ee_cash'];
                                            $summary_ee_transfer = $decodedCalculationsResponse['data']['employee summary']['summary_ee_transfer'];
                                            $summary_ee_deferred = $decodedCalculationsResponse['data']['employee summary']['summary_ee_deferred'];

                                            // employer Summary
                                            $summary_er_cash = $decodedCalculationsResponse['data']['employer summary']['summary_er_cash'];
                                            $summary_er_transfer = $decodedCalculationsResponse['data']['employer summary']['summary_er_transfer'];
                                            $summary_er_deferred = $decodedCalculationsResponse['data']['employer summary']['summary_er_deferred'];

                                            $memberDetails = [
                                                'p_hash' => $p_hash,
                                                'p_scheme_code' => $p_scheme_code,
                                                'p_scheme_name' => $p_scheme_name,
                                                'p_member_number' => $p_member_number,
                                                'p_stage' => $p_stage,
                                                'p_completed' => $p_completed,
                                                'm_name' => $memberName,
                                                'm_dob' => $memberDOB,
                                                'm_doe' => $memberDoE,
                                                'm_des' => $memberDeS,
                                                'm_doj' => $memberDoJ,
                                                'm_lcd' => $p_date, // last Contribution date find  a way to get it
                                                'm_national_id' => $memberNationalID,
                                                'm_tax_pin' => $memberKRAPIN,
                                                'm_email' => $memberEmail,
                                                'm_mobile' => $memberPhone,
                                                'm_box_address' => $memberPostAdress,
                                                'm_physical_address' => $memberPhyAddress,
                                                'm_next_of_kin' => $memberKinsName,
                                                'm_next_of_kin_phone' => $memberKinsPhone,
                                                'm_county' => '',
                                                'm_reason_for_exit' => $m_reason_for_exit,
                                                'm_other_selected' => 1,
                                                'm_other_reason' => '',
                                                'm_benefit_option_cash' => 1,
                                                'm_benefit_option_transfer' => 0,
                                                'm_benefit_option_annuity' => 0,
                                                'm_benefit_option_drawdown' => 0,
                                                'attach_national_id' => $IDDocument,
                                                'attach_tax_pin_certificate' => $TaxIDDocument,
                                                'attach_employer_consent_letter' => '1670571597_Resolution 06.12.2022 - Prof. Edward Karega.pdf',
                                                'attach_benefits_election_form' => $benefitsElection,
                                                'form_category' => 'ct', // /?????-?????-//
                                                'per_ee_te_c' => $per_ee_te_c,
                                                'per_ee_nte_c' => $per_ee_nte_c,
                                                'per_avc_te_c' => $per_avc_te_c,
                                                'per_avc_nte_c' => $per_avc_nte_c,
                                                'per_er_te_c' => $per_er_te_c,
                                                'per_er_nte_c' => $per_er_nte_c,
                                                'per_ee_te_t' => $per_ee_te_t,
                                                'per_ee_nte_t' => $per_ee_nte_t,
                                                'per_avc_te_t' => $per_avc_te_t,
                                                'per_avc_nte_t' => $per_avc_nte_t,
                                                'per_er_te_t' => $per_er_te_t,
                                                'per_er_nte_t' => $per_er_nte_t,
                                                'per_ee_te_d' => $per_ee_te_d,
                                                'per_ee_nte_d' => $per_ee_nte_d,
                                                'per_avc_te_d' => $per_avc_te_d,
                                                'per_avc_nte_d' => $per_avc_nte_d,
                                                'per_er_te_d' => $per_er_te_d,
                                                'per_er_nte_d' => $per_er_nte_d,
                                                'cash_total_percentage' => $cash_total_percentage,
                                                'cash_payee_name' => $memberName,
                                                'cash_bank' => 1096,
                                                'cash_branch' => 'HEAD OFFICE',
                                                'cash_account_number' => '01234566',
                                                'transfer_scheme_name' => ' ',
                                                'transfer_account_number' => '',
                                                'enter_cash_per' => 50.000000, // tbd//
                                                'enter_annuity_per' => 50.000000, // /tbd
                                                'issuant_name' => 'Octagon Income Drawdown Fund',
                                                'issuant_bank' => 7,
                                                'issuant_branch' => 'Chiromo',
                                                'issuant_account_number' => '0100004414564',
                                                'notification_form' => $benefitsElection,
                                                'supporting_docs' => $supportingDocs,
                                                'supporting_doc_types' => 'ATTACH NATIONAL ID,ATTACH TAX PIN CERTIFICATE,ATTACH BANK DETAILS PROOF',
                                                'transfer_bank' => 3,
                                                'transfer_branch' => ' ',
                                                'transfer_total_percentage' => $transfer_cash_percentage,
                                                'summary_ee_cash' => $summary_ee_cash,
                                                'summary_ee_transfer' => $summary_ee_transfer,
                                                'summary_ee_deferred' => $summary_ee_deferred,
                                                'summary_avc_cash' => $summary_avc_cash,
                                                'summary_avc_transfer' => $summary_avc_transfer,
                                                'summary_avc_deferred' => $summary_avc_deferred,
                                                'summary_er_cash' => $summary_er_cash,
                                                'summary_er_transfer' => $summary_er_transfer,
                                                'summary_er_deferred' => $summary_er_deferred,
                                            ];

                                            // Convert the array to JSON format
                                            $memberData = json_encode($memberDetails);
                                            // Create a new Guzzle HTTP client instance
                                            $client = new Client();
                                            $response = $client->post('https://cloud.octagonafrica.com/crm/portal/newClaimComputeWorksheet.php', [
                                                'headers' => [
                                                    'Content-Type' => 'application/json',
                                                ],
                                                'body' => $memberData,
                                            ]);
                                            // Get the response body
                                            $responseData = $response->getBody()->getContents();
                                            // Decode the JSON response
                                            $decodedResponse = json_decode($responseData, true);
                                            if ($decodedResponse['status'] === 200 && $decodedResponse['operation'] === 'Success') {
                                                // Show the success message
                                                $audit_activity = 'Making Claim';
                                                $audit_description = 'Making Claim Attempt: Success';
                                                $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                                VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);
                                                $successMessage = $decodedResponse['message'];

                                                return response()->json([
                                                    'status' => 200, // claim done successfully
                                                    'operation' => 'success',
                                                    'message' => "Dear $memberName, \n Your claim was successfully made. Please hold on as its proccesed. In case of any questions please feel free to reach out to us via support@octagonafrica.com or call 0709 986000",
                                                ], 200);
                                            } else {
                                                $audit_activity = 'Making Claim';
                                                $audit_description = 'Making Claim Attempt failed with error: Endpoint Error ';
                                                $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                                VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                                                return response()->json([
                                                    'status' => 400, // claim was not made
                                                    'operation' => 'failure',
                                                    'message' => 'Claim was not Made Please try again later',
                                                ], 400);
                                            }
                                        } elseif ($statusCode === 400) {
                                            $audit_activity = 'Making Claim';
                                            $audit_description = 'Making Claim Attempt failed with error: Calculations Failed ';
                                            $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                            VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);
                                            $responseData = [
                                                'status' => 409,
                                                'operation' => 'failure',
                                                'message' => $decodedCalculationsResponse['message'],
                                            ];

                                            return response()->json($responseData, 409);
                                            exit;
                                        } else {
                                            // Handle other status codes if needed
                                            $responseData = [
                                                'status' => $statusCode,
                                                'operation' => 'failure',
                                                'message' => 'Unexpected response from the server',
                                            ];

                                            return response()->json($responseData, $statusCode);
                                        }
                                        exit;
                                    } catch (ClientException $e) {
                                        // Handle the case when a client error occurs (e.g., 400 Bad Request)
                                        $response = $e->getResponse();
                                        $statusCode = $response->getStatusCode();
                                        $errorBody = $response->getBody()->getContents();
                                        $audit_activity = 'Making Claim';
                                        $audit_description = 'Making Claim Attempt failed with error: Tried To make a claim for a value over the expected Amount';
                                        $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                        VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                                        return response()->json([
                                            'status_code' => 409,
                                            'operation' => 'failure',
                                            'message' => "Dear $memberName you are trying to claim an amount that is higher than the expected amount. Please contact your administrator for more information or select the correct amounts",
                                            'messages' => 'Members under 50 years old cannot access more that 50% of their benefits as fund lumpsum or  or Cash Total Percentage Exceeds 100 percent. Please select a lower Amount',
                                            'error' => "Error $errorBody",
                                            'statusCode' => "$statusCode",
                                        ], 409);
                                        exit;
                                    } catch (\Exception $e) {
                                        // Handle other generic exceptions (if any)
                                        return response()->json([
                                            'status' => 500,
                                            'operation' => 'failure',
                                            'message' => 'An unexpected error occurred: '.$e->getMessage(),
                                        ], 500);
                                        exit;
                                    }
                                } else {// failed to move files
                                    $audit_activity = 'Making Claim';
                                    $audit_description = 'Making Claim Attempt failed with error: Claim Documents Failed.';
                                    $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                    VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                                    return response()->json([
                                        'status' => 402, // copying files failed
                                        'operation' => 'failure',
                                        'message' => 'Files not copied',
                                    ], 402);
                                }
                            } else { // /details not approved
                                $audit_activity = 'Making Claim';
                                $audit_description = 'Making Claim Attempt failed with error: Your details have not been approved your scheme HR details please contact them';
                                $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                                return response()->json([
                                    'status' => 403,  // profile provided but not approved by admin or Scheme HR
                                    'operation' => 'failure',
                                    'message' => 'Your details have not been approved your scheme HR details please contact them',
                                ], 403);
                            }
                        } else {// documents missing send link to client
                            $memberDetails = [
                                'schemeName' => $schemeName,
                                'schemeCode' => $memberSchemeCode,
                                'memberNumber' => $memberNo,
                            ];

                            // Convert the array to JSON format
                            $memberData = json_encode($memberDetails);
                            // Create a new Guzzle HTTP client instance
                            $client = new Client();
                            $response = $client->post('https://cloud.octagonafrica.com/opas/pension/sendonboardingrequest.php', [
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                ],
                                'body' => $memberData,
                            ]);
                            // Get the response body
                            $responseData = $response->getBody()->getContents();
                            // Decode the JSON response
                            $decodedResponse = json_decode($responseData, true);
                            if (isset($decodedResponse['status']) && isset($decodedResponse['operation'])) {
                                if ($decodedResponse['status'] === 200 && $decodedResponse['operation'] === 'Success') {
                                    // Show the success message
                                    $successMessage = $decodedResponse['message'];
                                    $audit_activity = 'Making Claim';
                                    $audit_description = 'Making Claim Attempt failed with error: You Do not have a complete Profile please update to make claims. Email Sent';
                                    $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                    VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                                    return response()->json([
                                        'status' => 202, // message to client sent successfully to update details
                                        'operation' => 'success',
                                        'message' => "Dear $memberName,\nAn email with a link to update your details has been sent to your email. Please update your details to process your claim on this channel. In case of any questions, please feel free to reach out to us via support@octagonafrica.com or 0709 986000",
                                    ], 202);
                                } else {
                                    $audit_activity = 'Making Claim';
                                    $audit_description = 'Making Claim Attempt failed with error: You Do not have a complete Profile please update to make claims. Email Not Sent';
                                    $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                    VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                                    return response()->json([
                                        'status' => 405, // onboarding link not sent
                                        'operation' => 'failure',
                                        'message' => "Dear $memberName Email was not sent because it does not exist . Please try again later or contact us at support@octagonafrica.com or 0709986000 or update  your profilr",
                                    ], 405);
                                }
                            } else {
                                // Handle the case when the required keys are missing or null
                                return response()->json([
                                    'status' => 401, // endpoint did not return the expected result
                                    'operation' => 'error',
                                    'message' => 'Invalid response received',
                                ], 401);
                            }
                        }
                    } else {
                        $audit_activity = 'Making Claim';
                        $audit_description = 'Making Claim Attempt failed with error: Member Number Not Found/Invalid. Member Does not exist ';
                        $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                        VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                        return response()->json([
                            'status' => 406, // invalid member number
                            'operation' => 'fail',
                            'message' => 'Member Number Not Found/Invalid.',
                        ], 406);
                    }
                } else {
                    // Handle the case where there are pending withdrawals
                    $log_data = "You have {$results->count()} unposted withdrawal(s).";
                    // $the_message = json_encode($log_data);
                    $audit_activity = 'Making Claim';
                    $audit_description = 'Making Claim Attempt failed with error: '.$log_data;
                    $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                    VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                    // Return a JSON response
                    return response()->json([
                        'status' => 411,
                        'operation' => 'Failure',
                        'message' => 'Hello Esteemed Customer please cancel the existing claim  or contact the scheme adminstrator to do it on your behalf',
                    ], 411);
                }
            } else {
                return response()->json([
                    'status' => 407, // details not found
                    'operation' => 'fail',
                    'message' => 'Member account does not exist. Please update account details or contact the scheme administrator',
                ], 407);
            }
        }
    }

    // //  add member to withdrawaltemp table
    // public function addNewWithdrawal(Request $request)
    // {
    //     $memberSchemeCode = $request['memberSchemeCode'];
    //     $m_number = $request['m_number'];
    //     $m_name = $request['m_name'];
    //     $m_dob = $request['m_dob'];
    //     $m_doe = $request['m_doe'];
    //     $m_des = $request['m_des'];
    //     $m_doj = $request['m_doj'];
    //     $m_lcd = $request['m_lcd'];
    //     $m_national_id = $request['m_national_id'];
    //     $m_tax_pin = $request['m_tax_pin'];
    //     $m_email = $request['m_email'];
    //     $m_mobile = $request['m_mobile'];
    //     $m_box_address = $request['m_box_address'];
    //     $m_physical_address = $request['m_physical_address'];
    //     $m_next_of_kin = $request['m_next_of_kin'];
    //     $m_next_of_kin_phone = $request['m_next_of_kin_phone'];
    //     $m_county = $request['m_county'];
    //     $m_reason_for_exit = $request['m_reason_for_exit'];
    //     $m_other_selected = $request['m_other_selected'];
    //     $m_other_reason = $request['m_other_reason_for_exit'];
    //     $m_benefit_option_cash = $request['m_benefit_option_cash']; // 0/1
    //     $m_benefit_option_transfer = $request['m_benefit_option_transfer']; // 0/1
    //     $m_benefit_option_annuity = $request['m_benefit_option_annuity']; // 0/1
    //     $m_benefit_option_drawdown = $request['m_benefit_option_drawdown']; // 0/1
    //     $schemeDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  scheme_tb where  scheme_code like '%$memberSchemeCode%'");
    //     $schemeData = $schemeDetails[0];
    //     $schemeName = $schemeData->scheme_name;
    //     $schemeCountry = $schemeData->scheme_country;
    //     $p_hash = Hash::make(time().$memberSchemeCode.$m_number.uniqid());
    //     $p_date = Carbon::now()->toDateString();
    //     $p_scheme_code = $memberSchemeCode;
    //     $p_member_number = $m_number;
    //     $p_stage = 1;
    //     $p_scheme_name = $schemeName;
    //     $p_completed = 0;
    //     if (!$memberSchemeCode && $m_number && $p_scheme_code) {
    //         return response()->json([
    //             'status' => 400,
    //             'operation' => 'fail',
    //             'message' => 'Member Number/SchemeCode/  required.',
    //         ], 400);
    //     } else {
    //         $memberDOB = Carbon::createFromFormat('Y-m-d', $m_dob);
    //         $memberCurrentAge = $memberDOB->diffInYears();

    //         $employer_limit = 100;
    //         $employee_limit = 100;
    //         $avc_limit = 100;
    //         if ($schemeCountry === 'Kenya' && $memberCurrentAge < 50) {
    //             $employer_limit = 50;
    //             $employee_limit = 50;
    //             $avc_limit = 50;
    //         }
    //         if ($m_reason_for_exit === 'Ill-Health' or $m_reason_for_exit === 'Death' or $m_reason_for_exit === 'Migration' or in_array($memberSchemeCode, ['KE236', 'KE291', 'KE202']) or in_array($m_number, ['IPP/28171332/21'])) {
    //             $employer_limit = 100;
    //             $employee_limit = 100;
    //             $avc_limit = 100;
    //         }

    //         // Define the data to be inserted
    //         $data = [
    //             'p_hash' => $p_hash,
    //             'p_date' => $p_date,
    //             'p_scheme_code' => $p_scheme_code,
    //             'p_member_number' => $p_member_number,
    //             'p_stage' => $p_stage,
    //             'p_scheme_name' => $p_scheme_name,
    //             'p_completed' => $p_completed,
    //             'm_name' => $m_name,
    //             'm_dob' => $m_dob,
    //             'm_doe' => $m_doe,
    //             'm_des' => $m_des,
    //             'm_doj' => $m_doj,
    //             'm_lcd' => $m_lcd,
    //             'm_national_id' => $m_national_id,
    //             'm_tax_pin' => $m_tax_pin,
    //             'm_email' => $m_email,
    //             'm_mobile' => $m_mobile,
    //             'm_box_address' => $m_box_address,
    //             'm_physical_address' => $m_physical_address,
    //             'm_next_of_kin' => $m_next_of_kin,
    //             'm_next_of_kin_phone' => $m_next_of_kin_phone,
    //             'm_county' => $m_county,
    //             'm_reason_for_exit' => $m_reason_for_exit,
    //             'm_other_selected' => $m_other_selected,
    //             'm_other_reason' => $m_other_reason,
    //             'm_benefit_option_cash' => $m_benefit_option_cash,
    //             'm_benefit_option_transfer' => $m_benefit_option_transfer,
    //             'm_benefit_option_annuity' => $m_benefit_option_annuity,
    //             'm_benefit_option_drawdown' => $m_benefit_option_drawdown,
    //         ];
    //         // Insert the data into the table
    //         $inserted = DB::connection('mydb_sqlsrv')->table('withdrawal_temp_tb')->insert($data);
    //         if ($inserted) {
    //             return response()->json([
    //                 'status' => 200,
    //                 'operation' => 'Succces',
    //                 'message' => 'Member Details Inserted Successfully',
    //                 'employer_limit' => $employer_limit,
    //                 'employee_limit' => $employee_limit,
    //                 'avc_limit' => $avc_limit,
    //                 'p_hash' => $p_hash,
    //                 '$schemeCountry' => $schemeCountry,
    //             ], 200);
    //         } else {
    //             return response()->json([
    //                 'status' => 400,
    //                 'operation' => 'fail',
    //                 'message' => 'Claim Insert unSuccessfully',
    //             ], 400);
    //         }
    //     }
    // }

    // get bank deatails
    public function fetchBanks(Request $request)
    {
        $schemeCountry = $request['schemeCountry'];
        $m_number = $request['m_number'];

        if (!$schemeCountry && !$m_number) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'Scheme Country/Member Number is required.',
            ], 400);
        } else {
            $memberDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from members_tb where m_number like '%$m_number%'");

            if (count($memberDetails) == 0) {
                return response()->json([
                    'status' => 400,
                    'operation' => 'fail',
                    'message' => 'Member Details Fetch Unsuccessful',
                ], 400);
            }

            $member = $memberDetails[0];
            $accountName = $member->m_name;
            $data = [];

            $bankDetails = DB::connection('mydb_macros_sqlsrv')->select("SELECT * from banks where is_active = 1 and bank_country like '%$schemeCountry%' order by bank_name asc");

            foreach ($bankDetails as $bankData) {
                $bankID = $bankData->bank_id;
                $bankName = $bankData->bank_name;
                $data[] = [
                    'bankID' => $bankID,
                    'bankName' => $bankName,
                ];
            }
            if (count($data) > 0) {
                return response()->json([
                    'status' => 200,
                    'operation' => 'success',
                    'message' => 'Banks Fetched Successfully',
                    'accountName' => $accountName,
                    'data' => $data,
                ], 200);
            } else {
                return response()->json([
                    'status' => 400,
                    'operation' => 'fail',
                    'message' => 'Banks Fetch Unsuccessful',
                ], 400);
            }
        }
    }

    public function getMemberClaims(Request $request)
    {
        $member_number = $request['member_number'];
        $scheme_id = $request['scheme_id']; // scheme code.
        if (!$member_number && !$scheme_id) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'Scheme Code / Member Number is required.',
            ], 400);
        } else {
            $claimDetails = DB::connection('mydb_macros_sqlsrv')->select("SELECT member_withdrawals.member_name, processing_stages.stage_name
            FROM member_withdrawals
            JOIN processing_stages ON member_withdrawals.processing_stage = processing_stages.processing_stage
            WHERE member_withdrawals.member_number = '$member_number'
            AND member_withdrawals.scheme_id = '$scheme_id'
            ");

            if (count($claimDetails) > 0) {
                // succes message
                $claimData = $claimDetails[0];
                $claimStage = $claimData->stage_name;
                $memberName = $claimData->member_name;

                // message formating.
                return response()->json([
                    'status' => 200,
                    'operation' => 'success',

                    'message' => "Dear $memberName You claim stage is $claimStage ",
                ], 200);
            } else {
                // check withdrwaltemp then send message
                $claimDetails = DB::connection('mydb_sqlsrv')->select("SELECT * from withdrawal_temp_tb where p_member_number= '$member_number' and p_scheme_code= '$scheme_id'");
                if (count($claimDetails) > 0) {
                    $claimData = $claimDetails[0];
                    $memberName = $claimData->m_name;

                    return response()->json([
                        'status' => 200,
                        'operation' => 'success',
                        'message' => "Dear $memberName You claim request is still in progress ",
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 400,
                        'operation' => 'success',
                        'message' => 'Dear estemed client, You are yet to initiate a benefits withdrawal claim.',
                    ], 400);
                }
            }
        }
    }

    // update bankDetails
    public function addBankDetails(Request $request)
    {
        $p_hash = $request['p_hash'];
        $cash_payee_name = $request['cash_payee_name'];
        $cash_bank = $request['cash_bank']; // bank id
        $cash_branch = $request['cash_branch'];
        $cash_account_number = $request['cash_account_number'];
        if (!$p_hash && $cash_account_number) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'P Hash / Account Number is required.',
            ], 400);
        } else {
            $bankDetails = DB::connection('mydb_sqlsrv')->update('UPDATE withdrawal_temp_tb set cash_payee_name = ?, cash_bank = ?, cash_branch = ?, cash_account_number = ? where p_hash = ?', [$cash_payee_name, $cash_bank, $cash_branch, $cash_account_number, $p_hash]);
            if ($bankDetails) {
                return response()->json([
                    'status' => 200,
                    'operation' => 'Success',
                    'message' => 'Your bank details have been updated successfully.',
                ], 200);
            } else {
                return response()->json([
                    'status' => 400,
                    'operation' => 'Fail',
                    'message' => 'Bank Details Failed.',
                ], 400);
            }
        }
    }

    public function showDocuments(Request $request)
    {
        $memberSchemeCode = $request['memberSchemeCode'];
        $memberNo = $request['memberNo'];
        if (!$memberSchemeCode && $memberNo) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'P Hash / Account Number is required.',
            ], 400);
        } else {
            $memberDocumentsDetails = DB::connection('mydb_macros_sqlsrv')->select("SELECT TOP (1) * from  member_profile_changes where  scheme_code = '$memberSchemeCode' and member_number ='$memberNo' order by profile_change_id DESC ");
            if (count($memberDocumentsDetails) > 0) {
                $memberDocumentsData = $memberDocumentsDetails[0];
                $scheme_name = $memberDocumentsData->scheme_name;
                $scheme_code = $memberDocumentsData->scheme_code;
                $member_name = $memberDocumentsData->member_name;
                $member_number = $memberDocumentsData->member_number;
                $amendments_file = json_decode($memberDocumentsData->amendments_file, true);
                // docs
                $filteredDocuments = [];
                if (is_array($amendments_file)) {
                    foreach ($amendments_file as $document) {
                        $name = $document['name'];
                        if ($name === 'ID Document' || $name === 'TAX ID DOCUMENT') {
                            // Assuming the documents are stored on a separate server
                            $documentUrl = 'https://cloud.octagonafrica.com/OPAS_DATA/macros_uploads/uploads/NOBs/'.$scheme_code.'/'.$document['file'];
                            $document['url'] = $documentUrl;
                            $filteredDocuments[] = $document;
                        }
                    }
                }

                return response()->json([
                    'status' => 200,
                    'operation' => 'Success',
                    'message' => 'Documents Found',
                    'data' => $filteredDocuments,
                ], 200);
            } else {// documents missing send link to client
                $schemeDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  scheme_tb where  scheme_code = '$memberSchemeCode'");
                $schemeData = $schemeDetails[0];
                $schemeName = $schemeData->scheme_name;
                $memberDetails = [
                    'schemeName' => $schemeName,
                    'schemeCode' => $memberSchemeCode,
                    'memberNumber' => $memberNo,
                ];

                // Convert the array to JSON format
                $memberData = json_encode($memberDetails);
                // Create a new Guzzle HTTP client instance
                $client = new Client();
                $response = $client->post('https://cloud.octagonafrica.com/opas/pension/sendonboardingrequest.php', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $memberData,
                ]);
                // Get the response body
                $responseData = $response->getBody()->getContents();
                // Decode the JSON response
                $decodedResponse = json_decode($responseData, true);

                if (isset($decodedResponse['status']) && isset($decodedResponse['operation'])) {
                    if ($decodedResponse['status'] === 200 && $decodedResponse['operation'] === 'Success') {
                        // Show the success message
                        $successMessage = $decodedResponse['message'];

                        return response()->json([
                            'status' => 200,
                            'operation' => 'success',
                            'message' => "Dear esteemed client,\nAn email with a link to update your details has been sent to your email. Please update your details to process your claim. In case of any questions, please feel free to reach out to us via support@octagonafrica.com or 0709986000",
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 400,
                            'operation' => 'success',
                            'message' => 'Email was not sent. Please try again later or contact us at support@octagonafrica.com or 0709986000',
                        ], 400);
                    }
                } else {
                    // Handle the case when the required keys are missing or null
                    return response()->json([
                        'status' => 400,
                        'operation' => 'error',
                        'message' => 'Invalid response received',
                    ], 400);
                }
            }
        }
    }

    public function getClaims(Request $request)
    {
        // if (is_null($request['claim_status'])) {
        //     $claim_status = 'Just Reported';
        // } else {
        //     $claim_status = filter_var(trim($request['claim_status']), FILTER_SANITIZE_STRING);
        // }
        // if (is_null($request['start'])) {
        //     $start = 0;
        // } else {
        //     $start = filter_var(trim($request['start']), FILTER_SANITIZE_STRING);
        // }

        // if (is_null($request['limit'])) {
        //     $limit = 10;
        // } else {
        //     $limit = filter_var(trim($request['limit']), FILTER_SANITIZE_STRING);
        // }
        // $q = filter_var(trim($request['q']), FILTER_SANITIZE_STRING);
        // if (is_null($request)) {
        //     if ($request['ClientID']){
        //         $ClientID = filter_var(trim($request['ClientID']), FILTER_SANITIZE_STRING);
        //         //
        //         //validate leads
        //         $sql = "SELECT claimant,A.ID,IDNO,ClientID,Mobile,  specificItems,policyNo,sumInsured,
        //     claimAmount,
        //     Description,
        //     AmountPaid,
        //     C.Name as status
        // FROM claimRegister A
        // LEFT JOIN claimStatus C
        // ON  A.Status = C.code
        // LEFT JOIN Clients B
        // ON A.ClientID= B.ClientID
        // WHERE
        //     A.ClientID = '$ClientID'";
        //     }
        // validate leads
        //     $sql2 = "SELECT
        //     *
        // FROM MedFamMembers
        // WHERE PRPMember = '$ClientID'
        // AND relationship = 'Beneficiary'";
        //     // $beneficiaries = $db_birthmark->dbSelectRows($sql2);
        //     $beneficiaries = DB::connection('sqlsrv')->select($sql2);
        //         return $beneficiaries;
        //     }

        //     $sql = "SELECT
        //     claimant,
        //     A.ID,
        //     IDNO,
        //     A.ClientID,
        //     Mobile,
        //     specificItems,
        //     policyNo,
        //     sumInsured,
        //     claimAmount,
        //     Description,
        //     AmountPaid,
        //     C.Name as status
        // FROM claimRegister A
        // LEFT JOIN claimStatus C
        // ON  A.Status = C.code
        // LEFT JOIN Clients B
        // ON A.ClientID= B.ClientID
        // WHERE
        // -- C.Name = '$claim_status' OR
        //    claimant LIKE '%" . $q . "%'
        //     ORDER BY A.ID desc
        //     OFFSET " . $start . " ROWS
        //     FETCH NEXT " . $limit . " ROWS ONLY";

        //         $Claims = DB::connection('sqlsrv')->select($sql);
        //     return $Claims;
        //     }

        //     } else {

        //     // $Claims = $db_birthmark->dbSelectRows($sql);

        //     if (count($Claims) > 0) {

        //         $payload = [
        //             'success' =>  true,
        //             'message' =>  'Successfully retrieved',
        //             'data' => [
        //                 'Claim' => $Claims,
        //                 // 'Beneficiary' => $beneficiaries
        //             ],
        //             // 'total' => $db_birthmark->getRowCount()
        //         ];
        //     } else {
        //         $payload = array(
        //             'success' =>  false,
        //             'message' =>  'No claim found'
        //         );
        //     }

        // return "Middleware Tes";
    }

    // Get Claims as per Client
    public function clientClaims(Request $request)
    {
        if (!$request['ClientID']) {
            $payload = [
                'success' => false,
                'message' => 'Insert Client ID',
            ];
        } else {
            $clientID = filter_var(trim($request['ClientID']), FILTER_SANITIZE_STRING);
            try {
                $client_Claims = DB::select("SELECT * FROM ClaimRegister WHERE ClientID = '$clientID'");
                $payload = [
                    'status' => 200,
                    'success' => true,
                    'message' => 'Claims retrieved successfully',
                    'total' => count($client_Claims),
                    'data' => $client_Claims,
                ];
            } catch (\Throwable $th) {
                $payload = response()->json([
                    'status' => 401,
                    'success' => false,
                    'message' => $th,
                ]);
            }
        }

        return $payload;
    }

    // check if bank Details are available
    public function checkBankDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schemeCode' => 'required|string|max:255',
            'memberNumber' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'operation' => 'failure',
                'message' => 'Missing data/invalid data. Please try again.',
                'errors' => $validator->errors()->all(),
            ], 400);
        } else {
            $schemeCode = $request->input('schemeCode');
            $memberNumber = $request->input('memberNumber');
            $memberBankDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  members_tb where  m_number ='$memberNumber' and m_scheme_code = '$schemeCode' and (m_account_no = '' or  m_account_no is null or m_account_name = '' or m_account_name is null or m_bank_name = '' or m_bank_name is null or m_branch_name = '' or m_branch_name is null)");
            if (count($memberBankDetails) > 0) {
                $memberData = $memberBankDetails[0];
                $memberName = $memberData->m_name;

                return response()->json([
                    'status' => 400,
                    'operation' => 'failure',
                    'message' => "Hello $memberName your bank Details are missing please go to your profile page and update your bank details",
                ], 400);
            } else {
                return response()->json([
                    'status' => 200,
                    'operation' => 'success',
                    'message' => 'Bank details are available.',
                ], 200);
            }
        }
    }

    public function listClaims(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'operation' => 'failure',
                'message' => 'Missing data/invalid data. Please try again.',
                'errors' => $validator->errors()->all(),
            ], 400);
        }
        $user_id = $request->input('user_id');
        $user_exist = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_id ='$user_id'");
        if (count($user_exist) == 0) {
            return response()->json([
                'status' => 404,
                'operation' => 'failure',
                'message' => 'User with id '.$user_id.' not found',
            ], 404);
        }
        $user = $user_exist[0];
        $id_number = $user->user_national_id;
        $memberName = $user->user_full_names;
        $audit_username = $user->user_username;
        $audit_fullnames = $user->user_full_names;
        $audit_date_time = date('Y-m-d H:i:s');
        $audit_scheme_code = $user->user_schemes;
        $pension = DB::connection('mydb_sqlsrv')->select("SELECT m_number, m_scheme_code
            FROM members_tb
            JOIN scheme_tb s ON m_scheme_code = s.scheme_code
            WHERE m_id_number = '$id_number'");
        if (count($pension) == 0) {
            return response()->json([
                'status' => 404,
                'operation' => 'success',
                'message' => 'Dear esteemed client, you do not have a pension account .',
            ], 404);
        }
        $claimData = [];
        foreach ($pension as $record) {
            $schemeCode = $record->m_scheme_code;
            $memberNumber = $record->m_number;
            $claimDetails = DB::connection('mydb_macros_sqlsrv')->select("SELECT mw.member_name,mw.OPAS_withdrawal_id, ps.stage_name, ps.next_action, ps.processing_stage, wnewtb.total_payable_amount, wnewtb.scheme_name,wnewtb.as_at_date, mw.created_on
            FROM member_withdrawals mw
            JOIN processing_stages ps ON mw.processing_stage = ps.processing_stage
            JOIN MYDB.dbo.withdrawals_new_tb wnewtb ON mw.scheme_id = wnewtb.scheme_code AND mw.member_number = wnewtb.member_number
            WHERE mw.member_number = '$memberNumber' AND mw.scheme_id = '$schemeCode'");
            foreach ($claimDetails as $claim) {
                // $opasData = $user_exist = DB::connection('mydb_sqlsrv')->select("SELECT * FROM sys_users_tb WHERE user_id ='$user_id'");
                $claimStage = $claim->stage_name;
                $memberName = $claim->member_name;
                $claimsNextStage = $claim->next_action;
                $procesing_stage = $claim->processing_stage;
                $withdrawalID = $claim->OPAS_withdrawal_id;
                $schemeName = $claim->scheme_name;
                $createdOn = $claim->created_on;
                $totalPayableAmount = $claim->total_payable_amount;
                $as_at_date = $claim->as_at_date;

                $claimData[] = [
                    'processing_stage' => $procesing_stage,
                    'claim_stage' => $claimStage,
                    'member_name' => $memberName,
                    'next_action' => $claimsNextStage,
                    'withdrawalID' => $withdrawalID,
                    'schemeName' => $schemeName,
                    'createdOn' => $createdOn,
                    'as_at_date' => $as_at_date,
                    'amount' => $totalPayableAmount,
                ];
            }
        }
        if (count($claimData) > 0) {
            $audit_activity = 'List Claims';
            $audit_description = 'Profile Update : Success Claims found';
            $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                                VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

            // Claims data found
            return response()->json([
                'status' => 200,
                'operation' => 'success',
                'claim_data' => $claimData,
                'message' => 'Claims list retrieved successfully.',
            ], 200);
        } else {
            $audit_activity = 'List Claims';
            $audit_description = 'Profile Update :failure : Claims not found';
            $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                                VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

            return response()->json([
                'status' => 200,
                'operation' => 'success',
                'claim_data' => $claimData,
                'message' => "Dear $memberName, You are yet to initiate a benefits withdrawal claim.",
            ], 200);
        }
    }
}
