<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\ExamFormItem;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tipe Ujian
        $exam_types = ['Proposal', 'Hasil Penelitian','Skripsi'];

        foreach ($exam_types as $type_key => $type) {
            ExamType::create(['name'=>'Ujian '.$type]);
            $form_items = ['Orisinalitas ','Tata tulis ','Kemampuan menjelaskan ','Penguasaan materi ','Bobot ilmiah '];

            foreach ($form_items as $item_key => $item) {
                ExamFormItem::create([
                    'exam_type_id'=>$type_key+1,
                    'order'=>$item_key+1,
                    'name'=>$item.($type=='Proposal'?'Proposal':'Skripsi'),
                    'is_active'=>1,
                ]);
            }
        }
    }
}
