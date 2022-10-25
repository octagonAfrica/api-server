<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class RegisterContoller extends Controller
{
    // protected $table = 'Clients';
    public function registerUser(Request $request)

    {
        $rules = [
            'firstname' => 'required',
            'lastname' => 'required',
            'ID' => 'required',
            'email' => 'required',
            'password' => 'required|min:6',
        ];

        // Validate request
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => "User registration failed. Please input Required Fields"
                ],401
            );
        } else {

            $data = $request->all();
            $firstname = $data['firstname'];
            $lastname = $data['lastname'];
            $ID = $data['ID'];
            $email = $data['email'];
            $phone_number = $data['phonenumber'];
            $password = $data['password'];
            $fullnames = $firstname . ' ' . $lastname;
            $firstname = strtolower($firstname);
            $lastname = strtolower($lastname);
            $username = $firstname . '.' . $lastname;

            // Encrypt Password
            $encryptedPassword  = password_hash($password, PASSWORD_DEFAULT);

            // Check if user Exist
            $user_exist = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_username ='$username'");

            if ($user_exist) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'failed to register user because a user with the same username already exists. Send a PATCH request to edit client details'
                    ], 401
                );
            } else {
                // Add user    
                $add_user = DB::connection('mydb_sqlsrv')->insert('INSERT INTO sys_users_tb(user_username,user_delagate_owner,user_enc_pwd,user_country,user_company,user_active,user_full_names,user_email,user_mobile,user_phone,user_national_id,user_role_id) values (?,?,?,?,?,?,?,?,?,?,?,?)', [$username, "", $encryptedPassword, 'Kenya', '', 1, $fullnames, $email, $phone_number, $phone_number, $ID, 100]);
                if ($add_user) {
                    $mailData = [
                        'fullnames' => $fullnames,
                        'username' => $username,
                        'password' => $password
                    ];
                    // Send Email to new user, username and Password
                    Mail::to($email)->send(new RegistrationMail($mailData));
                    return response()->json(
                        [
                            'operation' =>  'success',
                            'message' =>  "User registered successfully, Login details sent to '$email'"
                        ], 200
                    );
                } else
                    return response()->json(
                        [
                            'success' => false,
                            'message' => 'Internal Server Error '
                        ],500
                    );
            }
        }
    }
}