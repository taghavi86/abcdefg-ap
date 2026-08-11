<?php

namespace App\Http\Requests\Workbench;

use Illuminate\Foundation\Http\FormRequest;

class SubmitWorkResponseRequest extends FormRequest
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
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'selected_option' => ['required', 'string', 'in:A,B,C,D'],
            'response_time' => ['required', 'numeric', 'min:0'], // in seconds
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question_id.required' => 'شناسه سوال الزامی است',
            'question_id.exists' => 'سوال مورد نظر یافت نشد',
            'selected_option.required' => 'گزینه انتخابی الزامی است',
            'selected_option.in' => 'گزینه انتخابی باید A، B، C یا D باشد',
            'response_time.required' => 'زمان پاسخ‌دهی الزامی است',
            'response_time.numeric' => 'زمان پاسخ‌دهی باید عدد باشد',
        ];
    }
}
