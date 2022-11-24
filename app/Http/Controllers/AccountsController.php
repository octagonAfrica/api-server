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
                    'insurance' =>$insurance_payload,
                    'pension' => $pension_payload
                ],200);

        } catch (\Throwable $th) {
            $payload = response()->json([
                'status' => 400,
                'success' => false,
                'message' => $th
            ]);
        }
        return $payload;
    }

    public function individualInsuranceAccount(Request $request){
        return response()->json(["Hello" => $request['id']]);
    }
    public function individualPensionAccount(Request $request){
        return response()->json(["Hello" => $request['id']]);
    }
}
