<?php

namespace App\Http\Controllers;

use App\Services\Chat\ChatResponderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Chat d’assistance (réponses génériques + contexte patient si connecté).
 */
class ChatController extends Controller
{
    public function __construct(private ChatResponderFactory $factory)
    {
    }

    public function message(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $user = Auth::user();
        $responder = $this->factory->make();
        $result = $responder->respond($user, (string) $request->message);
        $reply = (string) ($result['reply'] ?? '');

        return response()->json([
            'success' => true,
            'reply' => $reply,
        ]);
    }
}
