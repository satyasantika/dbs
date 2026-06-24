<?php

namespace App\Filament\Dbs\Resources\NuirSubmissionResource\Pages;

use App\Filament\Dbs\Resources\NuirSubmissionResource;
use App\Models\NuirSubmission;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewNuirSubmission extends ViewRecord
{
    protected static string $resource = NuirSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        /** @var NuirSubmission $submission */
        $submission = $this->record;
        $approved = NuirSubmissionResource::approvedReferenceCount($submission);
        $minimum = NuirSubmissionResource::minimumApprovedReferences($submission);

        return [
            Actions\Action::make('approveContent')
                ->label('Setujui Konten')
                ->color('success')
                ->disabled($approved < $minimum)
                ->tooltip($approved < $minimum
                    ? "Minimal {$minimum} referensi disetujui (saat ini {$approved})."
                    : null)
                ->form([
                    Forms\Components\Textarea::make('dbs_note')
                        ->label('Catatan DBS'),
                ])
                ->action(function (array $data): void {
                    NuirSubmissionResource::reviewSubmission($this->record, 'content_ok', $data['dbs_note'] ?? null);
                    $this->refreshFormData(['status', 'dbs_note', 'dbs_reviewer_id', 'dbs_reviewed_at']);
                }),
            Actions\Action::make('requestRevision')
                ->label('Minta Revisi')
                ->color('warning')
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

        return "{$approved} dari {$submission->references->count()} referensi disetujui. Standar minimum: {$minimum}.";
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    public function getRelationManagers(): array
    {
        return parent::getRelationManagers();
    }
}
