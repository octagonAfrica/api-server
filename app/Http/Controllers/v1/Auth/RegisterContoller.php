<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\HRNotificationMail;
use App\Mail\ProfileChangeMail;
use App\Mail\RegistrationMail;
use App\Mail\MemberOnboardingMail;
use App\Mail\ExistingMemberOnboardingMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Hashids\Hashids;

class RegisterContoller extends Controller
{
    // protected $table = 'Clients';
    public function registerUser(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'firstname' => 'required',
            'lastname' => 'required',
            'ID' => 'required',
            'email' => 'required',
            'password' => 'required|min:6',
            'phonenumber' => [
                'required',
                'regex:/^(\+254|254|0|256|260)(\d{9})$/',
                'min:10',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'operation' => 'failure',
                'message' => 'Missing data/invalid data. Please try again.',
                'errors' => $validator->errors()->all(),
            ], 400);
        } else {
            $data = $request->all();
            $firstname = $data['firstname'];
            $lastname = $data['lastname'];
            $ID = $data['ID'];
            $email = $data['email'];
            $phone_number = $data['phonenumber'];
            $password = $data['password'];
            $fullnames = $firstname.' '.$lastname;
            $firstname = strtolower($firstname);
            $lastname = strtolower($lastname);
            $username = $ID;
            $phoneNumber = $phone_number;
            if (Str::contains($phoneNumber, '254')) {
                $pattern = "/^(\+254|254|0)[1-9]\d{8}$/";
                $kk = preg_match($pattern, $phone_number, $matches);
                // Add user to sys_users_tb
                if ($kk) {
                    if ($matches[1] === '0') {
                        $phone_number = '254'.substr($phone_number, 1);
                    } elseif ($matches[1] === '+254') {
                        $phone_number = '254'.substr($phone_number, 4);
                    }
                }
                $country = 'Kenya';
            } elseif (Str::contains($phoneNumber, '256')) {
                $pattern = "/^(\+256|256|0)[1-9]\d{8}$/";
                $kk = preg_match($pattern, $phone_number, $matches);
                // Add user to sys_users_tb
                if ($kk) {
                    if ($matches[1] === '0') {
                        $phone_number = '256'.substr($phone_number, 1);
                    } elseif ($matches[1] === '+256') {
                        $phone_number = '256'.substr($phone_number, 4);
                    }
                }
                $country = 'Uganda';
            } elseif (Str::contains($phoneNumber, '260')) {
                $pattern = "/^(\+260|260|0)[1-9]\d{8}$/";
                $kk = preg_match($pattern, $phone_number, $matches);
                // Add user to sys_users_tb
                if ($kk) {
                    if ($matches[1] === '0') {
                        $phone_number = '260'.substr($phone_number, 1);
                    } elseif ($matches[1] === '+260') {
                        $phone_number = '260'.substr($phone_number, 4);
                    }
                }
                $country = 'Zambia';
            }
            // Encrypt Password
            $encryptedPassword = password_hash($password, PASSWORD_DEFAULT);

            // insert to sys_users_tb if a user exists in the members_tb
            $user_exist_members_tb = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM members_tb WHERE m_email  ='$email'");
            if (count($user_exist_members_tb) > 0) {
                $add_user = DB::connection('mydb_sqlsrv')->insert('INSERT INTO sys_users_tb (
                    user_username, user_enc_pwd, user_country, user_active, user_full_names, user_email,
                    user_mobile, user_phone, user_national_id, user_role_id, is_email_verified,
                    is_phone_verified
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ', [
                    $username,
                    $encryptedPassword,
                    $country,
                    1,
                    $fullnames,
                    $email,
                    $phone_number,
                    $phone_number,
                    $ID,
                    100,
                    0,
                    1,
                ]);

                if ($add_user) {
                    $mailData = [
                        'fullnames' => $fullnames,
                        'username' => $username,
                        'email' => $email,
                        'password' => $password,
                    ];
                    // Send Email to new user, username and Password
                    Mail::to($email)->send(new RegistrationMail($mailData));

                    return response()->json(
                        [
                            'status' => 200,
                            'operation' => 'success',
                            'message' => "User registered successfully, Login details sent to '$email'",
                        ], 200
                    );
                } else {
                    return response()->json(
                        [
                            'status' => 500,
                            'success' => false,
                            'message' => 'Internal Server Error ',
                        ], 500
                    );
                }
            } else {
                // if user exists in login_registration send error Message
                $user_exist_login_registration = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM login_registration WHERE pr_email ='$email'");
                if (count($user_exist_login_registration) > 0) {
                    return response()->json(
                        ['status' => 407,
                            'success' => false,
                            'message' => 'We already have your registration request and is awaiting approval. Incase of any queries please contact support on support@octagonafrica.com or  +254709986000',
                        ], 407
                    );
                } else {
                    // else insert to login_registration table
                    $current_time = time();
                    $pr_indentifier = Hash::make($current_time);
                    $current_date_time = date('Y-m-d H:i:s');
                    $add_user_login_registration = DB::connection('mydb_sqlsrv')
                    ->insert('INSERT INTO login_registration(pr_identifier,pr_full_name,pr_dob,pr_email,pr_mobile_number,pr_id_number,pr_posting_date)
                    values (?,?,?,?,?,?,?)',
                        [$pr_indentifier, $fullnames, '1900-01-01', $email, $phone_number, $ID, $current_date_time]);

                    $add_user = DB::connection('mydb_sqlsrv')->insert('INSERT INTO sys_users_tb (
                            user_username, user_enc_pwd, user_country, user_active, user_full_names, user_email,
                            user_mobile, user_phone, user_national_id, user_role_id,  is_email_verified,
                            is_phone_verified
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ', [
                        $username,
                        $encryptedPassword,
                        $country,
                        1,
                        $fullnames,
                        $email,
                        $phone_number,
                        $phone_number,
                        $ID,
                        100,
                        0,
                        1,
                    ]);
                    if ($add_user_login_registration && $add_user) {
                        $mailData = [
                            'fullnames' => $fullnames,
                            'username' => $username,
                            'email' => $email,
                            'password' => $password,
                        ];

                        // Send Email to new user, username and Password
                        Mail::to($email)->send(new RegistrationMail($mailData));
                        return response()->json(
                            [
                                'status' => 200,
                                'operation' => 'success',
                                'message' => 'Your registration request has been recieved sucessfully and  is awaiting Approval. Incase of any queries please contact support on support@octagonafrica.com or +254709986000',
                            ], 200
                        );
                    } else {
                        return response()->json(
                            [
                                'status' => 500,
                                'success' => false,
                                'message' => 'Internal Server Error. ',
                            ], 500
                        );
                    }
                }
            }
        }
    }

    public function updateMemberProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'user_national_id' => 'string|max:255',
            'user_full_names' => 'string|max:255',
            'user_phone' => ['string', 'max:255', 'regex:/^(254|256|260|255)\d{9}$/'],
            'user_email' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'operation' => 'failure',
                'message' => 'Missing data/invalid data. Please try again.',
                'errors' => $validator->errors()->all(),
            ], 400);
        } else {
            $user_id = $request->input('user_id');

            $userAccountMapped = DB::connection('mydb_sqlsrv')->select("SELECT * FROM sys_users_tb  WHERE  user_id = '$user_id' and  (user_schemes != '' or user_schemes is not null )");
            if (count($userAccountMapped) <= 0) { // Use <= instead of <
                return response()->json([
                    'status' => 409,
                    'operation' => 'failure',
                    'message' => "Dear esteemed member, your account hasn't been mapped to a specific scheme. Please contact your scheme admin to do the mapping or contact support@octagonafrica.com.",
                ], 409);
            }
            $userData = $userAccountMapped[0];
            $schemeCode = $userData->user_schemes;
            $memberNumber = $userData->user_member_no;
            $logData = DB::connection('mydb_sqlsrv')->select("SELECT su.user_username, su.user_full_names, m.m_scheme_code, su.user_email from sys_users_tb su , members_tb m where su.user_national_id= m.m_id_number and m.m_number = '$memberNumber' and m.m_scheme_code = '$schemeCode'");
            if (count($logData) < 0) {
                return response()->json([
                    'status' => 409,
                    'operation' => 'failure',
                    'message' => 'User Account Not found.',
                ], 409);
            }
            $logDetails = $logData[0];
            $audit_username = $logDetails->user_username;
            $audit_fullnames = $logDetails->user_full_names;
            $audit_date_time = date('Y-m-d H:i:s');
            $audit_scheme_code = $schemeCode;
            // send notification to HR
            $hrEmails = DB::connection('mydb_sqlsrv')->select("SELECT user_email FROM sys_users_tb WHERE user_schemes = '$schemeCode' AND user_group = 'HR' AND user_active = 1;");

            if (!empty($hrEmails)) {
                $subject = 'Member Profile Changes';
                // Extract email addresses from the result set
                $emailAddresses = [];
                foreach ($hrEmails as $hrEmail) {
                    $emailAddresses[] = $hrEmail->user_email;
                }
                // Send the email to all HR recipients
                $mailData = [
                    'subject' => $subject,
                    'schemeCode' => $schemeCode,
                ];
                Mail::to($emailAddresses)->send(new HRNotificationMail($mailData));
            }
            $memberChanges = DB::connection('mydb_macros_sqlsrv')->select("SELECT TOP (1) * from  member_profile_changes where scheme_code = '$schemeCode' and member_number= '$memberNumber' and posted_status= 0 order by member_submitted_on DESC ");
            if (count($memberChanges) > 0) {
                $userID = $request->input('user_id');
                $memberDetails = $memberChanges[0];
                $profileChangeID = (int) $memberDetails->profile_change_id;
                // Initialize an empty array to store the update values
                $updates = [];
                // Define a mapping of field names in the request to database columns
                $fieldToColumnMapping = [
                    'user_national_id' => 'id_number',
                    'user_full_names' => 'member_name',
                    'user_phone' => 'phon_num',
                    'user_email' => 'email_address',
                ];

                // Iterate through the request data and build the update array
                foreach ($fieldToColumnMapping as $field => $column) {
                    if ($request->has($field)) {
                        $value = $request->input($field);
                        $updates[] = "$column = '$value'";
                    }
                }
                // Build the SQL update statement
                $updateSql = 'UPDATE member_profile_changes SET '.implode(', ', $updates)." WHERE profile_change_id = '$profileChangeID'";
                // Execute the update query
                $updateProfileStatus = DB::connection('mydb_macros_sqlsrv')->update($updateSql);
                $LogDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  sys_users_tb where user_id = '$userID' ");
                if (count($LogDetails) > 0) {
                    $logData = $LogDetails[0];
                    $audit_username = $logData->user_username;
                    $audit_fullnames = $logData->user_full_names;
                    $audit_date_time = date('Y-m-d H:i:s');
                    $audit_scheme_code = $logData->user_schemes;
                    $subject = 'Member Profile Update';
                    $email = $logData->user_email;
                    $member_name = $logData->user_full_names;
                    $mailData = [
                        'name' => $member_name,
                        'subject' => $subject,
                    ];
                    Mail::to($email)->send(new ProfileChangeMail($mailData));
                    if ($updateProfileStatus) {
                        $audit_activity = 'User Profile Update';
                        $audit_description = 'update User Profile Success';
                        $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                        return response()->json([
                            'status' => 200,
                            'operation' => 'success',
                            'message' => 'Dear esteemed member the  profile change request has been recorded, please wait as it is being approved.',
                        ], 200);
                    } else {
                        $audit_activity = 'User Profile Update';
                        $audit_description = 'update User Profile Failed';
                        $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                        return response()->json([
                            'status' => 400,
                            'operation' => 'failure',
                            'message' => 'Profile update failed',
                        ], 400);
                    }
                }
            } else {
                $memberDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  members_tb m, scheme_tb s where m.m_scheme_code = '$schemeCode' and m.m_number= '$memberNumber' and m.m_scheme_code = s.scheme_code");
                $memberData = $memberDetails[0];
                $member_name = '';
                if ($request->has('user_full_names')) {
                    $member_name = $request->input('user_full_names');
                } else {
                    $member_name = $logDetails->user_full_names;
                }
                $gender = $memberData->m_gender;
                $dob = $memberData->m_dob;
                $marital_status = $memberData->m_marital;
                $nationality = $memberData->m_nationality;
                $kra_pin = $memberData->m_pin;
                $physical_address = $memberData->m_address;
                $member_submitted_on = date('Y-m-d H:i:s');
                $scheme_name = $memberData->scheme_name;
                $email_address = $request->input('user_email');
                $id_number = $request->input('user_national_id');
                $phon_num = $request->input('user_phone');

                $addProfileChange = DB::connection('mydb_macros_sqlsrv')->insert('INSERT INTO member_profile_changes(scheme_code, member_number, member_name, gender, marital_status, nationality, kra_pin, phon_num, email_address, physical_address, member_submitted_on, scheme_name,  id_number)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', [$schemeCode, $memberNumber, $member_name, $gender, $marital_status, $nationality, $kra_pin, $phon_num, $email_address, $physical_address, $member_submitted_on, $scheme_name,  $id_number]);

                $subject = 'Member Profile Update';
                $member_name = $logDetails->user_full_names;
                $email = $logDetails->user_email;
                $mailData = [
                    'name' => $member_name,
                    'subject' => $subject,
                ];
                Mail::to($email)->send(new ProfileChangeMail($mailData));
                $audit_activity = 'Beneficiary, Bank details and next of kin update';
                $audit_description = 'Profile Update : Success';
                $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                                        VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                // Audit data   \
                return response()->json([
                    'status' => 200,
                    'operation' => 'success',
                    'message' => 'Dear esteemed member the  profile change request has been recorded, please wait as it is being approved.',
                ], 200);
            }
        }
    }

    public function userRatings(Request $request){

        $validator = Validator::make($request->all(),[
            'identifier' => 'required|string|max:15',
            'ratingValue' => 'required|regex:/^\d+(\.\d{1})?$/|max:20',
            'ratingReason' => 'required|string|',
            'ratingService' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'operation' => 'failure',
                'message' => 'Missing data/invalid data. Please try again.',
                'errors' => $validator->errors()->all()
            ], 400);
            exit();
        }
        $data = $request->all();
        $identifier = $data['identifier'];
        $ratingValue = $data['ratingValue'];
        $ratingReason = $data['ratingReason'];
        $ratingService = $data['ratingService'];

        $add_user_rating = $add_user = DB::connection('mydb_sqlsrv')->insert(' INSERT INTO user_ratings_tb ( identifier, ratingValue, ratingReason, ratingService) VALUES (?, ?, ?, ?)', [ $identifier, $ratingValue, $ratingReason, $ratingService]);

        if($add_user_rating){
            return response()->json(
                [
                    'status' => 200,
                    'operation' => 'success',
                    'message' => 'Your feedback has been successfully received. Thanks for your continued support!. Incase of any queries please contact support on support@octagonafrica.com or +254709986000',
                ], 200
            );
        }else{
            return response()->json(
                [
                    'status' => 403,
                    'operation' => 'failure',
                    'message' => 'Your feedback has not been successfully received. Please contact support on support@octagonafrica.com or +254709986000',
                ], 403
            );
        }
    }

    public function memberOnBoarding(Request $request){
        $validator = Validator::make($request->all(),[
            'product' => 'required|string|max:255',
            'productDescription' => 'required|string|max:255',
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'national_ID' => 'required|string|max:255',
            'modeOfPayment' => 'required|string|max:255',
            'methodOfPayment' => 'required|string|max:255',
            'amount' => 'required|string|max:13',
            'phoneNumber' => [
                'required',
                'regex:/^(\+254|254|0|256|260)(7\d{8})$/',
                'min:10',
            ],

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'operation' => 'failure',
                'message' => 'Missing data/invalid data. Please try again.',
                'errors' => $validator->errors()->all()
            ], 400);
            exit();
        }
        $data = $request->all();
        $product = $data['product'];
        $productDescription = $data['productDescription'];
        $firstname = $data['firstname'];
        $lastname = $data['lastname'];
        $email = $data['email'];
        $national_ID = $data['national_ID'];
        $methodOfPayment = $data['methodOfPayment'];
        $modeOfPayment = $data['modeOfPayment'];
        $amount = $data['amount'];
        $phoneNumber = $data['phoneNumber'];




        $memberOnboarding = $add_user = DB::connection('mydb_sqlsrv')->insert(' INSERT INTO access_channels_conversions_tb ( product, productDescription, firstname, lastname, email, national_ID, methodOfPayment, modeOfPayment, amount, phoneNumber) VALUES (?, ?, ?, ?, ?, ? ,?, ?, ?, ?)',[ $product, $productDescription, $firstname, $lastname, $email, $national_ID, $methodOfPayment, $modeOfPayment, $amount, $phoneNumber]);
        $password = "Octagon".date('Y').'!';
        $user_enc_pwd = password_hash($password, PASSWORD_DEFAULT);
        $user_username = $national_ID;
        $user_active = 1;
        $user_national_id = $national_ID;
        $currentYear = date('Y');
        $user_member_no = 'IPP/'.$national_ID.'/'.$currentYear;
        $user_schemes = "KE421";
        $user_mobile = $phoneNumber;
        $user_phone = $phoneNumber;
        $user_email = $email;
        $user_group = 'Member';
        $user_scheme_group = 'Scheme';
        $user_full_names = $firstname. ' '.$lastname;

        $m_scheme_code = $user_schemes;
        $m_combined = $user_schemes.":".$user_member_no;
        $m_number = $user_member_no;
        $m_name = $user_full_names;
        $m_doj = date('Y');
        $m_id_number = $user_national_id;
        $m_phone = $user_phone;
        $m_status = 'Active';
        $m_email = $user_email;
        $memberDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  sys_users_tb where  user_username ='$user_username'");
        if (count($memberDetails) > 0) {
            $memberDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  members_tb where  m_number ='$m_number' and m_scheme_code = '$m_scheme_code' ");
            if (count($memberDetails) > 0) {
                return response()->json(
                    [
                        'status' => 403,
                        'operation' => 'failure',
                        'message' => "Dear $firstname  $lastname Your We did not pick your response. Please contact support on support@octagonafrica.com or +254709986000 ",
                    ], 403
                );
                exit();
            }

            $insertMembersTB = $add_user = DB::connection('mydb_sqlsrv')->insert('INSERT INTO members_tb (m_scheme_code, m_combined, m_number, m_name, m_doj, m_id_number, m_phone, m_status, m_email)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ',[$m_scheme_code, $m_combined, $m_number, $m_name, $m_doj, $m_id_number, $m_phone, $m_status, $m_email]);
            if($insertMembersTB){
                $memberSendLink = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  members_tb where  m_number ='$m_number' and m_scheme_code = '$m_scheme_code' ");
                if (count($memberSendLink) > 0) {

                    $memberDetails = $memberSendLink[0];
                    $member_id = $memberDetails->m_id;
                    $hashids = new Hashids('jesusisthesaltoftheearth', 5);
                    $hash = $hashids->encode($member_id);
                    $details_update_link = "https://cloud.octagonafrica.com/macros/member_onboarding/index.php?mid=$hash";

                    $subject = 'Member Portal Login Credentials';
                    $mailData = [
                        'user_full_names' => $user_full_names,
                        'subject' => $subject,
                        'updateLink' => $details_update_link
                    ];
                    Mail::to($user_email)->send(new ExistingMemberOnboardingMail($mailData));
                    $trimmedNumber = trim($user_mobile);
                    $noSpacesNumber = str_replace([' ', '+'], '', $trimmedNumber);
                    $new_no = "+$noSpacesNumber";
                    $parameters = [
                        'message' => "Hello $user_full_names ,Thank you for buying Jistawishe IPP from us.  \nRegards \nOctagon Africa Team ", // the actual message
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

                        // return response()->json(
                        //     [
                        //         'status' => 200,
                        //         'token' => $token,
                        //         'operation' => 'success',
                        //         'message' => "OTP sent to $new_no and $identifier",
                        //         // 'sms status' => $get_sms_status
                        //     ],
                        //     200
                        // );
                    } catch (\Throwable $th) {
                        return response()->json(
                            [
                                'status' => 400,
                                'operation' => 'fail',
                                'message' => 'Unable to send OTP phone ',
                            ],
                            400
                        );
                    }
                    return response()->json(
                        [
                            'status' => 200, //201 created
                            'operation' => 'success',
                            'message' => "Dear $firstname  $lastname ,Your request has been successfully received.",
                        ], 200
                    );
                }
            }
        }

        $InsertSysUsers = $add_user = DB::connection('mydb_sqlsrv')->insert(' INSERT INTO sys_users_tb (user_username, user_enc_pwd, user_active, user_national_id, user_full_names, user_member_no, user_schemes, user_mobile, user_phone, user_email, user_group, user_scheme_group)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',[ $user_username, $user_enc_pwd, $user_active, $user_national_id, $user_full_names, $user_member_no, $user_schemes, $user_mobile, $user_phone, $user_email, $user_group, $user_scheme_group]);
        $memberDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  members_tb where  m_number ='$m_number' and m_scheme_code = '$m_scheme_code' ");
        if (count($memberDetails) > 0) {
            return response()->json(
                [
                    'status' => 403,
                    'operation' => 'failure',
                    'message' => "Dear $firstname  $lastname Your We did not pick your response. Please contact support on support@octagonafrica.com or +254709986000 ",
                ], 403
            );
            exit();
        }

        $insertMembersTB = $add_user = DB::connection('mydb_sqlsrv')->insert('INSERT INTO members_tb (m_scheme_code, m_combined, m_number, m_name, m_doj, m_id_number, m_phone, m_status, m_email)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ',[$m_scheme_code, $m_combined, $m_number, $m_name, $m_doj, $m_id_number, $m_phone, $m_status, $m_email]);

        if($memberOnboarding && $InsertSysUsers && $insertMembersTB){
            $memberSendLink = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  members_tb where  m_number ='$m_number' and m_scheme_code = '$m_scheme_code' ");
            if (count($memberSendLink) > 0) {

                $memberDetails = $memberSendLink[0];
                $member_id = $memberDetails->m_id;
                $hashids = new Hashids('jesusisthesaltoftheearth', 5);
                $hash = $hashids->encode($member_id);
                $details_update_link = "https://cloud.octagonafrica.com/macros/member_onboarding/index.php?mid=$hash";

                $subject = 'Member Portal Login Credentials';
                $mailData = [
                    'user_full_names' => $user_full_names,
                    'user_username' => $user_username,
                    'password' => $password,
                    'subject' => $subject,
                    'updateLink' => $details_update_link
                ];
                Mail::to($user_email)->send(new MemberOnboardingMail($mailData));
                $trimmedNumber = trim($user_mobile);
                $noSpacesNumber = str_replace([' ', '+'], '', $trimmedNumber);
                $new_no = "+$noSpacesNumber";
                $parameters = [
                    'message' => "Hello $user_full_names ,Thank you for registering with us. Please find your username and password below to access our Member Portal https://bit.ly/octagon_member_portal  \nUsername:  $user_username \nPassword:  $password \nRegards \nOctagon Africa Team ", // the actual message
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

                    // return response()->json(
                    //     [
                    //         'status' => 200,
                    //         'token' => $token,
                    //         'operation' => 'success',
                    //         'message' => "OTP sent to $new_no and $identifier",
                    //         // 'sms status' => $get_sms_status
                    //     ],
                    //     200
                    // );
                } catch (\Throwable $th) {
                    return response()->json(
                        [
                            'status' => 400,
                            'operation' => 'fail',
                            'message' => 'Unable to send OTP phone ',
                        ],
                        400
                    );
                }
                return response()->json(
                    [
                        'status' => 200, //201 created
                        'operation' => 'success',
                        'message' => "Dear $firstname  $lastname ,Your request has been successfully received.",
                    ], 200
                );
            }
        }else{
            return response()->json(
                [
                    'status' => 403,
                    'operation' => 'failure',
                    'message' => "Dear $firstname  $lastname Your We did not pick your response. Please contact support on support@octagonafrica.com or +254709986000",
                ], 403
            );
        }
    }

    // /member profile
    public function MemberProfile(Request $request)
    {
        $memberNo = $request['memberNo'];
        $memberSchemeCode = $request['memberSchemeCode'];
        if (!$memberNo && $memberSchemeCode) { return response()->json([
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
                $memberPostAddress = $member->m_address;
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
                    'memberTaxPIN' => $memberKRAPIN,
                    'memberEmail' => $memberEmail,
                    'memberPhone' => $memberPhone,
                    'memberPhyAddress' => $memberPhyAddress,
                    'memberPostAddress' => $memberPostAddress,
                    'memberAccountName' => $memberAccountName,
                    'memberAccountNo' => $memberAccountNo,
                    'memberBankName' => $memberBankName,
                    'memberBranchName' => $memberBranchName,
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

    // user logs
    public function userlogs(Request $request)
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
        $user_username = $user->user_username;
        $userFullNames = $user->user_full_names;
        $GeneralAudit = DB::connection('mydb_sqlsrv')->select("SELECT top 25 * FROM audit_trail_general_tb WHERE audit_username = '$user_username' AND audit_date_time BETWEEN DATEADD(MONTH, -2, GETDATE()) AND GETDATE() order by audit_date_time DESC ;");
        $MemberPortalAudit = DB::connection('mydb_sqlsrv')->select("SELECT top 25 * from audit_trail_member_portal_tb where audit_username = '$user_username' AND audit_date_time BETWEEN DATEADD(MONTH, -2, GETDATE()) AND GETDATE() order by audit_date_time DESC ;");
        if (count($GeneralAudit) == 0 && count($MemberPortalAudit) == 0) {
            return response()->json([
                'status' => 404,
                'operation' => 'failure',
                'message' => "Dear $userFullNames, you do not have a pension account .",
            ], 404);
        } else {
            $data = [
                'general' => $GeneralAudit,
                'portal' => $MemberPortalAudit,
            ];

            return response()->json([
                'status' => 200,
                'operation' => 'success',
                'message' => 'User logs fetched Succesfully  .',
                'data' => $data,
            ], 200);
        }
    }

    public function updateMemberBioDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'memberNumber' => 'required|string|max:255',
            'schemeCode' => 'required|string|max:255',
            'accountName' => 'string|max:255',
            'accountNo' => 'string|max:255',
            'bankName' => 'string|max:255',
            'branchName' => 'string|max:255',
            'nextOfKinName' => 'string|max:255',
            'nextOfKinPhone' => 'string|max:255',
            'beneficiaryDetails' => [
                'array',
            ],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'operation' => 'failure',
                'message' => 'Missing data/invalid data. Please try again.',
                'errors' => $validator->errors()->all(),
            ], 400);
        } else {
            $memberNumber = $request->input('memberNumber');
            $schemeCode = $request->input('schemeCode');
            $logData = DB::connection('mydb_sqlsrv')->select("9'");
            if (count($logData) < 0) {
                return response()->json([
                    'status' => 409,
                    'operation' => 'failure',
                    'message' => 'User Account Not found.',
                ], 409);
            }
            $logDetails = $logData[0];
            $audit_username = $logDetails->user_username;
            $audit_fullnames = $logDetails->user_full_names;
            $audit_date_time = date('Y-m-d H:i:s');
            $audit_scheme_code = $schemeCode;

            $hrEmails = DB::connection('mydb_sqlsrv')->select("SELECT user_email FROM sys_users_tb WHERE user_schemes = '$schemeCode' AND user_group = 'HR' AND user_active = 1;");

            if (!empty($hrEmails)) {
                $subject = 'Member Profile Changes';
                // Extract email addresses from the result set
                $emailAddresses = [];
                foreach ($hrEmails as $hrEmail) {
                    $emailAddresses[] = $hrEmail->user_email;
                }
                // Send the email to all HR recipients
                $mailData = [
                    'subject' => $subject,
                    'schemeCode' => $schemeCode,
                ];
                Mail::to($emailAddresses)->send(new HRNotificationMail($mailData));
            }
            // do the insert or update
            $memberChanges = DB::connection('mydb_macros_sqlsrv')->select("SELECT TOP (1) * from  member_profile_changes where scheme_code = '$schemeCode' and member_number= '$memberNumber' and posted_status= 0 order by member_submitted_on DESC ");
            if (count($memberChanges) > 0) {
                // update
                $memberDetails = $memberChanges[0];
                $profileChangeID = $memberDetails->profile_change_id;
                // Initialize an empty array to store the update values
                $updates = [];
                // Define a mapping of field names in the request to database columns
                $fieldToColumnMapping = [
                    'accountNo' => 'accountNo',
                    'accountName' => 'accountName',
                    'bankName' => 'bankName',
                    'branchName' => 'branchName',
                    'nextOfKinPhone' => 'nextOfKinPhone',
                    'nextOfKinName' => 'nextOfKinName',
                    'beneficiaryDetails' => 'beneficiary_details',
                ];

                if ($request->has('beneficiaryDetails')) {
                    $totalBeneficiaryShare = 0;
                    $beneficiaryDetails = $request->input('beneficiaryDetails');
                    // Calculate the total beneficiary share
                    foreach ($beneficiaryDetails as $beneficiary) {
                        if (isset($beneficiary['beneficiary_share'])) {
                            $totalBeneficiaryShare += (int) $beneficiary['beneficiary_share'];
                        }
                    }

                    if ($totalBeneficiaryShare === 100) {
                        foreach ($fieldToColumnMapping as $field => $column) {
                            if ($field === 'beneficiaryDetails') {
                                if ($request->has('beneficiaryDetails')) {
                                    // Convert the array to a JSON string
                                    $value = json_encode($beneficiaryDetails);
                                    $updates[] = "$column = '$value'";
                                }
                            } elseif ($request->has($field)) {
                                $value = $request->input($field);
                                $updates[] = "$column = '$value'";
                            }
                        }
                        // Build the SQL update statement
                        $updateSql = 'UPDATE member_profile_changes SET '.implode(', ', $updates)." WHERE  profile_change_id= '$profileChangeID'";
                        // Execute the update query
                        $updateProfileStatus = DB::connection('mydb_macros_sqlsrv')->update($updateSql);

                        $audit_activity = 'Beneficiary, Bank details and next of kin update';
                        $audit_description = 'Profile Update : Success';
                        $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                                    VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                        $subject = 'Member Profile Update';
                        $email = $logDetails->user_email;
                        $mailData = [
                            'name' => $audit_fullnames,
                            'subject' => $subject,
                        ];
                        Mail::to($email)->send(new ProfileChangeMail($mailData));

                        return response()->json([
                            'status' => 200,
                            'operation' => 'success',
                            'message' => 'Dear esteemed member the  profile change request has been recorded, please wait as it is being approved.  ',
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 409,
                            'operation' => 'failure',
                            'message' => 'Dear Esteemed Member the beneficiary shares should be equal to 100%',
                        ], 409);
                    }
                } else {
                    foreach ($fieldToColumnMapping as $field => $column) {
                        $value = $request->input($field);
                        $updates[] = "$column = '$value'";
                    }
                    // Build the SQL update statement
                    $updateSql = 'UPDATE member_profile_changes SET '.implode(', ', $updates)." WHERE  profile_change_id= '$profileChangeID'";
                    // Execute the update query
                    $updateProfileStatus = DB::connection('mydb_macros_sqlsrv')->update($updateSql);

                    $audit_activity = 'Beneficiary, Bank details and next of kin update';
                    $audit_description = 'Profile Update : Success';
                    $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                                    VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                    $subject = 'Member Profile Update';
                    $email = $logDetails->user_email;
                    $mailData = [
                        'name' => $audit_fullnames,
                        'subject' => $subject,
                    ];
                    Mail::to($email)->send(new ProfileChangeMail($mailData));

                    return response()->json([
                        'status' => 200,
                        'operation' => 'success',
                        'message' => 'Dear esteemed member the  profile change request has been recorded, please wait as it is being approved.  ',
                    ], 200);
                }
            } else {
                // insert
                $memberDetails = DB::connection('mydb_sqlsrv')->select("SELECT TOP (1) * from  members_tb m, scheme_tb s where m.m_scheme_code = '$schemeCode' and m.m_number= '$memberNumber' and m.m_scheme_code = s.scheme_code");
                $memberData = $memberDetails[0];
                $member_name = $memberData->m_name;
                $gender = $memberData->m_gender;
                $dob = $memberData->m_dob;
                $marital_status = $memberData->m_marital;
                $nationality = $memberData->m_nationality;
                $kra_pin = $memberData->m_pin;
                $phon_num = $memberData->m_phone;
                $email_address = $memberData->m_email;
                $physical_address = $memberData->m_address;
                $member_submitted_on = date('Y-m-d H:i:s');
                $scheme_name = $memberData->scheme_name;
                $beneficiaryDetails = $request->input('beneficiaryDetails');
                $beneficiary_details = json_encode($beneficiaryDetails);
                $id_number = $memberData->m_id_number;
                $accountName = $request->input('accountName');
                $accountNo = $request->input('accountNo');
                $branchName = $request->input('branchName');
                $bankName = $request->input('bankName');
                $nextOfKinName = $request->input('nextOfKinName');
                $nextOfKinPhone = $request->input('nextOfKinPhone');

                if ($request->has('beneficiaryDetails')) {
                    $totalBeneficiaryShare = 0;
                    foreach ($beneficiaryDetails as $beneficiary) {
                        if (isset($beneficiary['beneficiary_share'])) {
                            $totalBeneficiaryShare += (int) $beneficiary['beneficiary_share'];
                        }
                    }
                    if ($totalBeneficiaryShare === 100) {
                        $addProfileChange = DB::connection('mydb_macros_sqlsrv')->insert('INSERT INTO member_profile_changes(scheme_code, member_number, member_name, gender, marital_status, nationality, kra_pin, phon_num, email_address, physical_address, member_submitted_on, scheme_name, beneficiary_details, id_number, accountName, accountNo, branchName, bankName, nextOfKinName, nextOfKinPhone)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$schemeCode, $memberNumber, $member_name, $gender, $marital_status, $nationality, $kra_pin, $phon_num, $email_address, $physical_address, $member_submitted_on, $scheme_name, $beneficiary_details, $id_number, $accountName, $accountNo, $branchName, $bankName, $nextOfKinName, $nextOfKinPhone]);

                        $subject = 'Member Profile Update';
                        $email = $logDetails->user_email;
                        $mailData = [
                            'name' => $member_name,
                            'subject' => $subject,
                        ];
                        Mail::to($email)->send(new ProfileChangeMail($mailData));
                        $audit_activity = 'Beneficiary, Bank details and next of kin update';
                        $audit_description = 'Profile Update : Success';
                        $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                                        VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                        // Audit data   \
                        return response()->json([
                            'status' => 200,
                            'operation' => 'success',
                            'message' => 'The  profile change request has been recorded, please wait as it is being approved.  ',
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 409,
                            'operation' => 'failure',
                            'message' => 'Dear Esteemed Member the beneficiary shares should be equal to 100%',
                        ], 409);
                    }
                } else {
                    $addProfileChange = DB::connection('mydb_macros_sqlsrv')->insert('INSERT INTO member_profile_changes(scheme_code, member_number, member_name, gender, marital_status, nationality, kra_pin, phon_num, email_address, physical_address, member_submitted_on, scheme_name, beneficiary_details, id_number, accountName, accountNo, branchName, bankName, nextOfKinName, nextOfKinPhone)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$schemeCode, $memberNumber, $member_name, $gender, $marital_status, $nationality, $kra_pin, $phon_num, $email_address, $physical_address, $member_submitted_on, $scheme_name, $beneficiary_details, $id_number, $accountName, $accountNo, $branchName, $bankName, $nextOfKinName, $nextOfKinPhone]);

                    $subject = 'Member Profile Update';
                    $email = $logDetails->user_email;
                    $mailData = [
                        'name' => $member_name,
                        'subject' => $subject,
                    ];
                    Mail::to($email)->send(new ProfileChangeMail($mailData));
                    $audit_activity = 'Beneficiary, Bank details and next of kin update';
                    $audit_description = 'Profile Update : Success';
                    $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                                        VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                    // Audit data   \
                    return response()->json([
                        'status' => 200,
                        'operation' => 'success',
                        'message' => 'Dear esteemed member the  profile change request has been recorded, please wait as it is being approved. ',
                    ], 200);
                }
            }
        }
    }

    public function memberBioDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'memberNumber' => 'required|string|max:255',
            'schemeCode' => 'required|string|max:255',
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
            $logData = DB::connection('mydb_sqlsrv')->select("SELECT su.user_username, su.user_full_names, m.m_scheme_code from sys_users_tb su , members_tb m where su.user_national_id= m.m_id_number and m.m_number = '$memberNumber' and m.m_scheme_code = '$schemeCode'");
            if (count($logData) < 0) {
                return response()->json([
                    'status' => 409,
                    'operation' => 'failure',
                    'message' => 'User Account Not found.',
                ], 409);
            }
            $logDetails = $logData[0];
            $audit_username = $logDetails->user_username;
            $audit_fullnames = $logDetails->user_full_names;
            $audit_date_time = date('Y-m-d H:i:s');
            $audit_scheme_code = $schemeCode;
            $BankDetails = DB::connection('mydb_sqlsrv')->select("SELECT m_account_no as accountNo, m_account_name as accountName, m_bank_name as bankName, m_branch_name as branchName  from members_tb where m_number = '$memberNumber' and m_scheme_code = '$schemeCode' ;");
            $nextOfKinDetails = DB::connection('mydb_sqlsrv')->select("SELECT m_kin_name as nextOfKinName, m_kin_phone as nextOfKinPhone from members_tb where m_number = '$memberNumber' and m_scheme_code = '$schemeCode' ;");
            $beneficiaryDetails = DB::connection('mydb_sqlsrv')->select("SELECT b_names as beneficiary_name, b_relationship as beneficiary_relationship, b_phone as beneficiary_telephone, b_dob as beneficiary_dob, b_percentage  as beneficiary_share from beneficiaries_tb  where b_scheme_code = '$schemeCode' and b_member_number ='$memberNumber'  ;");

            if (count($beneficiaryDetails) + count($nextOfKinDetails) + count($BankDetails) < 0) {
                $audit_activity = 'Beneficiary, Bank details and next of kin listing';
                $audit_description = 'Profile listing : failed no profile';
                $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                                VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                return response()->json([
                    'status' => 404,
                    'operation' => 'failure',
                    'message' => 'Details Not found user should  update profile.',
                ], 404);
            } else {
                $audit_activity = 'Beneficiary, Bank details and next of kin listing';
                $audit_description = 'Profile Listing : Success';
                $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                                                VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                $data = [
                    'bank_details' => array_map(function ($item) {
                        return [
                            'accountNo' => trim($item->accountNo),
                            'accountName' => trim($item->accountName),
                            'bankName' => trim($item->bankName),
                            'branchName' => trim($item->branchName),
                        ];
                    }, $BankDetails),
                    'nextOfKinDetails' => array_map(function ($item) {
                        return [
                            'nextOfKinName' => trim($item->nextOfKinName),
                            'nextOfKinPhone' => trim($item->nextOfKinPhone),
                        ];
                    }, $nextOfKinDetails),
                    'beneficiaryDetails' => array_map(function ($item) {
                        return [
                            'beneficiary_name' => trim($item->beneficiary_name),
                            'beneficiary_relationship' => trim($item->beneficiary_relationship),
                            'beneficiary_telephone' => trim($item->beneficiary_telephone),
                            'beneficiary_dob' => trim($item->beneficiary_dob),
                            'beneficiary_share' => trim($item->beneficiary_share),
                        ];
                    }, $beneficiaryDetails),
                ];

                return response()->json([
                    'status' => 200,
                    'operation' => 'success',
                    'message' => 'Profile fetched successfully',
                    'data' => $data,
                ], 200);
            }
        }
    }
}
