<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Cleans editor output before it is stored.
 *
 * A WYSIWYG editor sends HTML the user controls. Storing it as-is and rendering
 * it with v-html turns the admin panel into a stored-XSS delivery mechanism: one
 * <script> or one onerror= attribute in a product description executes for every
 * visitor of the public site. So the rule is allow-list only — anything not
 * named below is stripped, including every event handler attribute, by default.
 *
 * Sanitising on write (not on read) means the database only ever holds safe
 * HTML, so a future template that forgets to escape cannot resurrect the hole.
 */
final class RichText
{
    /**
     * Exactly what the TipTap toolbar can produce. Widening this list is a
     * security decision, not a formatting one.
     *
     * @var array<string, array<int, string>>
     */
    private const ALLOWED = [
        'p' => [],
        'br' => [],
        'hr' => [],
        'strong' => [],
        'em' => [],
        's' => [],
        'code' => [],
        'pre' => [],
        'blockquote' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
    ];

    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = new HtmlSanitizerConfig;

        foreach (self::ALLOWED as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }

        $this->sanitizer = new HtmlSanitizer(
            $config
                // javascript: and vbscript: URLs are the classic way to smuggle
                // script execution through an href the sanitizer otherwise likes.
                ->allowLinkSchemes(['https', 'http', 'mailto'])
                // data: is allowed for images only, so pasted screenshots survive.
                ->allowMediaSchemes(['https', 'http', 'data'])
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
        );
    }

    public function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $clean = trim($this->sanitizer->sanitize($html));

        // An editor left untouched still posts "<p></p>"; treat that as empty
        // rather than storing a paragraph that renders as a blank gap.
        return in_array($clean, ['', '<p></p>', '<p><br></p>'], true) ? null : $clean;
    }
}
