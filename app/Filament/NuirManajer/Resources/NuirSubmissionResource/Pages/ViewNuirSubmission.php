<?php

namespace App\Filament\NuirManajer\Resources\NuirSubmissionResource\Pages;

use App\Filament\NuirManajer\Concerns\BuildsManajerNuirSubmissionInfolist;
use App\Filament\NuirManajer\Resources\NuirSubmissionResource;
use App\Models\NuirProposal;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirAssignmentService;
use App\Services\NuirProposalService;
use App\Services\NuirService;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewNuirSubmission extends ViewRecord
{
    use BuildsManajerNuirSubmissionInfolist;

    protected static string $resource = NuirSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('backToList')
                ->label('Kembali ke Daftar Submission')
                ->icon('heroicon-o-arrow-left')
                ->url(NuirSubmissionResource::getUrl('index', panel: 'nuir-manajer')),
            \Filament\Actions\Action::make('deleteSubmission')
                ->label('Hapus Submission')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Submission NUIR?')
                ->modalDescription('Seluruh data submission ini (konten, referensi, usulan pembimbing, histori, dan delegasi validator) akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, hapus')
                ->visible(fn (): bool => auth()->user()?->can('delete nuir submission') ?? false)
                ->action(function (): void {
                    /** @var NuirSubmission $submission */
                    $submission = $this->record;

                    try {
                        app(NuirService::class)->deleteSubmission($submission, auth()->user());

                        Notification::make()
                            ->success()
                            ->title('Submission NUIR berhasil dihapus.')
                            ->send();

                        $this->redirect(NuirSubmissionResource::getUrl('index', panel: 'nuir-manajer'));
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->danger()
                            ->title(collect($exception->errors())->flatten()->first())
                            ->send();
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Ringkasan')
                ->schema($this->manajerSubmissionRingkasanSchema($this->validatorManagementAction()))
                ->columns(4),
            Infolists\Components\Section::make('Pengusulan Pembimbing')
                ->description('Daftar usulan calon pembimbing mahasiswa, terbaru di atas.')
                ->schema($this->manajerProposalSchema())
                ->headerActions([
                    $this->cancelGuideAction(1),
                    $this->cancelGuideAction(2),
                ])
                ->visible(fn (NuirSubmission $record): bool => $record->proposals()->exists()),
            Infolists\Components\Section::make('Konten')
                ->description('Menampilkan teks terakhir setelah revisi. Histori per elemen dibuka lewat tautan di bawah masing-masing bagian.')
                ->schema($this->manajerSubmissionKontenSchema()),
            Infolists\Components\Section::make('Referensi')
                ->description('Menampilkan referensi terakhir. Histori revisi per referensi dibuka lewat tautan di masing-masing kartu.')
                ->schema([
                    Infolists\Components\ViewEntry::make('references_panel')
                        ->view('filament.nuir-manajer.infolists.references-panel')
                        ->viewData(fn (NuirSubmission $record): array => self::referencesPanelViewData($record)),
                ]),
        ]);
    }

    public function getSubheading(): ?string
    {
        /** @var NuirSubmission $submission */
        $submission = $this->record;
        $approved = NuirSubmissionResource::approvedReferenceCount($submission);
        $minimum = NuirSubmissionResource::minimumApprovedReferences($submission);
        $validationLabel = NuirSubmission::referenceValidationStatusLabel($submission->referenceValidationStatus());

        return "{$submission->referenceValidationProgressLabel()} referensi divalidasi validator ({$validationLabel}). "
            ."{$approved} disetujui — standar minimum: {$minimum}.";
    }

    private function cancelGuideAction(int $seat): Action
    {
        return Action::make('cancelGuide'.$seat)
            ->label('Batalkan Calon P'.$seat)
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Batalkan Calon Pembimbing '.$seat.'?')
            ->modalDescription('Calon pembimbing akan dibatalkan. Mahasiswa dapat memilih ulang calon untuk kursi ini.')
            ->form([
                Forms\Components\Textarea::make('note')
                    ->label('Catatan pembatalan (opsional)')
                    ->placeholder('Alasan pembatalan...')
                    ->rows(3),
            ])
            ->visible(fn (NuirSubmission $record): bool => $this->activeProposalHasGuide($record, $seat))
            ->action(function (array $data, NuirSubmission $record) use ($seat): void {
                $proposal = $record->proposals()->where('final', false)->latest('id')->first();

                if (! $proposal) {
                    return;
                }

                app(NuirProposalService::class)->cancelSeat(
                    $proposal->load('submission'),
                    $seat,
                    auth()->user(),
                    filled($data['note'] ?? '') ? $data['note'] : null,
                );

                Notification::make()
                    ->success()
                    ->title('Calon Pembimbing '.$seat.' berhasil dibatalkan.')
                    ->send();

                $this->refreshFormData(['proposals']);
            });
    }

    private function activeProposalHasGuide(NuirSubmission $record, int $seat): bool
    {
        $proposal = $record->proposals()->where('final', false)->latest('id')->first();

        if (! $proposal) {
            return false;
        }

        return (bool) ($seat === 1 ? $proposal->guide1_id : $proposal->guide2_id);
    }

    private function validatorManagementAction(): Action
    {
        $assignmentService = app(NuirAssignmentService::class);

        return Action::make('manageValidator')
            ->label(fn (NuirSubmission $record): string => $record->assignment?->validator_id ? 'Ubah' : 'Delegasikan')
            ->icon(fn (NuirSubmission $record): string => $record->assignment?->validator_id
                ? 'heroicon-o-arrow-path'
                : 'heroicon-o-user-plus')
            ->visible(fn (): bool => auth()->user()?->can('delegate nuir validator') ?? false)
            ->form([
                Forms\Components\Select::make('validator_id')
                    ->label('Validator NUIR')
                    ->options(fn () => $assignmentService->validators()->pluck('name', 'id'))
                    ->default(fn (NuirSubmission $record): ?int => $record->assignment?->validator_id)
                    ->required()
                    ->searchable(),
            ])
            ->action(function (array $data, NuirSubmission $record) use ($assignmentService): void {
                try {
                    $changing = $record->assignment?->validator_id !== null;
                    $validator = User::findOrFail($data['validator_id']);
                    $assignmentService->assignValidator($record, $validator, auth()->user());

                    Notification::make()
                        ->success()
                        ->title($changing ? 'Validator berhasil diubah.' : 'Validator berhasil ditugaskan.')
                        ->send();

                    $this->refreshFormData(['assignment']);
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title(collect($exception->errors())->flatten()->first())
                        ->send();
                }
            });
    }
}
