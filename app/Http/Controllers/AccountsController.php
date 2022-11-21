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
            $members = DB::connection('mydb_sqlsrv')->select("SELECT m_id,m_number,m_combined,m_name, m_id_number, m_pin, m_payment_mode FROM members_tb 
            WHERE m_id_number = '$id_number'");
            $accounts = DB::select("SELECT C.ID,C.ClientID,C.Name,I.Code,I.Description,I.Items, C.sum_assured,C.due_premium,C.dateFrom,C.dateTo
            from Clients C join InsuredItems I on C.ClientID = I.ClientID where C.UserID = '$user_id'");
            
            if (!$accounts) {
                $payload = response()->json([
                    'status' => 401,
                    'message' => 'No Accounts Found'
                ], 401);
            } else {
                $payload = response()->json([
                    'status' => 200,
                    'message' => 'Accounts retrieved Successfully',
                    'Total number of accounts' => count($accounts) + count($members),
                    'Insurance accounts ' => count($accounts),
                    'Pensions accounts' => count($members),
                    'Insurance accounts' => $accounts,
                    'Pension accounts' => $members
                ],200);
            };
        } catch (\Throwable $th) {
            $payload = response()->json([
                'status' => 400,
                'success' => false,
                'message' => $th
            ]);
        }
        return $payload;
    }
}
