<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Services\ApiCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Display a listing of messages for the authenticated user.
     * Optional: ?with_user_id=X pour afficher uniquement la conversation avec cet utilisateur (sync Dashboard / app mobile).
     */
    public function index(Request $request, ApiCacheService $apiCache)
    {
        $user = Auth::user();
        $perPage = min(max((int) $request->query('per_page', 50), 1), 100);
        $page = max((int) $request->query('page', 1), 1);
        $withUserId = $request->filled('with_user_id') ? (int) $request->with_user_id : 0;
        $ver = $apiCache->messagesVersion((int) $user->id);
        $cacheKey = md5(json_encode(['v' => $ver, 'with' => $withUserId, 'page' => $page, 'per_page' => $perPage]));
        $ttl = (int) config('optimization.cache.messages_ttl', 45);

        $payload = $apiCache->remember(
            "messages:{$user->id}:{$cacheKey}",
            function () use ($request, $user, $perPage, $page, $withUserId) {
                return $this->buildMessagesPayload($user, $withUserId, $perPage, $page);
            },
            $ttl
        );

        return response()->json($payload)
            ->header('Cache-Control', 'private, max-age='.$ttl)
            ->header('X-Cache', 'api');
    }

    private function buildMessagesPayload($user, int $withUserId, int $perPage, int $page): array
    {
        $query = Message::where(function ($q) use ($user) {
            $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id);
        })->with(['sender:id,name,username,profile_photo_path', 'receiver:id,name,username,profile_photo_path']);

        if ($withUserId > 0) {
            $query->where(function ($q) use ($user, $withUserId) {
                $q->where(function ($q2) use ($user, $withUserId) {
                    $q2->where('sender_id', $user->id)->where('receiver_id', $withUserId);
                })->orWhere(function ($q2) use ($user, $withUserId) {
                    $q2->where('sender_id', $withUserId)->where('receiver_id', $user->id);
                });
            });
        }

        $query->orderByDesc('created_at');
        $messages = $query->forPage($page, $perPage)->get()->reverse()->values();

        return [
            'success' => true,
            'data' => $messages,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'count' => $messages->count(),
            ],
        ];
    }

    /**
     * Store a newly created message (texte et/ou pièce jointe). Synchronisé Dashboard Web / app mobile.
     */
    public function store(Request $request, ApiCacheService $apiCache)
    {
        $user = Auth::user();

        $rules = [
            'receiver_id' => 'required|exists:users,id',
            'content' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|max:10240', // 10MB, images ou PDF
        ];
        // Au moins contenu texte ou fichier
        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($v) use ($request) {
            if (empty($request->content) && !$request->hasFile('attachment')) {
                $v->errors()->add('content', 'Un message ou une pièce jointe est requis.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ((int) $request->receiver_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot send a message to yourself'
            ], 422);
        }

        $receiver = User::find($request->receiver_id);
        if (!$this->canSendMessageTo($user, $receiver)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot send messages to this user'
            ], 403);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('messages/' . $user->id, 'public');
        }

        $content = $request->input('content', '');
        if ($content === '' && $attachmentPath) {
            $content = '[Fichier joint]';
        }

        $message = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $request->receiver_id,
            'content' => $content,
            'read_status' => false,
            'attachment_path' => $attachmentPath,
        ]);

        $apiCache->forgetMessages((int) $user->id);
        $apiCache->forgetMessages((int) $request->receiver_id);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $message->load(['sender', 'receiver'])
        ], 201);
    }

    /**
     * Display the specified message.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $message = Message::with(['sender', 'receiver'])->find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }

        // Check permissions
        if ($message->sender_id !== $user->id && $message->receiver_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Mark as read if the user is the receiver
        if ($message->receiver_id === $user->id && !$message->read_status) {
            $message->markAsRead();
        }

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(string $id)
    {
        $user = Auth::user();
        $message = Message::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }

        if ($message->receiver_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $message->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read'
        ]);
    }

    /**
     * Remove the specified message.
     * Users can only delete their own messages.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $message = Message::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }

        if ($message->sender_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }

    /**
     * Check if a user can send a message to another user based on roles.
     */
    private function canSendMessageTo($sender, $receiver)
    {
        // Patients can send to doctors and admins
        if ($sender->isPatient()) {
            return $receiver->isDoctor() || $receiver->isAdmin();
        }

        // Doctors can send to patients, other doctors, and admins
        if ($sender->isDoctor()) {
            return $receiver->isPatient() || $receiver->isDoctor() || $receiver->isAdmin();
        }

        // Admins can send to anyone
        if ($sender->isAdmin()) {
            return true;
        }

        return false;
    }
}
