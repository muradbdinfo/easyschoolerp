<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $vendorId = $this->route('vendor');

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['supplier', 'contractor', 'service_provider'])],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'business_registration' => ['nullable', 'string', 'max:100'],
            'bank_details' => ['nullable', 'string'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'status' => ['required', Rule::in(['active', 'inactive', 'blacklisted'])],
            'blacklist_reason' => ['required_if:status,blacklisted', 'nullable', 'string'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vendor name is required.',
            'type.required' => 'Vendor type is required.',
            'type.in' => 'Invalid vendor type selected.',
            'email.email' => 'Please provide a valid email address.',
            'rating.min' => 'Rating must be between 0 and 5.',
            'rating.max' => 'Rating must be between 0 and 5.',
            'blacklist_reason.required_if' => 'Blacklist reason is required when status is blacklisted.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'active']);
        }

        if (!$this->has('rating')) {
            $this->merge(['rating' => 0]);
        }

        if (!$this->has('payment_terms_days')) {
            $this->merge(['payment_terms_days' => 30]);
        }
    }
}