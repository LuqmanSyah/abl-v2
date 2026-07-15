@php($record = $getRecord())
<iframe
    title="Lokasi dinas {{ $record->location_name }}"
    src="https://maps.google.com/maps?q={{ $record->latitude }},{{ $record->longitude }}&z=16&output=embed"
    style="border:0;border-radius:.75rem;height:24rem;width:100%"
    loading="lazy"
    referrerpolicy="no-referrer-when-downgrade"
></iframe>
