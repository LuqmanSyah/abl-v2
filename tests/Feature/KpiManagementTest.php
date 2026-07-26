<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Filament\Resources\KpiResource\Pages\ListKpis;
use App\Filament\Resources\PerformanceReviewResource\Pages\CreatePerformanceReview;
use App\Filament\Resources\PerformanceReviewResource\Pages\ListPerformanceReviews;
use App\Models\Kpi;
use App\Models\PerformanceReview;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KpiManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_snapshots_kpis_calculates_subtotals_and_guards_submission(): void
    {
        $this->seed();

        $employee = User::query()->where('role', UserRole::Employee)->firstOrFail();
        $review = PerformanceReview::create([
            'user_id' => $employee->id,
            'reviewer_id' => $employee->manager_id,
            'period' => '2026-H2',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => ReviewStatus::Draft,
        ]);

        $this->assertCount(Kpi::count(), $review->reviewKpiDetails);
        $this->assertEquals(100, $review->reviewKpiDetails->sum('weight'));

        $detail = $review->reviewKpiDetails->first();
        $snapshotWeight = (float) $detail->weight;
        $detail->kpi->update(['weight' => 99]);
        $this->assertEquals($snapshotWeight, (float) $detail->fresh()->weight);

        try {
            $review->update(['status' => ReviewStatus::Submitted]);
            $this->fail('Review tanpa nilai manager seharusnya ditolak.');
        } catch (BusinessRuleException $exception) {
            $this->assertSame('Semua nilai KPI manager wajib diisi sebelum rapor disubmit.', $exception->getMessage());
        }

        $review->refresh()->reviewKpiDetails->each->update(['manager_score' => 80]);
        $this->assertEqualsWithDelta(
            $snapshotWeight * 0.8,
            (float) $detail->fresh()->subtotal_score,
            0.01,
        );

        $detail->fresh()->update(['weight' => $snapshotWeight + 1]);

        try {
            $review->refresh()->update(['status' => ReviewStatus::Submitted]);
            $this->fail('Review dengan total bobot bukan 100 seharusnya ditolak.');
        } catch (BusinessRuleException $exception) {
            $this->assertSame('Total bobot KPI harus 100 sebelum rapor disubmit.', $exception->getMessage());
        }

        $detail->fresh()->update(['weight' => $snapshotWeight]);
        $review->refresh()->update(['status' => ReviewStatus::Submitted]);

        $this->assertSame(ReviewStatus::Submitted, $review->fresh()->status);
    }

    public function test_kpi_and_performance_review_resources_create_records(): void
    {
        $this->seed();

        $admin = User::query()->where('role', UserRole::HrAdmin)->firstOrFail();
        $employee = User::query()->where('role', UserRole::Employee)->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(ListKpis::class)
            ->callAction('create', data: [
                'name' => 'Kepuasan Pelanggan',
                'category' => 'Kinerja',
                'weight' => 0,
            ])
            ->assertHasNoFormErrors();

        Livewire::test(CreatePerformanceReview::class)
            ->fillForm([
                'user_id' => $employee->id,
                'reviewer_id' => $employee->manager_id,
                'period' => '2026-H2',
                'start_date' => '2026-07-01',
                'end_date' => '2026-12-31',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $review = PerformanceReview::latest('id')->firstOrFail();
        $this->assertDatabaseHas(Kpi::class, ['name' => 'Kepuasan Pelanggan']);
        $this->assertCount(Kpi::count(), $review->reviewKpiDetails);
        $this->assertSame(ReviewStatus::Draft, $review->status);

        Livewire::test(ListPerformanceReviews::class)
            ->assertActionExists(TestAction::make('submit')->table($review));

        $this->get(route('filament.admin.resources.performance-reviews.edit', $review))
            ->assertOk()
            ->assertSee('Detail KPI')
            ->assertSee('Kepuasan Pelanggan');

        $review->reviewKpiDetails->each->update(['manager_score' => 80]);
        Livewire::test(ListPerformanceReviews::class)
            ->callAction(TestAction::make('submit')->table($review));
        $this->assertSame(ReviewStatus::Submitted, $review->fresh()->status);

        Livewire::test(ListPerformanceReviews::class)
            ->callAction(TestAction::make('approve')->table($review));
        $this->assertSame(ReviewStatus::Approved, $review->fresh()->status);
        $this->assertNotNull($review->fresh()->final_merit_score);

        Livewire::test(ListPerformanceReviews::class)
            ->callAction(TestAction::make('lock')->table($review));
        $this->assertSame(ReviewStatus::Locked, $review->fresh()->status);
    }
}
