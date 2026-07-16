<div class="space-y-4">
    <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
        <div class="text-sm text-gray-500 dark:text-gray-400">Periode {{ $breakdown['period'] }}</div>
        <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
            Skor merit {{ number_format((float) $breakdown['scores']['total'], 2, ',', '.') }}
        </div>
        <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                'kpi' => 'KPI',
                'discipline' => 'Disiplin',
                'manager' => 'Manager Review',
                'review_360' => '360 Review',
            ] as $key => $label)
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $label }} × {{ $breakdown['weights'][$key] }}%
                    </div>
                    <div class="font-semibold text-gray-950 dark:text-white">
                        {{ number_format((float) $breakdown['scores'][$key], 2, ',', '.') }}
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-3 text-sm text-gray-600 dark:text-gray-300">
            Estimasi bonus: Rp {{ number_format((float) $breakdown['scores']['estimated_bonus'], 0, ',', '.') }}
        </div>
    </div>

    <details class="rounded-xl border border-gray-200 p-4 dark:border-white/10" open>
        <summary class="cursor-pointer font-semibold text-gray-950 dark:text-white">Detail KPI</summary>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="p-2">Indikator</th>
                        <th class="p-2">Target</th>
                        <th class="p-2">Capaian</th>
                        <th class="p-2">Nilai</th>
                        <th class="p-2">Bobot</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($breakdown['kpis'] as $kpi)
                        <tr>
                            <td class="p-2 font-medium text-gray-950 dark:text-white">{{ $kpi['indicator'] }}</td>
                            <td class="p-2">{{ number_format((float) $kpi['target'], 2, ',', '.') }}</td>
                            <td class="p-2">{{ number_format((float) $kpi['achievement'], 2, ',', '.') }}</td>
                            <td class="p-2">{{ number_format((float) $kpi['score'], 2, ',', '.') }}</td>
                            <td class="p-2">{{ $kpi['weight'] }}%</td>
                        </tr>
                        @if ($kpi['history'])
                            <tr>
                                <td colspan="5" class="p-2 text-xs text-gray-500 dark:text-gray-400">
                                    <details>
                                        <summary class="cursor-pointer">Riwayat perubahan</summary>
                                        <ul class="mt-2 space-y-1 pl-4">
                                            @foreach ($kpi['history'] as $history)
                                                <li>
                                                    {{ $history['created_at']->format('d M Y H:i') }} — {{ $history['user'] }}
                                                    @if ($history['action'] === 'kpi.created')
                                                        membuat KPI
                                                    @else
                                                        mengubah
                                                        @foreach (($history['data']['changes'] ?? []) as $field => $change)
                                                            {{ $field }}: {{ $change['old'] ?? '-' }} menjadi {{ $change['new'] ?? '-' }}{{ ! $loop->last ? ',' : '' }}
                                                        @endforeach
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="p-2 text-gray-500">Belum ada KPI.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </details>

    <details class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
        <summary class="cursor-pointer font-semibold text-gray-950 dark:text-white">Detail Penilaian</summary>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-gray-500 dark:text-gray-400">
                    <tr><th class="p-2">Komponen</th><th class="p-2">Penilai</th><th class="p-2">Tipe</th><th class="p-2">Nilai</th><th class="p-2">Tanggal</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($breakdown['reviews'] as $review)
                        <tr>
                            <td class="p-2">{{ $review['component'] }}</td>
                            <td class="p-2">{{ $review['reviewer'] }}</td>
                            <td class="p-2">{{ $review['type'] }}</td>
                            <td class="p-2">{{ $review['score'] }}/5</td>
                            <td class="p-2">{{ $review['submitted_at']->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-2 text-gray-500">Belum ada penilaian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </details>

    <details class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
        <summary class="cursor-pointer font-semibold text-gray-950 dark:text-white">Detail Disiplin</summary>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-gray-500 dark:text-gray-400">
                    <tr><th class="p-2">Dinas</th><th class="p-2">Tanggal</th><th class="p-2">Status Absensi</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($breakdown['discipline'] as $trip)
                        <tr>
                            <td class="p-2">{{ $trip['destination'] }}</td>
                            <td class="p-2">{{ $trip['starts_at']->format('d M Y') }}</td>
                            <td class="p-2">{{ $trip['attendance_status'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-2 text-gray-500">Tidak ada dinas yang dihitung pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </details>
</div>
