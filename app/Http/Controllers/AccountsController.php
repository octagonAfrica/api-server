<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountsController extends Controller
{
    //get all Clients Accounts
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
                'message' => 'Email/Phone/UserID/nationalID required'
            ], 400);
        }
        if (empty($id_number)) {
            $user_exist = DB::connection('mydb_sqlsrv')
                ->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_id ='$user_id'");
            $user = $user_exist[0];
            $id_number = $user->user_national_id;
        }
        if (empty($email)) {
            $user_exist = DB::connection('mydb_sqlsrv')
                ->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_id ='$user_id'");
            $user = $user_exist[0];
            $email = $user->user_email;
        }
        if (empty($phone)) {
            $user_exist = DB::connection('mydb_sqlsrv')
                ->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_id ='$user_id'");
            $user = $user_exist[0];
            $phone = $user->user_mobile;
        }
        try {

            $trust = DB::connection('mydb_sqlsrv')->select("SELECT t_id,t_code as Code,t_alias_code,t_name as Name,t_category as Category
           ,t_registration_date as dateFrom,t_phone_no,t_frequency,t_status FROM trust_tb1 WHERE t_phone_no ='$phone' AND t_status = 'Active' ");

            $pension = DB::connection('mydb_sqlsrv')->select("SELECT m_id as ID,m_number as ClientID,
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
            $pension_payload = [
                'total_accounts' => count($pension), 'data' => $pension
            ];
            $trust_payload = [
                'total_accounts' => count($trust), 'data' => $trust
            ];
            $payload = response()->json([
                'status' => 200,
                'message' => 'Accounts retrieved Successfully',
                'total_number_of_accounts' => count($insurance) + count($pension) + count($trust),
                'insurance' => $insurance_payload,
                'pension' => $pension_payload,
                'trust' => $trust_payload
            ], 200);
        } catch (\Throwable $th) {
            $payload = response()->json([
                'status' => 400,
                'success' => false,
                'message' => $th
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
                'message' => 'code and user ID required'
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
                        "data" => $insurance
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'Information not available',
                        "data" => $insurance
                    ], 200);
                }
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'message' => $th
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
                'message' => 'code and user ID required'
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
                        "data" => $insurance
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'Information not available',
                        "data" => $insurance
                    ], 200);
                }
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'message' => $th
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
                'message' => 'code and user ID required'
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
                        "data" => $insurance
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'Information not available',
                        "data" => $insurance
                    ], 200);
                }
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'message' => $th
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
                'message' => 'code is required'
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
                        "data" => $individaul_pension
                    ], 200);
                } else {
                    return response()->json([

                        'status' => 200,
                        'success' => true,
                        'message' => 'Information not available',
                        "data" => $individaul_pension
                    ], 200);
                }
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'message' => $th
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
                'message' => 'code is required'
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
                        "data" => $individaul_pension
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 400,
                        'success' => false,
                        'message' => 'No Transactions available',
                        "data" => $individaul_pension
                    ], 400);
                }
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'message' => $th
                ]);
            }
        }
    }
    public function periods(Request $request)
    {
        $m_number = $request['ClientID'];
        if (!$m_number) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'm_number required'
            ], 400);
        } else {
            $sql = "SELECT p.period_id, p.period_name  FROM  members_tb m
        JOIN scheme_periods_tb p ON m.m_scheme_code = p.period_scheme_code
        WHERE m.m_number = '$m_number' order by p.period_id desc";

            $periods = DB::connection('mydb_sqlsrv')->select($sql);
            return response()->json(
                [
                    'total_periods' => count($periods),
                    'data' => $periods
                ]
            );
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
