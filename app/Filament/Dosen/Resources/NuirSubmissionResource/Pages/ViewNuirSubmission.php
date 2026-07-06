<?php

namespace App\Filament\Dosen\Resources\NuirSubmissionResource\Pages;

use App\Filament\Dosen\Concerns\BuildsDosenNuirSubmissionInfolist;
use App\Filament\Dosen\Resources\NuirSubmissionResource;
use App\Models\NuirContentReview;
use App\Models\NuirProposal;
use App\Models\NuirReference;
use App\Models\NuirSubmission;
use App\Services\NuirAssignmentService;
use App\Services\NuirRevisionHistoryService;
use App\Support\NuirGuideSeatSync;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewNuirSubmission extends ViewRecord
{
    use BuildsDosenNuirSubmissionInfolist;

    protected static string $resource = NuirSubmissionResource::class;

    protected static string $view = 'filament.dosen.pages.view-nuir-submission';

    public function pollRefresh(): void
    {
        $this->record->refresh();
        $this->record->load(['user', 'references', 'proposals' => fn ($query) => $query->with(['guide1', 'guide2'])->orderByDesc('id')]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('backToList')
                ->label('Kembali ke Daftar Submission')
                ->icon('heroicon-o-arrow-left')
                ->url(NuirSubmissionResource::getUrl('index', panel: 'dosen')),
            Actions\Action::make('dashboard')
                ->label('Dashboard')
                ->icon('heroicon-o-home')
                ->url(route('home')),
            $this->rejectProposalAction(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Ringkasan')
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')->label('Mahasiswa'),
                    Infolists\Components\TextEntry::make('year_generation')->label('Angkatan'),
                    Infolists\Components\TextEntry::make('version')->label('Versi'),
                    Infolists\Components\TextEntry::make('status')->label('Status')->badge(),
                    Infolists\Components\TextEntry::make('dbs_note')
                        ->label('Catatan Revisi')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])
                ->columns(4),
            Infolists\Components\Section::make('Kursi Saya')
                ->schema([
                    Infolists\Components\TextEntry::make('seat_label')
                        ->label('Posisi')
                        ->state(fn (NuirSubmission $record): string => $this->mySeatLabel($record) ?? '—'),
                    Infolists\Components\TextEntry::make('seat_status')
                        ->label('Status')
                        ->badge()
                        ->state(fn (NuirSubmission $record): string => $this->mySeatStatusLabel($record))
                        ->color(fn (NuirSubmission $record): string => $this->mySeatStatusColor($record))
                        ->hintAction(
                            Infolists\Components\Actions\Action::make('syncSeatStatus')
                                ->label('Sinkronkan')
                                ->icon('heroicon-m-arrow-path')
                                ->tooltip('Muat ulang status kursi berdasarkan progres review terbaru')
                                ->visible(fn (NuirSubmission $record): bool => $this->currentProposal($record) !== null)
                                ->action(fn () => $this->syncMySeatStatus()),
                        ),
                    Infolists\Components\TextEntry::make('seat_note')
                        ->label('Catatan Penolakan')
                        ->placeholder('—')
                        ->state(fn (NuirSubmission $record): ?string => $this->mySeatNote($record))
                        ->columnSpanFull(),
                ])
                ->columns(3),
            Infolists\Components\Section::make('Konten')
                ->description('Tinjau dan tanggapi setiap elemen: Judul, Novelty, Urgency, dan Impact.')
                ->schema($this->kontenSchema())
                ->visible(fn (NuirSubmission $record): bool => $this->currentProposal($record) !== null),
            Infolists\Components\Section::make('Referensi')
                ->description('Referensi hanya dapat diminta revisi oleh pembimbing; persetujuan referensi dilakukan oleh validator.')
                ->schema([
                    Infolists\Components\ViewEntry::make('references_panel')
                        ->view('filament.dosen.infolists.references-panel')
                        ->viewData(function (NuirSubmission $record): array {
                            $proposal = $this->currentProposal($record);

                            return $proposal
                                ? $this->dosenReferencesPanelViewData($record, $proposal, auth()->user())
                                : ['references' => collect(), 'canReview' => false];
                        }),
                ])
                ->visible(fn (NuirSubmission $record): bool => $this->currentProposal($record) !== null),
            Infolists\Components\Section::make('Histori Penolakan Usulan')
                ->schema([
                    Infolists\Components\ViewEntry::make('rejection_history')
                        ->label('')
                        ->view('filament.mahasiswa.pages.partials.rejection-accordion')
                        ->viewData(fn (NuirSubmission $record): array => [
                            'history' => app(NuirRevisionHistoryService::class)->rejectionHistoryForSubmission($record),
                            'expanded' => true,
                        ]),
                ])
                ->visible(fn (NuirSubmission $record): bool => app(NuirRevisionHistoryService::class)
                    ->rejectionHistoryForSubmission($record)->isNotEmpty()),
        ]);
    }

    public function getSubheading(): ?string
    {
        /** @var NuirSubmission $submission */
        $submission = $this->record;
        $proposal = $this->currentProposal($submission);

        if (! $proposal) {
            return 'Anda tidak lagi termasuk dalam usulan pembimbing pada versi submission ini.';
        }

        return 'Submission NUIR mahasiswa yang mengusulkan Anda sebagai calon pembimbing.';
    }

    /**
     * @return array<int, Infolists\Components\Component>
     */
    private function kontenSchema(): array
    {
        return collect(NuirContentReview::FIELDS)
            ->map(fn (string $field) => Infolists\Components\ViewEntry::make($field.'_content')
                ->view('filament.dosen.infolists.content-field')
                ->viewData(function (NuirSubmission $record) use ($field): array {
                    $proposal = $this->currentProposal($record);

                    return $this->dosenContentFieldViewData($record, $proposal, auth()->user(), $field);
                }))
            ->all();
    }

    private function rejectProposalAction(): Actions\Action
    {
        return Actions\Action::make('rejectProposal')
            ->label('Tolak Usulan')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(function (): bool {
                /** @var NuirSubmission $submission */
                $submission = $this->record;

                return $this->canRejectProposal($submission);
            })
            ->requiresConfirmation()
            ->modalHeading('Tolak Usulan Pembimbing?')
            ->modalDescription('Kursi Anda pada usulan ini akan dikosongkan dan kuota dilepaskan. Mahasiswa dapat mengusulkan calon lain.')
            ->form([
                Forms\Components\Textarea::make('note')
                    ->label('Catatan penolakan')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (array $data): void {
                /** @var NuirSubmission $submission */
                $submission = $this->record;
                $proposal = $this->currentProposal($submission);

                if (! $proposal) {
                    return;
                }

                try {
                    app(NuirAssignmentService::class)->rejectProposalAsGuide($proposal, auth()->user(), $data['note']);

                    Notification::make()->success()->title('Usulan ditolak. Kursi Anda dikosongkan.')->send();

                    $this->refreshFormData(['status']);
                } catch (ValidationException $exception) {
                    Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
                }
            });
    }

    public function approveContentField(string $field): void
    {
        $this->reviewContentField($field, true, null);
    }

    public function requestContentFieldRevision(string $field, string $note): void
    {
        $this->reviewContentField($field, false, $note);
    }

    private function reviewContentField(string $field, bool $approved, ?string $note): void
    {
        /** @var NuirSubmission $submission */
        $submission = $this->record;
        $proposal = $this->currentProposal($submission);

        if (! $proposal) {
            return;
        }

        try {
            app(NuirAssignmentService::class)->reviewContentAsGuide(
                $submission,
                $proposal,
                auth()->user(),
                $field,
                $approved,
                $note,
            );

            app(NuirGuideSeatSync::class)->tryFinalize($proposal->fresh());

            $this->record->refresh();

            Notification::make()->success()->title($approved ? 'Elemen NUI disetujui.' : 'Permintaan revisi elemen NUI disimpan.')->send();
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
        }
    }

    public function cancelContentFieldApproval(string $field): void
    {
        /** @var NuirSubmission $submission */
        $submission = $this->record;
        $proposal = $this->currentProposal($submission);

        if (! $proposal) {
            return;
        }

        app(NuirAssignmentService::class)->cancelContentReviewAsGuide($submission, $proposal, auth()->user(), $field);

        $this->record->refresh();

        Notification::make()->success()->title('Persetujuan elemen NUI dibatalkan.')->send();
    }

    public function requestReferenceRevision(int $referenceId, ?string $refNote = null, array $revisionFields = []): void
    {
        /** @var NuirSubmission $submission */
        $submission = $this->record;
        $proposal = $this->currentProposal($submission);

        if (! $proposal) {
            return;
        }

        $reference = NuirReference::query()
            ->where('nuir_submission_id', $submission->id)
            ->whereKey($referenceId)
            ->firstOrFail();

        try {
            app(NuirAssignmentService::class)->requestReferenceRevisionAsGuide(
                $reference,
                $proposal,
                auth()->user(),
                (string) $refNote,
                $revisionFields,
            );

            $this->record->refresh();
            $this->record->load('references');

            Notification::make()->success()->title('Permintaan revisi referensi disimpan.')->send();
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title(collect($exception->errors())->flatten()->first())->send();
        }
    }

    private function currentProposal(NuirSubmission $record): ?NuirProposal
    {
        $userId = auth()->id();

        return $record->proposals
            ->first(fn (NuirProposal $proposal): bool => $proposal->guide1_id === $userId || $proposal->guide2_id === $userId);
    }

    private function canRejectProposal(NuirSubmission $record): bool
    {
        $proposal = $this->currentProposal($record);

        if (! $proposal || $proposal->final) {
            return false;
        }

        if ($this->dosenSeatStatus($proposal, auth()->user()) === 'rejected') {
            return false;
        }

        return ! app(NuirGuideSeatSync::class)->guideHasApprovedAllNuiFields($proposal, auth()->user());
    }

    private function mySeatLabel(NuirSubmission $record): ?string
    {
        $proposal = $this->currentProposal($record);

        return $proposal ? $this->dosenSeatLabel($proposal, auth()->user()) : null;
    }

    public function syncMySeatStatus(): void
    {
        /** @var NuirSubmission $submission */
        $submission = $this->record;
        $proposal = $this->currentProposal($submission);

        if (! $proposal) {
            return;
        }

        app(NuirGuideSeatSync::class)->syncGuideSeat($proposal, auth()->user());

        $this->pollRefresh();

        Notification::make()->success()->title('Status kursi diperbarui.')->send();
    }

    private function mySeatStatusLabel(NuirSubmission $record): string
    {
        $proposal = $this->currentProposal($record);

        if (! $proposal) {
            return 'Tidak termasuk usulan';
        }

        return \App\Support\NuirSeatStatusPresenter::detailed($proposal, auth()->user())['label'];
    }

    private function mySeatStatusColor(NuirSubmission $record): string
    {
        $proposal = $this->currentProposal($record);

        if (! $proposal) {
            return 'gray';
        }

        return \App\Support\NuirSeatStatusPresenter::detailed($proposal, auth()->user())['color'];
    }

    private function mySeatNote(NuirSubmission $record): ?string
    {
        $proposal = $this->currentProposal($record);

        if (! $proposal) {
            return null;
        }

        return $proposal->guide1_id === auth()->id() ? $proposal->guide1_note : $proposal->guide2_note;
    }
}
