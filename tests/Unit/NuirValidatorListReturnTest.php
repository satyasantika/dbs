<?php

namespace Tests\Unit;

use App\Filament\NuirValidator\Resources\NuirReferenceResource;
use App\Filament\NuirValidator\Resources\NuirSubmissionResource;
use App\Support\NuirValidatorListReturn;
use Tests\TestCase;

class NuirValidatorListReturnTest extends TestCase
{
    public function test_builds_return_keys(): void
    {
        $this->assertSame('submissions:assigned', NuirValidatorListReturn::submissionKey());
        $this->assertSame(
            'submissions:validation_complete',
            NuirValidatorListReturn::submissionKey(NuirSubmissionResource::DASHBOARD_VIEW_VALIDATION_COMPLETE),
        );
        $this->assertSame(
            'references:pending_references',
            NuirValidatorListReturn::referenceKey(NuirReferenceResource::DASHBOARD_VIEW_PENDING_REFERENCES),
        );
    }

    public function test_resolves_list_urls_from_return_key(): void
    {
        $this->assertSame(
            NuirSubmissionResource::listUrl(NuirSubmissionResource::DASHBOARD_VIEW_ASSIGNED),
            NuirValidatorListReturn::url('submissions:assigned'),
        );

        $this->assertSame(
            NuirReferenceResource::listUrl(NuirReferenceResource::DASHBOARD_VIEW_AWAITING_REVALIDATION),
            NuirValidatorListReturn::url('references:awaiting_revalidation'),
        );

        $this->assertSame(
            NuirSubmissionResource::listUrl(NuirSubmissionResource::DASHBOARD_VIEW_ASSIGNED),
            NuirValidatorListReturn::url(null),
        );
    }

    public function test_resolves_back_labels_from_return_key(): void
    {
        $this->assertSame(
            'Kembali ke Submission Ditugaskan',
            NuirValidatorListReturn::label('submissions:assigned'),
        );

        $this->assertSame(
            'Kembali ke Referensi Pending',
            NuirValidatorListReturn::label('references:pending_references'),
        );

        $this->assertSame(
            'Kembali ke Daftar Submission',
            NuirValidatorListReturn::label(null),
        );
    }
}
