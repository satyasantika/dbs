<x-filament-panels::page class="fi-dosen-edit-scoring-page">
    @include('filament.dosen.pages.scoring-form-fields', array_merge($formData, [
        'returnUrl' => $this->getReturnUrl(),
        'formAction' => route('scoring.update', $record),
        'previousExams' => $previousExams,
    ]))
</x-filament-panels::page>
