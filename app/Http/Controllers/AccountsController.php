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
            $members = DB::connection('mydb_sqlsrv')->select("SELECT * FROM members_tb WHERE m_id_number ='$id_number'");
            $accounts = DB::select("SELECT * from Clients where UserID = '$user_id'");
            if (!$accounts) {
                $payload = response()->json([
                    'status' => 401,
                    'message' => 'No Accounts Found'
                ], 401);
            } else {
                $payload = response()->json([
                    'status' => 200,
                    'message' => 'Accounts retrieved Successfully',
                    'total accounts' => count($accounts) + count($members),
                    'total insurance accounts ' => count($accounts),
                    'total member accounts' => count($members),
                    'insurance accounts' => $accounts,
                    'member accounts' => $members
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
