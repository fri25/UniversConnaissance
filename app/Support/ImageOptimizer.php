<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Redimensionne et compresse les images téléversées (GD) : WebP si le serveur
 * le permet, sinon JPEG. Les images sont réduites (jamais agrandies) pour tenir
 * dans le cadre demandé, sans recadrage.
 */
class ImageOptimizer
{
    /** Couvertures : affichées en ~250 px de large, ×2 pour les écrans haute définition. */
    public const COVER = [600, 900];

    /** Images des descriptions. */
    public const CONTENT = [1600, 1600];

    /** Photos d'auteurs. */
    public const AVATAR = [400, 400];

    private const QUALITY = 80;

    public static function supportsWebp(): bool
    {
        return function_exists('imagewebp') && (gd_info()['WebP Support'] ?? false);
    }

    /**
     * Optimise un fichier téléversé et l'enregistre sur le disque public.
     *
     * @param  array{0: int, 1: int}  $box
     * @return string chemin relatif sur le disque
     */
    public static function storeUpload(UploadedFile $file, string $directory, array $box, string $disk = 'public'): string
    {
        $optimized = self::encode((string) file_get_contents($file->getRealPath()), $box);

        if ($optimized === null) {
            // Format non pris en charge (GIF animé, SVG…) : fichier d'origine.
            return $file->store($directory, $disk);
        }

        [$binary, $extension] = $optimized;
        $path = trim($directory, '/').'/'.Str::random(40).'.'.$extension;
        Storage::disk($disk)->put($path, $binary);

        return $path;
    }

    /**
     * Optimise une image binaire (ex. image collée en base64).
     *
     * @param  array{0: int, 1: int}  $box
     * @return array{0: string, 1: string}|null [contenu, extension] ou null si non optimisable
     */
    public static function encode(string $binary, array $box, ?int $originalSize = null): ?array
    {
        $info = @getimagesizefromstring($binary);
        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
            return null;
        }

        // GIF animé : GD ne garderait que la première image.
        if ($info[2] === IMAGETYPE_GIF && substr_count($binary, "\x21\xF9\x04") > 1) {
            return null;
        }

        $image = @imagecreatefromstring($binary);
        if (! $image instanceof GdImage) {
            return null;
        }

        if ($info[2] === IMAGETYPE_JPEG) {
            $image = self::applyExifOrientation($image, $binary);
        }

        $image = self::fit($image, $box[0], $box[1]);
        $hasAlpha = in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true);

        ob_start();
        if (self::supportsWebp()) {
            imagesavealpha($image, true);
            imagewebp($image, null, self::QUALITY);
            $extension = 'webp';
        } else {
            if ($hasAlpha) {
                $image = self::flattenOnWhite($image);
            }
            imageinterlace($image, true);
            imagejpeg($image, null, self::QUALITY);
            $extension = 'jpg';
        }
        $out = (string) ob_get_clean();
        imagedestroy($image);

        // Garde-fou : ne jamais produire plus lourd qu'avant sans réduction de taille.
        $before = $originalSize ?? strlen($binary);
        if ($out === '' || (strlen($out) >= $before && $info[0] <= $box[0] && $info[1] <= $box[1])) {
            return null;
        }

        return [$out, $extension];
    }

    private static function fit(GdImage $image, int $maxWidth, int $maxHeight): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);

        if ($ratio >= 1) {
            return $image;
        }

        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    private static function flattenOnWhite(GdImage $image): GdImage
    {
        $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);

        return $flat;
    }

    /**
     * Photos de téléphone : rotation selon l'orientation EXIF.
     */
    private static function applyExifOrientation(GdImage $image, string $binary): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($binary));
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated instanceof GdImage ? $rotated : $image;
    }
}
