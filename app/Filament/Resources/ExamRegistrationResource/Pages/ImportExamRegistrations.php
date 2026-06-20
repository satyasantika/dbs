<?php

namespace App\Filament\Resources\ExamRegistrationResource\Pages;

use App\Filament\Resources\ExamRegistrationResource;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;

class ImportExamRegistrations extends Page
{
    protected static string $resource = ExamRegistrationResource::class;

    protected static string $view = 'filament.resources.exam-registration-resource.pages.import-exam-registrations';

    protected static ?string $title = 'Import Banyak — Copy-Paste dari Spreadsheet';

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
