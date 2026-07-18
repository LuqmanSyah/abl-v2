<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan SDM {{ $filters['review_period_id'] ? '— '.$periods->firstWhere('id', $filters['review_period_id'])?->name : '' }}</title>
    <style>
        body { font: 11px DejaVu Sans, sans-serif; color: #111; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .subtitle { color: #6b7280; font-size: 12px; margin: 0 0 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; border: 1px solid #d1d5db; text-align: left; }
        th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
        td { font-size: 11px; }
        .footer { margin-top: 16px; font-size: 10px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <h1>Laporan SDM</h1>
    <p class="subtitle">
        Ringkasan absensi, merit, pelatihan, dan mentoring pegawai.
        Dicetak {{ now()->translatedFormat('d F Y H:i') }}
    </p>
    <table>
        <thead><tr>
            <th>NIP</th><th>Pegawai</th><th>Unit</th><th>Jabatan</th>
            <th>Total absensi</th><th>Absensi valid</th><th>Skor merit</th>
            <th>Pelatihan</th><th>Pelatihan selesai</th><th>Mentoring</th><th>Mentoring selesai</th>
        </tr></thead>
        <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['employee_number'] }}</td><td>{{ $row['name'] }}</td>
                <td>{{ $row['unit'] }}</td><td>{{ $row['position'] }}</td>
                <td>{{ $row['attendance_count'] }}</td><td>{{ $row['valid_attendance_count'] }}</td>
                <td>{{ $row['merit_score'] }}</td>
                <td>{{ $row['training_count'] }}</td><td>{{ $row['completed_training_count'] }}</td>
                <td>{{ $row['mentoring_count'] }}</td><td>{{ $row['completed_mentoring_count'] }}</td>
            </tr>
        @empty
            <tr><td colspan="11" style="padding:24px;text-align:center;color:#6b7280">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="footer">Sistem SDM — {{ config('app.url') }}</div>
</body>
</html>
