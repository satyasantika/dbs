<?php

namespace Tests\Feature;

use App\Filament\Informasi\Pages\Beranda;
use App\Filament\Widgets\ExamRegistrationsByDateWidget;
use App\Models\ExamRegistration;
use App\Models\User;
use App\Support\ExamFileLinkPaste;
use Database\Seeders\ExamSeeder;
use Database\Seeders\PermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExamFileLinkPasteTest extends TestCase
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
        $rows = ExamFileLinkPaste::parse("NPM\tLink\n\n222151146\thttps://drive.google.com/a\r\n222151147 https://drive.google.com/b\n");

        $this->assertSame([
            ['npm' => '222151146', 'link' => 'https://drive.google.com/a'],
            ['npm' => '222151147', 'link' => 'https://drive.google.com/b'],
        ], $rows);
    }

    public function test_widget_menyimpan_link_hanya_untuk_ujian_pada_tanggal_terpilih(): void
    {
        $date = now()->addDays(3)->toDateString();

        $a = $this->registrationFor('222151146', $date);
        $b = $this->registrationFor('222151147', $date, 'https://drive.google.com/lama');
        $other = $this->registrationFor('222151148', now()->addDays(5)->toDateString());

        $paste = implode("\n", [
            "NPM\tLink",
            "222151146\thttps://drive.google.com/a",
            "222151147\thttps://drive.google.com/b",
            "222151148\thttps://drive.google.com/c",
            "222151149\tbukan-link",
        ]);

        $admin = User::factory()->create()->assignRole('admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($admin)
            ->test(ExamRegistrationsByDateWidget::class)
            ->call('selectExamDate', $date)
            ->mountTableAction('pasteExamFileLinks')
            ->setTableActionData(['paste' => $paste])
            ->assertSee('Diisi')
            ->assertSee('Diganti')
            ->assertSee('Tidak ujian di tanggal ini')
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors()
            ->assertNotified('Link file ujian disimpan');

        $this->assertSame('https://drive.google.com/a', $a->fresh()->exam_file);
        $this->assertSame('https://drive.google.com/b', $b->fresh()->exam_file);
        $this->assertNull($other->fresh()->exam_file);
    }

    public function test_beranda_menampilkan_link_file_ujian(): void
    {
        $registration = $this->registrationFor('222151146', now()->addDays(2)->toDateString(), 'https://drive.google.com/folder-x');

        Livewire::test(Beranda::class)
            ->assertSee('Link File Ujian')
            ->assertSee('https://drive.google.com/folder-x', false);

        $registration->update(['exam_file' => null]);

        Livewire::test(Beranda::class)->assertDontSee('Link File Ujian');
    }

    private function registrationFor(string $npm, string $date, ?string $examFile = null): ExamRegistration
    {
        $student = User::factory()->create(['username' => $npm])->assignRole('mahasiswa');

        return ExamRegistration::factory()
            ->withoutSync()
            ->forStudent($student)
            ->create([
                'exam_date' => $date,
                'exam_time' => '09:00:00',
                'exam_file' => $examFile,
            ]);
    }
}
