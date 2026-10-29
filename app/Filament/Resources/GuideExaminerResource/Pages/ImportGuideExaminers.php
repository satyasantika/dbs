<?php

namespace App\Filament\Resources\GuideExaminerResource\Pages;

use App\Filament\Resources\GuideExaminerResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class ImportGuideExaminers extends Page
{
    protected static string $resource = GuideExaminerResource::class;

    protected static string $view = 'filament.resources.guide-examiner-resource.pages.import-guide-examiners';

    protected static ?string $title = 'Import Massal Pembimbing & Penguji — Copy-Paste dari Excel';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali ke Daftar')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(GuideExaminerResource::getUrl('index')),
        ];
    }
}
