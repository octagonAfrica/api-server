<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DeleteUserController extends Controller
{
    public function deleteUser(Request $request)
    {
        $rules = [
            'ID' => 'required',
            'password' => 'required',
        ];

        // Validate request
        $validator = Validator::make($request->all(), $rules);
        $data = $request->all();
        $national_id = $data['ID'];
        $password = $data['password'];
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 400,
                    'success' => false,
                    'message' => "Invalid request. Please input your National Id and password."
                ], 400
            );
        } else {
            $user_exist = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_national_id = '$national_id' AND user_active = 1");
            if (count($user_exist) > 0) {
                $user = $user_exist[0];
                // (password_verify($password, trim($user->user_enc_pwd)))
                if (password_verify($password, trim($user->user_enc_pwd))) {
                    //where user_active =5 means that the user "Deleted" Their Account
                    $update_status = DB::connection('mydb_sqlsrv')->update("UPDATE sys_users_tb SET user_active ='5' WHERE user_national_id ='$national_id'");
                    if ($update_status) {
                        return response()->json(
                            [
                                'status' => 200,
                                'operation' => 'success',
                                'message' => "User '$national_id' deactivated successfully."
                            ], 200
                        );
                    } else {
                        return response()->json(
                            [
                                'status' => 500,
                                'success' => false,
                                'message' => 'Internal Server Error'
                            ], 500
                        );
                    }
                } else {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => "Authentication failed. Incorrect password or username. Access denied."
                        ],
                        401
                    );
                }
            } else {
                return response()->json(
                    [
                        'success' => false,
                        'message' => "Authentication failed. User not found."
                    ],
                    401
                );
            }
        }
    }
}
