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
        if (empty($user_id) || empty($id_number) || !$user_id || !$id_number) {
            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'userID and national ID required'
            ], 400);
        }
        try {
            $pension = DB::connection('mydb_sqlsrv')->select("SELECT m_id as ID,m_number as ClientID,m_combined as Code,m_name as Name, m_id_number, m_pin, m_payment_mode FROM members_tb 
            WHERE m_id_number = '$id_number'");
            $insurance = DB::select("SELECT C.ID,C.ClientID,C.Name,I.Code,I.Description,I.Items, C.sum_assured,C.due_premium,C.dateFrom,C.dateTo
            from Clients C join InsuredItems I on C.ClientID = I.ClientID where C.UserID = '$user_id'");

            $insurance_payload = [
                'total_accounts' => count($insurance), 'data' => $insurance,
            ];
            $pension_payload = [
                'total_accounts' => count($pension), 'data' => $pension
            ];
            $payload = response()->json([
                'status' => 200,
                'message' => 'Accounts retrieved Successfully',
                'total_number_of_accounts' => count($insurance) + count($pension),
                'insurance' => $insurance_payload,
                'pension' => $pension_payload
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
                        'message' => 'contributions retrieved successfully.',
                        "data" => $insurance
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'No contributions available',
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
                        'message' => 'contributions retrieved successfully.',
                        "data" => $insurance
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'No contributions available',
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
                        'message' => 'contributions retrieved successfully.',
                        "data" => $insurance
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'No contributions available',
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
                $individaul_pension = DB::connection('mydb_sqlsrv')->select("SELECT  m_name,m_payment_mode,c.cont_id, c.cont_member_number,c.cont_category, c.cont_amount,c.cont_date_paid
                FROM members_tb join contributions_tb c on m_number=c.cont_member_number
                   where m_combined = '$code'");

                $individual_pension_payload = [
                    'total_contributions' => count($individaul_pension), 'data' => $individaul_pension
                ];
                if ($individaul_pension) {
                    return response()->json([
                        'status' => 200,
                        'success' => true,
                        'message' => 'Contributions successfully retrieved',
                        "data" => $individual_pension_payload
                    ], 200);
                } else {
                    return response()->json([

                        'status' => 200,
                        'success' => true,
                        'message' => 'No contributions available',
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
}
