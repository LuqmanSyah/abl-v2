<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan SDM</title>
    <style>
        body { margin: 0; background: #f3f4f6; color: #111827; font: 14px system-ui, sans-serif; }
        main { max-width: 1280px; margin: auto; padding: 24px; }
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px #0002; }
        .head, form { display: flex; gap: 12px; align-items: end; flex-wrap: wrap; }
        .head { justify-content: space-between; margin-bottom: 20px; }
        .back { display: inline-flex; margin-bottom: 12px; color: #92400e; font-weight: 700; text-decoration: none; }
        .back:hover { text-decoration: underline; }
        h1 { margin: 0 0 6px; }
        .subtitle { margin: 0; color: #6b7280; }
        label { display: grid; gap: 6px; font-weight: 600; }
        select, button, .button { border: 1px solid #d1d5db; border-radius: 8px; padding: 9px 12px; background: white; color: inherit; text-decoration: none; }
        button, .btn-primary { background: #b45309; color: white; border-color: #b45309; cursor: pointer; }
        .btn-excel { background: #16a34a; color: white; border-color: #16a34a; }
        .btn-pdf { background: #dc2626; color: white; border-color: #dc2626; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .table { overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; white-space: nowrap; }
        th, td { padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f9fafb; }
        @media (max-width: 640px) { main { padding: 12px; } .card { padding: 14px; } label { width: 100%; } select { width: 100%; } }
    </style>
</head>
<body>
<main>
    <div class="card">
        <div class="head">
            <div><a class="back" href="{{ url('/hr') }}">Kembali ke Panel HR</a><h1>Laporan SDM</h1><p class="subtitle">Ringkasan absensi, merit, pelatihan, dan mentoring pegawai.</p></div>
            <div class="actions">
                <a class="button btn-excel" href="{{ route('hr.reports.export', array_filter($filters)) }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Unduh CSV
                </a>
                <a class="button btn-pdf" href="{{ route('hr.reports.pdf', array_filter($filters)) }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    Unduh PDF
                </a>
            </div>
        </div>
        <form method="get">
            <label>Periode
                <select name="review_period_id"><option value="">Semua</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}" @selected($filters['review_period_id'] === $period->id)>{{ $period->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Unit
                <select name="unit_id"><option value="">Semua</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected($filters['unit_id'] === $unit->id)>{{ $unit->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Jabatan
                <select name="position_id"><option value="">Semua</option>
                    @foreach ($positions as $position)
                        <option value="{{ $position->id }}" @selected($filters['position_id'] === $position->id)>{{ $position->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn-primary" type="submit">Terapkan</button>
            @if (array_filter($filters)) <a class="button" href="{{ route('hr.reports.index') }}">Hapus filter</a> @endif
        </form>
        <div class="table">
            <table>
                <caption style="position:absolute;clip:rect(0,0,0,0)">Ringkasan SDM per pegawai</caption>
                <thead><tr><th>NIP</th><th>Pegawai</th><th>Unit</th><th>Jabatan</th><th>Total absensi</th><th>Absensi valid</th><th>Skor merit</th><th>Pelatihan</th><th>Pelatihan selesai</th><th>Mentoring</th><th>Mentoring selesai</th></tr></thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['employee_number'] }}</td><td>{{ $row['name'] }}</td><td>{{ $row['unit'] }}</td><td>{{ $row['position'] }}</td>
                        <td>{{ $row['attendance_count'] }}</td><td>{{ $row['valid_attendance_count'] }}</td><td>{{ $row['merit_score'] }}</td>
                        <td>{{ $row['training_count'] }}</td><td>{{ $row['completed_training_count'] }}</td><td>{{ $row['mentoring_count'] }}</td><td>{{ $row['completed_mentoring_count'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" style="padding:32px;text-align:center;color:#6b7280">Tidak ada pegawai yang cocok dengan filter. Ubah atau hapus filter untuk melihat data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
