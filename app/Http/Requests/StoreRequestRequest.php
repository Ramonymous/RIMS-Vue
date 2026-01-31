<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestRequest extends FormRequest
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
            'request_number' => ['nullable', 'string', 'max:255', 'unique:requests,request_number'],
            'requested_at' => ['required', 'date'],
            'destination' => ['required', 'string', 'in:Line KS,Line SU2ID'],
            'status' => ['required', 'in:draft,completed'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.part_id' => ['required', 'uuid', 'exists:parts,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.is_urgent' => ['required', 'boolean'],
            'items.*.is_supplied' => ['required', 'boolean'],
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
