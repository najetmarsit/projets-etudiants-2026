<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ProfileImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
class ProfilePhotoController extends Controller
{
    public function __construct(private ProfileImageOptimizer $optimizer)
    {
    }
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_SIZE = 5120;
    private const MAX_DIMENSION = 2048;

    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,png,webp',
                'max:' . self::MAX_SIZE,
            ],
        ], [
            'photo.required' => 'Veuillez sélectionner une photo.',
            'photo.image' => 'Le fichier doit être une image.',
            'photo.mimes' => 'Formats acceptés : JPG, PNG, WEBP.',
            'photo.max' => 'La photo ne doit pas dépasser 5 Mo.',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user = Auth::user();
        $file = $request->file('photo');

        if (!$this->isValidImage($file)) {
            return $this->error('Image invalide ou corrompue.', 422);
        }

        $this->deleteOldPhoto($user);

        $path = $this->optimizer->storeOptimized($file, (int) $user->id);
        $user->update(['profile_photo_path' => $path]);

        return $this->success(
            data: $user->fresh(),
            message: 'Photo de profil mise à jour.',
            extra: ['profile_photo_url' => Storage::disk('public')->url($path)]
        );
    }

    /**
     * Photo recadrée côté client (même pipeline que upload, compression JPEG).
     */
    public function uploadCropped(Request $request)
    {
        return $this->upload($request);
    }

    public function delete()
    {
        $user = Auth::user();

        if (!$user->profile_photo_path) {
            return $this->error('Aucune photo à supprimer.', 404);
        }

        $this->deleteOldPhoto($user);
        $user->update(['profile_photo_path' => null]);

        return $this->success(message: 'Photo de profil supprimée.');
    }

    public function show(int $id)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isDoctor() && $user->id !== $id) {
            return $this->unauthorized();
        }

        $target = User::find($id);
        if (!$target || !$target->profile_photo_path) {
            return $this->notFound('Photo non trouvée.');
        }

        if (!Storage::disk('public')->exists($target->profile_photo_path)) {
            return $this->notFound('Fichier photo introuvable.');
        }

        return response()->file(
            Storage::disk('public')->path($target->profile_photo_path)
        );
    }

    private function isValidImage($file): bool
    {
        try {
            $mime = $file->getMimeType();
            if (!in_array($mime, self::ALLOWED_MIMES)) {
                return false;
            }

            [$width, $height] = getimagesize($file->getRealPath());
            if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function deleteOldPhoto(User $user): void
    {
        if ($user->profile_photo_path) {
            $path = $user->profile_photo_path;
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            $dir = dirname($path);
            if (Storage::disk('public')->files($dir) === []) {
                Storage::disk('public')->deleteDirectory($dir);
            }
        }
    }
}
