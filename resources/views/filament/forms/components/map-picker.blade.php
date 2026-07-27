<style>
    .map-place-search {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .map-place-autocomplete {
        width: 100%;
        min-height: 2.5rem;
        color-scheme: light;
        background-color: #fff;
        border: 1px solid #d1d5db;
        border-radius: 0.75rem;
        color: #111827;
        font-family: inherit;
        font-size: 0.875rem;
        line-height: 1.25rem;
    }

    .map-place-autocomplete::part(input),
    .map-place-autocomplete::part(focus-ring) {
        min-height: 2.5rem;
        border-radius: 0.75rem;
    }

    .dark .map-place-autocomplete {
        color-scheme: dark;
        background-color: #18181b;
        border-color: #3f3f46;
        color: #fff;
    }
</style>

<div
    x-data="{
        apiKey: @js(config('services.google_maps.key')),
        latitude: $wire.entangle(@js($latitudeStatePath)),
        longitude: $wire.entangle(@js($longitudeStatePath)),
        map: null,
        marker: null,
        message: 'Memuat peta...',

        async init() {
            if (! this.apiKey) {
                this.message = 'GOOGLE_MAPS_API_KEY belum dikonfigurasi. Isi koordinat secara manual.';

                return;
            }

            try {
                await this.loadGoogleMaps();

                const hasPosition = this.hasValidPosition();
                const center = hasPosition
                    ? this.position()
                    : { lat: -2.5489, lng: 118.0149 };

                this.map = new google.maps.Map(this.$refs.map, {
                    center,
                    zoom: hasPosition ? 16 : 5,
                    streetViewControl: false,
                    mapTypeControl: false,
                });

                if (hasPosition) {
                    this.placeMarker(center);
                }

                this.map.addListener('click', (event) => this.select(event.latLng));
                this.$watch('latitude', () => this.syncMarker());
                this.$watch('longitude', () => this.syncMarker());
                this.message = 'Klik peta atau geser marker untuk memilih lokasi.';

                try {
                    await this.initSearch();
                } catch {
                    this.message = 'Peta siap. Pencarian lokasi gagal dimuat; pilih lokasi langsung pada peta.';
                }
            } catch {
                this.message = 'Peta gagal dimuat. Isi koordinat secara manual.';
            }
        },

        async loadGoogleMaps() {
            if (window.google?.maps) {
                return;
            }

            window.googleMapsLoadPromise ??= new Promise((resolve, reject) => {
                const callback = '__ablGoogleMapsReady';
                const script = document.createElement('script');
                const timeout = window.setTimeout(
                    () => reject(new Error('Google Maps load timeout')),
                    15000,
                );

                window[callback] = () => {
                    window.clearTimeout(timeout);
                    resolve();
                };

                script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(this.apiKey)}&v=weekly&loading=async&libraries=places&callback=${callback}`;
                script.async = true;
                script.dataset.googleMaps = 'true';
                script.addEventListener('error', () => {
                    window.clearTimeout(timeout);
                    reject(new Error('Google Maps script failed'));
                }, { once: true });
                document.head.append(script);
            });

            try {
                await window.googleMapsLoadPromise;
            } catch (error) {
                document.querySelector('script[data-google-maps]')?.remove();
                window.googleMapsLoadPromise = null;

                throw error;
            }
        },

        async initSearch() {
            const { PlaceAutocompleteElement } = await google.maps.importLibrary('places');
            const search = new PlaceAutocompleteElement();

            search.placeholder = 'Cari nama tempat atau alamat';
            search.className = 'map-place-autocomplete';
            search.addEventListener('gmp-select', async ({ placePrediction }) => {
                try {
                    const place = placePrediction.toPlace();

                    await place.fetchFields({ fields: ['location'] });

                    if (place.location) {
                        this.select(place.location);
                    }
                } catch {
                    this.message = 'Lokasi hasil pencarian gagal dibuka.';
                }
            });

            this.$refs.search.replaceChildren(search);
        },

        hasValidPosition() {
            const latitude = Number(this.latitude);
            const longitude = Number(this.longitude);

            return this.latitude !== null
                && this.latitude !== ''
                && this.longitude !== null
                && this.longitude !== ''
                && Number.isFinite(latitude)
                && Number.isFinite(longitude)
                && latitude >= -90
                && latitude <= 90
                && longitude >= -180
                && longitude <= 180;
        },

        position() {
            return {
                lat: Number(this.latitude),
                lng: Number(this.longitude),
            };
        },

        select(position) {
            this.latitude = Number(position.lat().toFixed(7));
            this.longitude = Number(position.lng().toFixed(7));
            this.placeMarker(this.position(), true);
        },

        syncMarker() {
            if (this.map && this.hasValidPosition()) {
                this.placeMarker(this.position());
            }
        },

        placeMarker(position, center = false) {
            if (! this.marker) {
                // ponytail: legacy Marker avoids mandatory map ID; migrate when project configures one.
                this.marker = new google.maps.Marker({
                    map: this.map,
                    position,
                    draggable: true,
                    title: 'Lokasi terpilih',
                });
                this.marker.addListener('dragend', (event) => this.select(event.latLng));
            } else {
                this.marker.setPosition(position);
            }

            if (center) {
                this.map.panTo(position);
                this.map.setZoom(16);
            }
        },
    }"
    class="space-y-2"
>
    <div class="map-place-search">
        <label class="text-sm font-medium text-gray-950 dark:text-white">
            Cari Lokasi
        </label>

        <div x-ref="search" wire:ignore></div>
    </div>

    <div
        x-ref="map"
        wire:ignore
        role="application"
        aria-label="Pilih lokasi pada peta Google"
        class="h-80 w-full rounded-xl border border-gray-300 dark:border-gray-700"
    ></div>

    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="message"></p>
</div>
