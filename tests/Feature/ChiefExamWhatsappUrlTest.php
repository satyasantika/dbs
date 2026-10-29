<?php

namespace Tests\Feature;

use App\Filament\Dosen\Pages\ViewChiefExam;
use App\Models\ExamRegistration;
use App\Models\ExamScore;
use App\Models\User;
use ReflectionMethod;
use Tests\TestCase;

class ChiefExamWhatsappUrlTest extends TestCase
{
    public function test_pesan_wa_ke_penguji_mengarah_ke_sintesys(): void
    {
        $registration = new ExamRegistration(['exam_date' => '2026-10-20']);
        $registration->setRelation('student', new User(['name' => 'Budi']));

        $score = new ExamScore;
        $score->setRelation('lecture', new User(['phone' => '81234567890']));
        $score->setRelation('registration', $registration);

        $method = new ReflectionMethod(ViewChiefExam::class, 'buildWhatsappUrl');
        $url = $method->invoke((new \ReflectionClass(ViewChiefExam::class))->newInstanceWithoutConstructor(), $score);

        $this->assertStringContainsString('phone=6281234567890', $url);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertStringContainsString('https://sintesys.unsil.ac.id', $query['text']);
        $this->assertStringNotContainsString('scoring', $query['text']);
        $this->assertStringNotContainsString('http://', $query['text']);
    }
}
