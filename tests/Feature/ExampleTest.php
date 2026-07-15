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
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_login_page_uses_primary_heading_and_contrasting_eyebrow(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<h1>Masuk ke akun</h1>', false)
            ->assertDontSee('<h2>Masuk ke akun</h2>', false)
            ->assertSee('main .eyebrow { color: #1d4ed8; }', false);
    }

    public function test_login_validation_messages_are_in_indonesian(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'email kantor wajib diisi.',
                'password' => 'kata sandi wajib diisi.',
            ]);
    }
}
