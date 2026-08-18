<?php

namespace App\Exports;

use App\Models\UserResponse;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserResponsesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->query
            ->with(['user:id,name,family,email', 'question:id,question_text,image_id', 'image:id,title,image_path'])
            ->orderBy('image_id')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID پاسخ',
            'ID کاربر',
            'نام کاربر',
            'نام خانوادگی',
            'ایمیل',
            'ID تصویر',
            'عنوان تصویر',
            'ID سوال',
            'متن سوال',
            'پاسخ کاربر',
            'جواب صحیح',
            'آیا صحیح است',
            'زمان پاسخ (ثانیه)',
            'امتیاز دقت',
            'امتیاز سرعت',
            'سکه کل',
            'نوع',
            'تعیین سطح',
            'وضعیت',
            'تاریخ پاسخ',
        ];
    }

    /**
     * @param mixed $response
     * @return array
     */
    public function map($response): array
    {
        return [
            $response->id,
            $response->user_id,
            $response->user->name ?? '',
            $response->user->family ?? '',
            $response->user->email ?? '',
            $response->image_id,
            $response->image->title ?? '',
            $response->question_id,
            $response->question->question_text ?? '',
            $response->user_answer ?? '-',
            $response->question->correct_answer ?? '-',
            $response->is_correct !== null ? ($response->is_correct ? 'بله' : 'خیر') : 'در انتظار بررسی',
            $response->response_time_seconds,
            $response->accuracy_score_earned,
            $response->speed_score_earned,
            $response->total_coins_earned,
            $response->type === 'workbench' ? 'میز کار' : 'تعیین سطح',
            $response->is_level_test ? 'بله' : 'خیر',
            $response->status,
            $response->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
