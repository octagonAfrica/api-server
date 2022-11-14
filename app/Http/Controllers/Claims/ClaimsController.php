<?php

namespace App\Http\Controllers\Claims;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;

class ClaimsController extends Controller
{
    public function getClaims(Request $request)
    {

        // if (is_null($request['claim_status'])) {
        //     $claim_status = 'Just Reported';
        // } else {
        //     $claim_status = filter_var(trim($request['claim_status']), FILTER_SANITIZE_STRING);
        // }
        // if (is_null($request['start'])) {
        //     $start = 0;
        // } else {
        //     $start = filter_var(trim($request['start']), FILTER_SANITIZE_STRING);
        // }

        // if (is_null($request['limit'])) {
        //     $limit = 10;
        // } else {
        //     $limit = filter_var(trim($request['limit']), FILTER_SANITIZE_STRING);
        // }
        // $q = filter_var(trim($request['q']), FILTER_SANITIZE_STRING);
        // if (is_null($request)) {
        //     if ($request['ClientID']){
        //         $ClientID = filter_var(trim($request['ClientID']), FILTER_SANITIZE_STRING);
        //         // 
        //         //validate leads
        //         $sql = "SELECT claimant,A.ID,IDNO,ClientID,Mobile,  specificItems,policyNo,sumInsured,
        //     claimAmount,
        //     Description,
        //     AmountPaid,
        //     C.Name as status
        // FROM claimRegister A
        // LEFT JOIN claimStatus C
        // ON  A.Status = C.code
        // LEFT JOIN Clients B
        // ON A.ClientID= B.ClientID 
        // WHERE 
        //     A.ClientID = '$ClientID'";
        //     }
        //validate leads
        //     $sql2 = "SELECT 
        //     *
        // FROM MedFamMembers
        // WHERE PRPMember = '$ClientID'
        // AND relationship = 'Beneficiary'";
        //     // $beneficiaries = $db_birthmark->dbSelectRows($sql2);
        //     $beneficiaries = DB::connection('sqlsrv')->select($sql2);
        //         return $beneficiaries;
        //     } 

        //     $sql = "SELECT 
        //     claimant,
        //     A.ID,
        //     IDNO,
        //     A.ClientID,
        //     Mobile,
        //     specificItems,
        //     policyNo,
        //     sumInsured,
        //     claimAmount,
        //     Description,
        //     AmountPaid,
        //     C.Name as status
        // FROM claimRegister A
        // LEFT JOIN claimStatus C
        // ON  A.Status = C.code
        // LEFT JOIN Clients B
        // ON A.ClientID= B.ClientID
        // WHERE
        // -- C.Name = '$claim_status' OR
        //    claimant LIKE '%" . $q . "%' 
        //     ORDER BY A.ID desc
        //     OFFSET " . $start . " ROWS
        //     FETCH NEXT " . $limit . " ROWS ONLY";

        //         $Claims = DB::connection('sqlsrv')->select($sql);
        //     return $Claims;
        //     }



        //     } else {




        //     // $Claims = $db_birthmark->dbSelectRows($sql);

        //     if (count($Claims) > 0) {

        //         $payload = [
        //             'success' =>  true,
        //             'message' =>  'Successfully retrieved',
        //             'data' => [
        //                 'Claim' => $Claims,
        //                 // 'Beneficiary' => $beneficiaries
        //             ],
        //             // 'total' => $db_birthmark->getRowCount()
        //         ];
        //     } else {
        //         $payload = array(
        //             'success' =>  false,
        //             'message' =>  'No claim found'
        //         );
        //     }

        // return "Middleware Tes";

    }
    //Get Cliams as per Client
    public function clientClaims(Request $request)
    {
        if (!$request['ClientID']) {

            $payload = [
                'success' =>  false,
                'message' =>  'Insert Client ID'
            ];
        } else {
            $clientID = filter_var(trim($request['ClientID']), FILTER_SANITIZE_STRING);
            try {
                $client_Cliams = DB::select("SELECT * FROM ClaimRegister WHERE ClientID = '$clientID'");
                $payload = [
                    'status' => 200,
                    'success' => true,
                    'message' => 'Claims retrieved successfully',
                    'total' => count($client_Cliams),
                    'data' => $client_Cliams
                ];
            } catch (\Throwable $th) {
                $payload = response()->json([
                    'status' => 401,
                    'success' => false,
                    'message' => $th
                ]);
            }
        }
        return $payload;
    }
}
