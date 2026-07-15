<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{ error: null, loading: true }"
        x-init="(async () => {
            const key = @js(config('services.google_maps.key'));
            if (!key) { error = 'Peta belum tersedia. Isi garis lintang dan garis bujur secara manual.'; loading = false; return; }
            window.googleMapsPromise ??= new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key=${key}&libraries=places`;
                script.onload = resolve; script.onerror = reject; document.head.appendChild(script);
            });
            await window.googleMapsPromise;
            const latitude = Number($wire.get('data.latitude'));
            const longitude = Number($wire.get('data.longitude'));
            const hasCoordinates = value => value !== null && value !== '' && Number.isFinite(Number(value));
            const center = hasCoordinates($wire.get('data.latitude')) && hasCoordinates($wire.get('data.longitude'))
                ? { lat: latitude, lng: longitude }
                : { lat: -6.2, lng: 106.8166667 };
            const map = new google.maps.Map($refs.map, { center, zoom: 12 });
            const marker = new google.maps.Marker({ map, position: center, draggable: true });
            const geocoder = new google.maps.Geocoder();
            const syncMarker = () => {
                const lat = Number($wire.get('data.latitude'));
                const lng = Number($wire.get('data.longitude'));
                if (!hasCoordinates($wire.get('data.latitude')) || !hasCoordinates($wire.get('data.longitude'))) return;
                const position = { lat, lng };
                marker.setPosition(position); map.panTo(position);
            };
            $wire.$watch('data.latitude', syncMarker);
            $wire.$watch('data.longitude', syncMarker);
            const setPoint = async position => {
                marker.setPosition(position); map.panTo(position);
                $wire.set('data.duty_location_id', null);
                $wire.set('data.latitude', position.lat().toFixed(7));
                $wire.set('data.longitude', position.lng().toFixed(7));
                const result = await geocoder.geocode({ location: position });
                const place = result.results?.[0];
                if (place) {
                    $wire.set('data.address', place.formatted_address);
                    $wire.set('data.location_name', place.address_components?.[0]?.long_name || place.formatted_address);
                }
            };
            map.addListener('click', event => setPoint(event.latLng));
            marker.addListener('dragend', event => setPoint(event.latLng));
            const search = new google.maps.places.SearchBox($refs.search);
            search.addListener('places_changed', () => {
                const place = search.getPlaces()?.[0];
                if (place?.geometry?.location) setPoint(place.geometry.location);
            });
            loading = false;
        })().catch(() => { loading = false; error = 'Peta gagal dimuat. Periksa koneksi atau isi koordinat secara manual.' })"
    >
        <label for="duty-location-search" style="display:block;font-weight:600;margin-bottom:.4rem">Cari alamat</label>
        <input id="duty-location-search" x-ref="search" type="search" placeholder="Contoh: Monas, Jakarta Pusat"
               style="width:100%;margin-bottom:.5rem;border:1px solid #d1d5db;border-radius:.5rem;padding:.65rem">
        <div x-ref="map" wire:ignore aria-label="Peta pemilihan lokasi" style="height:22rem;border-radius:.75rem;background:#e5e7eb"></div>
        <p x-show="loading" style="color:#4b5563;margin-top:.5rem">Memuat peta…</p>
        <p x-show="error" x-text="error" role="alert" style="color:#b91c1c;margin-top:.5rem"></p>
        <p style="color:#4b5563;margin-top:.5rem">Cari alamat, klik peta, atau geser penanda. Alamat dan koordinat akan terisi otomatis.</p>
    </div>
</x-dynamic-component>
