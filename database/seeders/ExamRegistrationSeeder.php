<?php

namespace Database\Seeders;

use App\Models\ExamRegistration;
use App\Models\ExamType;
use App\Models\GuideExaminer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ExamRegistrationSeeder extends Seeder
{
    /**
     * Simulasi data ujian: guide_examiners → exam_registrations → exam_scores (via sync).
     */
    public function run(): void
    {
        $students = User::role('mahasiswa')->limit(8)->get();
        $examTypes = ExamType::all();
        $lecturers = User::role('dosen')->pluck('id');

        if ($students->isEmpty() || $examTypes->isEmpty()) {
            $this->command?->warn('ExamRegistrationSeeder: tidak ada mahasiswa atau tipe ujian, dilewati.');

            return;
        }

        if ($lecturers->count() < 5) {
            $this->command?->warn('ExamRegistrationSeeder: minimal 5 dosen diperlukan untuk slot penguji, dilewati.');

            return;
        }

        foreach ($students as $index => $student) {
            $lecturerOffset = $index % max(1, $lecturers->count() - 4);

            $guideExaminer = GuideExaminer::factory()
                ->forStudent($student)
                ->create($this->lecturerSlots($lecturers, $lecturerOffset));

            $this->seedRegistrationsForStudent($guideExaminer, $examTypes, $index);
        }
    }

    /**
     * @param  Collection<int, \App\Models\ExamType>  $examTypes
     */
    protected function seedRegistrationsForStudent(GuideExaminer $guideExaminer, Collection $examTypes, int $studentIndex): void
    {
        foreach ($examTypes as $typeIndex => $examType) {
            $factory = ExamRegistration::factory()
                ->forGuideExaminer($guideExaminer, $examType)
                ->state([
                    'exam_date' => fake()->dateTimeBetween('-6 months', '+2 months')->format('Y-m-d'),
                    'exam_time' => fake()->time('H:i:s'),
                    'title' => fake()->sentence(6),
                ]);

            $factory = match ($studentIndex % 4) {
                0 => $typeIndex === 0
                    ? $factory->fullyScored()->invited()->sentToStudent()
                    : ($typeIndex === 1 ? $factory->partiallyScored()->invited() : $factory->invited()),
                1 => $typeIndex === 0 ? $factory->partiallyScored() : $factory,
                2 => $typeIndex === 2 ? $factory->fullyScored() : $factory,
                3 => $factory,
                default => $typeIndex === 1 ? $factory->invited() : $factory,
            };

            $factory->create();
        }
    }

    /**
     * @param  Collection<int, int>  $lecturers
     * @return array<string, int|null>
     */
    protected function lecturerSlots(Collection $lecturers, int $offset): array
    {
        $pick = fn (int $slot) => $lecturers->get(($offset + $slot) % $lecturers->count());

        return [
            'examiner1_id' => $pick(0),
            'examiner2_id' => $pick(1),
            'examiner3_id' => $pick(2),
            'guide1_id' => $pick(3),
            'guide2_id' => $pick(4),
            'chief_id' => $pick(0),
        ];
    }
}
