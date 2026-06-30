<?php

namespace App\Filament\Concerns;

use Filament\Forms;

trait NuirSettingFormSchema
{
    /**
     * @return array<int, Forms\Components\Component>
     */
    protected static function nuirSettingFormSchema(bool $includeAngkatanFields = true): array
    {
        $schema = [];

        if ($includeAngkatanFields) {
            $schema[] = Forms\Components\TextInput::make('year_generation')
                ->label('Angkatan')
                ->required()
                ->unique(ignoreRecord: true);
            $schema[] = Forms\Components\Select::make('stage')
                ->label('Tahap')
                ->options([
                    1 => '1 - NUIR penuh',
                    2 => '2 - Judul saja',
                    3 => '3 - Tanpa NUIR',
                ])
                ->required()
                ->native(false);
            $schema[] = Forms\Components\Toggle::make('active')
                ->label('Angkatan aktif');
            $schema[] = Forms\Components\DatePicker::make('deadline')
                ->label('Deadline');
        }

        return array_merge($schema, [
            Forms\Components\Section::make('Batas Referensi')
                ->schema([
                    Forms\Components\TextInput::make('min_references_approved')
                        ->label('Minimal referensi disetujui')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(20)
                        ->default(10)
                        ->required(),
                    Forms\Components\TextInput::make('max_references')
                        ->label('Maksimal referensi')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(20)
                        ->default(10)
                        ->required(),
                ])->columns(2),
            Forms\Components\Section::make('Batas Kata Novelty')
                ->schema([
                    Forms\Components\TextInput::make('min_words_novelty')
                        ->label('Minimal kata')
                        ->numeric()
                        ->minValue(1),
                    Forms\Components\TextInput::make('max_words_novelty')
                        ->label('Maksimal kata')
                        ->numeric()
                        ->minValue(1),
                ])->columns(2),
            Forms\Components\Section::make('Batas Kata Urgency')
                ->schema([
                    Forms\Components\TextInput::make('min_words_urgency')
                        ->label('Minimal kata')
                        ->numeric()
                        ->minValue(1),
                    Forms\Components\TextInput::make('max_words_urgency')
                        ->label('Maksimal kata')
                        ->numeric()
                        ->minValue(1),
                ])->columns(2),
            Forms\Components\Section::make('Batas Kata Impact')
                ->schema([
                    Forms\Components\TextInput::make('min_words_impact')
                        ->label('Minimal kata')
                        ->numeric()
                        ->minValue(1),
                    Forms\Components\TextInput::make('max_words_impact')
                        ->label('Maksimal kata')
                        ->numeric()
                        ->minValue(1),
                ])->columns(2),
            Forms\Components\Section::make('Batas Karakter (opsional, jika batas kata kosong)')
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('max_chars_novelty')
                        ->label('Max karakter Novelty')
                        ->numeric()
                        ->minValue(100),
                    Forms\Components\TextInput::make('max_chars_urgency')
                        ->label('Max karakter Urgency')
                        ->numeric()
                        ->minValue(100),
                    Forms\Components\TextInput::make('max_chars_impact')
                        ->label('Max karakter Impact')
                        ->numeric()
                        ->minValue(100),
                ])->columns(3),
        ]);
    }
}
