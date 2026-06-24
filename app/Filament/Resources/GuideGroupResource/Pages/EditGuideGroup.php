<?php

namespace App\Filament\Resources\GuideGroupResource\Pages;

use App\Filament\Resources\GuideGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGuideGroup extends EditRecord
{
    protected static string $resource = GuideGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
