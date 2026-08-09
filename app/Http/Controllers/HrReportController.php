<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Position;
use App\Models\ReviewPeriod;
use App\Models\Unit;
use App\Services\HrReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use League\Csv\Writer as CsvWriter;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrReportController extends Controller
{
    private const AVAILABLE_COLUMNS = [
        'employee_number' => 'Nomor Pegawai',
        'name' => 'Nama',
        'unit' => 'Unit',
        'position' => 'Jabatan',
        'attendance_count' => 'Total Absensi Dinas',
        'valid_attendance_count' => 'Absensi Dinas Valid',
        'merit_score' => 'Skor Merit',
        'training_count' => 'Pelatihan',
        'completed_training_count' => 'Pelatihan Selesai',
        'mentoring_count' => 'Mentoring',
        'completed_mentoring_count' => 'Mentoring Selesai',
    ];

    public function __construct(private HrReportService $reports) {}

    public function index(Request $request)
    {
        $this->authorizeHr($request);
        $filters = $this->filters($request);

        return view('reports.hr', [
            'filters' => $filters,
            'allColumns' => self::AVAILABLE_COLUMNS,
            'columns' => $this->resolveColumns($filters),
            'rows' => $this->rows($filters),
            'periods' => ReviewPeriod::orderByDesc('starts_at')->get(),
            'units' => Unit::orderBy('name')->get(),
            'positions' => Position::orderBy('name')->get(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeHr($request);
        $filters = $this->filters($request);
        $columns = $this->resolveColumns($filters);
        $rows = $this->rows($filters);

        $headers = array_values($columns);
        $cellMap = array_keys($columns);

        return response()->streamDownload(function () use ($rows, $headers, $cellMap): void {
            $writer = CsvWriter::createFromPath('php://output', 'w');
            $writer->setEscape('');
            $writer->addFormatter(function (array $row): array {
                return array_map(fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value, $row);
            });
            $writer->insertOne($headers);
            foreach ($rows as $group) {
                foreach ($group['items'] as $row) {
                    $values = $this->flatten($row, $cellMap);
                    $writer->insertOne($values);
                }
            }
        }, 'laporan-sdm-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request): Response
    {
        $this->authorizeHr($request);
        $filters = $this->filters($request);
        $columns = $this->resolveColumns($filters);

        $pdf = Pdf::loadView('reports.hr-pdf', [
            'filters' => $filters,
            'columns' => $columns,
            'rows' => $this->rows($filters),
            'periods' => ReviewPeriod::orderByDesc('starts_at')->get(),
        ]);

        return $pdf->download('laporan-sdm-'.now()->format('Ymd-His').'.pdf');
    }

    public function exportXlsx(Request $request): StreamedResponse
    {
        $this->authorizeHr($request);
        $filters = $this->filters($request);
        $columns = $this->resolveColumns($filters);
        $rows = $this->rows($filters);

        $headers = array_values($columns);
        $cellMap = array_keys($columns);

        return response()->streamDownload(function () use ($rows, $headers, $cellMap): void {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($headers));

            foreach ($rows as $group) {
                foreach ($group['items'] as $row) {
                    $values = $this->flatten($row, $cellMap);
                    $writer->addRow(Row::fromValues($values));
                }
            }

            $writer->close();
        }, 'laporan-sdm-'.now()->format('Ymd-His').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** @param array<string, mixed> $filters */
    private function rows(array $filters): Collection
    {
        $rows = $this->reports->rows($filters);

        $raw = match ($filters['group_by'] ?? null) {
            'unit' => $rows->groupBy('unit'),
            'position' => $rows->groupBy('position'),
            default => $rows->groupBy(fn () => 'all'),
        };

        return $raw->map(fn (Collection $group, string $label) => [
            'group' => $label,
            'items' => $group,
        ])->values();
    }

    /** @return array<string, string> */
    private function resolveColumns(array $filters): array
    {
        $selected = $filters['columns'] ?? null;

        if (empty($selected)) {
            return self::AVAILABLE_COLUMNS;
        }

        return array_intersect_key(self::AVAILABLE_COLUMNS, array_flip($selected));
    }

    /** @param array<string, mixed> $row */
    private function flatten(array $row, array $keys): array
    {
        return array_map(fn (string $key) => $row[$key] ?? '-', $keys);
    }

    /** @return array{review_period_id: int|null, unit_id: int|null, position_id: int|null, columns: array|null, group_by: string|null} */
    private function filters(Request $request): array
    {
        $data = $request->validate([
            'review_period_id' => ['nullable', 'integer', 'exists:review_periods,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'columns' => ['nullable', 'array'],
            'columns.*' => ['string', 'in:'.implode(',', array_keys(self::AVAILABLE_COLUMNS))],
            'group_by' => ['nullable', 'string', 'in:unit,position'],
        ]);

        return [
            'review_period_id' => isset($data['review_period_id']) ? (int) $data['review_period_id'] : null,
            'unit_id' => isset($data['unit_id']) ? (int) $data['unit_id'] : null,
            'position_id' => isset($data['position_id']) ? (int) $data['position_id'] : null,
            'columns' => $data['columns'] ?? null,
            'group_by' => $data['group_by'] ?? null,
        ];
    }

    private function authorizeHr(Request $request): void
    {
        abort_unless($request->user()?->is_active && $request->user()->role === UserRole::Hr, 403);
    }
}
