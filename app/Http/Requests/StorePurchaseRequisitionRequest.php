<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequisitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'department_id' => 'required|exists:departments,id',
            'branch_id' => 'required|exists:branches,id',
            'required_by_date' => 'required|date|after:today',
            'priority' => 'required|in:low,medium,high,urgent',
            
            // Justification
            'purpose' => 'required|string|min:20|max:1000',
            'justification' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:500',
            
            // Items
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.estimated_unit_price' => 'required|numeric|min:0',
            'items.*.specifications' => 'nullable|string|max:500',
            'items.*.notes' => 'nullable|string|max:500',
            
            // Attachments
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120', // 5MB max
            
            // Meta
            'is_urgent' => 'boolean',
            'status' => 'sometimes|in:draft,submitted',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'department_id.required' => 'Please select a department',
            'branch_id.required' => 'Please select a branch',
            'required_by_date.required' => 'Please specify when you need these items',
            'required_by_date.after' => 'Required date must be in the future',
            'purpose.required' => 'Please explain why these items are needed',
            'purpose.min' => 'Purpose must be at least 20 characters',
            'items.required' => 'Please add at least one item',
            'items.min' => 'Please add at least one item',
            'items.*.item_id.required' => 'Please select an item',
            'items.*.quantity.required' => 'Please enter quantity',
            'items.*.quantity.min' => 'Quantity must be greater than 0',
            'items.*.estimated_unit_price.required' => 'Please enter estimated price',
            'attachments.*.max' => 'File size must not exceed 5MB',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'draft']);
        }

        // Set user_id from authenticated user
        $this->merge(['user_id' => auth()->id()]);

        // Set PR date to today if not provided
        if (!$this->has('pr_date')) {
            $this->merge(['pr_date' => now()->toDateString()]);
        }
    }
}