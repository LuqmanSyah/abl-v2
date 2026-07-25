<div
    x-data="{
        loading: false,
        message: 'Lokasi belum diambil.',
        capture() {
            if (! navigator.geolocation) {
                this.message = 'Browser tidak mendukung GPS.';
                return;
            }

            this.loading = true;
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    $wire.set('data.latitude', position.coords.latitude);
                    $wire.set('data.longitude', position.coords.longitude);
                    this.message = `Lokasi tersimpan (akurasi ±${Math.round(position.coords.accuracy)} m).`;
                    this.loading = false;
                },
                () => {
                    this.message = 'Lokasi gagal diambil. Izinkan akses GPS lalu coba lagi.';
                    this.loading = false;
                },
                { enableHighAccuracy: true, timeout: 15000 },
            );
        },
    }"
    class="space-y-2"
>
    <x-filament::button type="button" x-on:click="capture" x-bind:disabled="loading">
        <span x-text="loading ? 'Mengambil lokasi...' : 'Ambil Lokasi GPS'"></span>
    </x-filament::button>

    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="message"></p>
</div>
