<?php

namespace App\Services\Chat;

use App\Models\User;

interface ChatResponder
{
    /**
     * @return array{reply:string, meta?:array<string,mixed>}
     */
    public function respond(User $user, string $message): array;
}

