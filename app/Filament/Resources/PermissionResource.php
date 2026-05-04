<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationGroup = 'Manajemen Pengguna';

    protected static ?string $modelLabel = 'Permission';

    protected static ?string $pluralModelLabel = 'Permissions';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama permission')
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
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(', ')
                    ->limitList(3),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('guard_name')
                    ->label('Guard')
                    ->options(fn () => Permission::query()->distinct()->pluck('guard_name', 'guard_name')->all()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn (Permission $record): bool => $record->isAssignedToUsersOrRoles())
                    ->tooltip(fn (Permission $record): ?string => $record->isAssignedToUsersOrRoles()
                        ? 'Masih dipakai oleh role atau pengguna; hapus penempelan terlebih dahulu.'
                        : null)
                    ->using(function (Permission $record): bool {
                        if ($record->isAssignedToUsersOrRoles()) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak dapat menghapus')
                                ->body('Permission ini masih terhubung ke tabel roles atau pengguna (langsung).')
                                ->send();

                            return false;
                        }

                        $record->delete();

                        return true;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (EloquentCollection $records): void {
                            /** @var EloquentCollection<int, Permission> $records */
                            $blocked = $records->filter(
                                fn (Permission $permission): bool => $permission->isAssignedToUsersOrRoles(),
                            );

                            if ($blocked->isNotEmpty()) {
                                Notification::make()
                                    ->danger()
                                    ->title('Penghapusan dibatalkan')
                                    ->body(
                                        'Permission berikut masih dipakai oleh pengguna atau role: '
                                            .$blocked->pluck('name')->sort()->values()->implode(', '),
                                    )
                                    ->persistent()
                                    ->send();

                                return;
                            }

                            $records->each(fn (Model $record) => $record->delete());

                            Notification::make()
                                ->success()
                                ->title(__('filament-actions::delete.multiple.notifications.deleted.title'))
                                ->send();
                        }),
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
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
