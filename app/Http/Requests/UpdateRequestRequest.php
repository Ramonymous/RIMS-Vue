<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequestRequest extends FormRequest
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
        $requestId = $this->route('request');

        return [
            'request_number' => ['required', 'string', 'max:255', Rule::unique('requests', 'request_number')->ignore($requestId)],
            'requested_at' => ['required', 'date'],
            'destination' => ['required', 'string', 'in:Line KS,Line SU2ID'],
            'status' => ['required', 'in:draft,completed'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.part_id' => ['required', 'uuid', 'exists:parts,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.is_urgent' => ['boolean'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'request_number.required' => 'Request number is required.',
            'request_number.unique' => 'Request number already exists.',
            'requested_at.required' => 'Request date is required.',
            'destination.required' => 'Destination is required.',
            'destination.in' => 'Invalid destination selected.',
            'items.required' => 'At least one item is required.',
            'items.*.part_id.required' => 'Part is required.',
            'items.*.part_id.exists' => 'Selected part does not exist.',
            'items.*.qty.required' => 'Quantity is required.',
            'items.*.qty.min' => 'Quantity must be at least 1.',
        ];
    }
}
