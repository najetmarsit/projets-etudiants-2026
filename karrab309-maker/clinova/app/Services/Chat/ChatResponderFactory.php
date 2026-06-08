<?php

namespace App\Services\Chat;

class ChatResponderFactory
{
    public function make(): ChatResponder
    {
        // Point d’extension pour un provider LLM: activer via env('CHAT_LLM_PROVIDER') + clé API.
        // Par défaut: règles + garde-fous.
        return new RuleBasedChatResponder();
    }
}

