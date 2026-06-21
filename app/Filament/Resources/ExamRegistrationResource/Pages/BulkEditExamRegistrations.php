<?php

namespace App\Filament\Resources\ExamRegistrationResource\Pages;

use App\Filament\Resources\ExamRegistrationResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class BulkEditExamRegistrations extends Page
{
    protected static string $resource = ExamRegistrationResource::class;

    protected static string $view = 'filament.resources.exam-registration-resource.pages.bulk-edit-exam-registrations';

    protected static ?string $title = 'Edit Banyak — Copy-Paste dari Spreadsheet';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('← Kembali ke Daftar')
                ->color('gray')
                ->url(ExamRegistrationResource::getUrl('index')),
        ];
    }
}
