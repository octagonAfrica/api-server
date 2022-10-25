<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResetPasswordController extends Controller
{
    public function resetPassword(Request $request){
        $email = $request['email'];

    $token = openssl_random_pseudo_bytes(16);
    //Convert the binary data into hexadecimal representation.
    $token = bin2hex($token);

    $sql = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb where user_email = '$email'");
    
    $name = $sql['user_full_names'];
    $user_id = $name['user_id'];
    $datecreated = date('Y-m-d H:i:s');
    $expirydate = date('Y-m-d H:i:s', strtotime($datecreated . ' + 1 days'));
    
    $insert_token = DB::connection('mydb_sqlsrv')->insert('INSERT INTO tokens(token_key,user_id,expire_date,created_date) values (?,?,?,?)', [$token, $user_id, $expirydate, $datecreated]);

        return $insert_token;
    }
}
