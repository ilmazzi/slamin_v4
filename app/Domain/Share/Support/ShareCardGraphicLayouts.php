<?php

namespace Domain\Share\Support;

use GdImage;
use RuntimeException;

final class ShareCardGraphicLayouts
{
    /**
     * @param  array{int,int,int}  $palette
     * @param  array<string, mixed>  $graphic
     */
    public function paint(
        GdImage $canvas,
        ?GdImage $cover,
        ShareCardTemplate $template,
        array $palette,
        array $graphic,
    ): void {
        if ($template === ShareCardTemplate::DateCut) {
            $this->dateCut($canvas, $cover, $palette, $graphic);

            return;
        }

        if ($template === ShareCardTemplate::Diagonal) {
            $this->diagonal($canvas, $cover, $palette, $graphic);

            return;
        }

        if ($template === ShareCardTemplate::Framed) {
            if ($cover instanceof GdImage) {
                $this->framed($canvas, $cover, $palette, $graphic);

                return;
            }

            $this->dateCut($canvas, $cover, $palette, $graphic);
        }
    }

    /**
     * @param  array{int,int,int}  $palette
     * @param  array<string, mixed>  $graphic
     */
    private function dateCut(GdImage $canvas, ?GdImage $cover, array $palette, array $graphic): void
    {
        $width = imagesx($canvas);
        $height = imagesy($canvas);
        $bubble = $this->speechBubble($width, $height);
        $white = $this->color($canvas, 255, 255, 255);
        [$red, $green, $blue] = $palette;
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
        $this->fillSpeechBubble($canvas, $this->color($canvas, $red, $green, $blue));

        $semibold = $this->font('Switzer-Semibold.otf');
        $medium = $this->font('Switzer-Medium.otf');
        $regular = $this->font('Switzer-Regular.otf');
        $ink = ($red + $green + $blue) < 90
            ? $white
            : $this->color($canvas, 17, 17, 17);
        $muted = ($red + $green + $blue) < 90
            ? $this->color($canvas, 210, 210, 210)
            : $this->color($canvas, 55, 55, 55);
        $scale = $width / 1080;
        $pad = max(36, (int) round($width * 0.045));
        $gap = (int) round($width * 0.046);
        $x = $pad;
        $maxWidth = $width - ($pad * 2);

        if ($cover instanceof GdImage) {
            $posterW = (int) round($width * 0.34);
            $posterH = max(1, $bubble['bodyBottom'] - ($pad * 2));
            $this->coverFill($canvas, $cover, $pad, $pad, $posterW, $posterH);
            $x = $pad + $posterW + $gap;
            $maxWidth = max(160, $width - $pad - $x);
        }

        $y = $pad;
        $weekday = trim((string) ($graphic['weekday'] ?? ''));
        $date = trim((string) ($graphic['date'] ?? ''));
        if ($weekday !== '') {
            $y = $this->drawLine($canvas, $semibold, 42 * $scale, $x, $y, $weekday, $ink);
            $y += (int) round(4 * $scale);
        }
        if ($date !== '') {
            $y = $this->drawLine($canvas, $semibold, 64 * $scale, $x, $y, $date, $ink);
            $y += (int) round(18 * $scale);
        }

        $headline = trim((string) ($graphic['headline'] ?? ''));
        if ($headline !== '') {
            foreach ($this->wrap($medium, 56 * $scale, $headline, $maxWidth, 3) as $line) {
                $y = $this->drawLine($canvas, $medium, 56 * $scale, $x, $y, $line, $ink);
                $y += (int) round(4 * $scale);
            }
        }

        $context = trim((string) ($graphic['context'] ?? ''));
        if ($context !== '' && $y < $bubble['bodyBottom'] - (int) round(160 * $scale)) {
            $y += (int) round(14 * $scale);
            foreach ($this->wrap($regular, 24 * $scale, $context, $maxWidth, 2) as $line) {
                $y = $this->drawLine($canvas, $regular, 24 * $scale, $x, $y, $line, $muted);
                $y += (int) round(4 * $scale);
            }
        }

        $this->drawPlaceBlock(
            $canvas,
            $semibold,
            $regular,
            $x,
            $bubble['bodyBottom'] - $pad - (int) round(86 * $scale),
            trim((string) ($graphic['city'] ?? '')),
            trim((string) ($graphic['place'] ?? '')),
            $ink,
            $muted,
            $scale,
            $maxWidth,
        );
    }

    /**
     * @param  array{int,int,int}  $palette
     * @param  array<string, mixed>  $graphic
     */
    private function diagonal(GdImage $canvas, ?GdImage $cover, array $palette, array $graphic): void
    {
        $width = imagesx($canvas);
        $height = imagesy($canvas);
        $bubble = $this->speechBubble($width, $height);
        $white = $this->color($canvas, 255, 255, 255);
        [$red, $green, $blue] = $palette;
        $fill = $this->color($canvas, $red, $green, $blue);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);

        if ($cover instanceof GdImage) {
            $this->coverFill($canvas, $cover, 0, 0, $width, $height);
            $this->fillPolygon($canvas, $bubble['cut'], $white);
        } else {
            $this->fillSpeechBubble($canvas, $fill);
        }

        $semibold = $this->font('Switzer-Semibold.otf');
        $regular = $this->font('Switzer-Regular.otf');
        $ink = $this->color($canvas, 17, 17, 17);
        $scale = $width / 1080;
        $x = max(36, (int) round($width * 0.045));
        $maxWidth = $width - ($x * 2);
        $y = $x;
        $onPhoto = $cover instanceof GdImage;
        $titleColor = $onPhoto ? $white : $ink;
        $metaColor = $onPhoto ? $white : $ink;

        $kicker = trim((string) ($graphic['kicker'] ?? ''));
        if ($kicker !== '') {
            $y = $this->drawLine($canvas, $semibold, 92 * $scale, $x, $y, $kicker, $titleColor);
            $y += (int) round(8 * $scale);
        }

        $lead = trim((string) ($graphic['lead'] ?? $graphic['headline'] ?? ''));
        if ($lead !== '') {
            foreach ($this->wrap($semibold, 42 * $scale, $lead, $maxWidth, 3) as $line) {
                $y = $this->drawLine($canvas, $semibold, 42 * $scale, $x, $y, $line, $metaColor);
                $y += (int) round(4 * $scale);
            }
            $y += (int) round(10 * $scale);
        }

        $site = trim((string) ($graphic['site'] ?? ''));
        $domain = trim((string) ($graphic['domain'] ?? ''));
        $size = 36 * $scale;
        if ($site !== '') {
            $this->drawLine($canvas, $regular, $size, $x, $y, $site, $metaColor);
            $x += $this->textWidth($regular, $size, $site.' ');
        }
        if ($domain !== '') {
            $this->drawLine($canvas, $semibold, $size, $x, $y, $domain, $titleColor);
        }
    }

    /**
     * @param  array{int,int,int}  $palette
     * @param  array<string, mixed>  $graphic
     */
    private function framed(GdImage $canvas, GdImage $cover, array $palette, array $graphic): void
    {
        $width = imagesx($canvas);
        $height = imagesy($canvas);
        $safe = $this->safeArea($width, $height);
        $scale = $width / 1080;
        $border = max($safe['left'] > 80 ? 36 : 28, (int) round(28 * $scale));
        $innerX = $border;
        $innerY = $border;
        $innerW = $width - ($border * 2);
        $innerH = $height - ($border * 2);
        $this->coverFill($canvas, $cover, $innerX, $innerY, $innerW, $innerH);

        $semibold = $this->font('Switzer-Semibold.otf');
        $medium = $this->font('Switzer-Medium.otf');
        $regular = $this->font('Switzer-Regular.otf');
        [$red, $green, $blue] = $palette;
        $accent = $this->color($canvas, $red, $green, $blue);
        $white = $this->color($canvas, 255, 255, 255);
        $right = $innerX + $innerW - max(36, $safe['right'] - $border);
        $y = $innerY + max(36, $safe['top'] - $border);
        $maxWidth = (int) round($innerW * 0.62);

        $weekday = trim((string) ($graphic['weekday'] ?? ''));
        if ($weekday !== '') {
            $y = $this->drawRightLine($canvas, $semibold, 42 * $scale, $right, $y, $weekday, $accent, $maxWidth);
            $y += (int) round(6 * $scale);
        }
        $date = trim((string) ($graphic['date'] ?? ''));
        if ($date !== '') {
            $y = $this->drawRightLine($canvas, $semibold, 64 * $scale, $right, $y, $date, $white, $maxWidth);
            $y += (int) round(16 * $scale);
        }
        $headline = trim((string) ($graphic['headline'] ?? ''));
        if ($headline !== '') {
            foreach ($this->wrap($medium, 48 * $scale, $headline, $maxWidth, 3) as $line) {
                $y = $this->drawRightLine($canvas, $medium, 48 * $scale, $right, $y, $line, $white, $maxWidth);
                $y += (int) round(4 * $scale);
            }
        }

        $bottom = $innerY + $innerH - max(36, $safe['bottom'] - $border);
        $place = trim((string) ($graphic['place'] ?? ''));
        $city = trim((string) ($graphic['city'] ?? ''));
        if ($place !== '') {
            $bottom -= (int) round(34 * $scale);
            $this->drawRightLine($canvas, $regular, 28 * $scale, $right, $bottom, $place, $white, $maxWidth);
        }
        if ($city !== '') {
            $bottom -= (int) round(40 * $scale);
            $this->drawRightLine($canvas, $semibold, 32 * $scale, $right, $bottom, $city, $white, $maxWidth);
        }
    }

    private function drawPlaceBlock(
        GdImage $canvas,
        string $semibold,
        string $regular,
        int $x,
        int $y,
        string $city,
        string $place,
        int $ink,
        int $muted,
        float $scale,
        int $maxWidth,
    ): void {
        if ($city !== '') {
            $line = $this->truncate($semibold, 36 * $scale, $city, $maxWidth);
            $y = $this->drawLine($canvas, $semibold, 36 * $scale, $x, $y, $line, $ink);
            $y += (int) round(6 * $scale);
        }
        if ($place !== '') {
            $line = $this->truncate($regular, 30 * $scale, $place, $maxWidth);
            $this->drawLine($canvas, $regular, 30 * $scale, $x, $y, $line, $muted);
        }
    }

    /**
     * Event-detail speech bubble: mint rectangle + `polygon(0 0, 100% 0, 100% 100%)` tail.
     *
     * @return array{bodyBottom:int, tailLeft:int, tail:list<int>, cut:list<int>}
     */
    private function speechBubble(int $width, int $height): array
    {
        $bodyBottom = (int) round($height * 0.772);
        $tailLeft = $width - (int) round($width * 0.28);

        return [
            'bodyBottom' => $bodyBottom,
            'tailLeft' => $tailLeft,
            'tail' => [$tailLeft, $bodyBottom, $width, $bodyBottom, $width, $height],
            'cut' => [0, $bodyBottom, $tailLeft, $bodyBottom, $width, $height, 0, $height],
        ];
    }

    private function fillSpeechBubble(GdImage $canvas, int $color): void
    {
        $width = imagesx($canvas);
        $height = imagesy($canvas);
        $bubble = $this->speechBubble($width, $height);
        imagefilledrectangle($canvas, 0, 0, $width, $bubble['bodyBottom'], $color);
        $this->fillPolygon($canvas, $bubble['tail'], $color);
    }

    private function drawLine(
        GdImage $canvas,
        string $font,
        float $size,
        int $x,
        int $y,
        string $text,
        int $color,
    ): int {
        $ascent = $this->ascent($font, $size, $text);
        imagettftext($canvas, $size, 0, $x, $y + $ascent, $color, $font, $text);

        return $y + (int) round($size * 1.12);
    }

    private function drawRightLine(
        GdImage $canvas,
        string $font,
        float $size,
        int $right,
        int $y,
        string $text,
        int $color,
        int $maxWidth,
    ): int {
        $text = $this->truncate($font, $size, $text, $maxWidth);
        $x = $right - $this->textWidth($font, $size, $text);

        return $this->drawLine($canvas, $font, $size, $x, $y, $text, $color);
    }

    /**
     * @return list<string>
     */
    private function wrap(string $font, float $size, string $text, int $maxWidth, int $maxLines): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
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
        if (count($lines) <= $maxLines) {
            return $lines === [] ? [''] : $lines;
        }

        $visible = array_slice($lines, 0, $maxLines);
        $last = array_key_last($visible);
        $visible[$last] = $this->truncate($font, $size, rtrim((string) $visible[$last], ' .…').'…', $maxWidth);

        return $visible;
    }

    private function truncate(string $font, float $size, string $text, int $maxWidth): string
    {
        if ($this->textWidth($font, $size, $text) <= $maxWidth) {
            return $text;
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $current = '';
        foreach ($chars as $char) {
            $candidate = $current.$char.'…';
            if ($this->textWidth($font, $size, $candidate) > $maxWidth) {
                break;
            }
            $current .= $char;
        }

        return $current === '' ? '…' : $current.'…';
    }

    /**
     * @param  list<int>  $points
     */
    private function fillPolygon(GdImage $canvas, array $points, int $color): void
    {
        $antialias = function_exists('imageantialias');
        if ($antialias) {
            imageantialias($canvas, false);
        }

        imagefilledpolygon($canvas, $points, $color);

        if ($antialias) {
            imageantialias($canvas, true);
        }
    }

    private function coverFill(GdImage $dest, GdImage $src, int $x, int $y, int $width, int $height): void
    {
        $sourceWidth = imagesx($src);
        $sourceHeight = imagesy($src);
        if ($sourceWidth < 1 || $sourceHeight < 1 || $width < 1 || $height < 1) {
            return;
        }

        $scale = max($width / $sourceWidth, $height / $sourceHeight);
        $cropWidth = min($sourceWidth, max(1, (int) round($width / $scale)));
        $cropHeight = min($sourceHeight, max(1, (int) round($height / $scale)));
        $cropX = (int) round(($sourceWidth - $cropWidth) / 2);
        $cropY = (int) round(($sourceHeight - $cropHeight) / 2);
        imagecopyresampled($dest, $src, $x, $y, $cropX, $cropY, $width, $height, $cropWidth, $cropHeight);
    }

    /**
     * @return array{left:int,top:int,right:int,bottom:int}
     */
    private function safeArea(int $width, int $height): array
    {
        $format = match (true) {
            $height >= 1800 => ShareCardFormat::Story,
            $width > $height => ShareCardFormat::Landscape,
            $height > $width => ShareCardFormat::Portrait,
            default => ShareCardFormat::Square,
        };

        return ShareCardSafeArea::for($format);
    }

    private function color(GdImage $canvas, int $red, int $green, int $blue): int
    {
        $color = imagecolorallocate($canvas, $red, $green, $blue);
        if ($color === false) {
            throw new RuntimeException('Unable to allocate a share-card color.');
        }

        return $color;
    }

    private function ascent(string $font, float $size, string $text): int
    {
        $box = imagettfbbox($size, 0, $font, $text === '' ? ' ' : $text);

        return is_array($box) ? (int) max(0, -min($box[5], $box[7])) : (int) round($size);
    }

    private function textWidth(string $font, float $size, string $text): int
    {
        $box = imagettfbbox($size, 0, $font, $text === '' ? ' ' : $text);

        return is_array($box) ? (int) abs(max($box[2], $box[4]) - min($box[0], $box[6])) : 0;
    }

    private function font(string $filename): string
    {
        $path = resource_path("fonts/{$filename}");
        if (! is_file($path)) {
            throw new RuntimeException("Missing share-card font: {$filename}");
        }

        return $path;
    }
}
