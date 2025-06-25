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
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,active,inactive',
            'city' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'has_email' => 'nullable|boolean',
            'has_phone' => 'nullable|boolean',
            'has_website' => 'nullable|boolean',
        ];
    }

    public function validationData(): array
    {
        return $this->query();
    }
}
