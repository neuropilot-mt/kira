<?php

namespace App\Support;

use RuntimeException;

class ImagePreview
{
    public static function jpeg(string $bytes, int $maxEdge = 1600): string
    {
        $source = imagecreatefromstring($bytes);

        if ($source === false) {
            throw new RuntimeException('Unreadable image.');
        }

        $oriented = self::applyOrientation($source, $bytes);
        $flat = self::flatten($oriented);
        $scaled = self::scale($flat, $maxEdge);

        ob_start();
        $written = imagejpeg($scaled, null, 74);
        $jpeg = ob_get_clean();

        if ($scaled !== $flat) {
            imagedestroy($scaled);
        }
        if ($flat !== $oriented) {
            imagedestroy($flat);
        }
        if ($oriented !== $source) {
            imagedestroy($oriented);
        }
        imagedestroy($source);

        if ($written !== true || ! is_string($jpeg) || $jpeg === '') {
            throw new RuntimeException('Could not encode preview.');
        }

        return $jpeg;
    }

    private static function applyOrientation(\GdImage $image, string $bytes): \GdImage
    {
        if (! str_starts_with($bytes, "\xFF\xD8") || ! function_exists('exif_read_data')) {
            return $image;
        }

        $temporary = tempnam(sys_get_temp_dir(), 'photo');

        if ($temporary === false) {
            return $image;
        }

        file_put_contents($temporary, $bytes);
        $exif = @exif_read_data($temporary);
        unlink($temporary);

        $orientation = (int) ($exif['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => false,
        };

        return $rotated instanceof \GdImage ? $rotated : $image;
    }

    private static function flatten(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $canvas = imagecreatetruecolor($width, $height);

        if ($canvas === false) {
            throw new RuntimeException('Could not prepare preview.');
        }

        $ivory = imagecolorallocate($canvas, 247, 244, 241);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $ivory);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

        return $canvas;
    }

    private static function scale(\GdImage $image, int $maxEdge): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $edge = max($width, $height);

        if ($edge <= $maxEdge || $edge === 0) {
            return $image;
        }

        $scale = $maxEdge / $edge;
        $scaled = imagescale($image, (int) round($width * $scale), (int) round($height * $scale), IMG_BICUBIC);

        if ($scaled === false) {
            throw new RuntimeException('Could not resize preview.');
        }

        return $scaled;
    }
}
