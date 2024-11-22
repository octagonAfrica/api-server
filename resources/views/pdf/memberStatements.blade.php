
<!DOCTYPE html>
<html>
<head>
    <title>{{ $pdfData['title'] }}</title>
    <style>
        table {
          border-collapse: collapse;
        }
         td {
          border: 1px solid black;
          padding: 5px;
        }
        </style>
        
</head>
<body>
    <h3><u><strong>{{ $pdfData['title'] }}</strong></u></h3>
    <p style="margin: auto;"><strong>{{ $pdfData['sub_title'] }}</strong></p>

    <table style="margin: auto;">
        <tr>
          <td>Name</td>
          <td>{{ $pdfData['name'] }}</td>
          <td>Date of Bith</td>
          <td>{{ $pdfData['member_dob'] }}</td>
        </tr>
        <tr>
          <td>Member No.</td>
          <td>{{ $pdfData['member_no'] }}</td>
          <td>Date of Joining Scheme</td>
          <td>{{ $pdfData['member_doj'] }}</td>
        </tr>
        <tr>
          <td>Current Age</td>
          <td>{{ $pdfData['age'] }} years</td>
          <td>Department/Branch</td>
          <td>{{ $pdfData['member_department'] }}</td>
        </tr>
    </table>
<br>
    <style>
        table {
           font-family: Arial Narrow,Arial,sans-serif;;
           font-size: 11pt;
       }
       </style>
       <table>
           <tr>
               <td style="width:2%;">
                   &nbsp;
               </td>
               <td style="width:96%;">
                    <table  border="1" cellpadding="2">
                        <tr style="color: #1c449c;">
                            <td rowspan="2" style="font-weight:bold;">Summary Details</td>
                            
                            <td colspan="2" style="font-weight:bold; text-align:center;">Employee</td>
                            
                            <td colspan="2" style="font-weight:bold; text-align:center;">AVC</td>
                            
                            <td colspan="2" style="font-weight:bold; text-align:center;">Employer</td>
                             
                            <td rowspan="2" style="font-weight:bold; text-align:right;">Totals</td>
                        </tr>
                        <tr>
                            
                            <td style="font-weight:bold;">Tax Exempt</td>
                            <td style="font-weight:bold;">Non Tax Exempt</td>
                             <td style="font-weight:bold;">Tax Exempt</td>
                            <td style="font-weight:bold;">Non Tax Exempt</td>
                            <td style="font-weight:bold;">Tax Exempt</td>
                            <td style="font-weight:bold;">Non Tax Exempt</td>
                            
                        </tr>
                        <tr>
                            <td>Opening Balance</td>
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td>
                            
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td>
                           
                            <td align="right">XXXX.XX</td>
                            <td align="right">XXXX.XX</td> 
                           
                            <td align="right">XXXX.XX$</td> 
                        </tr>
                       
                        <tr>
                            <td>Contributions</td>
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td> 
                            
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td> 
                           
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td> 
                           
                            <td align="right">XXXX.XX</td> 
                        </tr>
                       
                        <tr>
                            <td>Arrears</td>
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td> 
                            
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td>
                           
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td> 
                           
                            <td align="right">XXXX.XX</td>
                        </tr>
                       
                        <tr>
                            <td>Transfers in</td>
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td> 
                            
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td> 
                           
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td> 
                           
                            <td align="right">XXXX.XX</td> 
                        </tr>
                       
                        <tr>
                            <td>Interest</td>
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td> 
                            
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td> 
                           
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td> 
                           
                            <td align="right">XXXX.XX</td> 
                        </tr>
                       
                        <tr>
                            <td>Withdrawals</td>
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td> 
                            
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td> 
                           
                            <td align="right">XXXX.XX</td> 
                            <td align="right">XXXX.XX</td> 
                           
                            <td align="right">XXXX.XX</td> 
                        </tr>
                        <tr>
                            <td style="font-weight:bold;">Total</td>
                            <td align="right" style="font-weight:bold;">XXXX.XX</td> 
                            <td align="right" style="font-weight:bold;">XXXX.XX</td> 
                            
                            <td align="right" style="font-weight:bold;">XXXX.XX</td> 
                            <td align="right" style="font-weight:bold;">XXXX.XX</td> 
                           
                            <td align="right" style="font-weight:bold;">XXXX.XX</td> 
                            <td align="right" style="font-weight:bold;">XXXX.XX</td> 
                           
                            <td align="right" style="font-weight:bold;">XXXX.XX</td> 
                        </tr>
                       
                    </table>
                   </td>
               <td style="width:2%;">
                   &nbsp;
               </td>
           </tr>
       </table>

      <br>
<style>
    table {
       font-family: Arial Narrow,Arial,sans-serif;;
       font-size: $font_size;
   }
   </style>
    <table border="1">
             <tr>
                 <td style="font-weight:bold;">Month</td>
                <td style="font-weight:bold;">--</td>
                <td style="font-weight:bold;">--</td>
                <td style="font-weight:bold;">--</td>
                <td style="font-weight:bold;">--</td>
                <td style="font-weight:bold;">--</td>
                <td style="font-weight:bold;">--</td>
                <td style="font-weight:bold;">--</td>
                <td style="font-weight:bold;">--</td>
                <td style="font-weight:bold;">--</td>
                <td style="font-weight:bold;">--</td>
                <td style="font-weight:bold;">--</td>
                <td style="font-weight:bold;">--</td>
                <td style="font-weight:bold;">Totals</td>
             </tr>
             <tr>
                 <td>Employee</td>
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
             </tr>
             <tr>
                 <td>AVC</td>
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
             </tr>
             <tr>
                 <td>Employer</td>
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
                <td align="right">xxxx.xx</td> 
               
            </tr>
             <tr>
                 <td style="font-weight:bold;">Total</td>
                <td align="right" style="font-weight:bold;">xxxx.xx</td> 
                <td align="right" style="font-weight:bold;">xxxx.xx</td> 
                <td align="right" style="font-weight:bold;">xxxx.xx</td> 
                <td align="right" style="font-weight:bold;">xxxx.xx</td> 
                <td align="right" style="font-weight:bold;">xxxx.xx</td> 
                <td align="right" style="font-weight:bold;">xxxx.xx</td> 
                <td align="right" style="font-weight:bold;">xxxx.xx</td> 
                <td align="right" style="font-weight:bold;">xxxx.xx</td> 
                <td align="right" style="font-weight:bold;">xxxx.xx</td> 
                <td align="right" style="font-weight:bold;">xxxx.xx</td> 
                <td align="right" style="font-weight:bold;">xxxx.xx</td> 
                <td align="right" style="font-weight:bold;">xxxx.xx</td> 
                <td align="right" style="font-weight:bold;">xxxx.xx</td> 
            </tr>
       </table>
    <p>{{ $pdfData['footer1'] }}</p>

    <p><strong>Note:<u></u></strong><em>{{ $pdfData['footer2'] }}</em></p>
    <p>Thank You For Choosing Us</p>
    <hr>
</body>
</html>
