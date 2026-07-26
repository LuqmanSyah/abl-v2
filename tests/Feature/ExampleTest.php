<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_login_page_is_simple_responsive_and_hides_role_explanation(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<h1>Masuk ke akun</h1>', false)
            ->assertDontSee('<h2>Masuk ke akun</h2>', false)
            ->assertSee('Gunakan email dan kata sandi kantor.')
            ->assertSee('data-password-toggle', false)
            ->assertSee('@media (max-width: 520px)', false)
            ->assertDontSee('Aktivitas dan pengembangan')
            ->assertDontSee('Tim dan persetujuan')
            ->assertDontSee('Organisasi dan laporan');
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

    public function test_employee_login_redirects_to_employee_panel(): void
    {
        $this->seed();
        $user = User::query()->where('role', UserRole::Employee)->firstOrFail();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/app');

        $this->assertAuthenticatedAs($user);
    }
}
