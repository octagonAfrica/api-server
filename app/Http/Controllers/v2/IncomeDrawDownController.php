<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeDrawDownController extends Controller
{
    public function IDDF(Request $request)
    {
        $dob = $request['dob'];
        $emailaddress = $request['emailaddress'];
        $employer = $request['employer'];
        $idcopy = $request['idcopy'];
        $idno = $request['idno'];
        $kracopy = $request['kracopy'];
        $krapin = $request['krapin'];
        $names = $request['names'];
        $occupation = $request['occupation'];
        $phoneno = $request['phoneno'];
        $address = $request['address'];
        $town = $request['town'];
        $description = $request['description'];
        $items = $request['items'];


        $p_letter = substr($names, 0, 1);
        $sql = "SELECT Number  FROM Clients WHERE Number = ( SELECT MAX(Number) FROM Clients )";
        $qresult = DB::select($sql);
        $data = $qresult[0];
        $NumResult = $data->Number;
        $NumResult = $NumResult + 1;
        $p_clientID = '' . $p_letter . '' . $NumResult . '';

        if ($names) {
            try {
                $insert_client = DB::insert(
                    'INSERT INTO Clients(Name,DebitName,Number,Abbreviation,ClientID,Occupation,Mobile,Email,PINNO,DOB,IDNO,Address,Town)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    [$names, $names, $NumResult, $p_letter, $p_clientID, $occupation, $phoneno, $emailaddress, $krapin, $dob, $idno, $address, $town]
                );


                if ($insert_client) {
                    $sql = "SELECT Code  FROM InsuredItems WHERE id = ( SELECT MAX(id) FROM InsuredItems ) ";

                    $ItCode = DB::select($sql);
                    $data = $ItCode[0];
                    $insuredItems = $data->Code;
                    $int_variable = substr($insuredItems, 5, 4);
                    $int_variable += 1;
                    $ItemsCodei = 'ITEMS' . $int_variable . '';

                    $sql_insert = "INSERT INTO InsuredItems(ClientID,Code,Description,Items)Values(?,?,?,?)";
                    $insert_Insured_items = DB::insert($sql_insert, [$p_clientID, $ItemsCodei, $description, $items]);
                }
                return response()->json([
                    'status' => 200,
                    'operation' =>  'success',
                    'message' =>  'data stored successfully',
                    'data' => $insert_client,
                    'daata' => $insert_Insured_items
                ], 200);
            } catch (\Throwable $th) {
                return response()->json([
                    'status' => 200,
                    'operation' =>  'fail',
                    'message' =>  $th
                ], 200);
            }
        } else {
            return response()->json([
                'status' => 400,
                'operation' =>  'fail',
                'message' =>  'data required'
            ], 400);
        }
    }
}
