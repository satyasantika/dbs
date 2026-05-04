<?php

namespace App\Filament\Resources\ExamRegistrationResource\Pages;

use App\Filament\Resources\ExamRegistrationResource;
use App\Models\ExamScore;
use App\Models\GuideExaminer;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditExamRegistration extends EditRecord
{
    protected static string $resource = ExamRegistrationResource::class;

    public bool $wasSaved = false;

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label(fn (): string => $this->wasSaved ? 'Kembali' : 'Batal');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send_result')
                ->label(fn (): string => $this->record?->sent_at ? 'Kirim ulang' : 'Kabari')
                ->icon('heroicon-o-paper-airplane')
                ->color(fn (): string => $this->record?->sent_at ? 'gray' : 'success')
                ->iconButton()
                ->tooltip(fn (): string => $this->record?->sent_at
                    ? 'Kirim ulang via WhatsApp (waktu terkirim diperbarui). Terakhir: '.$this->record->sent_at->locale('id')->isoFormat('D MMM Y, HH.mm')
                    : 'Kabari mahasiswa: buka WhatsApp di tab baru dan tandai terkirim')
                ->action(function ($livewire): void {
                    ExamRegistrationResource::kabariMahasiswaLewatWhatsapp($this->record, $livewire);
                    $this->record->refresh();
                    $this->fillForm();
                })
                ->visible(function (): bool {
                    if (! $this->record) {
                        return false;
                    }
                    $id = $this->record->id;
                    $total = ExamScore::where('exam_registration_id', $id)->count();
                    if ($total === 0) {
                        return false;
                    }

                    return ExamScore::where('exam_registration_id', $id)->whereNull('grade')->doesntExist();
                }),

            Actions\DeleteAction::make()
                ->hidden(fn () => $this->record && ExamScore::where('exam_registration_id', $this->record->id)
                    ->whereNotNull('grade')->exists()),
        ];
    }

    protected function afterSave(): void
    {
        $this->wasSaved = true;

        $record = $this->record->fresh();

        // Create or update exam_scores for all slots with correct examiner_order
        $slots = [
            1 => $record->examiner1_id,
            2 => $record->examiner2_id,
            3 => $record->examiner3_id,
            4 => $record->guide1_id,
            5 => $record->guide2_id,
        ];

        $activeUserIds = [];
        foreach ($slots as $order => $userId) {
            if (!$userId) continue;

            ExamScore::updateOrCreate(
                [
                    'exam_registration_id' => $record->id,
                    'user_id'              => $userId,
                ],
                ['examiner_order' => $order]
            );

            $activeUserIds[] = $userId;
        }

        // Delete orphaned scores (old examiners replaced) that have not been graded yet
        ExamScore::where('exam_registration_id', $record->id)
            ->whereNotIn('user_id', $activeUserIds ?: [0])
            ->whereNull('grade')
            ->delete();

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
}
