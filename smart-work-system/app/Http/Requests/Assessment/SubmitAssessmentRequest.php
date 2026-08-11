<?php

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAssessmentRequest extends FormRequest
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
            'responses' => ['required', 'array', 'min:3', 'max:3'],
            'responses.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'responses.*.selected_option' => ['required', 'string', 'in:A,B,C,D'],
            'responses.*.response_time' => ['required', 'numeric', 'min:0'], // in seconds
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
            'responses.required' => 'پاسخ‌ها الزامی هستند',
            'responses.array' => 'پاسخ‌ها باید به صورت آرایه باشند',
            'responses.min' => 'باید حداقل به 3 سوال پاسخ دهید',
            'responses.max' => 'حداکثر 3 سوال مجاز است',
            'responses.*.question_id.required' => 'شناسه سوال الزامی است',
            'responses.*.question_id.exists' => 'سوال مورد نظر یافت نشد',
            'responses.*.selected_option.required' => 'گزینه انتخابی الزامی است',
            'responses.*.selected_option.in' => 'گزینه انتخابی باید A، B، C یا D باشد',
            'responses.*.response_time.required' => 'زمان پاسخ‌دهی الزامی است',
            'responses.*.response_time.numeric' => 'زمان پاسخ‌دهی باید عدد باشد',
        ];
    }
}
