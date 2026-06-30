<?php

namespace Tests\Unit;

use App\Support\NuirReferenceRevisionFields;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NuirReferenceRevisionFieldsTest extends TestCase
{
    public function test_normalizes_and_labels_fields(): void
    {
        $this->assertSame(
            ['link_ojs', 'quote'],
            NuirReferenceRevisionFields::normalize(['quote', 'link_ojs', 'invalid']),
        );

        $this->assertSame(
            'Link OJS, Kutipan',
            NuirReferenceRevisionFields::labelsText(['link_ojs', 'quote']),
        );
    }

    public function test_requires_at_least_one_field_for_revision(): void
    {
        $this->expectException(ValidationException::class);

        NuirReferenceRevisionFields::assertSelectedForRevision([]);
    }
}
