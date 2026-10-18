<?php

namespace Domain\Share\Support;

use GdImage;
use RuntimeException;

final class ShareCardGraphicLayouts
{
    /** @var array{x:int,y:int,width:int,height:int}|null */
    private ?array $imageBox = null;

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
        $this->imageBox = null;
        match ($template) {
            ShareCardTemplate::DateCut => $this->dateCut($canvas, $cover, $palette, $graphic),
            ShareCardTemplate::Framed => $this->framed($canvas, $cover, $palette, $graphic),
            ShareCardTemplate::PhotoClean => $this->photoClean($canvas, $cover, $palette, $graphic),
            ShareCardTemplate::PhotoLogo => $this->photoLogo($canvas, $cover, $palette, $graphic),
            ShareCardTemplate::Promo => $this->promo($canvas, $cover, $palette, $graphic),
            default => $this->dateCut($canvas, $cover, $palette, $graphic),
        };

        $this->stampLogo($canvas, $template);
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
        $ink = ShareCardContrast::isLightRgb($red, $green, $blue)
            ? $this->color($canvas, 17, 17, 17)
            : $white;
        $muted = ShareCardContrast::isLightRgb($red, $green, $blue)
            ? $this->color($canvas, 55, 55, 55)
            : $this->color($canvas, 210, 210, 210);
        $scale = $width / 1080;
        $pad = max(36, (int) round($width * 0.045));
        $gap = (int) round($width * 0.046);
        $x = $pad;
        $maxWidth = $width - ($pad * 2);

        if ($cover instanceof GdImage) {
            $posterW = (int) round($width * 0.34);
            $posterH = max(1, $bubble['bodyBottom'] - ($pad * 2));
            $this->coverContain($canvas, $cover, $pad, $pad, $posterW, $posterH);
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
    private function framed(GdImage $canvas, ?GdImage $cover, array $palette, array $graphic): void
    {
        $width = imagesx($canvas);
        $height = imagesy($canvas);
        $safe = $this->safeArea($width, $height);
        $scale = $width / 1080;
        [$red, $green, $blue] = $palette;
        $accent = $this->color($canvas, $red, $green, $blue);
        $white = $this->color($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $accent);

        $border = max($safe['left'] > 80 ? 48 : 36, (int) round(48 * $scale));
        $innerX = $border;
        $innerY = $border;
        $innerW = $width - ($border * 2);
        $innerH = $height - ($border * 2);
        imagefilledrectangle($canvas, $innerX, $innerY, $innerX + $innerW, $innerY + $innerH, $white);
        if ($cover instanceof GdImage) {
            $this->imageBox = $this->coverContain($canvas, $cover, $innerX, $innerY, $innerW, $innerH);
        }

        $semibold = $this->font('Switzer-Semibold.otf');
        $medium = $this->font('Switzer-Medium.otf');
        $regular = $this->font('Switzer-Regular.otf');
        $ink = $this->paletteInk($canvas, $palette, $white);
        $box = $this->imageBox ?? ['x' => $innerX, 'y' => $innerY, 'width' => $innerW, 'height' => $innerH];
        $inset = max(28, (int) round(min($box['width'], $box['height']) * 0.045));
        $right = $box['x'] + $box['width'] - $inset;
        $y = $box['y'] + $inset;
        $maxWidth = max(160, (int) round($box['width'] * 0.62));

        $topLines = [];
        $weekday = trim((string) ($graphic['weekday'] ?? ''));
        if ($weekday !== '') {
            $topLines[] = [$semibold, 42 * $scale, $weekday];
        }
        $date = trim((string) ($graphic['date'] ?? ''));
        if ($date !== '') {
            $topLines[] = [$semibold, 64 * $scale, $date];
        }
        $headline = trim((string) ($graphic['headline'] ?? ''));
        if ($headline !== '') {
            foreach ($this->wrap($medium, 48 * $scale, $headline, $maxWidth, 3) as $line) {
                $topLines[] = [$medium, 48 * $scale, $line];
            }
        }
        $this->drawOpaqueBlock($canvas, $topLines, $right, $y, 'right', $maxWidth, $accent, $ink, $scale);

        $place = trim((string) ($graphic['place'] ?? ''));
        $city = trim((string) ($graphic['city'] ?? ''));
        $bottomLines = [];
        if ($city !== '') {
            $bottomLines[] = [$semibold, 32 * $scale, $city];
        }
        if ($place !== '') {
            $bottomLines[] = [$regular, 28 * $scale, $place];
        }
        if ($bottomLines !== []) {
            $block = $this->measureOpaqueBlock($bottomLines, $maxWidth, $scale);
            $this->drawOpaqueBlock(
                $canvas,
                $bottomLines,
                $right,
                $box['y'] + $box['height'] - $inset - $block['height'],
                'right',
                $maxWidth,
                $accent,
                $ink,
                $scale,
            );
        }
    }

    /**
     * @param  array{int,int,int}  $palette
     * @param  array<string, mixed>  $graphic
     */
    private function photoClean(GdImage $canvas, ?GdImage $cover, array $palette, array $graphic): void
    {
        $width = imagesx($canvas);
        $height = imagesy($canvas);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $this->color($canvas, 255, 255, 255));
        $box = ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height];
        if ($cover instanceof GdImage) {
            $box = $this->coverContain($canvas, $cover, 0, 0, $width, $height);
            $this->imageBox = $box;
        }

        $this->drawPhotoCopy($canvas, $graphic, $box, $palette);
    }

    /**
     * @param  array{int,int,int}  $palette
     * @param  array<string, mixed>  $graphic
     */
    private function photoLogo(GdImage $canvas, ?GdImage $cover, array $palette, array $graphic): void
    {
        $width = imagesx($canvas);
        $height = imagesy($canvas);
        [$red, $green, $blue] = $palette;
        $barH = max(140, (int) round($height * 0.16));
        $photoH = max(1, $height - $barH);
        $accent = $this->color($canvas, $red, $green, $blue);
        imagefilledrectangle($canvas, 0, 0, $width, $photoH, $this->color($canvas, 255, 255, 255));
        $box = ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $photoH];
        if ($cover instanceof GdImage) {
            $box = $this->coverContain($canvas, $cover, 0, 0, $width, $photoH);
        }
        imagefilledrectangle($canvas, 0, $photoH, $width, $height, $accent);

        $this->drawPhotoCopy($canvas, $graphic, $box, $palette);
    }

    /**
     * @param  array{int,int,int}  $palette
     * @param  array<string, mixed>  $graphic
     */
    private function promo(GdImage $canvas, ?GdImage $cover, array $palette, array $graphic): void
    {
        $width = imagesx($canvas);
        $height = imagesy($canvas);
        [$red, $green, $blue] = $palette;
        $fill = $this->color($canvas, $red, $green, $blue);
        $white = $this->color($canvas, 255, 255, 255);
        $ink = ShareCardContrast::isLightRgb($red, $green, $blue)
            ? $this->color($canvas, 17, 17, 17)
            : $white;
        imagefilledrectangle($canvas, 0, 0, $width, $height, $fill);
        if ($cover instanceof GdImage) {
            $band = (int) round($height * 0.42);
            $this->coverContain($canvas, $cover, 0, 0, $width, $band);
        }

        $semibold = $this->font('Switzer-Semibold.otf');
        $medium = $this->font('Switzer-Medium.otf');
        $scale = $width / 1080;
        $x = max(48, (int) round($width * 0.07));
        $maxWidth = $width - ($x * 2);
        $y = $cover instanceof GdImage
            ? (int) round($height * 0.46)
            : max(56, (int) round($height * 0.12));

        $kicker = trim((string) ($graphic['kicker'] ?? $graphic['weekday'] ?? ''));
        if ($kicker !== '') {
            $y = $this->drawLine($canvas, $semibold, 36 * $scale, $x, $y, mb_strtoupper($kicker), $ink);
            $y += (int) round(18 * $scale);
        }

        $headline = trim((string) ($graphic['headline'] ?? $graphic['lead'] ?? ''));
        if ($headline !== '') {
            foreach ($this->wrap($medium, 72 * $scale, $headline, $maxWidth, 4) as $line) {
                $y = $this->drawLine($canvas, $medium, 72 * $scale, $x, $y, $line, $ink);
                $y += (int) round(6 * $scale);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $graphic
     * @param  array{x:int,y:int,width:int,height:int}  $box
     * @param  array{int,int,int}  $palette
     */
    private function drawPhotoCopy(GdImage $canvas, array $graphic, array $box, array $palette): void
    {
        $headline = trim((string) ($graphic['headline'] ?? ''));
        if ($headline === '') {
            return;
        }

        $semibold = $this->font('Switzer-Semibold.otf');
        $scale = imagesx($canvas) / 1080;
        $inset = max(24, (int) round(min($box['width'], $box['height']) * 0.05));
        $x = $box['x'] + $inset;
        $y = $box['y'] + $inset;
        $maxWidth = max(80, $box['width'] - ($inset * 2));
        $lines = [];
        foreach ($this->wrap($semibold, 42 * $scale, $headline, $maxWidth, 2) as $line) {
            $lines[] = [$semibold, 42 * $scale, $line];
        }
        [$red, $green, $blue] = $palette;
        $this->drawOpaqueBlock(
            $canvas,
            $lines,
            $x,
            $y,
            'left',
            $maxWidth,
            $this->color($canvas, $red, $green, $blue),
            $this->paletteInk($canvas, $palette, $this->color($canvas, 255, 255, 255)),
            $scale,
        );
    }

    private function stampLogo(GdImage $canvas, ShareCardTemplate $template): void
    {
        $height = imagesy($canvas);
        if ($template === ShareCardTemplate::DateCut) {
            ShareCardLogo::stampAt(
                $canvas,
                ShareCardLogo::destX(),
                ShareCardLogo::destY($height),
                true,
            );

            return;
        }

        if ($template === ShareCardTemplate::PhotoLogo) {
            $barTop = $height - max(140, (int) round($height * 0.16));
            $y = $barTop + (int) round((($height - $barTop) - ShareCardLogo::displayHeight()) / 2);
            ShareCardLogo::stampAt($canvas, ShareCardLogo::destX(), max($barTop + 8, $y));

            return;
        }

        if (in_array($template, [ShareCardTemplate::Framed, ShareCardTemplate::PhotoClean], true) && $this->imageBox !== null) {
            $this->stampLogoOnBox($canvas, $this->imageBox);

            return;
        }

        ShareCardLogo::stamp($canvas);
    }

    /**
     * @param  array{x:int,y:int,width:int,height:int}  $box
     */
    private function stampLogoOnBox(GdImage $canvas, array $box): void
    {
        $x = $box['x'] + max(16, (int) round($box['width'] * 0.04));
        $y = $box['y'] + $box['height'] - ShareCardLogo::MARGIN_BOTTOM - ShareCardLogo::displayHeight();
        ShareCardLogo::stampAt($canvas, $x, max($box['y'] + 8, $y));
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

    /**
     * @param  array{int,int,int}  $palette
     */
    private function paletteInk(GdImage $canvas, array $palette, int $white): int
    {
        [$red, $green, $blue] = $palette;

        return ShareCardContrast::isLightRgb($red, $green, $blue)
            ? $this->color($canvas, 17, 17, 17)
            : $white;
    }

    /**
     * @param  list<array{0:string,1:float,2:string}>  $lines
     * @return array{width:int,height:int,padX:int,padY:int,gap:int,lines:list<array{0:string,1:float,2:string,3:int,4:int}>}
     */
    private function measureOpaqueBlock(array $lines, int $maxWidth, float $scale): array
    {
        $padX = max(16, (int) round(22 * $scale));
        $padY = max(12, (int) round(16 * $scale));
        $gap = max(2, (int) round(4 * $scale));
        $prepared = [];
        $contentWidth = 0;
        $contentHeight = 0;
        foreach ($lines as $index => [$font, $size, $text]) {
            $text = $this->truncate($font, $size, $text, $maxWidth);
            $lineWidth = $this->textWidth($font, $size, $text);
            $lineHeight = (int) round($size * 1.12);
            $contentWidth = max($contentWidth, $lineWidth);
            $contentHeight += $lineHeight + ($index > 0 ? $gap : 0);
            $prepared[] = [$font, $size, $text, $lineWidth, $lineHeight];
        }

        return [
            'width' => $contentWidth + ($padX * 2),
            'height' => $contentHeight + ($padY * 2),
            'padX' => $padX,
            'padY' => $padY,
            'gap' => $gap,
            'lines' => $prepared,
        ];
    }

    /**
     * @param  list<array{0:string,1:float,2:string}>  $lines
     */
    private function drawOpaqueBlock(
        GdImage $canvas,
        array $lines,
        int $anchorX,
        int $anchorY,
        string $align,
        int $maxWidth,
        int $fill,
        int $ink,
        float $scale,
    ): int {
        if ($lines === []) {
            return $anchorY;
        }

        $block = $this->measureOpaqueBlock($lines, $maxWidth, $scale);
        $x = $align === 'right' ? $anchorX - $block['width'] : $anchorX;
        $y = $anchorY;
        imagefilledrectangle($canvas, $x, $y, $x + $block['width'] - 1, $y + $block['height'] - 1, $fill);

        $cursor = $y + $block['padY'];
        foreach ($block['lines'] as [$font, $size, $text, $lineWidth, $lineHeight]) {
            $textX = $align === 'right'
                ? $x + $block['width'] - $block['padX'] - $lineWidth
                : $x + $block['padX'];
            $this->drawLine($canvas, $font, $size, $textX, $cursor, $text, $ink);
            $cursor += $lineHeight + $block['gap'];
        }

        return $y + $block['height'];
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

    /**
     * @return array{x:int,y:int,width:int,height:int}
     */
    private function coverContain(GdImage $dest, GdImage $src, int $x, int $y, int $width, int $height): array
    {
        $box = ['x' => $x, 'y' => $y, 'width' => max(0, $width), 'height' => max(0, $height)];
        $sourceWidth = imagesx($src);
        $sourceHeight = imagesy($src);
        if ($sourceWidth < 1 || $sourceHeight < 1 || $width < 1 || $height < 1) {
            return $box;
        }

        $scale = min($width / $sourceWidth, $height / $sourceHeight);
        $destWidth = max(1, (int) round($sourceWidth * $scale));
        $destHeight = max(1, (int) round($sourceHeight * $scale));
        $destX = $x + (int) round(($width - $destWidth) / 2);
        $destY = $y + (int) round(($height - $destHeight) / 2);
        imagecopyresampled($dest, $src, $destX, $destY, 0, 0, $destWidth, $destHeight, $sourceWidth, $sourceHeight);

        return ['x' => $destX, 'y' => $destY, 'width' => $destWidth, 'height' => $destHeight];
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
