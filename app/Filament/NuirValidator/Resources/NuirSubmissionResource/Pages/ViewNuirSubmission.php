<?php

namespace App\Filament\NuirValidator\Resources\NuirSubmissionResource\Pages;

use App\Filament\NuirValidator\Resources\NuirSubmissionResource;
use App\Models\NuirSubmission;
use Filament\Resources\Pages\ViewRecord;

class ViewNuirSubmission extends ViewRecord
{
    protected static string $resource = NuirSubmissionResource::class;

    public function getSubheading(): ?string
    {
        /** @var NuirSubmission $submission */
        $submission = $this->record;

        if (! NuirSubmissionResource::canReviewReferences($submission)) {
            return 'Submission masih draft — referensi hanya dapat dilihat.';
        }

        $approved = $submission->references()->where('ref_approved', true)->count();
        $total = $submission->references()->count();

        return "{$approved} dari {$total} referensi disetujui.";
    }
}
