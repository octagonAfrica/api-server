<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MemberStatement extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($mailData)
    {
        $this->mailData = $mailData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $pdf = $this->mailData['pdf'];
        $name = $this->mailData['name'];
        $period = $this->mailData['period'];
        $pdfData = $this->mailData['pdfData'];

       return $this->view('emails.memberStatements', compact('pdfData'))
        ->attachData($pdf->output(), "$name-$period.pdf");
    }
}
