<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class StatementsController extends Controller
{
    public function MemberStatements(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period_id' => 'required|integer',
            'memberID' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'operation' => 'failure',
                'message' => 'Missing data/invalid data. Please try again.',
                'errors' => $validator->errors()->all(),
            ], 400);
        } else {
            date_default_timezone_set('Africa/Nairobi');
            $period_id = $request->input('period_id');
            $memberID = $request->input('memberID');
            $LogDetails = DB::connection('mydb_sqlsrv')->select("select su.user_username, su.user_full_names, m.m_scheme_code from sys_users_tb su , members_tb m where  m.m_number = su.user_member_no and  m.m_scheme_code=su.user_schemes and m.m_id = '$memberID'");
            if (count($LogDetails) > 0) {
                $logData = $LogDetails[0];
                $audit_username = $logData->user_username;
                $audit_fullnames = $logData->user_full_names;
                $audit_date_time = date('Y-m-d H:i:s');
                $audit_scheme_code = $logData->m_scheme_code;

                $sql_period_name = "SELECT period_name, period_end_date FROM scheme_periods_tb WHERE period_id LIKE '%$period_id%' ORDER BY period_start_date DESC";
                $period_name_data = DB::connection('mydb_sqlsrv')->select($sql_period_name);
                $data = $period_name_data[0];
                $period_name = $data->period_name;
                $period_end_date = $data->period_end_date;
                $schemePeriodsData_1 = DB::connection('mydb_sqlsrv')->select("SELECT sub_period_end_date FROM scheme_sub_periods_tb WHERE scheme_period_id LIKE '%$period_id%' ");
                $schemePrdData1 = $schemePeriodsData_1[0];
                $sub_period_end_date = $schemePrdData1->sub_period_end_date;
                $dates = Carbon::createFromFormat('Y-m-d', $sub_period_end_date);
                $last_day_of_year = $dates->setDate($dates->year, 12, 31)->endOfYear();
                $as_at_date = $last_day_of_year->toDateString();

                $schemePeriodsDataFinal = DB::connection('mydb_sqlsrv')->select("SELECT sub_period_id FROM scheme_sub_periods_tb WHERE scheme_period_id LIKE '%$period_id' AND sub_period_end_date LIKE '%$as_at_date%' ORDER BY sub_period_number ASC");
                $schemePrdDataFinal = $schemePeriodsDataFinal[0];
                $as_at_sub_period_id = $schemePrdDataFinal->sub_period_id;

                $memberDetails = DB::connection('mydb_sqlsrv')->select("SELECT * from  members_tb where  m_id = '$memberID'");
                $memberData = $memberDetails[0]; // fetch Member Data
                $scheme_code = $memberData->m_scheme_code;
                $member_number = $memberData->m_number;
                $user_email = $memberData->m_email;
                $name = $memberData->m_name;
                $display = 2;
                $MemberStatementData = [
                    'member_number' => $member_number,
                    'scheme_code' => $scheme_code,
                    'period_id' => $period_id,
                    'display' => $display,
                    'as_at_sub_period_id' => $as_at_sub_period_id,
                    'as_at_date' => $as_at_date,
                    'name' => $name,
                    'email' => $user_email,
                    'period_name' => $period_name,
                ];

                $maxRetries = 3;
                $retryDelay = 5000;
                function makeHttpRequest($url, $data, $maxRetries, $retryDelay)
                {
                    $attempts = 0;

                    while ($attempts < $maxRetries) {
                        try {
                            // Make the HTTP request here
                            $response = Http::asMultipart()->timeout(240)->post($url, $data);

                            if ($response->successful()) {
                                $responseMessage = $response->body();
                                if (strpos($responseMessage, 'Mail has been sent successfully.') !== false) {
                                    return [
                                        'status' => 200,
                                        'message' => 'Success',
                                    ];
                                } else {
                                    error_log("Failed to send data to endpoint. Response message: $responseMessage");

                                    return [
                                        'error' => "Failed to send data to endpoint. Response message: $responseMessage",
                                    ];
                                }
                            }
                        } catch (ConnectionException $e) {
                            if ($attempts < $maxRetries) {
                                usleep($retryDelay * 1000); // Convert to microseconds
                            } else {
                                error_log('Exhausted all retry attempts. Last error: '.$e->getMessage());

                                return [
                                    'error' => 'Exhausted all retry attempts. Last error: '.$e->getMessage(),
                                ];
                            }
                        }
                        // Increment the attempts counter
                        ++$attempts;
                    }

                    return [
                        'error' => 'Failed to send data to endpoint after multiple retry attempts.',
                    ];
                }
                // Call the function to make the HTTP request
                $result = makeHttpRequest('https://cloud.octagonafrica.com/opas/commons/tcpdf/examples/memberStatementNew.php', $MemberStatementData, $maxRetries, $retryDelay);
                if (isset($result['status'])) {
                    $audit_activity = 'Generate Member Statement';
                    $audit_description = 'Generate Member Statement: Success';
                    $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                    return response()->json([
                        'status' => 200,
                        'operation' => 'success',
                        'message' => "Dear $name Member statement for $period_name sent to $user_email.",
                        'data' => $MemberStatementData,
                    ], 200);
                } elseif (isset($result['error'])) {
                    $audit_activity = 'Generate Member Statement';
                    $audit_description = 'Generate Member Statement failed: '.$result['error'];
                    $addLog = DB::connection('mydb_sqlsrv')->insert('INSERT INTO audit_trail_general_tb (audit_date_time, audit_scheme_code,audit_username, audit_fullnames, audit_activity, audit_description)
                VALUES (?, ?, ?, ?, ?, ?)', [$audit_date_time, $audit_scheme_code, $audit_username, $audit_fullnames, $audit_activity, $audit_description]);

                    return response()->json([
                        'error' => $result['error'],
                    ], 500);
                }
            } else {
                return response()->json([
                    'status' => 409, // details not found
                    'operation' => 'fail',
                    'message' => 'Member does not exist.',
                ], 409);
            }
        }
    }
}
