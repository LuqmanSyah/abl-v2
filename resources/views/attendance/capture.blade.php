<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Absensi Dinas</title>
    <style>
        body { background:#f3f4f6; color:#111827; font:16px system-ui; margin:0; }
        main { margin:auto; max-width:34rem; padding:1rem; }
        article { background:white; border-radius:1rem; box-shadow:0 1px 4px #0002; padding:1.25rem; }
        label { display:block; font-weight:600; margin-top:1rem; }
        input, button { box-sizing:border-box; font:inherit; margin-top:.4rem; padding:.8rem; width:100%; }
        button { background:#2563eb; border:0; border-radius:.6rem; color:white; font-weight:700; }
        button:disabled { opacity:.5; }
        #status { margin-top:1rem; white-space:pre-line; }
        small { color:#4b5563; }
    </style>
</head>
<body>
<main>
    <article>
        <h1>Absensi Dinas</h1>
        <p><strong>{{ $trip->destination }}</strong><br>{{ $trip->location_name }}<br><small>{{ $trip->address }}</small></p>
        <p>{{ $trip->starts_at->format('d M Y H:i') }}–{{ $trip->ends_at->format('d M Y H:i') }}</p>

        @if ($trip->attendance)
            <p>Absensi tercatat: <strong>{{ $trip->attendance->status->label() }}</strong></p>
        @else
            <form id="attendance-form">
                <label for="photo">Foto langsung</label>
                <input id="photo" type="file" accept="image/*" capture="user" required>
                <button id="submit" type="submit">Ambil GPS dan Simpan</button>
            </form>
        @endif
        <p id="status"></p>
        <p><a href="{{ url('/pegawai') }}">Kembali ke portal</a></p>
    </article>
</main>
<script>
const form = document.querySelector('#attendance-form');
const statusBox = document.querySelector('#status');
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const endpoint = @json(route('attendance.store', $trip));
const employee = @json($trip->employee->name);
const place = @json($trip->location_name);

function locationNow() {
    return new Promise((resolve, reject) => navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true, timeout: 20000, maximumAge: 0,
    }));
}

async function watermarkedPhoto(file, data) {
    const image = new Image();
    image.src = URL.createObjectURL(file);
    await image.decode();
    const canvas = document.createElement('canvas');
    const scale = Math.min(1, 1600 / image.width);
    canvas.width = image.width * scale;
    canvas.height = image.height * scale;
    const context = canvas.getContext('2d');
    context.drawImage(image, 0, 0, canvas.width, canvas.height);
    const lines = [employee, new Date(data.captured_at).toLocaleString('id-ID'), `${data.latitude}, ${data.longitude}`, place];
    context.font = `${Math.max(18, canvas.width / 45)}px system-ui`;
    const lineHeight = Math.max(25, canvas.width / 32);
    context.fillStyle = '#000a';
    context.fillRect(0, canvas.height - lineHeight * (lines.length + 1), canvas.width, lineHeight * (lines.length + 1));
    context.fillStyle = 'white';
    lines.forEach((line, index) => context.fillText(line, 20, canvas.height - lineHeight * (lines.length - index)));
    return new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', .82));
}

function openQueue() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('sdm-attendance', 1);
        request.onupgradeneeded = () => request.result.createObjectStore('queue', { keyPath: 'client_uuid' });
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

async function send(data) {
    const body = new FormData();
    Object.entries(data).forEach(([key, value]) => key !== 'photo' && body.append(key, value));
    body.append('photo', data.photo, 'attendance.jpg');
    const response = await fetch(endpoint, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body });
    if (!response.ok) throw new Error((await response.json()).message || 'Sinkronisasi gagal.');
    return response.json();
}

async function syncQueue() {
    const db = await openQueue();
    const records = await new Promise((resolve, reject) => {
        const request = db.transaction('queue').objectStore('queue').getAll();
        request.onsuccess = () => resolve(request.result); request.onerror = () => reject(request.error);
    });
    for (const record of records) {
        try {
            await send(record);
            db.transaction('queue', 'readwrite').objectStore('queue').delete(record.client_uuid);
            statusBox.textContent = 'Absensi tersinkronisasi.';
        }
        catch (error) { statusBox.textContent = `Tersimpan luring. ${error.message}`; }
    }
}

form?.addEventListener('submit', async event => {
    event.preventDefault();
    const button = document.querySelector('#submit');
    button.disabled = true;
    try {
        statusBox.textContent = 'Mengambil lokasi…';
        const position = await locationNow();
        const data = {
            client_uuid: crypto.randomUUID(), captured_at: new Date().toISOString(),
            latitude: position.coords.latitude, longitude: position.coords.longitude,
            accuracy_meters: Math.round(position.coords.accuracy), mock_location_suspected: 0,
        };
        data.photo = await watermarkedPhoto(document.querySelector('#photo').files[0], data);
        await queue(data);
        await syncQueue();
    } catch (error) { statusBox.textContent = error.message; }
    finally { button.disabled = false; }
});

window.addEventListener('online', syncQueue);
if (navigator.onLine) syncQueue();
</script>
</body>
</html>
