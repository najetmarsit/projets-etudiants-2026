<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Compression et redimensionnement des photos de profil (non destructif pour l'API).
 */
class ProfileImageOptimizer
{
    private const MAX_EDGE = 1024;

    public function storeOptimized(UploadedFile $file, int $userId): string
    {
        $quality = (int) config('optimization.upload.image_quality', 80);
        $directory = 'profiles/' . $userId;
        Storage::disk('public')->makeDirectory($directory);

        if (! extension_loaded('gd')) {
            return $file->store($directory, 'public');
        }

        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            return $file->store($directory, 'public');
        }

        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            return $file->store($directory, 'public');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        [$newW, $newH] = $this->scaledDimensions($width, $height, self::MAX_EDGE);

        $canvas = imagecreatetruecolor($newW, $newH);
        if ($newW !== $width || $newH !== $height) {
            imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($image);
            $image = $canvas;
        } else {
            imagedestroy($canvas);
        }

        $filename = Str::uuid()->toString() . '.jpg';
        $relativePath = $directory . '/' . $filename;
        $absolutePath = Storage::disk('public')->path($relativePath);

        imagejpeg($image, $absolutePath, min(max($quality, 60), 95));
        imagedestroy($image);

        return $relativePath;
    }

    /** @return array{0: int, 1: int} */
    private function scaledDimensions(int $width, int $height, int $maxEdge): array
    {
        if ($width <= $maxEdge && $height <= $maxEdge) {
            return [$width, $height];
        }

        $ratio = min($maxEdge / $width, $maxEdge / $height);

        return [
            (int) round($width * $ratio),
            (int) round($height * $ratio),
        ];
    }
}
