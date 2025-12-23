<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'asset_code' => [
                'required',
                'string',
                'max:191',
                Rule::unique('assets', 'asset_code')->ignore($this->asset),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(['PC', 'Laptop', 'Monitor', 'Peripheral'])],
            'factory' => ['nullable', 'string', 'max:150'],
            'brand' => ['nullable', 'string', 'max:150'],
            'model' => ['nullable', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:191'],
            'specs' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(Asset::STATUSES)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_expired' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
