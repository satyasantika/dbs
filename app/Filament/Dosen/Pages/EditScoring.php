<?php

namespace App\Filament\Dosen\Pages;

use App\Models\ExamFormItem;
use App\Models\ExamRegistration;
use App\Models\ExamScore;
use App\Services\Examination\ScoringFormPresenter;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class EditScoring extends Page
{
    protected static ?string $slug = 'examination/scoring/{record}/edit';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.dosen.pages.edit-scoring';

    public ExamScore $record;

    public array $formData = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access examination/scoring') ?? false;
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'dosen', $tenant);
    }

    public function mount(ExamScore $record): void
    {
        if ($record->user_id !== auth()->id() && ! auth()->user()->can('force edit score')) {
            $this->redirect(Scoring::getUrl(['activeTab' => 'unscored']));

            return;
        }

        $record->loadMissing(['registration.student', 'registration.examtype', 'lecture']);

        $examRegistration = ExamRegistration::query()->findOrFail($record->exam_registration_id);
        $formItems = ExamFormItem::query()
            ->select('id', 'name', 'exam_type_id')
            ->where('exam_type_id', $examRegistration->exam_type_id)
            ->get();

        $this->record = $record;
        $this->formData = app(ScoringFormPresenter::class)->present($record, $examRegistration, $formItems);
    }

    public function getTitle(): string | Htmlable
    {
        $studentName = $this->record->registration?->student?->name ?? '-';

        return 'Menilai '.$studentName;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->url(Scoring::getUrl(['activeTab' => 'unscored'])),
        ];
    }
}
