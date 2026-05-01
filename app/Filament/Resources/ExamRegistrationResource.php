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

                Forms\Components\Section::make('Pembimbing & Penguji')
                    ->schema([
                        Forms\Components\Select::make('guide1_id')
                            ->label('Pembimbing 1')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable(),
                        Forms\Components\Select::make('guide2_id')
                            ->label('Pembimbing 2')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable(),
                        Forms\Components\Select::make('examiner1_id')
                            ->label('Penguji 1')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable(),
                        Forms\Components\Select::make('examiner2_id')
                            ->label('Penguji 2')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable(),
                        Forms\Components\Select::make('examiner3_id')
                            ->label('Penguji 3')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable(),
                        Forms\Components\Select::make('chief_id')
                            ->label('Ketua Penguji')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Skripsi')
                    ->schema([
                        Forms\Components\Textarea::make('title')
                            ->label('Judul Skripsi')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('ipk')
                            ->label('IPK')
                            ->numeric()
                            ->step(0.01),
                        Forms\Components\TextInput::make('online_link')
                            ->label('Link Online')
                            ->url()
                            ->maxLength(500),
                        Forms\Components\TextInput::make('online_user')
                            ->label('User Meeting')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('online_password')
                            ->label('Password Meeting')
                            ->maxLength(100),
                    ])->columns(2)->collapsed(),

                Forms\Components\Section::make('Hasil Ujian')
                    ->schema([
                        Forms\Components\TextInput::make('grade')
                            ->label('Nilai Akhir')
                            ->numeric()
                            ->step(0.01),
                        Forms\Components\TextInput::make('letter')
                            ->label('Huruf Mutu')
                            ->maxLength(5),
                        Forms\Components\Toggle::make('pass_exam')
                            ->label('Lulus'),
                    ])->columns(3)->collapsed(),
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
