<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Positions\PositionResource;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_only_hr_can_access_organization_resources(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $this->actingAs($employee);

        $this->assertFalse(UserResource::canViewAny());
        $this->assertFalse(UnitResource::canViewAny());
        $this->assertFalse(PositionResource::canViewAny());

        $hr = User::factory()->create(['role' => UserRole::Hr]);
        $this->actingAs($hr);

        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(UnitResource::canViewAny());
        $this->assertTrue(PositionResource::canViewAny());
    }

    public function test_inactive_user_cannot_access_panel(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->assertFalse($user->canAccessPanel(filament()->getPanel('admin')));
    }
}
