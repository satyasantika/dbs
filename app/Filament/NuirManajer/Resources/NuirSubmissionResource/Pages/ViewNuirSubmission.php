<?php

namespace App\Filament\NuirManajer\Resources\NuirSubmissionResource\Pages;

use App\Filament\NuirManajer\Resources\NuirSubmissionResource;
use App\Models\NuirSubmission;
use App\Services\NuirAssignmentService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewNuirSubmission extends ViewRecord
{
    protected static string $resource = NuirSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        /** @var NuirSubmission $submission */
        $submission = $this->record;
        $approved = NuirSubmissionResource::approvedReferenceCount($submission);
        $minimum = NuirSubmissionResource::minimumApprovedReferences($submission);
        $assignmentService = app(NuirAssignmentService::class);

        return [
            Actions\Action::make('assignValidator')
                ->label('Delegasikan Validator')
                ->icon('heroicon-o-user-plus')
                ->visible(fn (): bool => auth()->user()?->can('delegate nuir validator') ?? false)
                ->form([
                    Forms\Components\Select::make('validator_id')
                        ->label('Validator NUIR')
                        ->options(fn () => $assignmentService->validators()->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data) use ($assignmentService, $submission): void {
                    try {
                        $validator = \App\Models\User::findOrFail($data['validator_id']);
                        $assignmentService->assignValidator($submission, $validator, auth()->user());
                        Notification::make()->success()->title('Validator berhasil ditugaskan.')->send();
                        $this->refreshFormData(['assignment']);
                    } catch (ValidationException $exception) {
                        Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
                    }
                }),
            Actions\Action::make('approveContent')
                ->label('Setujui Konten')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->can('review nuir submission') ?? false)
                ->disabled($approved < $minimum)
                ->tooltip($approved < $minimum
                    ? "Minimal {$minimum} referensi disetujui (saat ini {$approved})."
                    : null)
                ->form([
                    Forms\Components\Textarea::make('dbs_note')
                        ->label('Catatan'),
                ])
                ->action(function (array $data): void {
                    NuirSubmissionResource::reviewSubmission($this->record, 'content_ok', $data['dbs_note'] ?? null);
                    $this->refreshFormData(['status', 'dbs_note', 'dbs_reviewer_id', 'dbs_reviewed_at']);
                }),
            Actions\Action::make('requestRevision')
                ->label('Minta Revisi')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->can('review nuir submission') ?? false)
                ->form([
                    Forms\Components\Textarea::make('dbs_note')
                        ->label('Catatan revisi')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    NuirSubmissionResource::reviewSubmission($this->record, 'revision', $data['dbs_note']);
                    $this->refreshFormData(['status', 'dbs_note', 'dbs_reviewer_id', 'dbs_reviewed_at']);
                }),
        ];
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
}
