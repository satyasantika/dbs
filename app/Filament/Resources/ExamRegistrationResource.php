<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamRegistrationResource\Pages;
use App\Models\ExamRegistration;
use App\Models\ExamType;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ExamRegistrationResource extends Resource
{
    protected static ?string $model = ExamRegistration::class;


    protected static ?string $navigationGroup = 'Manajemen Ujian';

    protected static ?string $modelLabel = 'Pendaftaran Ujian';

    protected static ?string $pluralModelLabel = 'Pendaftaran Ujian';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Ujian')
                    ->schema([
                        Forms\Components\Select::make('exam_type_id')
                            ->label('Jenis Ujian')
                            ->relationship('examtype', 'name')
                            ->required(),
                        Forms\Components\TextInput::make('registration_order')
                            ->label('Ujian Ke-')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('Mahasiswa')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\DatePicker::make('exam_date')
                            ->label('Tanggal Ujian'),
                        Forms\Components\TimePicker::make('exam_time')
                            ->label('Waktu Ujian'),
                        Forms\Components\TextInput::make('room')
                            ->label('Ruangan')
                            ->maxLength(100),
                    ])->columns(2),

                Forms\Components\Section::make('Pembimbing')
                    ->schema([
                        Forms\Components\Select::make('guide1_id')
                            ->label('Pembimbing 1')
                            ->options(fn () => User::role('dosen')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable(),
                        Forms\Components\Select::make('guide2_id')
                            ->label('Pembimbing 2')
                            ->options(fn () => User::role('dosen')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Penguji')
                    ->description('Tombol ↑/↓ untuk menyusun ulang urutan. Tombol "Set Ketua" untuk menetapkan Ketua Penguji.')
                    ->schema([
                        Forms\Components\Hidden::make('chief_id'),

                        Forms\Components\Select::make('examiner1_id')
                            ->label(fn (Forms\Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner1_id')
                                ? new HtmlString('Penguji 1 <span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 text-xs font-semibold text-success-700">★ Ketua</span>')
                                : 'Penguji 1')
                            ->options(fn () => User::role('dosen')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable()
                            ->hintActions([
                                Forms\Components\Actions\Action::make('set_chief_1')
                                    ->label('Set Ketua')
                                    ->icon('heroicon-m-star')
                                    ->color('warning')
                                    ->hidden(fn (Forms\Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner1_id'))
                                    ->action(fn (Forms\Set $set, Forms\Get $get) => $set('chief_id', $get('examiner1_id'))),
                                Forms\Components\Actions\Action::make('swap_down_1')
                                    ->label('↓')->tooltip('Tukar dengan Penguji 2')
                                    ->icon('heroicon-m-arrow-down')->color('gray')
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        [$a, $b] = [$get('examiner1_id'), $get('examiner2_id')];
                                        $set('examiner1_id', $b);
                                        $set('examiner2_id', $a);
                                    }),
                            ]),

                        Forms\Components\Select::make('examiner2_id')
                            ->label(fn (Forms\Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner2_id')
                                ? new HtmlString('Penguji 2 <span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 text-xs font-semibold text-success-700">★ Ketua</span>')
                                : 'Penguji 2')
                            ->options(fn () => User::role('dosen')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable()
                            ->hintActions([
                                Forms\Components\Actions\Action::make('set_chief_2')
                                    ->label('Set Ketua')
                                    ->icon('heroicon-m-star')
                                    ->color('warning')
                                    ->hidden(fn (Forms\Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner2_id'))
                                    ->action(fn (Forms\Set $set, Forms\Get $get) => $set('chief_id', $get('examiner2_id'))),
                                Forms\Components\Actions\Action::make('swap_up_2')
                                    ->label('↑')->tooltip('Tukar dengan Penguji 1')
                                    ->icon('heroicon-m-arrow-up')->color('gray')
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        [$a, $b] = [$get('examiner1_id'), $get('examiner2_id')];
                                        $set('examiner1_id', $b);
                                        $set('examiner2_id', $a);
                                    }),
                                Forms\Components\Actions\Action::make('swap_down_2')
                                    ->label('↓')->tooltip('Tukar dengan Penguji 3')
                                    ->icon('heroicon-m-arrow-down')->color('gray')
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        [$a, $b] = [$get('examiner2_id'), $get('examiner3_id')];
                                        $set('examiner2_id', $b);
                                        $set('examiner3_id', $a);
                                    }),
                            ]),

                        Forms\Components\Select::make('examiner3_id')
                            ->label(fn (Forms\Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner3_id')
                                ? new HtmlString('Penguji 3 <span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 text-xs font-semibold text-success-700">★ Ketua</span>')
                                : 'Penguji 3')
                            ->options(fn () => User::role('dosen')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable()
                            ->hintActions([
                                Forms\Components\Actions\Action::make('set_chief_3')
                                    ->label('Set Ketua')
                                    ->icon('heroicon-m-star')
                                    ->color('warning')
                                    ->hidden(fn (Forms\Get $get) => $get('chief_id') && $get('chief_id') == $get('examiner3_id'))
                                    ->action(fn (Forms\Set $set, Forms\Get $get) => $set('chief_id', $get('examiner3_id'))),
                                Forms\Components\Actions\Action::make('swap_up_3')
                                    ->label('↑')->tooltip('Tukar dengan Penguji 2')
                                    ->icon('heroicon-m-arrow-up')->color('gray')
                                    ->action(function (Forms\Set $set, Forms\Get $get) {
                                        [$a, $b] = [$get('examiner2_id'), $get('examiner3_id')];
                                        $set('examiner2_id', $b);
                                        $set('examiner3_id', $a);
                                    }),
                            ]),
                    ])->columns(1),

                Forms\Components\Section::make('Detail Skripsi')
                    ->schema([
                        Forms\Components\Textarea::make('title')
                            ->label('Judul Skripsi')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('ipk')
                            ->label('IPK')
                            ->numeric()
                            ->step(0.01),
                        Forms\Components\Textarea::make('online_link')
                            ->label('Link Online')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('online_user')
                            ->label('User Meeting')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('online_password')
                            ->label('Password Meeting')
                            ->maxLength(100),
                    ])->columns(2),

                Forms\Components\Section::make('Hasil Ujian')
                    ->schema([
                        Forms\Components\Placeholder::make('grade_display')
                            ->label('Nilai Akhir')
                            ->content(fn (?ExamRegistration $record): string => $record?->grade !== null
                                ? number_format((float) $record->grade, 2)
                                : '—'),
                        Forms\Components\Placeholder::make('letter_display')
                            ->label('Huruf Mutu')
                            ->content(fn (?ExamRegistration $record): string => $record?->letter ?? '—'),
                        Forms\Components\Placeholder::make('pass_display')
                            ->label('Status Kelulusan')
                            ->content(fn (?ExamRegistration $record): HtmlString|string => match (true) {
                                is_null($record?->pass_exam) => '—',
                                (bool) $record->pass_exam    => new HtmlString('<span class="font-semibold text-success-600">✓ Lulus</span>'),
                                default                      => new HtmlString('<span class="font-semibold text-danger-600">✗ Belum Lulus</span>'),
                            }),
                        Forms\Components\Placeholder::make('sent_display')
                            ->label('Pesan Hasil ke Mahasiswa')
                            ->content(function (?ExamRegistration $record): HtmlString|string {
                                if (!$record) return '—';
                                if ($record->sent_at) {
                                    $tgl = $record->sent_at->locale('id')->isoFormat('D MMMM Y, [pukul] HH.mm');
                                    return new HtmlString('<span class="text-success-700 font-medium">✓ Dikabari pada ' . e($tgl) . '</span>');
                                }
                                $pending = \App\Models\ExamScore::where('exam_registration_id', $record->id)
                                    ->whereNull('grade')->count();
                                if ($pending > 0) {
                                    return new HtmlString('<span class="text-warning-600">Menunggu selesai penilaian <strong>' . $pending . '</strong> penguji</span>');
                                }
                                return new HtmlString('<span class="text-primary-600">Penilaian sudah lengkap — pesan belum dikirim</span>');
                            }),
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('examtype.name')
                    ->label('Jenis Ujian')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('registration_order')
                    ->label('Ke-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('exam_date')
                    ->label('Tgl Ujian')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->title),
                Tables\Columns\TextColumn::make('grade')
                    ->label('Nilai'),
                Tables\Columns\TextColumn::make('letter')
                    ->label('Huruf'),
                Tables\Columns\IconColumn::make('pass_exam')
                    ->label('Lulus')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_type_id')
                    ->label('Jenis Ujian')
                    ->relationship('examtype', 'name'),
                Tables\Filters\TernaryFilter::make('pass_exam')
                    ->label('Status Kelulusan'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('exam_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamRegistrations::route('/'),
            'create' => Pages\CreateExamRegistration::route('/create'),
            'edit' => Pages\EditExamRegistration::route('/{record}/edit'),
        ];
    }
}
