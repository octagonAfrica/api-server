<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TwoWaySMSController extends Controller
{
    public function fetchUser(Request $request)
    {
      $rules = [
          'phoneNumber' => 'required'
      ];
      // Validate request
      $validator = Validator::make($request->all(), $rules);
      $data = $request->all();
      $phoneNumber = $data['phoneNumber'];
      if ($validator->fails()) {
          return response()->json(
              [
                  'status' => 400,
                  'success' => false,
                  'message' => "Invalid request. Please input your phone Number."
              ], 400
          );
      } else {
        $user_exist = DB::connection('mydb_sqlsrv')->select("SELECT TOP 1 * FROM sys_users_tb WHERE user_mobile = '$phoneNumber' or user_phone = '$phoneNumber'");
        if (count($user_exist) > 0) {
          $user = $user_exist[0];
          $username = $user->user_full_names;
          $response = [
            "name" => $username
          ];
          return response()->json(
            [
              'status' => 200,
              'operation' => "success",
              'message' => "User found",
              'data' =>$response
            ], 200
          );
        } else {
          return response()->json(
            [
              'success' => false,
              'message' => "Phone number Does Not Exist"
            ],
            401
          );
      }
    }
  }
}
