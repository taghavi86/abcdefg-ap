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
            'user_answer' => ['required', 'string', 'max:100'], // Numeric answer as string
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
            'user_answer.required' => 'پاسخ سوال الزامی است',
            'user_answer.string' => 'پاسخ باید متنی باشد',
            'response_time.required' => 'زمان پاسخ‌دهی الزامی است',
            'response_time.numeric' => 'زمان پاسخ‌دهی باید عدد باشد',
        ];
    }
}
