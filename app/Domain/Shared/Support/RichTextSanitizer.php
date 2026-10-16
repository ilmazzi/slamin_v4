<?php

namespace Domain\Shared\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class RichTextSanitizer
{
    private const MAX_LENGTH = 250_000;

    private readonly HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->allowElement('p', ['data-text-align'])
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('h2', ['data-text-align'])
            ->allowElement('h3', ['data-text-align'])
            ->allowElement('blockquote')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('hr')
            ->allowElement('a', ['href', 'title'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks()
            ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
            ->withMaxInputLength(self::MAX_LENGTH);

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function sanitize(string $html): string
    {
        return trim($this->sanitizer->sanitize($this->normalizeTextAlignment($html)));
    }

    public function toPlainText(string $html): string
    {
        $sanitized = $this->sanitize($html);
        $sanitized = preg_replace('/<br\s*\/?>/i', "\n", $sanitized) ?? $sanitized;
        $sanitized = preg_replace('/<\/(?:p|h2|h3|blockquote|li)>/i', "\n", $sanitized) ?? $sanitized;
        $text = strip_tags($sanitized);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/u", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function normalizeTextAlignment(string $html): string
    {
        if ($html === '' || (! str_contains($html, 'style=') && ! str_contains($html, 'data-text-align'))) {
            return $html;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousState = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        $xpath = new DOMXPath($document);

        foreach ($xpath->query('//*[@style or @data-text-align]') ?: [] as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }

            $alignment = strtolower(trim($element->getAttribute('data-text-align')));
            $style = $element->getAttribute('style');

            if (preg_match('/(?:^|;)\s*text-align\s*:\s*(left|center|right|justify)\s*(?:;|$)/i', $style, $matches)) {
                $alignment = strtolower($matches[1]);
            }

            $element->removeAttribute('style');

            if (in_array($alignment, ['left', 'center', 'right', 'justify'], true)) {
                $element->setAttribute('data-text-align', $alignment);
            } else {
                $element->removeAttribute('data-text-align');
            }
        }

        $body = $document->getElementsByTagName('body')->item(0);
        $normalized = '';

        if ($body) {
            foreach ($body->childNodes as $child) {
                $normalized .= $document->saveHTML($child);
            }
        }

        return $normalized;
    }
}
