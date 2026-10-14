<?php

namespace Domain\Poetry\Support;

use Domain\Poetry\Models\Poem;
use Domain\Share\Support\ShareCardLogo;
use Domain\Share\Support\SlaminShareMark;
use GdImage;
use RuntimeException;

class PoemShareCardRenderer
{
    private const SIZE = 1080;

    private const BACKGROUND = [107, 33, 168];

    public function render(Poem $poem): string
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagettftext')) {
            throw new RuntimeException('GD with FreeType is required to render poem share cards.');
        }

        $regular = $this->fontPath('Switzer-Regular.otf');
        $medium = $this->fontPath('Switzer-Medium.otf');
        $semibold = $this->fontPath('Switzer-Semibold.otf');

        $image = imagecreatetruecolor(self::SIZE, self::SIZE);
        if ($image === false) {
            throw new RuntimeException('Unable to allocate the poem share card canvas.');
        }

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $purple = imagecolorallocate($image, self::BACKGROUND[0], self::BACKGROUND[1], self::BACKGROUND[2]);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, self::SIZE, self::SIZE, $purple);

        $previousLocale = app()->getLocale();
        $locale = in_array($poem->language, ['it', 'en'], true) ? $poem->language : 'it';
        app()->setLocale($locale);

        try {
            return $this->paint($poem, $image, $regular, $medium, $semibold, $purple, $white);
        } finally {
            app()->setLocale($previousLocale);
            imagedestroy($image);
        }
    }

    private function paint(
        Poem $poem,
        GdImage $image,
        string $regular,
        string $medium,
        string $semibold,
        int $purple,
        int $white,
    ): string {
        $authorName = $this->authorFirstName($poem);
        $placeLine = $this->placeLine($poem);
        $bubble = $this->measureBubble($semibold, $regular, $authorName, $placeLine);

        $paddingX = 80;
        $paddingTop = 96;
        $textMaxWidth = self::SIZE - ($paddingX * 2);
        $textMaxBottom = min($bubble['y'] - 48, ShareCardLogo::destY(self::SIZE) - ShareCardLogo::TEXT_GAP);

        $poemText = $this->poemBody($poem);
        [$fontSize, $lines] = $this->fitPoemLines(
            font: $medium,
            text: $poemText,
            maxWidth: $textMaxWidth,
            maxBottom: $textMaxBottom,
            startY: $paddingTop,
        );

        $lineHeight = (int) round($fontSize * 1.36);
        $y = $paddingTop;
        foreach ($lines as $line) {
            $box = $this->textMetrics($medium, $fontSize, $line === '' ? ' ' : $line);
            imagettftext($image, $fontSize, 0, $paddingX, $y + $box['ascent'], $white, $medium, $line);
            $y += $lineHeight;
        }

        $this->drawSlaminMark($image, $white, $purple, $semibold, $regular, $authorName, $placeLine, $bubble);
        ShareCardLogo::stamp($image);

        ob_start();
        imagepng($image, null, 8);
        $png = (string) ob_get_clean();

        if ($png === '' || ! str_starts_with($png, "\x89PNG")) {
            throw new RuntimeException('Failed to encode the poem share card.');
        }

        return $png;
    }

    private function fitPoemLines(string $font, string $text, int $maxWidth, int $maxBottom, int $startY): array
    {
        $untruncated = null;

        for ($size = 48; $size >= 34; $size -= 2) {
            $lineHeight = (int) round($size * 1.36);
            $maxLines = max(1, (int) floor(($maxBottom - $startY) / $lineHeight));
            $lines = $this->wrapText($font, $size, $text, $maxWidth);
            $untruncated = $lines;

            if (count($lines) <= $maxLines) {
                return [$size, $lines];
            }
        }

        $size = 34;
        $lineHeight = (int) round($size * 1.36);
        $maxLines = max(1, (int) floor(($maxBottom - $startY) / $lineHeight));

        return [$size, $this->truncateLines($font, $size, $untruncated ?? [], $maxWidth, $maxLines)];
    }

    /**
     * @return list<string>
     */
    private function wrapText(string $font, float $size, string $text, int $maxWidth): array
    {
        $paragraphs = preg_split("/\n/u", $text) ?: [''];
        $lines = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                if ($lines !== []) {
                    $lines[] = '';
                }

                continue;
            }

            $words = preg_split('/\s+/u', $paragraph) ?: [$paragraph];
            $current = '';

            foreach ($words as $word) {
                $candidate = $current === '' ? $word : $current.' '.$word;
                if ($this->textMetrics($font, $size, $candidate)['width'] <= $maxWidth) {
                    $current = $candidate;

                    continue;
                }

                if ($current !== '') {
                    $lines[] = $current;
                }

                if ($this->textMetrics($font, $size, $word)['width'] <= $maxWidth) {
                    $current = $word;

                    continue;
                }

                foreach ($this->splitLongWord($font, $size, $word, $maxWidth) as $chunk) {
                    $lines[] = $chunk;
                }
                $current = '';
            }

            if ($current !== '') {
                $lines[] = $current;
            }
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

    /**
     * @return list<string>
     */
    private function splitLongWord(string $font, float $size, string $word, int $maxWidth): array
    {
        $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [$word];
        $chunks = [];
        $current = '';

        foreach ($chars as $char) {
            $candidate = $current.$char;
            if ($current !== '' && $this->textMetrics($font, $size, $candidate)['width'] > $maxWidth) {
                $chunks[] = $current;
                $current = $char;

                continue;
            }
            $current = $candidate;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
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
     * @return array{x: int, y: int, width: int, height: int, bodyHeight: int, paddingX: int, paddingY: int, nameSize: int, placeSize: int}
     */
    private function measureBubble(string $nameFont, string $placeFont, string $authorName, ?string $placeLine): array
    {
        $nameSize = 32;
        $placeSize = 22;
        $paddingX = 28;
        $paddingY = 22;

        $nameBox = $this->textMetrics($nameFont, $nameSize, $authorName);
        $placeBox = $placeLine
            ? $this->textMetrics($placeFont, $placeSize, $placeLine)
            : ['width' => 0, 'height' => 0];

        $innerWidth = (int) max($nameBox['width'], $placeBox['width']);
        $innerHeight = (int) ($nameBox['height'] + ($placeLine ? 8 + $placeBox['height'] : 0));
        $bodyWidth = $innerWidth + ($paddingX * 2);
        $bodyHeight = $innerHeight + ($paddingY * 2);
        $height = SlaminShareMark::totalHeight($bodyHeight);
        $width = $bodyWidth;

        return [
            'x' => self::SIZE - 56 - $width,
            'y' => self::SIZE - 52 - $height,
            'width' => $width,
            'height' => $height,
            'bodyHeight' => $bodyHeight,
            'paddingX' => $paddingX,
            'paddingY' => $paddingY,
            'nameSize' => $nameSize,
            'placeSize' => $placeSize,
        ];
    }

    /**
     * @param  array{x: int, y: int, width: int, height: int, bodyHeight: int, paddingX: int, paddingY: int, nameSize: int, placeSize: int}  $bubble
     */
    private function drawSlaminMark(
        GdImage $image,
        int $white,
        int $purple,
        string $nameFont,
        string $placeFont,
        string $authorName,
        ?string $placeLine,
        array $bubble,
    ): void {
        $x = $bubble['x'];
        $y = $bubble['y'];

        SlaminShareMark::fill(
            $image,
            $x,
            $y,
            $bubble['width'],
            $bubble['height'],
            $bubble['bodyHeight'],
            $white,
        );

        $nameBox = $this->textMetrics($nameFont, $bubble['nameSize'], $authorName);
        $textX = $x + $bubble['paddingX'];
        $nameY = $y + $bubble['paddingY'] + $nameBox['ascent'];
        imagettftext($image, $bubble['nameSize'], 0, $textX, $nameY, $purple, $nameFont, $authorName);

        if ($placeLine) {
            $placeBox = $this->textMetrics($placeFont, $bubble['placeSize'], $placeLine);
            $placeY = $nameY + 8 + $placeBox['ascent'];
            imagettftext($image, $bubble['placeSize'], 0, $textX, $placeY, $purple, $placeFont, $placeLine);
        }
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

    private function poemBody(Poem $poem): string
    {
        $raw = html_entity_decode(strip_tags((string) $poem->content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $raw = preg_replace("/\r\n|\r/u", "\n", $raw) ?? $raw;
        $raw = trim($raw);

        if ($raw !== '') {
            return $raw;
        }

        return trim((string) $poem->title);
    }

    private function authorFirstName(Poem $poem): string
    {
        $displayName = trim((string) ($poem->user?->profile?->display_name ?? ''));
        if ($displayName !== '') {
            $parts = preg_split('/\s+/u', $displayName) ?: [$displayName];

            return mb_substr((string) $parts[0], 0, 22);
        }

        $nickname = trim((string) ($poem->user?->profile?->nickname ?? ''));
        if ($nickname !== '' && ! preg_match('/^user[0-9a-f]{12,}$/i', $nickname)) {
            return mb_substr($nickname, 0, 22);
        }

        return 'Slam In';
    }

    private function placeLine(Poem $poem): ?string
    {
        $profile = $poem->user?->profile;
        $location = trim((string) ($profile?->location ?? ''));
        $visibility = is_array($profile?->visibility) ? $profile->visibility : [];

        if (array_key_exists('location', $visibility) && $visibility['location'] === false) {
            $location = '';
        }

        if ($location === '') {
            return __('poems.share_card.without_place');
        }

        return __('poems.share_card.from', ['place' => $location]);
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
