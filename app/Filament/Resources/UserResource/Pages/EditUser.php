<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Kembali');
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getResource()::getUrl('index');
    }

    private string $activeStatus = 'aktif';

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->activeStatus = $data['active_status'] ?? 'aktif';
        unset($data['active_status']);

        return $data;
    }

    protected function afterSave(): void
    {
        $method = $this->activeStatus === 'aktif' ? 'givePermissionTo' : 'revokePermissionTo';
        $this->record->{$method}('active');
    }
}
