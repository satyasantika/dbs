<?php

namespace App\Filament\Resources\ExamRegistrationResource\Pages;

use App\Filament\Resources\ExamRegistrationResource;
use App\Models\ExamScore;
use App\Services\Examination\ExamRegistrationExaminerSync;
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
                ->hidden(fn () => $this->record && $this->record->examScores()->exists()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return app(ExamRegistrationExaminerSync::class)->resolveChiefIdForSave($this->record, $data);
    }

    protected function afterSave(): void
    {
        $this->wasSaved = true;

        app(ExamRegistrationExaminerSync::class)->syncFromRegistration($this->record->fresh());
    }
}
