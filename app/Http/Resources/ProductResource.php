<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'status' => $this->status,
            'published' => $this->isPublished(),
            'sort_order' => $this->sort_order,
            'cover_url' => $this->coverUrl(),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // The admin editor needs every language at once; the public site
            // only ever wants the visitor's. Both are served from here.
            'translations' => $this->whenLoaded('translations', fn () => $this->translations
                ->mapWithKeys(fn ($translation) => [
                    $translation->locale => [
                        'name' => $translation->name,
                        'summary' => $translation->summary,
                        'body' => $translation->body,
                    ],
                ])),
        ];
    }
}
