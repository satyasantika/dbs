<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesDbsPanelAccess;
use App\Filament\Resources\SelectionElementCommentResource\Pages;
use App\Models\SelectionElement;
use App\Models\SelectionElementComment;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SelectionElementCommentResource extends Resource
{
    use AuthorizesDbsPanelAccess;

    protected static ?string $model = SelectionElementComment::class;

    protected static ?string $navigationGroup = 'Manajemen Seleksi';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $modelLabel = 'Komentar NUIR';

    protected static ?string $pluralModelLabel = 'Komentar NUIR';

    protected static ?int $navigationSort = 5;

    protected static function dbsAccessPermission(): string
    {
        return 'access selection/element/comments';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('selection_element_id')
                ->label('Elemen Mahasiswa')
                ->options(fn () => SelectionElement::with('stage.student')
                    ->where('approved', 0)
                    ->latest()
                    ->get()
                    ->mapWithKeys(fn (SelectionElement $element) => [
                        $element->id => ($element->stage?->student?->name ?? '-')
                            .' — '.$element->element
                            .' (tahap '.$element->stage?->stage_order.')',
                    ]))
                ->searchable()
                ->required()
                ->disabled(fn (?SelectionElementComment $record) => filled($record)),
            Forms\Components\Select::make('user_id')
                ->label('Verifikator')
                ->options(fn () => User::role('dosen')->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required()
                ->disabled(fn (?SelectionElementComment $record) => filled($record)),
            Forms\Components\Select::make('verificator')
                ->label('Peran Verifikator')
                ->options([
                    'dosen' => 'Dosen',
                    'dbs' => 'DBS',
                ])
                ->required()
                ->disabled(fn (?SelectionElementComment $record) => filled($record)),
            Forms\Components\Textarea::make('comment')
                ->label('Komentar')
                ->rows(10)
                ->columnSpanFull(),
            Forms\Components\Toggle::make('revised')
                ->label('Perlu direvisi'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('element.stage.student.name')->label('Mahasiswa')->searchable(),
                Tables\Columns\TextColumn::make('element.element')->label('Elemen'),
                Tables\Columns\TextColumn::make('verifiedBy.name')->label('Verifikator'),
                Tables\Columns\TextColumn::make('verificator')->label('Peran'),
                Tables\Columns\IconColumn::make('revised')->label('Revisi')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSelectionElementComments::route('/'),
            'create' => Pages\CreateSelectionElementComment::route('/create'),
            'edit' => Pages\EditSelectionElementComment::route('/{record}/edit'),
        ];
    }
}
