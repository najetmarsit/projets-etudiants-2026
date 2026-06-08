<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PatientAdmissionCredentialsMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $plainPassword,
        public string $chamberNumber
    ) {
    }

    public function build()
    {
        return $this->subject('Vos identifiants & numéro de chambre')
            ->view('emails.patient-admission-credentials');
    }
}

