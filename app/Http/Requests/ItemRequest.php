<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
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
        $itemId = $this->route('item');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:item_categories,id'],
            'type' => ['required', Rule::in(['consumable', 'asset', 'both'])],
            'unit' => ['required', 'string', 'max:20'],
            'unit_secondary' => ['nullable', 'string', 'max:20'],
            'conversion_factor' => ['nullable', 'numeric', 'min:0'],
            'current_price' => ['required', 'numeric', 'min:0'],
            'min_stock_level' => ['nullable', 'numeric', 'min:0'],
            'max_stock_level' => ['nullable', 'numeric', 'min:0', 'gte:min_stock_level'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_consumable' => ['boolean'],
            'is_asset' => ['boolean'],
            'asset_threshold_amount' => ['nullable', 'numeric', 'min:0'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'specifications' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'], // 2MB max
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('items', 'barcode')->ignore($itemId)
            ],
            'sku' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive', 'discontinued'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Item name is required.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'type.required' => 'Item type is required.',
            'type.in' => 'Invalid item type selected.',
            'unit.required' => 'Unit of measurement is required.',
            'current_price.required' => 'Current price is required.',
            'current_price.min' => 'Price cannot be negative.',
            'max_stock_level.gte' => 'Maximum stock level must be greater than or equal to minimum stock level.',
            'photo.image' => 'File must be an image.',
            'photo.max' => 'Image size must not exceed 2MB.',
            'barcode.unique' => 'This barcode is already in use.',
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

        if (!$this->has('is_consumable')) {
            $this->merge(['is_consumable' => $this->type === 'consumable' || $this->type === 'both']);
        }

        if (!$this->has('is_asset')) {
            $this->merge(['is_asset' => $this->type === 'asset' || $this->type === 'both']);
        }

        if (!$this->has('lead_time_days')) {
            $this->merge(['lead_time_days' => 7]);
        }
    }
}