<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
{
    /**
     * Columns the client is allowed to sort by.
     *
     * Whitelisted rather than passed through: orderBy() interpolates its column
     * straight into the SQL, so an unchecked value is an injection point.
     */
    public const SORTABLE = ['name', 'email', 'created_at'];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role' => ['sometimes', 'nullable', 'string', 'exists:roles,name'],
            'verified' => ['sometimes', 'nullable', 'boolean'],
            'sort' => ['sometimes', Rule::in(self::SORTABLE)],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:5', 'max:100'],
        ];
    }
}
