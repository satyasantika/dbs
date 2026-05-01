<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuideExaminerResource\Pages;
use App\Models\GuideExaminer;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GuideExaminerResource extends Resource
{
    protected static ?string $model = GuideExaminer::class;


    protected static ?string $navigationGroup = 'Manajemen Ujian';

    protected static ?string $modelLabel = 'Pembimbing & Penguji';

    protected static ?string $pluralModelLabel = 'Pembimbing & Penguji';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $lecturerOptions = fn () => User::whereHas('roles', fn ($q) => $q->whereIn('name', ['dosen', 'lecture']))
            ->orderBy('name')->pluck('name', 'id');
        $studentOptions = fn () => User::whereHas('roles', fn ($q) => $q->where('name', 'mahasiswa'))
            ->orderBy('name')->pluck('name', 'id');

        return $form
            ->schema([
                Forms\Components\Section::make('Data Mahasiswa')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Mahasiswa')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('year_generation')
                            ->label('Angkatan')
                            ->required()
                            ->maxLength(10),
                    ])->columns(2),

                Forms\Components\Section::make('Pembimbing')
                    ->schema([
                        Forms\Components\Select::make('guide1_id')
                            ->label('Pembimbing 1')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Select::make('guide2_id')
                            ->label('Pembimbing 2')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Penguji')
                    ->schema([
                        Forms\Components\Select::make('examiner1_id')
                            ->label('Penguji 1')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Select::make('examiner2_id')
                            ->label('Penguji 2')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Select::make('examiner3_id')
                            ->label('Penguji 3')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Select::make('chief_id')
                            ->label('Ketua Penguji')
                            ->options(fn () => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Jadwal Ujian')
                    ->schema([
                        Forms\Components\DatePicker::make('proposal_date')
                            ->label('Tanggal Seminar Proposal'),
                        Forms\Components\DatePicker::make('seminar_date')
                            ->label('Tanggal Seminar Hasil'),
                        Forms\Components\DatePicker::make('thesis_date')
                            ->label('Tanggal Sidang Skripsi'),
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
                Tables\Columns\TextColumn::make('year_generation')
                    ->label('Angkatan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('guide1.name')
                    ->label('Pembimbing 1')
                    ->searchable(),
                Tables\Columns\TextColumn::make('guide2.name')
                    ->label('Pembimbing 2')
                    ->searchable(),
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
            ->defaultSort('student.name');
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
