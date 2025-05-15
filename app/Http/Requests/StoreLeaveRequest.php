<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the Policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:500'],
            'type' => ['required', 'string', 'in:sick,vacation,personal,emergency,marriage,maternity'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'Leave cannot be requested for past dates',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
            'documents.max' => 'Supporting document must not exceed 2MB',
        ];
    }
}
