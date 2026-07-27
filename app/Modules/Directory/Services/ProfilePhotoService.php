<?php

namespace App\Modules\Directory\Services;

use App\Models\User;
use App\Shared\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ProfilePhotoService
{
    public function store(User $user, UploadedFile $file): void
    {
        $dir = 'profiles/'.$user->id;
        $base = Str::uuid()->toString();
        $fullPath = $dir.'/'.$base.'.jpg';
        $thumbPath = $dir.'/'.$base.'_thumb.jpg';

        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if ($image === false) {
            throw new \RuntimeException('Could not process the uploaded image.');
        }

        $this->writeResized($image, $fullPath, 400);
        $this->writeResized($image, $thumbPath, 96);
        imagedestroy($image);

        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        foreach (array_filter([$profile->photo_path, $profile->photo_thumb_path]) as $old) {
            Storage::disk('public')->delete($old);
        }

        $profile->update([
            'photo_path' => $fullPath,
            'photo_thumb_path' => $thumbPath,
        ]);

        app(AuditLogger::class)->log('profile.photo_updated', $user);
    }

    /**
     * @param  \GdImage  $source
     */
    private function writeResized($source, string $path, int $max): void
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min($max / max($width, 1), $max / max($height, 1), 1);
        $newW = (int) max(1, round($width * $scale));
        $newH = (int) max(1, round($height * $scale));

        $canvas = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 85);
        $binary = (string) ob_get_clean();
        imagedestroy($canvas);

        Storage::disk('public')->put($path, $binary);
    }
}
