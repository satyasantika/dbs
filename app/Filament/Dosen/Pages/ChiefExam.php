<?php

namespace App\Filament\Dosen\Pages;

use App\Models\ExamRegistration;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ChiefExam extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Menu Dosen';

    protected static ?string $title = 'Halaman Ketua Penguji';

    protected static ?string $slug = 'examination/chief';

    protected static string $view = 'filament.dosen.pages.chief-exam';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('dosen') ?? false;
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'dosen', $tenant);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->url(UnscoredScoring::getUrl()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ExamRegistration::query()
                    ->with(['student', 'examtype'])
                    ->where('chief_id', auth()->id())
                    ->orderByDesc('exam_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Mahasiswa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.username')
                    ->label('NIM')
                    ->searchable(),
                Tables\Columns\TextColumn::make('examtype.name')
                    ->label('Jenis Ujian')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('exam_date')
                    ->label('Tanggal Ujian')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('exam_time')
                    ->label('Waktu')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('room')
                    ->label('Ruang')
                    ->placeholder('-'),
                Tables\Columns\IconColumn::make('pass_exam')
                    ->label('Lulus')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ExamRegistration $record): string => ViewChiefExam::getUrl(['record' => $record->id])),
            ])
            ->emptyStateHeading('Belum ada tugas ketua penguji')
            ->emptyStateDescription('Ujian akan muncul setelah Anda ditetapkan sebagai ketua penguji.')
            ->emptyStateIcon('heroicon-o-user-circle')
            ->paginated([10, 25, 50]);
    }
}
