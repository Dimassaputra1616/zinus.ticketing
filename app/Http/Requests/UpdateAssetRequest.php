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
                'nullable',
                'string',
                'max:191',
                Rule::unique('assets', 'asset_code')->ignore($this->asset),
            ],
            'name' => ['required', 'string', 'max:255'],
            'hostname' => [
                'nullable',
                'string',
                'max:191',
                Rule::unique('assets', 'hostname')->ignore($this->asset),
            ],
            'category' => ['required', 'string', 'max:100'],
            'sub_category' => ['nullable', 'string', 'max:100'],
            'factory' => ['nullable', 'string', 'max:150'],
            'brand' => ['nullable', 'string', 'max:150'],
            'model' => ['nullable', 'string', 'max:150'],
            'serial_number' => [
                'nullable',
                'string',
                'max:191',
                Rule::unique('assets', 'serial_number')->ignore($this->asset),
            ],
            'cpu' => ['nullable', 'string', 'max:255'],
            'ram_gb' => ['nullable', 'integer', 'min:0'],
            'storage_gb' => ['nullable', 'integer', 'min:0'],
            'os_name' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:150'],
            'specs' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(Asset::STATUSES)],
            'condition' => ['nullable', 'string', 'in:good,minor_issue,damaged,repair,disposed,lost'],
            'lifecycle_status' => ['nullable', 'string', 'in:active,in_repair,spare,assigned,disposed,lost,replaced'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_expired' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'warranty_until' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'rustdesk_id' => ['nullable', 'string', 'max:100'],
            'anydesk_id' => ['nullable', 'string', 'max:100'],
            'source_type' => ['nullable', 'string', 'in:agent,manual,import_excel'],
        ];
    }
}
