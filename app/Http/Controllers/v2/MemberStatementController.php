<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MemberStatementController extends Controller
{
    public function MemberStatement(Request $request)
    {
        $period_id = $request['period_id'];
        $user_id = $request['user_id'];
        $m_email = $request['email'];
        $m_number = $request['ClientID'];
        $m_combined = $request['Code'];
        if (!$user_id || !$period_id) {
            return response()->json([
                'status' => 400,
                'operation' => 'fail',
                'message' => 'UserID name required.',
            ], 400);
        } else {
            $UserID_Exists = DB::connection('mydb_sqlsrv')->select("SELECT user_full_names,user_email from  sys_users_tb where  user_id like '%$user_id%'"); // get user Data
            $sql_period_name = "SELECT period_name,period_end_date  FROM  scheme_periods_tb WHERE period_id like '%$period_id%' ORDER BY period_start_date DESC";
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

            $user = $UserID_Exists[0];
            $user_email = $user->user_email;
            $name = $user->user_full_names;

            $memberDetails = DB::connection('mydb_sqlsrv')->select("SELECT m_number, m_scheme_code from  members_tb where  m_email like '%$user_email%'");
            $memberData = $memberDetails[0]; // fetch Member Data
            $scheme_code = $memberData->m_scheme_code;
            $member_number = $memberData->m_number;
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
            $response = Http::asMultipart()->timeout(240)->post('https://cloud.octagonafrica.com/opas/commons/tcpdf/examples/memberStatementNew.php', $MemberStatementData);

            $maxRetries = 3;
            $retryDelay = 5000; // Delay in milliseconds (e.g., 5 seconds)

            for ($attempt = 1; $attempt <= $maxRetries; ++$attempt) {
                try {
                    // Make the HTTP request here
                    $response = Http::asMultipart()->timeout(240)->post('https://cloud.octagonafrica.com/opas/commons/tcpdf/examples/memberStatementNew.php', $MemberStatementData);

                    // Check for success and break out of the loop if successful
                    if ($response->successful()) {
                        $responseMessage = $response->body();
                        if (strpos($responseMessage, 'Mail has been sent successfully.') !== false) {
                            return response()->json([
                                'status' => 200,
                                'operation' => 'success',
                                'message' => "Dear $name Member statement for $period_name sent to $user_email.",
                                'data' => $MemberStatementData,
                            ], 200);
                        } else {
                            // Log the error message
                            error_log("Failed to send data to endpoint. Response message: $responseMessage");

                            return response()->json([
                                'error' => "Failed to send data to endpoint. Response message: $responseMessage",
                            ], 500);
                        }
                    }
                } catch (ConnectionException $e) {
                    // Handle the exception or log it
                    if ($attempt < $maxRetries) {
                        // Sleep for the retry delay before the next attempt
                        usleep($retryDelay * 1000); // Convert to microseconds
                    } else {
                        // Log an error or throw an exception if retries are exhausted
                        error_log('Exhausted all retry attempts. Last error: '.$e->getMessage());

                        return response()->json([
                            'error' => 'Exhausted all retry attempts. Last error: '.$e->getMessage(),
                        ], 500);
                    }
                }
            }

            // If all retries fail, return an appropriate response
            return response()->json([
                'error' => 'Failed to send data to endpoint after multiple retry attempts.',
            ], 500);
        }
    }
}

