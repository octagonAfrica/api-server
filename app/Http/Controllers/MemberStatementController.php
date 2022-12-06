<?php

namespace App\Http\Controllers;

use App\Mail\MemberStatement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PDF;

class MemberStatementController extends Controller
{
    public function MemberStatement(Request $request){
       
        $period_id = $request['period_id'];
        $user_id = $request['user_id'];
        $m_email = $request['email'];
        $m_number = $request['ClientID'];
        $m_combined = $request['Code'];
        if(!$user_id || !$period_id)
        {
            return response()->json([
                'statu' => 400,
                'operation' => 'fail',
                'message' => "user_id, period_id, email, ClientID and Code."
            ], 400);  
        } else {
            $sql_period_name = "SELECT period_name  FROM  scheme_periods_tb WHERE period_id = '$period_id'";
            $period_name_data = DB::connection('mydb_sqlsrv')
                ->select($sql_period_name);
            $data = $period_name_data[0];
            $period_name = $data->period_name;
            if (!isset($m_email) || !$m_email) {
                $sql_email = "SELECT TOP 1 * FROM sys_users_tb WHERE user_id ='$user_id'";
                $user_exist = DB::connection('mydb_sqlsrv')
                    ->select($sql_email);
                $user = $user_exist[0];
                $m_email = $user->user_email;
                $name = $user->user_username;
            }
            ;
            if ($m_email) {
            //     $sql_sum_EE_Tax = "SELECT SUM(cont_amount) as Total_EE_Tax FROM contributions_tb
            // where cont_combined = 'KE003:3' AND cont_category = 'EE'
            // AND cont_taxation = 'Tax Exempt' AND cont_period ='26'";

            //     $sql_sum_EE_nonTax = "SELECT SUM(cont_amount) as Total_EE_nonTax FROM contributions_tb
            // where cont_combined = 'KE003:3' AND cont_category = 'EE'
            // AND cont_taxation = 'Non Tax Exempt' AND cont_period ='26'";

            //     $sql_sum_ER_Tax = "SELECT SUM(cont_amount) as Total_ER_Tax FROM contributions_tb
            // where cont_combined = 'KE003:3' AND cont_category = 'ER'
            // AND cont_taxation = 'Tax Exempt' AND cont_period ='26'";

            //     $sql_sum_ER_nonTax = "SELECT SUM(cont_amount) as Total_ER_nonTax FROM contributions_tb
            // where cont_combined = 'KE003:3' AND cont_category = 'ER'
            // AND cont_taxation = 'Non Tax Exempt' AND cont_period ='26'";

            //     $sql_sum_AVC_Tax = "SELECT SUM(cont_amount) as Total_AVC_Tax FROM contributions_tb
            // where cont_combined = 'KE003:3' AND cont_category = 'AVC'
            // AND cont_taxation = 'Tax Exempt' AND cont_period ='26'";

            //     $sql_sum_AVC_nonTax = "SELECT SUM(cont_amount) as Total_AVC_nonTax FROM contributions_tb
            // where cont_combined = 'KE003:3' AND cont_category = 'AVC'
            // AND cont_taxation = 'Non Tax Exempt' AND cont_period ='26'";

                $pdfData = [
                    'title' => "MEMBER BENEFIT STATEMENT FOR THE PERIOD $period_name",
                    'sub_title' => 'Remmited Contributions',
                    // 'sum_EE_nonTax' => $sql_sum_EE_nonTax,
                    // 'sum_EE_Tax' => $sql_sum_EE_Tax,
                    // 'sum_ER_nonTax' => $sql_sum_ER_nonTax,
                    // 'sum_ER_Tax' => $sql_sum_ER_Tax,
                    // 'sum_AVC_nonTax' => $sql_sum_AVC_nonTax,
                    // 'sum_AVC_Tax' => $sql_sum_AVC_Tax
                ];
                $pdf = PDF::loadView('pdf/memberStatements', $pdfData);

                $mailData = [
                    'pdfData' => $pdfData,
                    'pdf' => $pdf,
                    'period' => $period_name,
                    'name' => $name
                ];

                $send_mail = Mail::to($m_email)
                    ->send(new MemberStatement($mailData));
                if ($send_mail) {
                    return response()->json([
                        'statu' => 200,
                        'operation' => 'success',
                        'message' => "Member statemet for $period_name sent to $m_email."
                    ], 200);
                }
            } else {
                return response()->json([
                    'statu' => 400,
                    'operation' => 'false',
                    'message' => "Email not Sent. Kindly contact the HR to set your mail."
                ], 400); 
            }
        }
}
}
