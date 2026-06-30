<?php

namespace App\Filament\NuirManajer\Resources\NuirSubmissionResource\Pages;

use App\Filament\NuirManajer\Concerns\BuildsManajerNuirSubmissionInfolist;
use App\Filament\NuirManajer\Resources\NuirSubmissionResource;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirAssignmentService;
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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Ringkasan')
                ->schema($this->manajerSubmissionRingkasanSchema($this->validatorManagementAction()))
                ->columns(4),
            Infolists\Components\Section::make('Konten')
                ->description('Teks yang dikirim mahasiswa beserta jumlah kata per elemen.')
                ->schema($this->manajerSubmissionKontenSchema()),
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
