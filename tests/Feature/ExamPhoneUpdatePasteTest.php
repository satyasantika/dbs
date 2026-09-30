<?php

namespace Tests\Feature;

use App\Filament\Widgets\ExamRegistrationsByDateWidget;
use App\Models\ExamRegistration;
use App\Models\User;
use App\Support\ExamPhoneUpdatePaste;
use Database\Seeders\ExamSeeder;
use Database\Seeders\PermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExamPhoneUpdatePasteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(ExamSeeder::class);
    }

    public function test_parse_melewati_baris_judul_dan_baris_kosong(): void
    {
        $rows = ExamPhoneUpdatePaste::parse("NPM\tHP\n\n222151146\t081234567890\r\n222151147 081234567891\n");

        $this->assertSame([
            ['npm' => '222151146', 'phone' => '81234567890'],
            ['npm' => '222151147', 'phone' => '81234567891'],
        ], $rows);
    }

    public function test_widget_menyimpan_nomor_hp_hanya_untuk_ujian_pada_tanggal_terpilih(): void
    {
        $date = now()->addDays(3)->toDateString();

        $a = $this->registrationFor('222151146', $date);
        $b = $this->registrationFor('222151147', $date, '81234500000');
        $other = $this->registrationFor('222151148', now()->addDays(5)->toDateString());

        $paste = implode("\n", [
            "NPM\tHP",
            "222151146\t081234567890",
            "222151147\t081234567891",
            "222151148\t081234567892",
            "222151149\tbukan-nomor",
        ]);

        $admin = User::factory()->create()->assignRole('admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($admin)
            ->test(ExamRegistrationsByDateWidget::class)
            ->call('selectExamDate', $date)
            ->mountTableAction('pasteExamPhoneUpdate')
            ->setTableActionData(['paste' => $paste])
            ->assertSee('Diisi')
            ->assertSee('Diganti')
            ->assertSee('Tidak ujian di tanggal ini')
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors()
            ->assertNotified('Nomor HP disimpan');

        $this->assertSame('81234567890', $a->student->fresh()->phone);
        $this->assertSame('81234567891', $b->student->fresh()->phone);
        $this->assertNull($other->student->fresh()->phone);
    }

    private function registrationFor(string $npm, string $date, ?string $phone = null): ExamRegistration
    {
        $student = User::factory()->create(['username' => $npm, 'phone' => $phone])->assignRole('mahasiswa');

        return ExamRegistration::factory()
            ->withoutSync()
            ->forStudent($student)
            ->create([
                'exam_date' => $date,
                'exam_time' => '09:00:00',
            ]);
    }
}
