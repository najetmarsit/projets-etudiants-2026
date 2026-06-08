<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewUserCredentialsMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $plainPassword
    ) {
    }

    public function build()
    {
        return $this->subject('Vos identifiants – plateforme de suivi')
            ->view('emails.new-user-credentials');
    }
}
