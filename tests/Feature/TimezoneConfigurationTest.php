<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TimezoneConfigurationTest extends TestCase
{
    public function test_runtime_and_database_clocks_are_aligned(): void
    {
        $this->assertArrayHasKey('deployment:check-timezone', Artisan::all());

        $this->artisan('deployment:check-timezone')
            ->expectsOutputToContain('Asia/Jakarta')
            ->assertSuccessful();
    }

    public function test_scheduled_commands_use_jakarta_timezone(): void
    {
        $events = collect(app(Schedule::class)->events());

        foreach ([
            'attendance:aggregate',
            'career:expire-promotions',
            'career:apply-promotions',
            'career:scan-candidates',
        ] as $command) {
            $event = $events->first(fn ($event): bool => str_contains($event->command, $command));

            $this->assertNotNull($event, "{$command} is not scheduled.");
            $this->assertSame('Asia/Jakarta', $event->timezone);
        }
    }

    public function test_compose_sets_container_and_mysql_timezones(): void
    {
        $compose = file_get_contents(base_path('compose.yaml'));

        $this->assertStringContainsString('TZ: "${APP_TIMEZONE:-Asia/Jakarta}"', $compose);
        $this->assertStringContainsString('--default-time-zone=+07:00', $compose);
    }
}
