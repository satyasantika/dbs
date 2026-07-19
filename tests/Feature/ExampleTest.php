<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // '/' redirects to the public Filament Beranda page (App\Filament\
        // Informasi\Pages\Beranda) — see routes/web.php.
        $response = $this->get('/');

        $response->assertRedirect();
        $this->get($response->headers->get('Location'))->assertOk();
    }
}
