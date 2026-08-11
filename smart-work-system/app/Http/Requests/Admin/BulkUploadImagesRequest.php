<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkUploadImagesRequest extends FormRequest
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
            'images' => ['required', 'array', 'min:1', 'max:300'],
            'images.*' => ['image', 'max:5120'], // Max 5MB each
            'questions_per_image' => ['required', 'integer', 'min:1', 'max:10'],
            'is_active' => ['boolean'],
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
            'images.required' => 'تصاویر الزامی هستند',
            'images.array' => 'تصاویر باید به صورت آرایه باشند',
            'images.min' => 'حداقل یک تصویر الزامی است',
            'images.max' => 'حداکثر 300 تصویر مجاز است',
            'images.*.image' => 'همه فایل‌ها باید تصویر باشند',
            'questions_per_image.required' => 'تعداد سوالات برای هر تصویر الزامی است',
            'questions_per_image.integer' => 'تعداد سوالات باید عدد باشد',
        ];
    }
}
