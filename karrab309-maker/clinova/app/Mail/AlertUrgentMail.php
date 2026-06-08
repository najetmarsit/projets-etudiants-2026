<?php

namespace App\Mail;

use App\Models\Alert;
use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertUrgentMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Alert $alert,
        public Patient $patient
    ) {
    }

    public function build()
    {
        return $this->subject('[Alerte patient] '.$this->alert->message)
            ->view('emails.alert-urgent');
    }
}
