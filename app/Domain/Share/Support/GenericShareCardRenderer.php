<?php

namespace Domain\Share\Support;

use GdImage;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GenericShareCardRenderer
{
    /**
     * @param  array{title:string, subtitle?:string|null, author?:string|null, eyebrow?:string|null, image_path?:string|null, background:array{int,int,int}}  $data
     */
    public function render(
        array $data,
        ShareCardFormat $format,
        ShareCardTemplate $template = ShareCardTemplate::Poster,
    ): string {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagettftext')) {
            throw new RuntimeException('GD with FreeType is required to render share cards.');
        }

        $canvas = imagecreatetruecolor($format->width(), $format->height());
        if (! $canvas instanceof GdImage) {
            throw new RuntimeException('Unable to allocate the share-card canvas.');
        }

        if (function_exists('imageantialias')) {
            imageantialias($canvas, true);
        }

        [$red, $green, $blue] = $data['background'];
        $background = imagecolorallocate($canvas, $red, $green, $blue);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $format->width(), $format->height(), $background);

        $cover = $this->loadImage($data['image_path'] ?? null);

        try {
            $this->paint(
                $canvas,
                $cover,
                trim($data['title']) ?: 'Slam In',
                trim((string) ($data['subtitle'] ?? '')),
                trim((string) ($data['author'] ?? '')),
                trim((string) ($data['eyebrow'] ?? '')),
                $format,
                $template,
                $white,
            );

            ob_start();
            imagepng($canvas, null, 8);
            $png = (string) ob_get_clean();

            if ($png === '' || ! str_starts_with($png, "\x89PNG")) {
                throw new RuntimeException('Failed to encode the share card.');
            }

            return $png;
        } finally {
            if ($cover instanceof GdImage) {
                imagedestroy($cover);
            }
            imagedestroy($canvas);
        }
    }

    private function paint(
        GdImage $canvas,
        ?GdImage $cover,
        string $title,
        string $subtitle,
        string $author,
        string $eyebrow,
        ShareCardFormat $format,
        ShareCardTemplate $template,
        int $white,
    ): void {
        $width = imagesx($canvas);
        $height = imagesy($canvas);
        $regular = $this->fontPath('Switzer-Regular.otf');
        $semibold = $this->fontPath('Switzer-Semibold.otf');
        $safeArea = ShareCardSafeArea::for($format);
        $padding = $safeArea['left'];
        $textX = $padding;
        $textY = $safeArea['top'];
        $textWidth = $width - $safeArea['left'] - $safeArea['right'];

        if ($template === ShareCardTemplate::Brand) {
            ShareCardLogo::stampCentered($canvas);

            return;
        }

        if (in_array($template, [ShareCardTemplate::PhotoClean, ShareCardTemplate::PhotoLogo], true)) {
            if ($cover instanceof GdImage) {
                $this->drawImageContain($canvas, $cover, 0, 0, $width, $height);
            }
            if ($template === ShareCardTemplate::PhotoLogo || ! $cover instanceof GdImage) {
                ShareCardLogo::stampCentered($canvas);
            }

            return;
        }

        if ($cover instanceof GdImage && $template !== ShareCardTemplate::Typographic) {
            if ($format === ShareCardFormat::Landscape) {
                $coverX = (int) round($width * 0.58);
                $this->drawImageContain($canvas, $cover, $coverX, 0, $width - $coverX, $height);
                $textWidth = $coverX - $padding - 44;
            } else {
                $coverHeight = match ($format) {
                    ShareCardFormat::Story => 920,
                    ShareCardFormat::Portrait => 720,
                    default => 570,
                };
                $this->drawImageContain($canvas, $cover, 0, 0, $width, $coverHeight);
                $textY = $coverHeight + 54;
            }
        }

        $logoTop = ShareCardLogo::destY($height);
        $authorReserve = $author !== '' ? 70 : 0;
        $textBottom = $logoTop - ShareCardLogo::TEXT_GAP - $authorReserve;
        [$titleSize, $titleLines] = $this->fitLines(
            $semibold,
            $title,
            $textWidth,
            max(80, $textBottom - $textY),
            $format === ShareCardFormat::Landscape ? 50 : 58,
            32,
            4,
        );

        $lineHeight = (int) round($titleSize * 1.16);
        $cursorY = $textY;
        if ($eyebrow !== '') {
            $eyebrow = mb_strtoupper($eyebrow);
            $eyebrowMetrics = $this->textMetrics($semibold, 22, $eyebrow);
            imagettftext($canvas, 22, 0, $textX, $cursorY + $eyebrowMetrics['ascent'], $white, $semibold, $eyebrow);
            $cursorY += 42;
        }
        foreach ($titleLines as $line) {
            $metrics = $this->textMetrics($semibold, $titleSize, $line);
            imagettftext($canvas, $titleSize, 0, $textX, $cursorY + $metrics['ascent'], $white, $semibold, $line);
            $cursorY += $lineHeight;
        }

        if ($subtitle !== '' && $cursorY < $textBottom - 44) {
            $cursorY += 18;
            $subtitleLines = array_slice($this->wrapText($regular, 25, $subtitle, $textWidth), 0, 3);
            foreach ($subtitleLines as $line) {
                $metrics = $this->textMetrics($regular, 25, $line);
                imagettftext($canvas, 25, 0, $textX, $cursorY + $metrics['ascent'], $white, $regular, $line);
                $cursorY += 34;
            }
        }

        if ($author !== '') {
            $authorMetrics = $this->textMetrics($semibold, 24, $author);
            imagettftext(
                $canvas,
                24,
                0,
                $textX,
                $logoTop - 30 + $authorMetrics['ascent'],
                $white,
                $semibold,
                $author,
            );
        }

        ShareCardLogo::stamp($canvas);
    }

    /**
     * @return array{float,list<string>}
     */
    private function fitLines(
        string $font,
        string $text,
        int $maxWidth,
        int $maxHeight,
        int $largest,
        int $smallest,
        int $maxLines,
    ): array {
        for ($size = $largest; $size >= $smallest; $size -= 2) {
            $lines = $this->wrapText($font, $size, $text, $maxWidth);
            $height = count($lines) * (int) round($size * 1.16);
            if (count($lines) <= $maxLines && $height <= $maxHeight) {
                return [$size, $lines];
            }
        }

        $lines = array_slice($this->wrapText($font, $smallest, $text, $maxWidth), 0, $maxLines);
        if (count($this->wrapText($font, $smallest, $text, $maxWidth)) > $maxLines) {
            $last = array_key_last($lines);
            $lines[$last] = rtrim($lines[$last], ' .…').'…';
        }

        return [$smallest, $lines];
    }

    /**
     * @return list<string>
     */
    private function wrapText(string $font, float $size, string $text, int $maxWidth): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [$text];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : "{$current} {$word}";
            if ($current !== '' && $this->textWidth($font, $size, $candidate) > $maxWidth) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [''];
    }

    private function drawImageContain(
        GdImage $canvas,
        GdImage $source,
        int $x,
        int $y,
        int $width,
        int $height,
    ): void {
        $scale = min($width / imagesx($source), $height / imagesy($source));
        $destinationWidth = max(1, (int) round(imagesx($source) * $scale));
        $destinationHeight = max(1, (int) round(imagesy($source) * $scale));
        $destinationX = $x + (int) floor(($width - $destinationWidth) / 2);
        $destinationY = $y + (int) floor(($height - $destinationHeight) / 2);

        imagecopyresampled(
            $canvas,
            $source,
            $destinationX,
            $destinationY,
            0,
            0,
            $destinationWidth,
            $destinationHeight,
            imagesx($source),
            imagesy($source),
        );
    }

    private function loadImage(?string $path): ?GdImage
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $contents = Storage::disk('public')->get($path);
        $image = @imagecreatefromstring($contents);

        return $image instanceof GdImage ? $image : null;
    }

    /**
     * @return array{ascent:int}
     */
    private function textMetrics(string $font, float $size, string $text): array
    {
        $box = imagettfbbox($size, 0, $font, $text);
        if (! is_array($box)) {
            return ['ascent' => (int) round($size)];
        }

        return ['ascent' => (int) max(0, -min($box[5], $box[7]))];
    }

    private function textWidth(string $font, float $size, string $text): int
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return is_array($box) ? (int) abs(max($box[2], $box[4]) - min($box[0], $box[6])) : 0;
    }

    private function fontPath(string $filename): string
    {
        $path = resource_path("fonts/{$filename}");
        if (! is_file($path)) {
            throw new RuntimeException("Missing share-card font: {$filename}");
        }

        return $path;
    }
}
