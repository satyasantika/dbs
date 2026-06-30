<?php

namespace App\Filament\NuirValidator\Resources\NuirSubmissionResource\Pages;

use App\Filament\NuirValidator\Concerns\BuildsValidatorNuirSubmissionInfolist;
use App\Filament\NuirValidator\Resources\NuirSubmissionResource;
use App\Models\NuirReference;
use App\Models\NuirSubmission;
use App\Services\NuirAssignmentService;
use App\Support\NuirValidatorListReturn;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;

class ViewNuirSubmission extends ViewRecord
{
    use BuildsValidatorNuirSubmissionInfolist;

    protected static string $resource = NuirSubmissionResource::class;

    #[Url(as: 'reference')]
    public ?int $openReferenceId = null;

    #[Url(as: 'return')]
    public ?string $returnTo = null;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Ringkasan')
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')->label('Mahasiswa'),
                    Infolists\Components\TextEntry::make('year_generation')->label('Angkatan'),
                    Infolists\Components\TextEntry::make('version')->label('Versi'),
                    Infolists\Components\TextEntry::make('status')->label('Status')->badge(),
                    Infolists\Components\TextEntry::make('title')->label('Judul')->columnSpanFull(),
                ])
                ->columns(4),
            Infolists\Components\Section::make('Referensi')
                ->description('Menampilkan referensi terakhir. Histori revisi per referensi dibuka lewat tautan di masing-masing kartu.')
                ->schema([
                    Infolists\Components\ViewEntry::make('references_panel')
                        ->view('filament.nuir-validator.infolists.references-panel')
                        ->viewData(fn (NuirSubmission $record): array => [
                            ...self::referencesPanelViewData($record),
                            'canReview' => NuirSubmissionResource::canReviewReferences($record),
                            'openReferenceId' => $this->resolveOpenReferenceId($record),
                        ]),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('backToList')
                ->label(NuirValidatorListReturn::label($this->returnTo))
                ->icon('heroicon-o-arrow-left')
                ->url(NuirValidatorListReturn::url($this->returnTo, panel: 'nuir-validator')),
        ];
    }

    public function getSubheading(): ?string
    {
        /** @var NuirSubmission $submission */
        $submission = $this->record;

        if (! NuirSubmissionResource::canReviewReferences($submission)) {
            return 'Submission masih draft — referensi hanya dapat dilihat.';
        }

        $approved = $submission->references()->where('ref_approved', true)->count();
        $total = $submission->references()->count();

        return "{$approved} dari {$total} referensi disetujui.";
    }

    public function approveReference(int $referenceId): void
    {
        $reference = $this->findOwnedReference($referenceId);

        try {
            app(NuirAssignmentService::class)->reviewReferenceAsValidator(
                $reference,
                auth()->user(),
                true,
            );

            $this->refreshReferences();

            Notification::make()->success()->title('Referensi disetujui.')->send();
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
        }
    }

    public function requestReferenceRevision(int $referenceId, ?string $refNote = null, array $revisionFields = []): void
    {
        $reference = $this->findOwnedReference($referenceId);

        try {
            app(NuirAssignmentService::class)->reviewReferenceAsValidator(
                $reference,
                auth()->user(),
                false,
                $refNote,
                $revisionFields,
            );

            $this->refreshReferences();

            Notification::make()->success()->title('Permintaan revisi referensi disimpan.')->send();
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
        }
    }

    protected function findOwnedReference(int $referenceId): NuirReference
    {
        /** @var NuirSubmission $submission */
        $submission = $this->record;

        return NuirReference::query()
            ->where('nuir_submission_id', $submission->id)
            ->whereKey($referenceId)
            ->firstOrFail();
    }

    protected function refreshReferences(): void
    {
        $this->record->refresh();
        $this->record->load('references');
    }

    protected function resolveOpenReferenceId(NuirSubmission $record): ?int
    {
        if (blank($this->openReferenceId)) {
            return null;
        }

        return $record->references()->whereKey($this->openReferenceId)->exists()
            ? $this->openReferenceId
            : null;
    }
}
