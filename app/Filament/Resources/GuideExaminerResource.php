<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuideExaminerResource\Pages;
use App\Models\GuideExaminer;
use App\Models\User;
use App\Services\Information\AcademicSemester;
use App\Support\ExaminerSlotSelectOptions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class GuideExaminerResource extends Resource
{
    protected static ?string $model = GuideExaminer::class;


    protected static ?string $navigationGroup = 'Manajemen Ujian';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $modelLabel = 'Pembimbing & Penguji';

    protected static ?string $pluralModelLabel = 'Pembimbing & Penguji';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        $lecturerOptions = fn (Get $get, string $field): array => ExaminerSlotSelectOptions::optionsFor($get, $field);

        $migrateChiefWhenSlotChanges = function (?string $state, Set $set, Get $get, ?string $old): void {
            if (blank($old)) {
                return;
            }

            if ((int) $get('chief_id') === (int) $old) {
                $set('chief_id', $state);
            }
        };

        $studentOptions = function (?GuideExaminer $record): array {
            return User::role('mahasiswa')
                ->where(function (Builder $query) use ($record): void {
                    $query->whereDoesntHave(
                        'guideExaminer',
                        fn (Builder $query) => $query->whereNotNull('thesis_date'),
                    );

                    if ($record?->exists && filled($record->user_id)) {
                        $query->orWhere('id', $record->user_id);
                    }
                })
                ->when(
                    $record?->exists,
                    fn (Builder $query) => $query->where(function (Builder $query) use ($record): void {
                        $query->whereDoesntHave('guideExaminer')
                            ->orWhereHas(
                                'guideExaminer',
                                fn (Builder $query) => $query->whereKey($record->id),
                            );
                    }),
                    fn (Builder $query) => $query->whereDoesntHave('guideExaminer'),
                )
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all();
        };

        $yearOptions = collect(range(2017, (int) date('Y') + 1))
            ->merge(GuideExaminer::query()->distinct()->pluck('year_generation'))
            ->map(fn ($year) => (int) $year)
            ->filter(fn (int $year) => $year >= 2017)
            ->unique()
            ->sort()
            ->mapWithKeys(fn (int $year): array => [(string) $year => (string) $year])
            ->all();

        return $form
            ->schema([
                Forms\Components\Section::make('Data Mahasiswa')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Mahasiswa')
                            ->options(fn (?GuideExaminer $record): array => $studentOptions($record))
                            ->getOptionLabelUsing(fn ($value): ?string => filled($value) ? User::find($value)?->name : null)
                            ->disabled(fn (?GuideExaminer $record): bool => $record?->exists && filled($record->thesis_date))
                            ->dehydrated()
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('year_generation')
                            ->label('Angkatan')
                            ->options($yearOptions)
                            ->searchable()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Section::make('Pembimbing')
                            ->columnSpan(1)
                    ->description('Gunakan ikon ⇅ di samping field untuk menukar Pembimbing 1 dan 2.')
                    ->schema([
                        Forms\Components\Select::make('guide1_id')
                            ->label('Pembimbing 1')
                            ->options(fn (Get $get): array => $lecturerOptions($get, 'guide1_id'))
                            ->searchable()
                            ->live()
                            ->nullable()
                            ->afterStateUpdated($migrateChiefWhenSlotChanges)
                            ->hintActions([
                                Forms\Components\Actions\Action::make('swap_guides_from_1')
                                    ->label('Tukar')
                                    ->tooltip('Tukar Pembimbing 1 dan 2')
                                    ->icon('heroicon-m-arrows-up-down')
                                    ->color('gray')
                                    ->action(function (Set $set, Get $get): void {
                                        [$a, $b] = [$get('guide1_id'), $get('guide2_id')];
                                        $set('guide1_id', $b);
                                        $set('guide2_id', $a);
                                    }),
                            ]),
                        Forms\Components\Select::make('guide2_id')
                            ->label('Pembimbing 2')
                            ->options(fn (Get $get): array => $lecturerOptions($get, 'guide2_id'))
                            ->searchable()
                            ->live()
                            ->nullable()
                            ->afterStateUpdated($migrateChiefWhenSlotChanges)
                            ->hintActions([
                                Forms\Components\Actions\Action::make('swap_guides_from_2')
                                    ->label('Tukar')
                                    ->tooltip('Tukar Pembimbing 1 dan 2')
                                    ->icon('heroicon-m-arrows-up-down')
                                    ->color('gray')
                                    ->action(function (Set $set, Get $get): void {
                                        [$a, $b] = [$get('guide1_id'), $get('guide2_id')];
                                        $set('guide1_id', $b);
                                        $set('guide2_id', $a);
                                    }),
                            ]),
                    ])->columns(1),

                        Forms\Components\Section::make('Penguji')
                            ->columnSpan(1)
                    ->description('Tombol ↑/↓ untuk menyusun ulang urutan. Tombol "Set Ketua" untuk menetapkan Ketua Penguji.')
                    ->schema([
                        Forms\Components\Hidden::make('chief_id'),

                        Forms\Components\Select::make('examiner1_id')
                            ->label(fn (Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner1_id')
                                ? new HtmlString('Penguji 1 <span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 text-xs font-semibold text-success-700">★ Ketua</span>')
                                : 'Penguji 1')
                            ->options(fn (Get $get): array => $lecturerOptions($get, 'examiner1_id'))
                            ->searchable()
                            ->live()
                            ->nullable()
                            ->afterStateUpdated($migrateChiefWhenSlotChanges)
                            ->hintActions([
                                Forms\Components\Actions\Action::make('set_chief_1')
                                    ->label('Set Ketua')
                                    ->icon('heroicon-m-star')
                                    ->color('warning')
                                    ->hidden(fn (Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner1_id'))
                                    ->action(fn (Set $set, Get $get) => $set('chief_id', $get('examiner1_id'))),
                                Forms\Components\Actions\Action::make('swap_down_1')
                                    ->label('↓')
                                    ->tooltip('Tukar dengan Penguji 2')
                                    ->icon('heroicon-m-arrow-down')
                                    ->color('gray')
                                    ->action(function (Set $set, Get $get): void {
                                        [$a, $b] = [$get('examiner1_id'), $get('examiner2_id')];
                                        $set('examiner1_id', $b);
                                        $set('examiner2_id', $a);
                                    }),
                            ]),

                        Forms\Components\Select::make('examiner2_id')
                            ->label(fn (Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner2_id')
                                ? new HtmlString('Penguji 2 <span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 text-xs font-semibold text-success-700">★ Ketua</span>')
                                : 'Penguji 2')
                            ->options(fn (Get $get): array => $lecturerOptions($get, 'examiner2_id'))
                            ->searchable()
                            ->live()
                            ->nullable()
                            ->afterStateUpdated($migrateChiefWhenSlotChanges)
                            ->hintActions([
                                Forms\Components\Actions\Action::make('set_chief_2')
                                    ->label('Set Ketua')
                                    ->icon('heroicon-m-star')
                                    ->color('warning')
                                    ->hidden(fn (Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner2_id'))
                                    ->action(fn (Set $set, Get $get) => $set('chief_id', $get('examiner2_id'))),
                                Forms\Components\Actions\Action::make('swap_up_2')
                                    ->label('↑')
                                    ->tooltip('Tukar dengan Penguji 1')
                                    ->icon('heroicon-m-arrow-up')
                                    ->color('gray')
                                    ->action(function (Set $set, Get $get): void {
                                        [$a, $b] = [$get('examiner1_id'), $get('examiner2_id')];
                                        $set('examiner1_id', $b);
                                        $set('examiner2_id', $a);
                                    }),
                                Forms\Components\Actions\Action::make('swap_down_2')
                                    ->label('↓')
                                    ->tooltip('Tukar dengan Penguji 3')
                                    ->icon('heroicon-m-arrow-down')
                                    ->color('gray')
                                    ->action(function (Set $set, Get $get): void {
                                        [$a, $b] = [$get('examiner2_id'), $get('examiner3_id')];
                                        $set('examiner2_id', $b);
                                        $set('examiner3_id', $a);
                                    }),
                            ]),

                        Forms\Components\Select::make('examiner3_id')
                            ->label(fn (Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner3_id')
                                ? new HtmlString('Penguji 3 <span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 text-xs font-semibold text-success-700">★ Ketua</span>')
                                : 'Penguji 3')
                            ->options(fn (Get $get): array => $lecturerOptions($get, 'examiner3_id'))
                            ->searchable()
                            ->live()
                            ->nullable()
                            ->afterStateUpdated($migrateChiefWhenSlotChanges)
                            ->hintActions([
                                Forms\Components\Actions\Action::make('set_chief_3')
                                    ->label('Set Ketua')
                                    ->icon('heroicon-m-star')
                                    ->color('warning')
                                    ->hidden(fn (Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner3_id'))
                                    ->action(fn (Set $set, Get $get) => $set('chief_id', $get('examiner3_id'))),
                                Forms\Components\Actions\Action::make('swap_up_3')
                                    ->label('↑')
                                    ->tooltip('Tukar dengan Penguji 2')
                                    ->icon('heroicon-m-arrow-up')
                                    ->color('gray')
                                    ->action(function (Set $set, Get $get): void {
                                        [$a, $b] = [$get('examiner2_id'), $get('examiner3_id')];
                                        $set('examiner2_id', $b);
                                        $set('examiner3_id', $a);
                                    }),
                            ]),
                    ])->columns(1),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),

                Forms\Components\Section::make('Jadwal Ujian')
                    ->schema([
                        Forms\Components\DatePicker::make('proposal_date')
                            ->label('Tanggal Seminar Proposal'),
                        Forms\Components\DatePicker::make('seminar_date')
                            ->label('Tanggal Seminar Hasil'),
                        Forms\Components\DatePicker::make('thesis_date')
                            ->label('Tanggal Sidang Skripsi')
                            ->live(),
                        Forms\Components\TextInput::make('doc')
                            ->label('Link Bukti Kelulusan')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => filled($get('thesis_date')))
                            ->placeholder('https://...')
                            ->helperText('URL dokumen bukti kelulusan, misalnya tautan Google Drive.')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('open_doc')
                                    ->label('Buka')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->url(fn (Get $get): ?string => filled($get('doc')) ? $get('doc') : null)
                                    ->openUrlInNewTab()
                                    ->visible(fn (Get $get): bool => filled($get('doc'))),
                            ),
                    ])->columns(3)
                    ->collapsed(fn (?GuideExaminer $record): bool => ! $record?->exists),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Mahasiswa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year_generation')
                    ->label('Angkatan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('guide1.name')
                    ->label('Pembimbing 1')
                    ->searchable(),
                Tables\Columns\TextColumn::make('guide2.name')
                    ->label('Pembimbing 2')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jadwal_ujian')
                    ->label('Jadwal Ujian')
                    ->getStateUsing(function (GuideExaminer $record): ?string {
                        $schedule = static::buildExamScheduleHtml($record);

                        return filled($schedule) ? $schedule : null;
                    })
                    ->html()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('examiner1.name')
                    ->label('Penguji 1')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('examiner2.name')
                    ->label('Penguji 2')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('examiner3.name')
                    ->label('Penguji 3')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year_generation')
                    ->label('Angkatan')
                    ->options(fn () => GuideExaminer::distinct()->pluck('year_generation', 'year_generation')),
                Tables\Filters\SelectFilter::make('graduation_semester')
                    ->label('Semester Kelulusan')
                    ->options(fn (): array => AcademicSemester::semesterOptionsFromThesisDates(static::getEloquentQuery()))
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'])) {
                            return $query;
                        }

                        return AcademicSemester::applySemesterFilter(
                            $query,
                            (string) $data['value'],
                            'thesis_date',
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('doc')
                    ->label('Bukti')
                    ->icon('heroicon-o-document')
                    ->iconButton()
                    ->tooltip('Bukti Kelulusan')
                    ->color('primary')
                    ->url(fn (GuideExaminer $record): ?string => filled($record->doc) ? $record->doc : null)
                    ->openUrlInNewTab()
                    ->visible(fn (GuideExaminer $record): bool => filled($record->doc)),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Hapus')
                    ->visible(fn (GuideExaminer $record): bool => static::canDelete($record)),
            ])
            ->bulkActions([])
            ->defaultSort('student.name');
    }

    public static function buildExamScheduleHtml(GuideExaminer $record): string
    {
        $formatDate = fn (?Carbon $date): ?string => filled($date)
            ? $date->locale('id')->isoFormat('DD MMM Y')
            : null;

        $items = collect([
            'Sempro' => $formatDate($record->proposal_date),
            'Semhas' => $formatDate($record->seminar_date),
            'Sidang' => $formatDate($record->thesis_date),
        ])->filter();

        if ($items->isEmpty()) {
            return '';
        }

        $html = $items
            ->map(function (string $formattedDate, string $label): string {
                return sprintf(
                    '<div class="flex items-center gap-2 text-sm leading-6"><span>%s</span><span class="text-gray-950 dark:text-white">%s</span></div>',
                    static::examScheduleLabelBadge($label),
                    e($formattedDate),
                );
            })
            ->implode('');

        return '<div class="flex flex-col gap-1">'.$html.'</div>';
    }

    public static function examScheduleLabelBadge(string $label): string
    {
        $colorClasses = match ($label) {
            'Sempro' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30',
            'Semhas' => 'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30',
            'Sidang' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30',
            default => 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30',
        };

        return sprintf(
            '<span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset %s">%s</span>',
            $colorClasses,
            e($label),
        );
    }

    public static function canDelete(Model $record): bool
    {
        /** @var GuideExaminer $record */
        return ! static::studentHasExamRegistrations($record)
            && blank($record->proposal_date)
            && blank($record->seminar_date)
            && blank($record->thesis_date);
    }

    public static function studentHasExamRegistrations(GuideExaminer $record): bool
    {
        if (array_key_exists('has_exam_registrations', $record->getAttributes())) {
            return (int) $record->getAttribute('has_exam_registrations') > 0;
        }

        return $record->examRegistrations()->exists();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withExists('examRegistrations as has_exam_registrations');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuideExaminers::route('/'),
            'create' => Pages\CreateGuideExaminer::route('/create'),
            'edit' => Pages\EditGuideExaminer::route('/{record}/edit'),
        ];
    }
}
