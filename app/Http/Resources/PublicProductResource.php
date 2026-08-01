<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the public site is allowed to know about a product.
 *
 * Built as an allow-list rather than by hiding fields from the admin resource:
 * a new column added later is invisible here until someone decides it should be
 * public, which is the safe direction for that mistake to fail in.
 *
 * There is no id. The slug is the public identifier, so nothing here invites a
 * caller to start counting rows.
 *
 * @mixin Product
 */
class PublicProductResource extends JsonResource
{
    private bool $includeBody = false;

    /**
     * The listing does not need the full article, and sending it would mean
     * shipping every product's entire body to render a grid of cards.
     */
    public function withBody(): static
    {
        $this->includeBody = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Falls back to the default locale, so a product written in only one
        // language still renders rather than showing an empty card.
        $translation = $this->translate();

        return [
            'slug' => $this->slug,
            'name' => $translation?->name,
            'summary' => $translation?->summary,
            'cover_url' => $this->coverUrl(),
            'published_at' => $this->published_at?->toIso8601String(),
            // Already sanitised on write, which is what makes it safe to render
            // as HTML on a page served to everyone.
            ...($this->includeBody ? ['body' => $translation?->body] : []),
        ];
    }
}
