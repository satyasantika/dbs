<x-filament-panels::page class="fi-dosen-edit-scoring-page">
    <x-filament::section>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

        @include('examination.partials.scoring-form-fields', array_merge($formData, [
            'returnUrl' => $this->getReturnUrl(),
            'formAction' => route('scoring.update', $record),
        ]))
    </x-filament::section>
</x-filament-panels::page>
