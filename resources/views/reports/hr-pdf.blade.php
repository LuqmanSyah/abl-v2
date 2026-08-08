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
        .group-row { background: #fef3c7; font-weight: 700; }
        .footer { margin-top: 16px; font-size: 10px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <h1>Laporan SDM</h1>
    <p class="subtitle">
        Ringkasan absensi dinas, merit, pelatihan, dan mentoring pegawai.
        Dicetak {{ now()->translatedFormat('d F Y H:i') }}
    </p>
    <table>
        <thead><tr>
            @foreach ($columns as $label)
                <th>{{ $label }}</th>
            @endforeach
        </tr></thead>
        <tbody>
        @php $colKeys = array_keys($columns); @endphp
        @forelse ($rows as $group)
            @if ($group['group'] !== 'all')
                <tr class="group-row"><td colspan="{{ count($columns) }}">{{ $group['group'] }}</td></tr>
            @endif
            @foreach ($group['items'] as $row)
                <tr>
                    @foreach ($colKeys as $key)
                        <td>{{ $row[$key] }}</td>
                    @endforeach
                </tr>
            @endforeach
        @empty
            <tr><td colspan="{{ count($columns) }}" style="padding:24px;text-align:center;color:#6b7280">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="footer">Sistem SDM — {{ config('app.url') }}</div>
</body>
</html>
