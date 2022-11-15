<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountsController extends Controller
{

    public function fetchUserID($email){
        try {
            $get_user_id = DB::connection('mydb_sqlsrv')
        ->select("SELECT * FROM sys_users_tb where  user_email = '$email'");
        $payload = $get_user_id[0]->user_id;
        } catch (\Throwable $th) {
            $payload = [
                'status'=>401,
                'message' => $th
            ];
        }
        
        return $payload;
    }
    //get all Clients Accounts
    public function accounts(Request $request){
        

        if($request['user_id']){
            $user_id = $request['user_id'];
            $id_number = $request['user_national_id'];
            try {
            $members = DB::connection('mydb_sqlsrv')->select("SELECT * FROM members_tb WHERE m_id_number ='$id_number'");           
            $accounts = DB::select("SELECT * from Clients where UserID = '$user_id'");
            if(!$accounts){
                $payload = response()->json([
                    'status' => 401,
                    'message' => 'No Accounts Found'
                ], 401);
            } else {
                $payload = [
                    'status' => 200,
                    'message' => 'Accounts retrieved Successfully',
                    'total' => count($accounts)+count($members),
                    'total insurance ' => count($accounts),
                    'total members' => count($members),
                    'insurance accounts' => $accounts,
                    'members accounts' => $members
                ];
            };
        } catch (\Throwable $th) {
            return response()->json([
                'status'=> 400,
                'message' => $th
            ]);
        }   
            return $payload;
        }
    } 
}
