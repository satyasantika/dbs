<?php

namespace App\Filament\Resources\ExamRegistrationResource\Pages;

use App\Filament\Resources\ExamRegistrationResource;
use App\Models\ExamRegistration;
use App\Models\ExamScore;
use App\Models\GuideExaminer;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditExamRegistration extends EditRecord
{
    protected static string $resource = ExamRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send_result')
                ->label(fn () => $this->record?->sent_at
                    ? 'Kirim Ulang Hasil (' . $this->record->sent_at->format('d/m/Y') . ')'
                    : 'Kirim Hasil ke Mahasiswa')
                ->icon('heroicon-o-paper-airplane')
                ->color(fn () => $this->record?->sent_at ? 'gray' : 'success')
                ->modalHeading('Kirim Hasil Ujian ke Mahasiswa')
                ->modalSubmitActionLabel('Tandai Sudah Terkirim')
                ->modalContent(fn () => view('filament.modals.send-exam-result', [
                    'record' => $this->record,
                    'waUrl'  => $this->buildStudentWaUrl(),
                ]))
                ->action(function () {
                    $this->record->update(['sent_at' => now()]);
                    $this->record->refresh();
                    $this->fillForm();
                    Notification::make()
                        ->title('Pesan hasil ujian ditandai sebagai terkirim.')
                        ->success()
                        ->send();
                })
                ->visible(function () {
                    if (!$this->record) return false;
                    $id = $this->record->id;
                    $total = ExamScore::where('exam_registration_id', $id)->count();
                    if ($total === 0) return false;
                    return ExamScore::where('exam_registration_id', $id)->whereNull('grade')->doesntExist();
                }),

            Actions\DeleteAction::make()
                ->hidden(fn () => $this->record && ExamScore::where('exam_registration_id', $this->record->id)
                    ->whereNotNull('grade')->exists()),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record->fresh();

        // Sync examiner_order in exam_scores based on new slot positions
        $slotMap = array_filter([
            1 => $record->examiner1_id,
            2 => $record->examiner2_id,
            3 => $record->examiner3_id,
        ]);

        foreach ($slotMap as $order => $userId) {
            ExamScore::where('exam_registration_id', $record->id)
                ->where('user_id', $userId)
                ->update(['examiner_order' => $order]);
        }

        // Sync guide_examiners for this student
        GuideExaminer::where('user_id', $record->user_id)->update([
            'guide1_id'    => $record->guide1_id,
            'guide2_id'    => $record->guide2_id,
            'examiner1_id' => $record->examiner1_id,
            'examiner2_id' => $record->examiner2_id,
            'examiner3_id' => $record->examiner3_id,
            'chief_id'     => $record->chief_id,
        ]);
    }

    private function buildStudentWaUrl(): ?string
    {
        $record = $this->record;

        if (!$record?->student?->phone) {
            return null;
        }

        $examtype    = $record->examtype?->name ?? '-';
        $studentName = $record->student->name;
        $examDate    = $record->exam_date?->isoFormat('dddd, D MMMM Y') ?? '-';
        $resultUrl   = route('exam.result');

        $text = "*INFORMASI Hasil {$examtype}*\n\n"
            . "Saudara *{$studentName}*, Kami informasikan bahwa masing-masing dosen penguji "
            . "telah menuliskan revisi {$examtype} ({$examDate}) dan dapat dicetak pada sistem DBS berikut.\n\n"
            . "{$resultUrl}\n"
            . "(jika eror saat buka link di handphone, pastikan awalannya http:// bukan https://)";

        if ($record->exam_type_id == 3) {
            $text .= "\n\nTerakhir, harap laporkan hasil ujian Anda pada laman "
                . "(siapkan lembar revisi yang sudah ditandatangani dan foto ujian):\n"
                . "https://forms.gle/umUKgAcXLnhowgpw7";
        }

        $text .= "\n\nDemikian informasi ini Kami sampaikan. Atas perhatian Anda, Kami ucapkan terima kasih.\n"
            . "(ttd.) *Kajur Pendidikan Matematika*";

        return 'https://api.whatsapp.com/send/?phone=62' . $record->student->phone . '&text=' . rawurlencode($text);
    }
}
