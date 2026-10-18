<?php

namespace Domain\Event\Support;

use Domain\Event\Enums\EventDeliveryMode;
use Domain\Event\Models\Event;
use Domain\Share\Support\ShareCardFormat;
use Domain\Share\Support\ShareCardGraphicLayouts;
use Domain\Share\Support\ShareCardLogo;
use Domain\Share\Support\ShareCardPalette;
use Domain\Share\Support\ShareCardSafeArea;
use Domain\Share\Support\ShareCardTemplate;
use Domain\Share\Support\SlaminShareMark;
use GdImage;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class EventShareCardRenderer
{
    private const INK = [17, 17, 17];

    private const LANDSCAPE_TEXT_RATIO = 0.55;

    public function render(
        Event $event,
        ShareCardFormat $format = ShareCardFormat::Square,
        ShareCardTemplate $template = ShareCardTemplate::Poster,
    ): string {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagettftext')) {
            throw new RuntimeException('GD with FreeType is required to render event share cards.');
        }

        $regular = $this->fontPath('Switzer-Regular.otf');
        $medium = $this->fontPath('Switzer-Medium.otf');
        $semibold = $this->fontPath('Switzer-Semibold.otf');

        $image = imagecreatetruecolor($format->width(), $format->height());
        if ($image === false) {
            throw new RuntimeException('Unable to allocate the event share card canvas.');
        }

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        [$red, $green, $blue] = ShareCardPalette::for('event');
        $mint = imagecolorallocate($image, $red, $green, $blue);
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, self::INK[0], self::INK[1], self::INK[2]);
        imagefilledrectangle($image, 0, 0, $format->width(), $format->height(), $mint);

        $previousLocale = app()->getLocale();
        $locale = in_array($event->language, ['it', 'en'], true) ? $event->language : 'it';
        app()->setLocale($locale);

        $cover = $this->loadCover($event->cover_image_path);

        try {
            return $this->paint($event, $image, $cover, $regular, $medium, $semibold, $template, $mint, $white, $ink);
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
        ShareCardTemplate $template,
        int $mint,
        int $white,
        int $ink,
    ): string {
        $width = imagesx($image);
        $height = imagesy($image);
        if ($template->isGraphic()) {
            app(ShareCardGraphicLayouts::class)->paint(
                $image,
                $cover,
                $template,
                ShareCardPalette::for('event'),
                $this->graphicBillboard($event),
            );

            return $this->encode($image);
        }

        if (in_array($template, [ShareCardTemplate::Typographic, ShareCardTemplate::Verse, ShareCardTemplate::Brand], true)) {
            $cover = null;
        }

        $organizer = $this->organizerFirstName($event);
        $placeLine = $this->placeLine($event);
        $bubble = $this->measureMark($semibold, $regular, $organizer, $placeLine, $width, $height);
        $logoLimit = ShareCardLogo::destY($height) - ShareCardLogo::TEXT_GAP;
        $category = $this->categoryLine($event);
        $details = $this->detailLines($event);
        $detailSize = $width > $height ? 19 : 22;
        $detailLineHeight = (int) round($detailSize * 1.38);
        $detailsHeight = count($details) * $detailLineHeight + ($details === [] ? 0 : 18);

        if ($width > $height) {
            $textRight = $width - 80;
            if ($cover instanceof GdImage) {
                $panelX = (int) round($width * self::LANDSCAPE_TEXT_RATIO);
                $this->drawCover($image, $cover, $panelX, 0, $width - $panelX, $height);
                $textRight = $panelX - 48;
            }

            $contentTop = 58;
            $titleMaxWidth = $textRight - 80;
            $contentBottom = $bubble['x'] < $textRight ? min($bubble['y'] - 36, $logoLimit) : $logoLimit;
        } else {
            $coverBox = $cover instanceof GdImage
                ? $this->drawCover($image, $cover, 0, 0, $width, $this->coverHeight($height))
                : null;

            $hasCover = $coverBox !== null && $coverBox['height'] > 0;
            $safeArea = ShareCardSafeArea::for($height >= 1800
                ? ShareCardFormat::Story
                : ($height > 1080 ? ShareCardFormat::Portrait : ShareCardFormat::Square));
            $contentTop = $hasCover ? $coverBox['y'] + $coverBox['height'] + 40 : $safeArea['top'];
            $contentBottom = min($bubble['y'] - 36, $logoLimit);
            $titleMaxWidth = $hasCover
                ? max(420, $width - 136 - $bubble['width'])
                : $width - 160;
        }

        $titleTop = $contentTop;
        if ($category !== '') {
            $categoryBox = $this->textMetrics($semibold, 21, $category);
            imagettftext($image, 21, 0, 80, $contentTop + $categoryBox['ascent'], $ink, $semibold, $category);
            $titleTop += 38;
        }

        $titleMaxBottom = max($titleTop + 40, $contentBottom - $detailsHeight);
        $title = trim((string) $event->title) ?: __('events.event_title');
        [$titleSize, $titleLines] = $this->fitLines($semibold, $title, $titleMaxWidth, $titleMaxBottom, $titleTop, 52, 32);

        $y = $titleTop;
        $titleLineHeight = (int) round($titleSize * 1.22);
        foreach ($titleLines as $line) {
            $box = $this->textMetrics($semibold, $titleSize, $line === '' ? ' ' : $line);
            imagettftext($image, $titleSize, 0, 80, $y + $box['ascent'], $ink, $semibold, $line);
            $y += $titleLineHeight;
        }

        if ($details !== []) {
            $y += 14;
            foreach ($details as $detail) {
                $detail = $this->truncateToWidth($medium, $detailSize, $detail, $titleMaxWidth);
                $detailBox = $this->textMetrics($medium, $detailSize, $detail);
                imagettftext($image, $detailSize, 0, 80, $y + $detailBox['ascent'], $ink, $medium, $detail);
                $y += $detailLineHeight;
            }
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

        return $this->encode($image);
    }

    private function encode(GdImage $image): string
    {
        ob_start();
        imagepng($image, null, 8);
        $png = (string) ob_get_clean();

        if ($png === '' || ! str_starts_with($png, "\x89PNG")) {
            throw new RuntimeException('Failed to encode the event share card.');
        }

        return $png;
    }

    /**
     * @return array{x: int, y: int, width: int, height: int}
     */
    private function drawCover(GdImage $dest, GdImage $src, int $boxX, int $boxY, int $boxW, int $boxH): array
    {
        $sw = imagesx($src);
        $sh = imagesy($src);
        if ($sw < 1 || $sh < 1 || $boxW < 1 || $boxH < 1) {
            return ['x' => $boxX, 'y' => $boxY, 'width' => 0, 'height' => 0];
        }

        $scale = min($boxW / $sw, $boxH / $sh);
        $dw = max(1, (int) round($sw * $scale));
        $dh = max(1, (int) round($sh * $scale));
        $dx = $boxX + (int) round(($boxW - $dw) / 2);
        $dy = $boxY;

        imagecopyresampled($dest, $src, $dx, $dy, 0, 0, $dw, $dh, $sw, $sh);

        return ['x' => $dx, 'y' => $dy, 'width' => $dw, 'height' => $dh];
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
    private function measureMark(string $nameFont, string $placeFont, string $organizer, ?string $placeLine, int $canvasWidth, int $canvasHeight): array
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
            'x' => $canvasWidth - 56 - $bodyWidth,
            'y' => $canvasHeight - 52 - $height,
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

    private function coverHeight(int $canvasHeight): int
    {
        if ($canvasHeight <= 1080) {
            return 540;
        }

        if ($canvasHeight <= 1350) {
            return 650;
        }

        return 800;
    }

    private function categoryLine(Event $event): string
    {
        $name = trim((string) $event->category?->translatedName());

        return $name === '' ? '' : mb_strtoupper($name);
    }

    /**
     * @return list<string>
     */
    private function detailLines(Event $event): array
    {
        return array_values(array_filter([
            $this->dateTimeLine($event),
            $this->locationLine($event),
            $this->costLine($event),
        ]));
    }

    private function dateTimeLine(Event $event): string
    {
        $occurrence = $event->occurrences->firstWhere('is_primary', true) ?? $event->occurrences->first();
        $startsAt = $occurrence?->starts_at;
        if ($startsAt === null) {
            return '';
        }

        $timezone = $occurrence->timezone ?: 'UTC';
        $locale = app()->getLocale();
        $localStart = $startsAt->timezone($timezone)->locale($locale);

        return $localStart->translatedFormat('j F Y').' · '.$localStart->format('H:i');
    }

    private function locationLine(Event $event): string
    {
        if ($event->delivery_mode === EventDeliveryMode::Online) {
            return __('events.share_card.online_label');
        }

        $place = trim((string) ($event->venueDetail?->place_name ?? ''));
        $city = trim((string) ($event->venueDetail?->city ?? ''));
        $parts = array_values(array_unique(array_filter([$place, $city])));

        return $parts === [] ? __('events.share_card.place_tbd') : implode(' · ', $parts);
    }

    private function costLine(Event $event): string
    {
        if (! $event->has_cost) {
            return __('events.share_card.free');
        }

        if ($event->price === null || (string) $event->price === '') {
            return __('events.share_card.paid');
        }

        $locale = app()->getLocale();
        $decimalSeparator = $locale === 'it' ? ',' : '.';
        $thousandsSeparator = $locale === 'it' ? '.' : ',';
        $amount = number_format((float) $event->price, 2, $decimalSeparator, $thousandsSeparator);
        $currency = trim((string) ($event->currency ?: 'EUR'));

        return "{$amount} {$currency}";
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

    /**
     * @return array{weekday:string,date:string,headline:string,city:string,place:string,kicker:string,lead:string,domain:string}
     */
    private function graphicBillboard(Event $event): array
    {
        $occurrence = $event->occurrences->firstWhere('is_primary', true)
            ?? $event->occurrences->sortBy('starts_at')->first();
        $weekday = '';
        $date = '';
        if ($occurrence?->starts_at !== null) {
            $local = $occurrence->starts_at
                ->timezone($occurrence->timezone ?: 'UTC')
                ->locale(app()->getLocale());
            $weekday = mb_strtolower($local->translatedFormat('l'));
            $date = mb_strtolower($local->translatedFormat('j F'));
        }

        $city = '';
        $place = '';
        if ($event->delivery_mode === EventDeliveryMode::Online) {
            $place = (string) __('events.share_card.online_label');
        } else {
            $city = trim((string) ($event->venueDetail?->city ?? ''));
            $place = trim((string) ($event->venueDetail?->place_name ?? ''));
        }

        $headline = trim((string) $event->title) ?: (string) __('events.event_title');

        return [
            'weekday' => $weekday,
            'date' => $date,
            'headline' => $headline,
            'city' => $city,
            'place' => $place,
            'kicker' => $weekday !== '' ? $weekday : $headline,
            'lead' => $headline,
            'domain' => 'slamin.it',
        ];
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
