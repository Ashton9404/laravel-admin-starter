<?php

namespace App\Models;

use App\Contracts\Loggable;
use App\Models\Concerns\LogsActivity;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['slug', 'status', 'sort_order', 'cover_path', 'published_at'])]
class Product extends Model implements Loggable
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, LogsActivity;

    public const DRAFT = 'draft';

    public const PUBLISHED = 'published';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    /**
     * @return HasMany<ProductTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    /**
     * The translation for a locale, falling back to the application default so
     * a product written in only one language still renders everywhere.
     */
    public function translate(?string $locale = null): ?ProductTranslation
    {
        $locale ??= app()->getLocale();

        return $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', config('app.fallback_locale'))
            ?? $this->translations->first();
    }

    /**
     * The slug rather than the translated name, on purpose. By the time a
     * deletion is logged the translation rows are already gone with it, and a
     * slug is what the public URL shows anyway.
     */
    public function activityLabel(): string
    {
        return $this->slug;
    }

    public function isPublished(): bool
    {
        return $this->status === self::PUBLISHED;
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::PUBLISHED);
    }

    /**
     * The order the public site displays products in. `id` breaks ties so the
     * sequence is stable — without it, equal sort_order values come back in
     * whatever order the database feels like today.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
