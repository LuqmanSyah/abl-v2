<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{ error: null }"
        x-init="(async () => {
            const key = @js(config('services.google_maps.key'));
            if (!key) { error = 'GOOGLE_MAPS_API_KEY belum diatur. Isi koordinat secara manual.'; return; }
            window.googleMapsPromise ??= new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key=${key}&libraries=places`;
                script.onload = resolve; script.onerror = reject; document.head.appendChild(script);
            });
            await window.googleMapsPromise;
            const center = { lat: -6.2000000, lng: 106.8166667 };
            const map = new google.maps.Map($refs.map, { center, zoom: 12 });
            const marker = new google.maps.Marker({ map, position: center, draggable: true });
            const geocoder = new google.maps.Geocoder();
            const setPoint = async position => {
                marker.setPosition(position); map.panTo(position);
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
        })().catch(() => error = 'Google Maps gagal dimuat. Isi koordinat secara manual.')"
    >
        <input x-ref="search" type="search" placeholder="Cari nama atau alamat lokasi"
               style="width:100%;margin-bottom:.5rem;border:1px solid #d1d5db;border-radius:.5rem;padding:.65rem">
        <div x-ref="map" style="height:22rem;border-radius:.75rem;background:#e5e7eb"></div>
        <p x-show="error" x-text="error" style="color:#b91c1c;margin-top:.5rem"></p>
        <p style="color:#4b5563;margin-top:.5rem">Klik peta atau geser penanda. Alamat dan koordinat terisi otomatis.</p>
    </div>
</x-dynamic-component>
