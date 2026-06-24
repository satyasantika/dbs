<?php

namespace Database\Seeders;

use App\Models\ExamFormItem;
use App\Models\ExamType;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $examTypes = [
            ['name' => 'Ujian Proposal', 'code' => 'sempro'],
            ['name' => 'Ujian Hasil Penelitian', 'code' => 'semhas'],
            ['name' => 'Ujian Skripsi', 'code' => 'skripsi'],
        ];

        $formItems = [
            'Orisinalitas ',
            'Tata tulis ',
            'Kemampuan menjelaskan ',
            'Penguasaan materi ',
            'Bobot ilmiah ',
        ];

        foreach ($examTypes as $type) {
            $examType = ExamType::create($type);
            $suffix = $type['code'] === 'sempro' ? 'Proposal' : 'Skripsi';

            foreach ($formItems as $itemKey => $item) {
                ExamFormItem::create([
                    'exam_type_id' => $examType->id,
                    'item_order' => $itemKey + 1,
                    'name' => $item.$suffix,
                    'active' => 1,
                ]);
            }
        }
    }
}
