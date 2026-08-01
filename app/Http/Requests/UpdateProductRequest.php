<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Support\Locales;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'slug' => [
                'sometimes', 'string', 'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique(Product::class)->ignore($this->route('product')),
            ],
            'status' => ['sometimes', Rule::in([Product::DRAFT, Product::PUBLISHED])],

            'translations' => ['sometimes', 'array'],
            'translations.*.name' => ['required', 'string', 'max:255'],
            'translations.*.summary' => ['nullable', 'string', 'max:1000'],
            'translations.*.body' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach (array_keys($this->input('translations', [])) as $locale) {
                if (! Locales::isSupported($locale)) {
                    $validator->errors()->add('translations', __('Unsupported language: :locale', ['locale' => $locale]));
                }
            }
        });
    }
}
