<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:120'],
            'author' => ['nullable', 'string', 'max:120'],
            'language' => ['nullable', Rule::in(array_keys(Book::LANGUAGES))],
            'format' => ['nullable', Rule::in(['pdf', 'epub'])],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'promo' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['recent', 'popular', 'rating', 'price_asc', 'price_desc', 'title'])],
        ];
    }

    /**
     * Un filtre invalide ne doit pas casser la navigation : on l'ignore.
     */
    protected function failedValidation($validator): void
    {
        foreach (array_keys($validator->failed()) as $key) {
            $this->query->remove($key);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return array_filter(
            $this->only(array_keys($this->rules())),
            fn ($value) => $value !== null && $value !== '',
        );
    }
}
