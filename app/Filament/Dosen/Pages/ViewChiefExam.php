<?php

namespace App\Filament\Dosen\Pages;

use App\Models\ExamRegistration;
use App\Models\ExamScore;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ViewChiefExam extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'examination/chief/{record}';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.dosen.pages.view-chief-exam';

    public ExamRegistration $record;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('dosen') ?? false;
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'dosen', $tenant);
    }

    public function mount(ExamRegistration $record): void
    {
        if ($record->chief_id !== auth()->id()) {
            $this->redirect(Scoring::getUrl(['activeTab' => 'unscored']));

            return;
        }

        $this->record = $record->loadMissing(['student', 'examtype']);
    }

    public function getTitle(): string | Htmlable
    {
        $studentName = $this->record->student?->name ?? '-';

        return 'Ketua Penguji — '.$studentName;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pass')
                ->label('Finalisasi Kelulusan')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Finalisasi kelulusan ujian')
                ->modalDescription('Pastikan semua penguji sudah memberikan penilaian dan persetujuan lanjut.')
                ->visible(fn (): bool => ! $this->record->pass_exam)
                ->action(function (): void {
                    $approvedCount = ExamScore::query()
                        ->where('exam_registration_id', $this->record->id)
                        ->where('pass_approved', true)
                        ->count();

                    if ($approvedCount < 5) {
                        Notification::make()
                            ->title('Tidak bisa finalisasi')
                            ->body('Masih ada nilai yang belum terinput atau belum disetujui lanjut.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $this->record->update(['pass_exam' => true]);

                    Notification::make()
                        ->title('Mahasiswa layak dilanjutkan')
                        ->body('Mahasiswa '.strtoupper($this->record->student?->name ?? '').' telah layak dilanjutkan.')
                        ->success()
                        ->send();
                }),
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->url(Scoring::getUrl()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ExamScore::query()
                    ->with(['lecture', 'registration.student', 'registration.examtype'])
                    ->where('exam_registration_id', $this->record->id)
                    ->orderBy('examiner_order')
            )
            ->columns([
                Tables\Columns\TextColumn::make('lecture.name')
                    ->label('Nama')
                    ->description(fn (ExamScore $record): ?string => filled($record->lecture?->phone) ? 'WA tersedia' : null)
                    ->color(fn (ExamScore $record): string => blank($record->letter) ? 'danger' : 'gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('letter')
                    ->label('Nilai')
                    ->placeholder('Belum dinilai')
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'primary' : 'danger'),
                Tables\Columns\IconColumn::make('revision')
                    ->label('Revisi')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('warning')
                    ->falseColor('success'),
                Tables\Columns\IconColumn::make('pass_approved')
                    ->label('Lanjutkan')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('whatsapp')
                    ->label('WA')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (ExamScore $record): ?string => $this->buildWhatsappUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn (ExamScore $record): bool => filled($record->lecture?->phone)),
            ])
            ->recordClasses(fn (ExamScore $record): ?string => blank($record->letter) ? 'bg-danger-50 dark:bg-danger-950/30' : null)
            ->emptyStateHeading('Belum ada data penilaian')
            ->emptyStateDescription('Penilaian penguji akan muncul setelah admin menetapkan penguji.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->paginated(false);
    }

    protected function buildWhatsappUrl(ExamScore $record): ?string
    {
        $phone = $record->lecture?->phone;

        if (blank($phone)) {
            return null;
        }

        $studentName = $record->registration?->student?->name ?? '-';
        $examTypeName = $record->registration?->examtype?->name ?? '-';
        $examDate = $record->registration?->exam_date?->isoFormat('dddd, D MMMM Y') ?? '-';
        $scoringUrl = url(route('scoring.edit', $record->id, absolute: false));

        $message = implode("\n", [
            'Yth. Penguji '.$studentName.',',
            '',
            'Mohon segera memberikan penilaian '.$examTypeName.' pada '.$examDate.' agar mahasiswa tersebut dapat segera mencetak lembar revisinya',
            '',
            'Silakan akses:',
            '',
            $scoringUrl,
            '',
            '(jika eror saat buka link di handphone, pastikan awalannya http:// bukan https://)',
        ]);

        return 'https://api.whatsapp.com/send/?phone=62'.$phone.'&text='.rawurlencode($message);
    }
}
