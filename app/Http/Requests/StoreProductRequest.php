<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Support\Locales;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'slug' => [
                'required', 'string', 'max:255',
                // Lowercase letters, digits and single hyphens only: this ends up
                // in a public URL, so anything else is a normalisation problem
                // waiting to happen.
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique(Product::class),
            ],
            'status' => ['required', Rule::in([Product::DRAFT, Product::PUBLISHED])],

            'translations' => ['required', 'array'],
            'translations.*.name' => ['required', 'string', 'max:255'],
            'translations.*.summary' => ['nullable', 'string', 'max:1000'],
            'translations.*.body' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $locales = array_keys($this->input('translations', []));

            foreach ($locales as $locale) {
                if (! Locales::isSupported($locale)) {
                    $validator->errors()->add('translations', __('Unsupported language: :locale', ['locale' => $locale]));
                }
            }

            // A product with no name in the default language would render as a
            // blank row anywhere the fallback is used.
            if (! in_array(Locales::DEFAULT, $locales, true)) {
                $validator->errors()->add(
                    'translations',
                    __('The :locale translation is required.', ['locale' => Locales::DEFAULT])
                );
            }
        });
    }
}
