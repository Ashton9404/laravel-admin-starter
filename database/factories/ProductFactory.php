<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => Str::slug(fake()->unique()->words(3, true)),
            'status' => Product::DRAFT,
            'sort_order' => 0,
            'cover_path' => null,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => Product::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function atPosition(int $position): static
    {
        return $this->state(fn () => ['sort_order' => $position]);
    }

    /**
     * @param  array<string, string>  $names  locale => product name
     */
    public function withTranslations(array $names = ['en' => 'Test Product']): static
    {
        return $this->afterCreating(function (Product $product) use ($names) {
            foreach ($names as $locale => $name) {
                // Created through the relation so the foreign key is set by
                // Eloquent — product_id is not fillable, so passing it in an
                // array would be dropped without a word.
                $product->translations()->create([
                    'locale' => $locale,
                    'name' => $name,
                    'summary' => 'A short summary.',
                    'body' => '<p>Body copy.</p>',
                ]);
            }
        });
    }
}
