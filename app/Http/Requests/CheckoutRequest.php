<?php

namespace App\Http\Requests;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formulaire d'achat (sans connexion) : coordonnées exigées par Chariow.
 */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Client connecté : l'achat est rattaché à son compte.
        if ($this->user()) {
            $this->merge(['email' => $this->user()->email]);
        }

        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone_country' => ['required', Rule::in(array_keys(Phone::COUNTRIES))],
            'phone_number' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ().-]{6,30}$/'],
            'accept_terms' => ['accepted'],
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
        return ['name' => 'nom', 'email' => 'adresse email', 'phone_country' => 'pays', 'phone_number' => 'téléphone'];
    }
}
