<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccommodationFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,active,inactive',
            'city' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:100',
            'has_email' => 'boolean',
            'has_phone' => 'boolean',
            'has_website' => 'boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:200',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'search.max' => 'La recherche ne peut pas dépasser 255 caractères.',
            'search.string' => 'La recherche doit être une chaîne de caractères.',
            'status.in' => 'Le statut doit être : pending, active ou inactive.',
            'city.max' => 'Le nom de la ville ne peut pas dépasser 100 caractères.',
            'city.string' => 'Le nom de la ville doit être une chaîne de caractères.',
            'type.max' => 'Le type ne peut pas dépasser 100 caractères.',
            'type.string' => 'Le type doit être une chaîne de caractères.',
            'has_email.boolean' => 'Le filtre email doit être vrai ou faux.',
            'has_phone.boolean' => 'Le filtre téléphone doit être vrai ou faux.',
            'has_website.boolean' => 'Le filtre site web doit être vrai ou faux.',
            'page.integer' => 'Le numéro de page doit être un entier.',
            'page.min' => 'Le numéro de page doit être supérieur à 0.',
            'per_page.integer' => 'Le nombre d\'éléments par page doit être un entier.',
            'per_page.min' => 'Le nombre minimum d\'éléments par page est 10.',
            'per_page.max' => 'Le nombre maximum d\'éléments par page est 200.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'search' => 'recherche',
            'status' => 'statut',
            'city' => 'ville',
            'type' => 'type',
            'has_email' => 'filtre email',
            'has_phone' => 'filtre téléphone',
            'has_website' => 'filtre site web',
            'page' => 'page',
            'per_page' => 'éléments par page',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        $this->merge([
            'has_email' => $this->boolean('has_email'),
            'has_phone' => $this->boolean('has_phone'),
            'has_website' => $this->boolean('has_website'),
        ]);

        // Trim and sanitize string inputs
        if ($this->has('search')) {
            $this->merge([
                'search' => trim($this->input('search'))
            ]);
        }

        if ($this->has('city')) {
            $this->merge([
                'city' => trim($this->input('city'))
            ]);
        }

        if ($this->has('type')) {
            $this->merge([
                'type' => trim($this->input('type'))
            ]);
        }
    }

    /**
     * Get validated data with defaults
     */
    public function getFilters(): array
    {
        $validated = $this->validated();
        
        return [
            'search' => $validated['search'] ?? '',
            'status' => $validated['status'] ?? '',
            'city' => $validated['city'] ?? '',
            'type' => $validated['type'] ?? '',
            'has_email' => $validated['has_email'] ?? false,
            'has_phone' => $validated['has_phone'] ?? false,
            'has_website' => $validated['has_website'] ?? false,
            'page' => $validated['page'] ?? 1,
            'per_page' => $validated['per_page'] ?? 100,
        ];
    }

    /**
     * Check if any filter is applied
     */
    public function hasFilters(): bool
    {
        $filters = $this->getFilters();
        
        return !empty($filters['search']) ||
               !empty($filters['status']) ||
               !empty($filters['city']) ||
               !empty($filters['type']) ||
               $filters['has_email'] ||
               $filters['has_phone'] ||
               $filters['has_website'];
    }
}