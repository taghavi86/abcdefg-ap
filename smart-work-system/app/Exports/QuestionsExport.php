<?php

namespace App\Exports;

use App\Models\Image;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuestionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $image;

    public function __construct(Image $image)
    {
        $this->image = $image;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->image->questions()
            ->orderBy('order_index')
            ->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID سوال',
            'ID تصویر',
            'عنوان تصویر',
            'متن سوال',
            'نوع سوال',
            'جواب صحیح',
            'شماره ترتیب',
            'فعال',
            'تاریخ ایجاد',
        ];
    }

    /**
     * @param mixed $question
     * @return array
     */
    public function map($question): array
    {
        return [
            $question->id,
            $question->image_id,
            $question->image->title ?? '',
            $question->question_text,
            $question->type === 'workbench' ? 'میز کار' : 'تعیین سطح',
            $question->correct_answer ?? '-',
            $question->order_index,
            $question->is_active ? 'بله' : 'خیر',
            $question->created_at->format('Y-m-d H:i:s'),
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
