<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOutgoingRequest extends FormRequest
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
            'doc_number' => ['required', 'string', 'max:255', 'unique:outgoings,doc_number,'.$this->outgoing->id],
            'issued_at' => ['required', 'date'],
            'status' => ['required', 'in:draft,completed'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.part_id' => ['required', 'uuid', 'exists:parts,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'doc_number.required' => 'Document number is required.',
            'doc_number.unique' => 'Document number already exists.',
            'issued_at.required' => 'Issue date is required.',
            'items.required' => 'At least one item is required.',
            'items.*.part_id.required' => 'Part is required.',
            'items.*.part_id.exists' => 'Selected part does not exist.',
            'items.*.qty.required' => 'Quantity is required.',
            'items.*.qty.min' => 'Quantity must be at least 1.',
        ];
    }
}
