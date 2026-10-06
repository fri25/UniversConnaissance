<?php

namespace App\Http\Requests;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('purchase', $this->route('book'));
    }

    public function rules(): array
    {
        return [
            'accept_terms' => ['accepted'],
            'phone_country' => ['required', Rule::in(array_keys(Phone::COUNTRIES))],
            'phone_number' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ().-]{6,30}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'accept_terms.accepted' => 'Vous devez accepter les conditions générales de vente.',
            'phone_number.regex' => 'Le numéro de téléphone n\'est pas valide.',
        ];
    }

    public function attributes(): array
    {
        return ['phone_country' => 'pays', 'phone_number' => 'téléphone'];
    }
}
