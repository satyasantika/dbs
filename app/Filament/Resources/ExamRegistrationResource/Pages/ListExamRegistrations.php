<?php

namespace App\Filament\Resources\ExamRegistrationResource\Pages;

use App\Filament\Resources\ExamRegistrationResource;
use App\Filament\Resources\SetScoringToExaminerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExamRegistrations extends ListRecords
{
    protected static string $resource = ExamRegistrationResource::class;

    protected static string $view = 'filament.resources.exam-registration-resource.pages.list-exam-registrations';

    protected function getHeaderActions(): array
    {
        $pendingSetCount = SetScoringToExaminerResource::pendingCount();

        return [
            Actions\Action::make('setScoringToExaminerYet')
                ->label('Set Penguji (' . $pendingSetCount . ')')
                ->icon('heroicon-o-user-plus')
                ->color('warning')
                ->url(SetScoringToExaminerResource::getUrl())
                ->visible($pendingSetCount > 0),
            Actions\Action::make('pasteImport')
                ->label('Import Banyak')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('success')
                ->url(ExamRegistrationResource::getUrl('import')),
            Actions\Action::make('bulkEdit')
                ->label('Edit Banyak')
                ->icon('heroicon-o-pencil-square')
                ->color('info')
                ->url(ExamRegistrationResource::getUrl('bulk-edit')),
            Actions\CreateAction::make(),
        ];
    }
}
