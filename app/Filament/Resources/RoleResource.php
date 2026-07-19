<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesAdminPanelAccess;
use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class RoleResource extends Resource
{
    use AuthorizesAdminPanelAccess;

    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Manajemen Pengguna';

    protected static ?string $modelLabel = 'Role';

    protected static ?string $pluralModelLabel = 'Roles';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Role')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama role')
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where(
                                    'guard_name',
                                    $get('guard_name') ?: 'web',
                                ),
                            ),
                        Forms\Components\TextInput::make('guard_name')
                            ->label('Guard')
                            ->default('web')
                            ->required()
                            ->live(onBlur: true)
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Permissions untuk role ini')
                    ->description('Centang permission yang dimiliki oleh semua pengguna dengan role ini.')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Permissions')
                            ->relationship(
                                'permissions',
                                'name',
                                fn ($query, $livewire) => $query->where(
                                    'guard_name',
                                    $livewire->data['guard_name'] ?? 'web',
                                ),
                            )
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->gridDirection('row'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'default' => 1,
            ])
            ->columns([
                // Dibungkus Layout\Stack — ini yang membuat Filament benar-benar
                // merender tiap baris sebagai card (hasColumnsLayout()), bukan
                // cuma ->contentGrid() saja (itu cuma mengatur lebar kolom grid,
                // tanpa Stack tabelnya tetap <table> biasa).
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('name')
                            ->label('Nama')
                            ->searchable()
                            ->sortable()
                            ->weight(FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('guard_name')
                            ->label('Guard')
                            ->badge()
                            ->color('gray')
                            ->sortable()
                            ->grow(false),
                    ]),
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('permissions_count')
                            ->label('Jumlah permission')
                            ->counts('permissions')
                            ->badge()
                            ->color('gray')
                            ->sortable(),
                        Tables\Columns\TextColumn::make('users_count')
                            ->label('Jumlah pengguna')
                            ->counts('users')
                            ->badge()
                            ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                            ->sortable(),
                    ]),
                    Tables\Columns\TextColumn::make('updated_at')
                        ->label('Diperbarui')
                        ->dateTime('d M Y H:i')
                        ->sortable()
                        ->color('gray')
                        ->toggleable(isToggledHiddenByDefault: true),
                ])->space(2),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('guard_name')
                    ->label('Guard')
                    ->options(fn () => Role::query()->distinct()->pluck('guard_name', 'guard_name')->all()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    // Role yang masih dipakai (punya pengguna) tidak boleh
                    // dihapus — akan melepas role dari semua pengguna itu
                    // secara diam-diam kalau dipaksakan.
                    ->hidden(fn (Role $record): bool => $record->name === 'admin' || $record->users_count > 0),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
