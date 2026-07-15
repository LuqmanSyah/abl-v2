<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light">
    <title>Absensi · {{ $trip->destination }}</title>
    <style>
        :root { font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #0f172a; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; background: radial-gradient(circle at top, #dbeafe 0, transparent 34rem), #f8fafc; }
        main { width: min(720px, 100%); margin: auto; padding: 24px 16px 48px; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
        .back { color: #1d4ed8; font-weight: 700; text-decoration: none; }
        .back:hover { text-decoration: underline; }
        .network { display: inline-flex; align-items: center; gap: 7px; color: #475569; font-size: 13px; font-weight: 700; }
        .network::before { content: ""; width: 8px; height: 8px; border-radius: 999px; background: #16a34a; }
        .network.offline::before { background: #d97706; }
        article { overflow: hidden; border: 1px solid #dbe4f0; border-radius: 22px; background: white; box-shadow: 0 22px 55px rgba(15, 23, 42, .09); }
        .hero { padding: 30px 32px 26px; background: linear-gradient(145deg, #1d4ed8, #1e40af); color: white; }
        .eyebrow { margin: 0 0 10px; color: #bfdbfe; font-size: 12px; font-weight: 800; letter-spacing: .11em; text-transform: uppercase; }
        h1 { margin: 0; font-size: clamp(28px, 6vw, 38px); line-height: 1.12; letter-spacing: -.04em; }
        .destination { margin: 12px 0 0; color: #dbeafe; font-size: 16px; line-height: 1.6; }
        .content { padding: 28px 32px 32px; }
        .facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin: 0 0 26px; }
        .fact { min-width: 0; padding: 14px; border: 1px solid #e2e8f0; border-radius: 13px; background: #f8fafc; }
        .fact dt { margin-bottom: 5px; color: #64748b; font-size: 12px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
        .fact dd { overflow-wrap: anywhere; margin: 0; color: #1e293b; font-size: 14px; font-weight: 650; line-height: 1.5; }
        .notice { margin: 0 0 22px; padding: 15px 16px; border: 1px solid #bae6fd; border-radius: 12px; background: #f0f9ff; color: #0c4a6e; line-height: 1.55; }
        .notice.success { border-color: #bbf7d0; background: #f0fdf4; color: #166534; }
        .notice.warning { border-color: #fde68a; background: #fffbeb; color: #92400e; }
        .steps { display: grid; gap: 9px; margin: 0 0 24px; padding: 0; counter-reset: step; list-style: none; color: #475569; font-size: 14px; }
        .steps li { display: flex; align-items: center; gap: 10px; }
        .steps li::before { counter-increment: step; content: counter(step); display: grid; width: 25px; height: 25px; flex: 0 0 auto; place-items: center; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 12px; font-weight: 850; }
        label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 800; }
        input[type="file"] { width: 100%; padding: 12px; border: 1px dashed #94a3b8; border-radius: 12px; background: #f8fafc; color: #334155; font: inherit; }
        input[type="file"]:focus { outline: 0; border-color: #2563eb; box-shadow: 0 0 0 4px #dbeafe; }
        .help { display: block; margin: 8px 0 20px; color: #64748b; font-size: 13px; line-height: 1.5; }
        button { width: 100%; min-height: 51px; border: 0; border-radius: 12px; background: #2563eb; color: white; font: inherit; font-weight: 850; cursor: pointer; box-shadow: 0 9px 20px #2563eb33; }
        button:hover { background: #1d4ed8; }
        button:disabled { cursor: wait; opacity: .65; }
        #status { display: none; margin: 18px 0 0; padding: 13px 14px; border-radius: 11px; background: #eff6ff; color: #1e40af; line-height: 1.5; white-space: pre-line; }
        #status:not(:empty) { display: block; }
        #status[data-kind="success"] { background: #f0fdf4; color: #166534; }
        #status[data-kind="warning"] { background: #fffbeb; color: #92400e; }
        #status[data-kind="error"] { background: #fef2f2; color: #b91c1c; }
        .privacy { margin: 22px 0 0; color: #64748b; font-size: 12px; line-height: 1.55; }
        [hidden] { display: none !important; }
        @media (max-width: 560px) {
            main { padding: 16px 10px 32px; }
            .hero, .content { padding-inline: 20px; }
            .facts { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<main>
    <nav class="topbar" aria-label="Navigasi halaman">
        <a class="back" href="{{ url('/pegawai') }}">← Kembali ke portal</a>
        <span id="network" class="network">Terhubung</span>
    </nav>

    <article>
        <header class="hero">
            <p class="eyebrow">Absensi dinas</p>
            <h1>Catat kehadiran di lokasi</h1>
            <p class="destination"><strong>{{ $trip->destination }}</strong><br>{{ $trip->location_name }}</p>
        </header>

        <div class="content">
            <dl class="facts">
                <div class="fact"><dt>Jadwal</dt><dd>{{ $trip->starts_at->translatedFormat('d M Y, H:i') }}–{{ $trip->ends_at->translatedFormat('H:i') }} WIB</dd></div>
                <div class="fact"><dt>Lokasi</dt><dd>{{ $trip->address }}</dd></div>
                <div class="fact"><dt>Batas jarak</dt><dd>Maksimal {{ number_format($trip->radius_meters) }} meter dari titik tugas</dd></div>
                <div class="fact"><dt>Pegawai</dt><dd>{{ $trip->employee->name }}</dd></div>
            </dl>

            @if ($trip->attendance)
                <p class="notice success">Absensi sudah tercatat dengan status <strong>{{ $trip->attendance->status->label() }}</strong>.</p>
            @elseif (now()->isBefore($trip->starts_at))
                <p class="notice warning">Absensi dibuka pada <strong>{{ $trip->starts_at->translatedFormat('d F Y, H:i') }} WIB</strong>. Kembali ke halaman ini saat jadwal dimulai.</p>
            @else
                <ol class="steps" aria-label="Langkah absensi">
                    <li>Ambil foto wajah di lokasi tugas.</li>
                    <li>Izinkan browser membaca lokasi akurat.</li>
                    <li>Data dikirim otomatis atau disimpan saat luring.</li>
                </ol>

                <form id="attendance-form">
                    <label for="photo">Foto kehadiran</label>
                    <input id="photo" type="file" accept="image/*" capture="user" required>
                    <small class="help">Foto diberi waktu dan koordinat secara otomatis. Ukuran maksimum 5 MB.</small>
                    <button id="submit" type="submit">Ambil lokasi dan simpan absensi</button>
                </form>
            @endif

            <p id="status" role="status" aria-live="polite"></p>
            <p class="privacy">Lokasi dan foto hanya dipakai untuk verifikasi dinas. Antrean luring tetap terikat pada dinas ini dan dikirim saat koneksi tersedia.</p>
        </div>
    </article>
</main>
<script>
const form = document.querySelector('#attendance-form');
const statusBox = document.querySelector('#status');
const networkBox = document.querySelector('#network');
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const endpoint = @json(route('attendance.store', $trip));
const tripId = @json($trip->id);
const employee = @json($trip->employee->name);
const place = @json($trip->location_name);

function setStatus(message, kind = 'info') {
    statusBox.textContent = message;
    statusBox.dataset.kind = kind;
}

function updateNetwork() {
    const online = navigator.onLine;
    networkBox.textContent = online ? 'Terhubung' : 'Mode luring';
    networkBox.classList.toggle('offline', !online);
}

function locationNow() {
    if (!navigator.geolocation) return Promise.reject(new Error('Perangkat ini tidak mendukung pembacaan lokasi.'));

    return new Promise((resolve, reject) => navigator.geolocation.getCurrentPosition(resolve, error => {
        const messages = {
            1: 'Izin lokasi ditolak. Aktifkan izin lokasi browser lalu coba lagi.',
            2: 'Lokasi belum ditemukan. Aktifkan GPS dan coba di area terbuka.',
            3: 'Pembacaan lokasi terlalu lama. Coba lagi.',
        };
        reject(new Error(messages[error.code] || 'Lokasi gagal dibaca. Coba lagi.'));
    }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }));
}

async function watermarkedPhoto(file, data) {
    const image = new Image();
    const objectUrl = URL.createObjectURL(file);
    image.src = objectUrl;
    try {
        await image.decode();
        const canvas = document.createElement('canvas');
        const scale = Math.min(1, 1600 / image.width);
        canvas.width = Math.round(image.width * scale);
        canvas.height = Math.round(image.height * scale);
        const context = canvas.getContext('2d');
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        const lines = [employee, new Date(data.captured_at).toLocaleString('id-ID'), `${data.latitude}, ${data.longitude}`, place];
        context.font = `${Math.max(18, canvas.width / 45)}px system-ui`;
        const lineHeight = Math.max(25, canvas.width / 32);
        context.fillStyle = '#000b';
        context.fillRect(0, canvas.height - lineHeight * (lines.length + 1), canvas.width, lineHeight * (lines.length + 1));
        context.fillStyle = 'white';
        lines.forEach((line, index) => context.fillText(line, 20, canvas.height - lineHeight * (lines.length - index)));

        return await new Promise((resolve, reject) => canvas.toBlob(
            blob => blob ? resolve(blob) : reject(new Error('Foto gagal diproses. Ambil foto ulang.')),
            'image/jpeg',
            .82,
        ));
    } finally {
        URL.revokeObjectURL(objectUrl);
    }
}

function openQueue() {
    return new Promise((resolve, reject) => {
        if (!window.indexedDB) {
            reject(new Error('Penyimpanan luring tidak didukung browser ini.'));
            return;
        }

        const request = indexedDB.open('sdm-attendance', 2);
        request.onupgradeneeded = event => {
            const db = request.result;
            if (!db.objectStoreNames.contains('queue')) {
                db.createObjectStore('queue', { keyPath: 'client_uuid' });
                return;
            }

            if (event.oldVersion < 2) {
                const cursorRequest = request.transaction.objectStore('queue').openCursor();
                cursorRequest.onsuccess = () => {
                    const cursor = cursorRequest.result;
                    if (!cursor) return;
                    if (!cursor.value.endpoint || !cursor.value.duty_trip_id) cursor.delete();
                    cursor.continue();
                };
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function queue(data) {
    const db = await openQueue();
    const transaction = db.transaction('queue', 'readwrite');
    transaction.objectStore('queue').put(data);
    return new Promise((resolve, reject) => {
        transaction.oncomplete = resolve;
        transaction.onerror = () => reject(transaction.error);
    });
}

async function removeQueued(db, clientUuid) {
    const transaction = db.transaction('queue', 'readwrite');
    transaction.objectStore('queue').delete(clientUuid);
    return new Promise((resolve, reject) => {
        transaction.oncomplete = resolve;
        transaction.onerror = () => reject(transaction.error);
    });
}

function syncError(message, retryable = true) {
    const error = new Error(message);
    error.retryable = retryable;
    return error;
}

async function send(data) {
    if (!data.endpoint || !data.duty_trip_id) {
        throw syncError('Antrean lama tidak memiliki tujuan dinas. Ambil ulang absensi dari halaman dinas asal.', false);
    }

    const body = new FormData();
    Object.entries(data).forEach(([key, value]) => !['photo', 'endpoint', 'duty_trip_id'].includes(key) && body.append(key, value));
    body.append('photo', data.photo, 'attendance.jpg');
    const response = await fetch(data.endpoint, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body,
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        const retryable = [408, 425, 429].includes(response.status) || response.status >= 500;
        throw syncError(payload.message || 'Sinkronisasi gagal. Coba lagi saat koneksi stabil.', retryable);
    }

    return payload;
}

async function syncQueue() {
    const db = await openQueue();
    const records = await new Promise((resolve, reject) => {
        const request = db.transaction('queue').objectStore('queue').getAll();
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
    let currentSynced = false;
    let pending = 0;

    for (const record of records) {
        try {
            const payload = await send(record);
            await removeQueued(db, record.client_uuid);
            if (record.duty_trip_id === tripId) {
                currentSynced = true;
                form?.setAttribute('hidden', 'hidden');
                setStatus(payload.message || 'Absensi berhasil disinkronkan.', 'success');
            }
        } catch (error) {
            if (error.retryable === false) {
                await removeQueued(db, record.client_uuid);
                if (record.duty_trip_id === tripId) {
                    setStatus(`Absensi tidak dapat disinkronkan. ${error.message} Ambil ulang absensi.`, 'error');
                }
                continue;
            }

            pending++;
            if (record.duty_trip_id === tripId) setStatus(`Tersimpan luring. ${error.message}`, 'warning');
        }
    }

    if (pending && !currentSynced && !statusBox.textContent) {
        setStatus(`${pending} absensi masih menunggu sinkronisasi.`, 'warning');
    }

    return currentSynced;
}

form?.addEventListener('submit', async event => {
    event.preventDefault();
    const button = document.querySelector('#submit');
    const file = document.querySelector('#photo').files[0];
    if (!file) return setStatus('Ambil foto terlebih dahulu.', 'error');

    button.disabled = true;
    button.textContent = 'Membaca lokasi…';
    try {
        const position = await locationNow();
        button.textContent = 'Menyiapkan foto…';
        const data = {
            endpoint,
            duty_trip_id: tripId,
            client_uuid: crypto.randomUUID(),
            captured_at: new Date().toISOString(),
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy_meters: Math.round(position.coords.accuracy),
            mock_location_suspected: 0,
        };
        data.photo = await watermarkedPhoto(file, data);
        if (data.photo.size > 5 * 1024 * 1024) {
            throw new Error('Foto setelah diproses melebihi 5 MB. Gunakan kamera dengan resolusi lebih rendah.');
        }

        try {
            await queue(data);
        } catch {
            if (!navigator.onLine) {
                throw new Error('Penyimpanan luring tidak tersedia. Sambungkan internet lalu coba lagi.');
            }

            button.textContent = 'Menyinkronkan…';
            const payload = await send(data);
            form.setAttribute('hidden', 'hidden');
            setStatus(payload.message || 'Absensi berhasil disinkronkan.', 'success');
            return;
        }

        if (!navigator.onLine) {
            setStatus('Absensi tersimpan di perangkat dan akan dikirim saat kembali terhubung.', 'warning');
            return;
        }

        button.textContent = 'Menyinkronkan…';
        await syncQueue();
    } catch (error) {
        setStatus(error.message || 'Absensi gagal diproses. Coba lagi.', 'error');
    } finally {
        button.disabled = false;
        button.textContent = 'Ambil lokasi dan simpan absensi';
    }
});

window.addEventListener('online', () => { updateNetwork(); syncQueue().catch(() => {}); });
window.addEventListener('offline', updateNetwork);
updateNetwork();
if (navigator.onLine) syncQueue().catch(() => {});
</script>
</body>
</html>
