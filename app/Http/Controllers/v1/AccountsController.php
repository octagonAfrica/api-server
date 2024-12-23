<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;

class AccountsController extends Controller
{
    // get all Clients Accounts
    public function accounts(Request $request)
    {
        $user_id = $request['user_id'];
        $id_number = $request['user_national_id'];
        $email = $request['user_email'];
        $phone = $request['user_mobile'];
        if (!isset($user_id)) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'Email/Phone/UserID/nationalID required',
            ], 400);
        }
        if (empty($id_number)) {
            $user_exist = DB::connection('mydb_sqlsrv')
                ->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_id ='$user_id'");
	    
	    try {
		    // Attempt to access the Array Key
		    $user = $user_exist[0];
		    $id_number = $user->user_national_id;
	    } catch (\ErrorException $e) {
	    // Handle the TypeError exception
	    return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'Issue while verifying user account! Please contact support@octagonafrica.com for assistance.',
            ], 400);
           }
	}
	if(is_null($id_number) || strlen($id_number)<7) {

            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'Issue with national identification number! Please contact support@octagonafrica.com for assistance.',
            ], 400);
        }
        if (empty($email)) {
            $user_exist = DB::connection('mydb_sqlsrv')
                ->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_id ='$user_id'");
            $user = $user_exist[0];
            $email = $user->user_email;
	}
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
		return response()->json([ 
		'status' => 400, 
		'success' => false,
		'message' => 'Incorrect email format! Please contact support@octagonafrica.com for assistance.', ], 400); 
	}
        if (empty($phone)) {
            $user_exist = DB::connection('mydb_sqlsrv')
                ->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_id ='$user_id'");
            $user = $user_exist[0];
            $phone = $user->user_mobile;
	}
	if (strlen($phone)<10) {
                return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'Issue with phone number! Please contact support@octagonafrica.com for assistance.', ], 400);
        }
        try {
            $trust = DB::connection('mydb_sqlsrv')->select("SELECT t_id,t_code as Code,t_alias_code,t_name as Name,t_category as Category
           ,t_registration_date as dateFrom,t_phone_no,t_frequency,t_status FROM trust_tb1 WHERE t_phone_no ='$phone' AND t_status = 'Active' ");

            $pension = DB::connection('mydb_sqlsrv')->select("SELECT m_id as ID,m_number as ClientID, m_scheme_code as schemeCode,
            m_combined as Code,m_name as Name, s.scheme_name,
            m_id_number, m_pin, m_payment_mode,
            m_status_date as dateFrom
            FROM members_tb
            JOIN scheme_tb s ON m_scheme_code = s.scheme_code
            WHERE m_id_number = '$id_number'");

            $insurance = DB::select("SELECT C.ID,C.ClientID,C.Name,I.Code,I.Description,I.Items, C.sum_assured,C.due_premium,C.dateFrom,C.dateTo
            from Clients C join InsuredItems I on C.ClientID = I.ClientID
            where C.UserID = '$user_id'  OR
            C.Mobile ='$phone' OR
            C.Email = '$email' OR
            C.IDNO = '$id_number'");

            $insurance_payload = [
                'total_accounts' => count($insurance), 'data' => $insurance,
            ];

            $isCorporate = true;
            foreach ($pension as $record) {
                $schemeCode = $record->schemeCode;

                if ($schemeCode === 'KE001' || $schemeCode === 'OUPTF0002') {
                    $isCorporate = false;
                }
            }

            $pension_payload = [
                'isCorporate' => $isCorporate,
                'total_accounts' => count($pension),
                'data' => $pension,
            ];

            $trust_payload = [
                'total_accounts' => count($trust), 'data' => $trust,
            ];
            $merged = array_merge($insurance, $pension, $trust);
            $totalAccounts = count($merged);
            if ($totalAccounts == 0) {
                $sql_user = DB::connection('mydb_sqlsrv')
                ->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_id ='$user_id'");
                if ($sql_user) {
                    $user = $sql_user[0];
                    $phone = $user->user_mobile;

                    $trimmedNumber = trim($phone);
                    $noSpacesNumber = str_replace(' ', '', $trimmedNumber);
                    $new_no = "+$noSpacesNumber";
                    $parameters = [
                        'message' => "Hello Esteemed Member,\nSome of your details are missing. Please contact support at support@octagonafrica.com or call 0709 986 000 to update your details.
                        \n1. National ID Number(Please send a copy of  front and back of your ID).", // the actual message
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
                } else {
                    return response()->json([
                        'status' => 400,
                        'operation' => 'fail',
                        'message' => 'Phone number not registered.',
                    ], 400);
                }
            }
            $payload = response()->json([
                'status' => 200,
                'message' => 'Accounts retrieved Successfully',
                'total_number_of_accounts' => count($insurance) + count($pension) + count($trust),
                'totalAccounts' => $totalAccounts,
                'insurance' => $insurance_payload,
                'pension' => $pension_payload,
                // 'messageResponse' => $messageResponse,
                'trust' => $trust_payload,
            ], 200);
        } catch (\Throwable $th) {
            $payload = response()->json([
                'status' => 400,
                'success' => false,
                'message' => $th,
            ]);
        }

        return $payload;
    }

    public function individualIppAccount(Request $request)
    {
        $code = $request['code'];
        $user_id = $request['user_id'];
        if (!$code && !$user_id) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'code and user ID required',
            ], 400);
        } else {
            try {
                $insurance = DB::select("SELECT C.ID,C.ClientID,C.Name,I.Code,I.Description,I.Items, C.sum_assured,C.due_premium,C.dateFrom,C.dateTo
            from Clients C join InsuredItems I on C.ClientID = I.ClientID where C.UserID = '$user_id' AND I.Code = '$code'");
                if ($insurance) {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'Account infromation retrieved successfully.',
                        'data' => $insurance,
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'Information not available',
                        'data' => $insurance,
                    ], 200);
                }
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'message' => $th,
                ]);
            }
        }
    }

    public function individualEasyCoverAccount(Request $request)
    {
        $code = $request['code'];
        $user_id = $request['user_id'];
        if (!$code && !$user_id) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'code and user ID required',
            ], 400);
        } else {
            try {
                $insurance = DB::select("SELECT C.ID,C.ClientID,C.Name,I.Code,I.Description,I.Items, C.sum_assured,C.due_premium,C.dateFrom,C.dateTo
            from Clients C join InsuredItems I on C.ClientID = I.ClientID where C.UserID = '$user_id' AND I.Code = '$code'");
                if ($insurance) {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'Account Information retrieved successfully.',
                        'data' => $insurance,
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'Information not available',
                        'data' => $insurance,
                    ], 200);
                }
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'message' => $th,
                ]);
            }
        }
    }

    public function individualMotorAccount(Request $request)
    {
        $code = $request['code'];
        $user_id = $request['user_id'];
        if (!$code && !$user_id) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'code and user ID required',
            ], 400);
        } else {
            try {
                $insurance = DB::select("SELECT C.ID,C.ClientID,C.Name,I.Code,I.Description,I.Items, C.sum_assured,C.due_premium,C.dateFrom,C.dateTo
                from Clients C join InsuredItems I on C.ClientID = I.ClientID where C.UserID = '$user_id' AND I.Code = '$code'");
                if ($insurance) {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'Account information retrieved successfully.',
                        'data' => $insurance,
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'Information not available',
                        'data' => $insurance,
                    ], 200);
                }
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'message' => $th,
                ]);
            }
        }
    }

    public function individualPensionAccount(Request $request)
    {
        $code = $request['code'];
        if (!$code || empty($code)) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'code is required',
            ], 400);
        } else {
            try {
                $individaul_pension = DB::connection('mydb_sqlsrv')
                    ->select("SELECT m_id as member_id,
                 m_combined as Code,
                 m_number as member_number,
                 s.scheme_id, s.scheme_code,
                 s.scheme_name as scheme,
                 m_payment_mode as paymet_mode,
                 m_payment_frequency,
                 m_account_no, m_status
                FROM members_tb
                join scheme_tb s on m_scheme_code = s.scheme_code
                where  m_combined = '$code'");

                if ($individaul_pension) {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'Account information successfully retrieved',
                        'data' => $individaul_pension,
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'Information not available',
                        'data' => $individaul_pension,
                    ], 200);
                }
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'message' => $th,
                ]);
            }
        }
    }

    public function individualPensionAccountTransactions(Request $request)
    {
        $cont_member_number = $request['ClientID'];
        $m_id = $request['ID'];

        if (!$cont_member_number || empty($cont_member_number)) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'code is required',
            ], 400);
        } else {
            try {
                $sql = "  SELECT  cont_id, cont_date_paid as Display_date, cont_document as Batch,cont_type as Type, cont_amount as Amount from contributions_tb
                join members_tb m on cont_member_number = m.m_number
                 where cont_member_number = '$cont_member_number' AND m.m_id ='$m_id'
                 order by cont_date_paid desc OFFSET 0 ROWS FETCH NEXT 30 ROWS ONLY";
                $individaul_pension = DB::connection('mydb_sqlsrv')
                    ->select($sql);
                if ($individaul_pension) {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'Transactions successfully retrieved',
                        'total_transactions' => count($individaul_pension),
                        'data' => $individaul_pension,
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 400,
                        'success' => false,
                        'message' => 'No Transactions available',
                        'data' => $individaul_pension,
                    ], 400);
                }
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'message' => $th,
                ]);
            }
        }
    }

    public function periods(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ClientID' => 'required|max:255',
            'schemeCode' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'operation' => 'failure',
                'message' => 'Missing data/invalid data. Please try again.',
                'errors' => $validator->errors()->all(),
            ], 400);
        } else {
            $memberNumber = $request->input('ClientID');
            $schemeCode = $request->input('schemeCode');

            $sql = "SELECT DISTINCT p.period_id, p.period_name
                        FROM members_tb m
                            JOIN scheme_periods_tb p ON m.m_scheme_code = p.period_scheme_code
                            JOIN scheme_sub_periods_tb sp ON p.period_id = sp.scheme_period_id
                        WHERE m.m_number = '$memberNumber' and m.m_scheme_code='$schemeCode'
                        ORDER BY p.period_id DESC;";
            $periods = DB::connection('mydb_sqlsrv')->select($sql);

            return response()->json(
                [
                    'total_periods' => count($periods),
                    'data' => $periods,
                ]
            );
        }
    }

    // to handle accounts requests from two-way-sms
    public function showAccounts(Request $request)
    {
        $identifier = $request['identifier'];
        if (!$identifier || empty($identifier)) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'Username required.',
            ], 400);
        } else {
            $sql_user = DB::connection('mydb_sqlsrv')
                ->select("SELECT TOP 1 * FROM sys_users_tb where user_username  = '$identifier' OR user_national_id = '$identifier' OR user_email = '$identifier'");
            if ($sql_user) {
                $user = $sql_user[0];
                $name = $user->user_full_names;
                $user_id = $user->user_id;
                $user_schemes = $user->user_schemes;
                $user_member_no = $user->user_member_no;

                return response()->json(
                    [
                        'status' => 200,
                        'operation' => 'success',
                        'message' => "$name's is $user_id",
                        'data' => $user_id,
                        'user_schemes' => $user_schemes,
                        'user_member_no' => $user_member_no,
                    ],
                    200
                );
            } else {
                return response()->json([
                    'status' => 400,
                    'operation' => 'fail',
                    'message' => 'Username not registered.',
                ], 400);
            }
        }
    }

    // Get periods on two-way-sms and provide data for MembersStatement and
    public function twowayPeriods(Request $request)
    {
        $description = $request['description'];
        if (!$description || empty($description)) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'Account description required.',
            ], 400);
        } else {
            $scheme_code = DB::connection('mydb_sqlsrv')
                ->select("SELECT TOP 1 * FROM members_tb where m_combined = '$description'");
            if ($scheme_code) {
                $scheme = $scheme_code[0];
                $code = $scheme->m_scheme_code;
                $periods_available = DB::connection('mydb_sqlsrv')->select("SELECT period_name from scheme_periods_tb where period_scheme_code like '%$code%' order by period_id DESC");
                if (count($periods_available) > 0) {
                    return response()->json(
                        [
                            'status' => 200,
                            'operation' => 'success',
                            'message' => 'Periods fetched Succesfully',
                            'data' => $periods_available,
                        ],
                        200
                    );
                } else {
                    return response()->json([
                        'status' => 400,
                        'operation' => 'fail',
                        'message' => 'Scheme periods Not Found',
                    ], 400);
                }
            } else {
                return response()->json([
                    'status' => 400,
                    'operation' => 'fail',
                    'message' => 'Scheme Does not exist.',
                ], 400);
            }
        }
    }

    // get prd id for the Member statements.
    public function twowayPeriodID(Request $request)
    {
        $periodname = $request['periodname'];
        $description = $request['description'];
        if (!$periodname || empty($periodname) || !$description || empty($description)) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'Period name required.',
            ], 400);
        } else {
            $parts = explode(':', $description);
            $schemeCode = $parts[0];
            $period_ID = DB::connection('mydb_sqlsrv')
                ->select("SELECT period_id from  scheme_periods_tb where  period_name like '%$periodname%' and period_scheme_code like '%$schemeCode%'");
            if ($period_ID) {
                $periods = $period_ID[0];
                $PeriodID = $periods->period_id;

                return response()->json(
                    [
                        'status' => 200,
                        'operation' => 'success',
                        'message' => 'Periods fetched Succesfully',
                        'data' => $PeriodID,
                    ],
                    200
                );
            } else {
                return response()->json([
                    'status' => 400,
                    'operation' => 'fail',
                    'message' => 'Period does not exist.',
                ], 400);
            }
        }
    }

    // account balance
    public function accountBalance(Request $request)
    {
        date_default_timezone_set('Africa/Nairobi');
        $validator = Validator::make($request->all(), [
            'memberID' => 'required|int',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'operation' => 'failure',
                'message' => 'Missing data/invalid data. Please try again.',
                'errors' => $validator->errors()->all(),
            ], 400);
        } else {

            $memberID = $request->input('memberID');
            $memberSQL = "SELECT * FROM members_tb WHERE m_id ='$memberID'";
            $member = DB::connection('mydb_sqlsrv')->select($memberSQL);
            if (count($member) > 0) {
                $memberData = $member[0];
                $schemeCode = $memberData->m_scheme_code;
                $memberNumber = $memberData->m_number;
                $memberStatus = $memberData->m_status;

                if($memberStatus =='Active') {
                    $schemeSQL = "SELECT * FROM scheme_tb WHERE scheme_code LIKE '%$schemeCode%'";
                    $scheme = DB::connection('mydb_sqlsrv')->select($schemeSQL);
                    if (count($scheme) > 0) {
                        $schemeData = $scheme[0];
                        $scheme_commencement = $schemeData->scheme_commencement;
                        $scheme_commencement = Carbon::createFromFormat('Y-m-d', $scheme_commencement);
                        $dateToday = date('Y-m-d');

                        $amountsSQL = "SELECT cont_category, cont_taxation, SUM(cont_amount) AS cont_amount FROM contributions_tb WHERE cont_scheme_code LIKE '%$schemeCode%' AND cont_member_number LIKE '%$memberNumber%'  AND cont_date_paid BETWEEN '$scheme_commencement' AND '$dateToday' GROUP BY cont_category, cont_taxation";
                        $amounts = DB::connection('mydb_sqlsrv')->select($amountsSQL);

                        $total_pro = 0;
                        $pro_ee_te = 0;
                        $pro_ee_nte = 0;
                        $pro_avc_te = 0;
                        $pro_avc_nte = 0;
                        $pro_er_te = 0;
                        $pro_er_nte = 0;

                        foreach ($amounts as $row) {
                            $total_pro += $row->cont_amount;

                            if ($row->cont_category === 'EE') {
                                if ($row->cont_taxation === 'Tax Exempt') {
                                    $pro_ee_te += $row->cont_amount;
                                }
                                if ($row->cont_taxation === 'Non Tax Exempt') {
                                    $pro_ee_nte += $row->cont_amount;
                                }
                            }

                            if ($row->cont_category === 'AVC') {
                                if ($row->cont_taxation === 'Tax Exempt') {
                                    $pro_avc_te += $row->cont_amount;
                                }
                                if ($row->cont_taxation === 'Non Tax Exempt') {
                                    $pro_avc_nte += $row->cont_amount;
                                }
                            }

                            if ($row->cont_category === 'ER') {
                                if ($row->cont_taxation === 'Tax Exempt') {
                                    $pro_er_te += $row->cont_amount;
                                }
                                if ($row->cont_taxation === 'Non Tax Exempt') {
                                    $pro_er_nte += $row->cont_amount;
                                }
                            }
                        }

                        $accountBalance = $total_pro; // /not taking to account what has already been withdrawn

                        $data = [
                            'accountBalance' => $accountBalance,
                        ];

                        return response()->json([
                            'status' => 200,
                            'operation' => 'success',
                            'message' => 'Member account balance fetched successfully',
                            'data' => $data,
                        ], 200);
                    } else {
                        return response()->json([
                            'status' => 409,
                            'operation' => 'failure',
                            'message' => 'Dear Esteemed Customer, the scheme code provided for your account does not exist.  Please contact your scheme adminstrator or contact support at support@octagonafrica.com or call 0709986000',
                        ], 409);
                    }
                }else{
                    return response()->json([
                        'status' => 403,
                        'operation' => 'failure',
                        'message' => 'Dear Esteemed Customer, Your account is inactive  please contact your scheme adminstrator or contact support at support@octagonafrica.com or call 0709986000',

                    ], 403);
                }
            }else{
                return response()->json([
                    'status' => 404,
                    'operation' => 'failure',
                    'message' => 'Dear customer, Your account does not exist. Please contact your scheme adminstrator or contact support at support@octagonafrica.com or call 0709986000',

                ], 404);

            }
        }
    }
}

// SQL to get member statements
//   select top 1000 c.cont_amount, c.cont_date_paid from scheme_tb s
// join scheme_sub_periods_tb p on s.scheme_id = p.scheme_period_id
// join scheme_periods_tb t on p.scheme_period_id = t.period_id
// join contributions_tb c on  s.scheme_code = c.cont_scheme_code
// join members_tb m on m.m_scheme_code = s.scheme_code
// where m.m_number='TEST3' and t.period_id = '20' and m.m_scheme_code= 'KE003';

// SQL to get all periods as per memmber no.
// select p.period_id, p.period_name  from  members_tb m
// join scheme_periods_tb p on m.m_scheme_code = p.period_scheme_code
// where m.m_number = 'TEST3'
