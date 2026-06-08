<?php

namespace App\Services\Chat;

use App\Models\Patient;
use App\Models\User;

class RuleBasedChatResponder implements ChatResponder
{
    public function respond(User $user, string $message): array
    {
        $text = mb_strtolower(trim($message));

        $context = null;
        if ($user->isPatient()) {
            $p = Patient::where('user_id', $user->id)->first();
            if ($p) {
                $context = 'Contexte dossier: ';
                if ($p->diagnosis) {
                    $context .= 'diagnostic indiqué «'.$p->diagnosis.'». ';
                }
                // Ne jamais donner de diagnostic ou de prescription: contexte purement descriptif.
            }
        }

        $reply = $this->buildReply($text, $context);
        return ['reply' => $reply, 'meta' => ['mode' => 'rule_based']];
    }

    private function buildReply(string $text, ?string $context): string
    {
        $out = '';

        if (str_contains($text, 'urgence') || str_contains($text, 'urgent') || str_contains($text, 'détresse') || str_contains($text, 'detresse')) {
            $out .= 'Si vous pensez être en danger immédiat (difficulté à respirer, douleur thoracique, malaise), appelez le 15/112 immédiatement. ';
        }

        if (str_contains($text, 'douleur')) {
            $out .= 'Une douleur persistante ou intense doit être évaluée; notez son intensité et sa durée dans l’application pour aider l’équipe à vous orienter. ';
        }
        if (str_contains($text, 'fièvre') || str_contains($text, 'température') || str_contains($text, 'fievre')) {
            $out .= 'Surveillez la température régulièrement. Si elle monte, persiste, ou s’accompagne de frissons/altération, contactez votre équipe soignante. ';
        }
        if (str_contains($text, 'pansement') || str_contains($text, 'blessure') || str_contains($text, 'plaie')) {
            $out .= 'Surveillez la plaie/pansement (rougeur, chaleur, écoulement, odeur) et envoyez une photo via l’application si possible. ';
        }
        if (str_contains($text, 'médicament') || str_contains($text, 'medicament') || str_contains($text, 'traitement')) {
            $out .= 'Ne modifiez jamais un traitement sans avis médical. En cas d’effet indésirable important, contactez votre médecin. ';
        }

        if ($out === '') {
            $out = ($context ?? '')
                .'Je peux donner des informations générales et t’aider à utiliser l’application. Pour un avis médical personnalisé, utilise la messagerie sécurisée avec ton médecin.';
        } else {
            $out .= 'Pour un avis personnalisé, utilise la messagerie sécurisée avec ton médecin.';
        }

        return trim($out);
    }
}

