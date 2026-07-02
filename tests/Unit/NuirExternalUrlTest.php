<?php

namespace Tests\Unit;

use App\Support\NuirExternalUrl;
use PHPUnit\Framework\TestCase;

class NuirExternalUrlTest extends TestCase
{
    public function test_normalize_menambahkan_https(): void
    {
        $this->assertSame(
            'https://drive.google.com/file/d/abc/view',
            NuirExternalUrl::normalize('drive.google.com/file/d/abc/view'),
        );
    }

    public function test_normalize_mempertahankan_https(): void
    {
        $url = 'https://drive.google.com/file/d/abc/view';

        $this->assertSame($url, NuirExternalUrl::normalize($url));
    }

    public function test_normalize_mengembalikan_null_untuk_url_tidak_valid(): void
    {
        $this->assertNull(NuirExternalUrl::normalize('bukan-url'));
    }

    public function test_is_google_drive_mengenali_drive_dan_docs(): void
    {
        $this->assertTrue(NuirExternalUrl::isGoogleDrive('https://drive.google.com/file/d/abc/view'));
        $this->assertTrue(NuirExternalUrl::isGoogleDrive('docs.google.com/document/d/abc/edit'));
        $this->assertFalse(NuirExternalUrl::isGoogleDrive('https://example.com/file.pdf'));
    }

    public function test_normalize_google_drive_menolak_bukan_drive(): void
    {
        $this->assertNull(NuirExternalUrl::normalizeGoogleDrive('https://example.com/file.pdf'));
    }
}
