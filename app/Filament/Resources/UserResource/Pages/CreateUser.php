<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    private string $activeStatus = 'aktif';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->activeStatus = $data['active_status'] ?? 'aktif';
        unset($data['active_status']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $method = $this->activeStatus === 'aktif' ? 'givePermissionTo' : 'revokePermissionTo';
        $this->record->{$method}('active');
    }
}
