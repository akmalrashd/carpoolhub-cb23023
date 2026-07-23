<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Turns an uploaded image into a compressed base64 data URI for storage in the
 * database. Compression is what makes DB-side image storage viable: a 5 MB phone
 * photo becomes ~40-150 KB. Uses GD (ext-gd), which needs no proc_open and is
 * present on the Hostinger PHP build.
 */
class ImageService
{
    /**
     * Resize (down only) to fit within $maxDimension on the longest side and
     * re-encode. Photos become JPEG at $quality; set $preferPng for high-contrast
     * images like QR codes, which stay crisp and scannable as PNG. If the bytes
     * cannot be decoded as an image, the original is base64-encoded unchanged so
     * nothing is ever lost.
     */
    public function toCompressedBase64(
        UploadedFile $file,
        int $maxDimension = 800,
        int $quality = 78,
        bool $preferPng = false,
    ): string {
        $raw = file_get_contents($file->getRealPath());
        $image = @imagecreatefromstring($raw);

        if ($image === false) {
            return 'data:' . $file->getMimeType() . ';base64,' . base64_encode($raw);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1.0, $maxDimension / max($width, $height));

        if ($scale < 1.0) {
            $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));
            if ($resized !== false) {
                imagedestroy($image);
                $image = $resized;
            }
        }

        ob_start();

        if ($preferPng) {
            imagesavealpha($image, true);
            imagepng($image, null, 6);
            $mime = 'image/png';
        } else {
            // Flatten any transparency onto white so a PNG with alpha does not
            // turn black when re-encoded as JPEG.
            $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
            $white = imagecolorallocate($flat, 255, 255, 255);
            imagefilledrectangle($flat, 0, 0, imagesx($image), imagesy($image), $white);
            imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            imagejpeg($flat, null, $quality);
            imagedestroy($flat);
            $mime = 'image/jpeg';
        }

        $encoded = ob_get_clean();
        imagedestroy($image);

        return 'data:' . $mime . ';base64,' . base64_encode($encoded);
    }
}
