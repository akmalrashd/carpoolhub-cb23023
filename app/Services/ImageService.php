<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Turns an uploaded image into a compressed base64 data URI for storage in the
 * database. Compression is what makes DB-side image storage viable: a 5 MB phone
 * photo becomes ~40-150 KB. Uses GD (ext-gd), which needs no proc_open and is
 * present on the Hostinger PHP build.
 */
class ImageService
{
    /**
     * Ceiling for the "GD could not decode it, keep the original bytes" path.
     * Comfortably above any real photo that survives the max: rules on the
     * upload (2-5 MB), because by this point the bytes are not a normal image.
     */
    private const MAX_UNDECODABLE_BYTES = 2 * 1024 * 1024;

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
            // GD could not decode the bytes, so they are stored as-is to avoid
            // losing the upload. That path skips the re-encode that normally
            // bounds the size, so cap it here: without this, a file that passes
            // the `image` validation rule but defeats GD is written verbatim
            // into a LONGTEXT column and then dragged into memory by every query
            // that touches the row. base64 adds ~33%, so the stored string stays
            // under roughly 1.4x this limit.
            if (strlen($raw) > self::MAX_UNDECODABLE_BYTES) {
                throw ValidationException::withMessages([
                    $file->getClientOriginalName() => 'That image could not be processed. Please upload a standard JPEG or PNG.',
                ]);
            }

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
