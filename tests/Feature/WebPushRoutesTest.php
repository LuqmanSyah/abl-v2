<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BranchOffice;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPushRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_subscribe_and_unsubscribe(): void
    {
        $user = $this->user();
        $payload = [
            'endpoint' => 'https://push.example/subscription',
            'keys' => [
                'auth' => 'auth-token',
                'p256dh' => 'public-key',
            ],
        ];

        $this->actingAs($user)
            ->postJson(route('webpush.subscribe'), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $user->id,
            'endpoint' => $payload['endpoint'],
        ]);

        $this->postJson(route('webpush.unsubscribe'), ['endpoint' => $payload['endpoint']])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $payload['endpoint']]);
    }

    public function test_inactive_user_cannot_register_push_subscription(): void
    {
        $user = $this->user(false);

        $this->actingAs($user)
            ->postJson(route('webpush.subscribe'), [
                'endpoint' => 'https://push.example/inactive',
                'keys' => ['auth' => 'auth-token', 'p256dh' => 'public-key'],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => 'https://push.example/inactive',
        ]);
    }

    public function test_pwa_registration_reports_failures(): void
    {
        $html = view('pwa.register')->render();

        $this->assertStringContainsString(route('webpush.subscribe'), $html);
        $this->assertStringContainsString("console.error('Web push registration failed.'", $html);
        $this->assertStringContainsString('VAPID public key is not configured.', $html);
        $this->assertStringContainsString("navigator.serviceWorker.register('/sw.js')", $html);
    }

    private function user(bool $active = true): User
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $position = Position::create(['department_id' => $department->id, 'title' => 'IT Admin', 'level' => 1]);
        $schedule = WorkSchedule::create([
            'name' => 'Regular',
            'check_in_time' => '08:00',
            'check_out_time' => '17:00',
            'late_tolerance_minutes' => 15,
            'alfa_cutoff_minutes' => 120,
        ]);
        $branch = BranchOffice::create([
            'name' => 'Jakarta',
            'code' => 'JKT',
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'allowed_radius_meters' => 100,
        ]);

        return User::factory()->create([
            'position_id' => $position->id,
            'work_schedule_id' => $schedule->id,
            'branch_office_id' => $branch->id,
            'role' => UserRole::ItAdmin,
            'status' => $active,
        ]);
    }
}
