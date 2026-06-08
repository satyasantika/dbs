<?php

namespace Database\Seeders;

use App\Models\ExamRegistration;
use App\Models\ExamType;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExamRegistrationSeeder extends Seeder
{
    /**
     * Seed sample exam registrations using the temporary factory.
     */
    public function run(): void
    {
        $students = User::role('mahasiswa')->limit(5)->get();
        $examTypes = ExamType::all();
        $lecturers = User::role('dosen')->pluck('id');

        if ($students->isEmpty() || $examTypes->isEmpty()) {
            $this->command?->warn('ExamRegistrationSeeder: tidak ada mahasiswa atau tipe ujian, dilewati.');

            return;
        }

        foreach ($students as $student) {
            foreach ($examTypes as $examType) {
                ExamRegistration::factory()->create([
                    'user_id' => $student->id,
                    'exam_type_id' => $examType->id,
                    'registration_order' => 1,
                    'examiner1_id' => $lecturers->get(0),
                    'examiner2_id' => $lecturers->get(1),
                    'examiner3_id' => $lecturers->get(2),
                    'guide1_id' => $lecturers->get(3),
                    'guide2_id' => $lecturers->get(4),
                    'chief_id' => $lecturers->get(0),
                ]);
            }
        }
    }
}
