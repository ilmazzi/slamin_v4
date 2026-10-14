<?php

namespace Domain\Event\Support;

use Domain\Event\Enums\EventDeliveryMode;
use Domain\Event\Models\Event;
use Domain\Share\Support\ShareCardLogo;
use Domain\Share\Support\SlaminShareMark;
use GdImage;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class EventShareCardRenderer
{
    private const SIZE = 1080;

    private const BACKGROUND = [132, 218, 180];

    private const INK = [17, 17, 17];

    private const IMAGE_HEIGHT = 700;

    public function render(Event $event): string
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagettftext')) {
            throw new RuntimeException('GD with FreeType is required to render event share cards.');
        }

        $regular = $this->fontPath('Switzer-Regular.otf');
        $medium = $this->fontPath('Switzer-Medium.otf');
        $semibold = $this->fontPath('Switzer-Semibold.otf');

        $image = imagecreatetruecolor(self::SIZE, self::SIZE);
        if ($image === false) {
            throw new RuntimeException('Unable to allocate the event share card canvas.');
        }

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $mint = imagecolorallocate($image, self::BACKGROUND[0], self::BACKGROUND[1], self::BACKGROUND[2]);
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, self::INK[0], self::INK[1], self::INK[2]);
        imagefilledrectangle($image, 0, 0, self::SIZE, self::SIZE, $mint);

        $previousLocale = app()->getLocale();
        $locale = in_array($event->language, ['it', 'en'], true) ? $event->language : 'it';
        app()->setLocale($locale);

        $cover = $this->loadCover($event->cover_image_path);

        try {
            return $this->paint($event, $image, $cover, $regular, $medium, $semibold, $mint, $white, $ink);
        } finally {
            app()->setLocale($previousLocale);
            if ($cover instanceof GdImage) {
                imagedestroy($cover);
            }
            imagedestroy($image);
        }
    }

    private function paint(
        Event $event,
        GdImage $image,
        ?GdImage $cover,
        string $regular,
        string $medium,
        string $semibold,
        int $mint,
        int $white,
        int $ink,
    ): string {
        $organizer = $this->organizerFirstName($event);
        $placeLine = $this->placeLine($event);
        $bubble = $this->measureMark($semibold, $regular, $organizer, $placeLine);

        if ($cover instanceof GdImage) {
            $this->drawCover($image, $cover, 0, 0, self::SIZE, self::IMAGE_HEIGHT);
        }

        $hasCover = $cover instanceof GdImage;
        $titleTop = $hasCover ? self::IMAGE_HEIGHT + 48 : 96;
        $titleMaxBottom = min($bubble['y'] - 36, ShareCardLogo::destY(self::SIZE) - ShareCardLogo::TEXT_GAP);
        $titleMaxWidth = $hasCover
            ? max(420, self::SIZE - 136 - $bubble['width'])
            : self::SIZE - 160;

        $title = trim((string) $event->title) ?: __('events.event_title');
        [$titleSize, $titleLines] = $this->fitLines($semibold, $title, $titleMaxWidth, $titleMaxBottom, $titleTop, 52, 32);

        $y = $titleTop;
        $titleLineHeight = (int) round($titleSize * 1.22);
        foreach ($titleLines as $line) {
            $box = $this->textMetrics($semibold, $titleSize, $line === '' ? ' ' : $line);
            imagettftext($image, $titleSize, 0, 80, $y + $box['ascent'], $ink, $semibold, $line);
            $y += $titleLineHeight;
        }

        $meta = $this->metaLine($event);
        if ($meta !== '') {
            $y += 12;
            $metaBox = $this->textMetrics($medium, 24, $meta);
            imagettftext($image, 24, 0, 80, $y + $metaBox['ascent'], $ink, $medium, $meta);
        }

        SlaminShareMark::fill(
            $image,
            $bubble['x'],
            $bubble['y'],
            $bubble['width'],
            $bubble['height'],
            $bubble['bodyHeight'],
            $white,
        );

        $nameBox = $this->textMetrics($semibold, $bubble['nameSize'], $organizer);
        $textX = $bubble['x'] + $bubble['paddingX'];
        $nameY = $bubble['y'] + $bubble['paddingY'] + $nameBox['ascent'];
        imagettftext($image, $bubble['nameSize'], 0, $textX, $nameY, $mint, $semibold, $organizer);

        if ($placeLine) {
            $placeBox = $this->textMetrics($regular, $bubble['placeSize'], $placeLine);
            imagettftext(
                $image,
                $bubble['placeSize'],
                0,
                $textX,
                $nameY + 8 + $placeBox['ascent'],
                $mint,
                $regular,
                $placeLine,
            );
        }

        ShareCardLogo::stamp($image);

        ob_start();
        imagepng($image, null, 8);
        $png = (string) ob_get_clean();

        if ($png === '' || ! str_starts_with($png, "\x89PNG")) {
            throw new RuntimeException('Failed to encode the event share card.');
        }

        return $png;
    }

    private function drawCover(GdImage $dest, GdImage $src, int $dx, int $dy, int $dw, int $dh): void
    {
        $sw = imagesx($src);
        $sh = imagesy($src);
        if ($sw < 1 || $sh < 1) {
            return;
        }

        $srcAspect = $sw / $sh;
        $dstAspect = $dw / $dh;

        if ($srcAspect > $dstAspect) {
            $cropH = $sh;
            $cropW = (int) round($sh * $dstAspect);
            $srcX = (int) (($sw - $cropW) / 2);
            $srcY = 0;
        } else {
            $cropW = $sw;
            $cropH = (int) round($sw / $dstAspect);
            $srcX = 0;
            $srcY = (int) (($sh - $cropH) / 2);
        }

        imagecopyresampled($dest, $src, $dx, $dy, $srcX, $srcY, $dw, $dh, max(1, $cropW), max(1, $cropH));
    }

    private function loadCover(?string $path): ?GdImage
    {
        $relative = trim((string) $path);
        if ($relative === '') {
            return null;
        }

        $full = Storage::disk('public')->path($relative);
        if (! is_file($full)) {
            return null;
        }

        $info = @getimagesize($full);
        if ($info === false) {
            return null;
        }

        $loaded = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($full),
            IMAGETYPE_PNG => @imagecreatefrompng($full),
            IMAGETYPE_GIF => @imagecreatefromgif($full),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($full) : false,
            default => false,
        };

        return $loaded instanceof GdImage ? $loaded : null;
    }

    /**
     * @return array{x: int, y: int, width: int, height: int, bodyHeight: int, paddingX: int, paddingY: int, nameSize: int, placeSize: int}
     */
    private function measureMark(string $nameFont, string $placeFont, string $organizer, ?string $placeLine): array
    {
        $nameSize = 32;
        $placeSize = 22;
        $paddingX = 28;
        $paddingY = 22;

        $nameBox = $this->textMetrics($nameFont, $nameSize, $organizer);
        $placeBox = $placeLine
            ? $this->textMetrics($placeFont, $placeSize, $placeLine)
            : ['width' => 0, 'height' => 0];

        $innerWidth = (int) max($nameBox['width'], $placeBox['width']);
        $innerHeight = (int) ($nameBox['height'] + ($placeLine ? 8 + $placeBox['height'] : 0));
        $bodyWidth = $innerWidth + ($paddingX * 2);
        $bodyHeight = $innerHeight + ($paddingY * 2);
        $height = SlaminShareMark::totalHeight($bodyHeight);

        return [
            'x' => self::SIZE - 56 - $bodyWidth,
            'y' => self::SIZE - 52 - $height,
            'width' => $bodyWidth,
            'height' => $height,
            'bodyHeight' => $bodyHeight,
            'paddingX' => $paddingX,
            'paddingY' => $paddingY,
            'nameSize' => $nameSize,
            'placeSize' => $placeSize,
        ];
    }

    /**
     * @return array{0: int, 1: list<string>}
     */
    private function fitLines(string $font, string $text, int $maxWidth, int $maxBottom, int $startY, int $maxSize, int $minSize): array
    {
        $untruncated = null;

        for ($size = $maxSize; $size >= $minSize; $size -= 2) {
            $lineHeight = (int) round($size * 1.22);
            $maxLines = max(1, (int) floor(($maxBottom - $startY) / $lineHeight));
            $lines = $this->wrapText($font, $size, $text, $maxWidth);
            $untruncated = $lines;

            if (count($lines) <= $maxLines) {
                return [$size, $lines];
            }
        }

        $lineHeight = (int) round($minSize * 1.22);
        $maxLines = max(1, (int) floor(($maxBottom - $startY) / $lineHeight));

        return [$minSize, $this->truncateLines($font, $minSize, $untruncated ?? [], $maxWidth, $maxLines)];
    }

    /**
     * @return list<string>
     */
    private function wrapText(string $font, float $size, string $text, int $maxWidth): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [trim($text)];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }

            $candidate = $current === '' ? $word : $current.' '.$word;
            if ($this->textMetrics($font, $size, $candidate)['width'] <= $maxWidth) {
                $current = $candidate;

                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
            }
            $current = $word;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines === [] ? [''] : $lines;
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function truncateLines(string $font, float $size, array $lines, int $maxWidth, int $maxLines): array
    {
        if (count($lines) <= $maxLines) {
            return $lines;
        }

        $visible = array_slice($lines, 0, $maxLines);
        $last = rtrim((string) ($visible[$maxLines - 1] ?? ''), ' .…');
        $visible[$maxLines - 1] = $this->truncateToWidth($font, $size, $last.'…', $maxWidth);

        return $visible;
    }

    private function truncateToWidth(string $font, float $size, string $text, int $maxWidth): string
    {
        if ($this->textMetrics($font, $size, $text)['width'] <= $maxWidth) {
            return $text;
        }

        $chars = preg_split('//u', mb_substr($text, 0, -1), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $current = '';
        foreach ($chars as $char) {
            $candidate = $current.$char.'…';
            if ($this->textMetrics($font, $size, $candidate)['width'] > $maxWidth) {
                break;
            }
            $current .= $char;
        }

        return $current === '' ? '…' : $current.'…';
    }

    /**
     * @return array{width: int, height: int, ascent: int, descent: int}
     */
    private function textMetrics(string $font, float $size, string $text): array
    {
        $box = imagettfbbox($size, 0, $font, $text === '' ? ' ' : $text);
        if ($box === false) {
            return ['width' => 0, 'height' => 0, 'ascent' => 0, 'descent' => 0];
        }

        $xs = [$box[0], $box[2], $box[4], $box[6]];
        $ys = [$box[1], $box[3], $box[5], $box[7]];

        return [
            'width' => (int) ceil(max($xs) - min($xs)),
            'height' => (int) ceil(max($ys) - min($ys)),
            'ascent' => (int) abs(min($ys)),
            'descent' => (int) max($ys),
        ];
    }

    private function metaLine(Event $event): string
    {
        $occurrence = $event->occurrences->firstWhere('is_primary', true) ?? $event->occurrences->first();
        $startsAt = $occurrence?->starts_at;
        if ($startsAt === null) {
            return '';
        }

        $timezone = $occurrence->timezone ?: 'UTC';
        $locale = app()->getLocale();

        return $startsAt->timezone($timezone)->locale($locale)->translatedFormat('j F Y');
    }

    private function organizerFirstName(Event $event): string
    {
        $displayName = trim((string) ($event->user?->profile?->display_name ?? ''));
        if ($displayName !== '') {
            $parts = preg_split('/\s+/u', $displayName) ?: [$displayName];

            return mb_substr((string) $parts[0], 0, 22);
        }

        $nickname = trim((string) ($event->user?->profile?->nickname ?? ''));
        if ($nickname !== '' && ! preg_match('/^user[0-9a-f]{12,}$/i', $nickname)) {
            return mb_substr($nickname, 0, 22);
        }

        return 'Slam In';
    }

    private function placeLine(Event $event): string
    {
        if ($event->delivery_mode === EventDeliveryMode::Online) {
            return __('events.share_card.online');
        }

        $city = trim((string) ($event->venueDetail?->city ?? ''));
        if ($city !== '') {
            return __('events.share_card.from', ['place' => $city]);
        }

        return __('events.share_card.without_place');
    }

    private function fontPath(string $filename): string
    {
        $path = resource_path('fonts/'.$filename);
        if (! is_file($path)) {
            throw new RuntimeException("Missing share-card font: {$filename}");
        }

        return $path;
    }
}
