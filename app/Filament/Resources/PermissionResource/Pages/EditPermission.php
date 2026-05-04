<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use App\Models\Permission;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPermission extends EditRecord
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->disabled(fn (): bool => $this->record->isAssignedToUsersOrRoles())
                ->tooltip(fn (): ?string => $this->record->isAssignedToUsersOrRoles()
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
        ];
    }
}
