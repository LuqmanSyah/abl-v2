<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#2563eb">
    <title>Absensi Dinas · {{ $trip->destination }}</title>
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
        #camera-ui { text-align: center; }
        #preview { width: 100%; max-height: 400px; border-radius: 12px; background: #1e293b; object-fit: cover; }
        #captured-canvas { display: none; }
        #captured-img { width: 100%; max-height: 400px; border-radius: 12px; object-fit: cover; }
        .camera-actions { display: flex; gap: 10px; margin-top: 14px; }
        #attendance-form { margin-top: 14px; }
        .camera-actions button { flex: 1; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 51px; border: 0; border-radius: 12px; font-weight: 850; cursor: pointer; touch-action: manipulation; }
        .btn-primary { background: #2563eb; color: white; box-shadow: 0 9px 20px #2563eb33; }
        .btn-block { display: flex; width: 100%; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-success { background: #16a34a; color: white; box-shadow: 0 9px 20px #16a34a33; }
        .btn-success:hover { background: #15803d; }
        .btn-danger { background: #dc2626; color: white; box-shadow: 0 9px 20px #dc262633; }
        .btn-danger:hover { background: #b91c1c; }
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
        <a class="back" href="{{ url('/pegawai') }}">Kembali ke portal</a>
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
                <div class="fact"><dt>Jadwal</dt><dd>{{ $trip->starts_at->translatedFormat('d M Y, H:i') }} – {{ $trip->ends_at->translatedFormat('d M Y, H:i') }} WIB</dd></div>
                <div class="fact"><dt>Lokasi</dt><dd>{{ $trip->address }}</dd></div>
                <div class="fact"><dt>Batas jarak</dt><dd>Maksimal {{ number_format($trip->radius_meters) }} meter dari titik tugas</dd></div>
                <div class="fact"><dt>Pegawai</dt><dd>{{ $trip->employee->name }}</dd></div>
            </dl>

            @php $todayAttendance = $trip->attendances()->whereDate('captured_at', today())->first(); @endphp
            @if ($todayAttendance)
                <p class="notice success">Absensi dinas hari ini sudah tercatat dengan status <strong>{{ $todayAttendance->status->label() }}</strong>.</p>
            @elseif (now()->isBefore($trip->starts_at))
                <p class="notice warning">Absensi dinas dibuka pada <strong>{{ $trip->starts_at->translatedFormat('d F Y, H:i') }} WIB</strong>. Kembali ke halaman ini saat jadwal dimulai.</p>
            @else
                <ol class="steps" aria-label="Langkah absensi dinas">
                    <li>Izinkan browser mengakses kamera dan lokasi akurat.</li>
                    <li>Ambil foto wajah di lokasi tugas.</li>
                    <li>Pastikan perangkat online, lalu kirim absensi dinas.</li>
                </ol>

                <div id="camera-ui">
                    <video id="preview" autoplay playsinline hidden></video>
                    <img id="captured-img" hidden>
                    <canvas id="captured-canvas"></canvas>

                    <div class="camera-actions">
                        <button id="btn-camera" class="btn btn-primary" type="button">Buka kamera</button>
                        <button id="btn-capture" class="btn btn-success" type="button" hidden>Ambil foto</button>
                        <button id="btn-retake" class="btn btn-secondary" type="button" hidden>Ulangi</button>
                    </div>
                </div>

                <form id="attendance-form" hidden>
                    <button id="submit" class="btn btn-primary btn-block" type="submit">Ambil lokasi dan simpan absensi dinas</button>
                </form>
            @endif

            <p id="status" role="status" aria-live="polite"></p>
            <p class="privacy">Lokasi dan foto hanya dipakai untuk verifikasi dinas. Absensi dinas memerlukan koneksi internet dan tidak disimpan pada browser.</p>
        </div>
    </article>
</main>
<script>
const form = document.querySelector('#attendance-form');
const statusBox = document.querySelector('#status');
const networkBox = document.querySelector('#network');
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const endpoint = @json(route('attendance.store', $trip));
const employee = @json($trip->employee->name);
const place = @json($trip->location_name);

const preview = document.querySelector('#preview');
const capturedImg = document.querySelector('#captured-img');
const capturedCanvas = document.querySelector('#captured-canvas');
const btnCamera = document.querySelector('#btn-camera');
const btnCapture = document.querySelector('#btn-capture');
const btnRetake = document.querySelector('#btn-retake');

let cameraStream = null;
let capturedBlob = null;

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

async function startCamera() {
    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
        });
        preview.srcObject = cameraStream;
        preview.hidden = false;
        btnCamera.hidden = true;
        btnCapture.hidden = false;
        capturedImg.hidden = true;
        capturedBlob = null;
    } catch (error) {
        if (error.name === 'NotAllowedError') {
            setStatus('Izin kamera ditolak. Aktifkan izin kamera browser lalu coba lagi.', 'error');
        } else if (error.name === 'NotFoundError') {
            setStatus('Kamera tidak ditemukan.', 'error');
        } else {
            setStatus('Kamera gagal dibuka. ' + (error.message || ''), 'error');
        }
    }
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(t => t.stop());
        cameraStream = null;
    }
    preview.srcObject = null;
}

function capturePhoto() {
    capturedCanvas.width = preview.videoWidth;
    capturedCanvas.height = preview.videoHeight;
    const ctx = capturedCanvas.getContext('2d');
    ctx.drawImage(preview, 0, 0);

    capturedCanvas.toBlob(blob => {
        capturedBlob = blob;
        capturedImg.src = capturedCanvas.toDataURL('image/jpeg');
        preview.hidden = true;
        capturedImg.hidden = false;
        btnCapture.hidden = true;
        btnRetake.hidden = false;
        form.hidden = false;
        stopCamera();
    }, 'image/jpeg', 0.85);
}

function retakePhoto() {
    capturedImg.hidden = true;
    capturedBlob = null;
    btnRetake.hidden = true;
    form.hidden = true;
    startCamera();
}

btnCamera.addEventListener('click', startCamera);
btnCapture.addEventListener('click', capturePhoto);
btnRetake.addEventListener('click', retakePhoto);

async function watermarkedPhoto(blob, data) {
    const image = new Image();
    const objectUrl = URL.createObjectURL(blob);
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

async function send(data) {
    const body = new FormData();
    Object.entries(data).forEach(([key, value]) => key !== 'photo' && body.append(key, value));
    body.append('photo', data.photo, 'attendance.jpg');
    const response = await fetch(endpoint, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body,
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(payload.message || 'Absensi dinas gagal disimpan. Coba lagi.');
    }

    return payload;
}

form?.addEventListener('submit', async event => {
    event.preventDefault();
    if (!capturedBlob) return setStatus('Ambil foto terlebih dahulu.', 'error');
    if (!navigator.onLine) return setStatus('Perangkat sedang luring. Sambungkan internet lalu coba lagi.', 'error');

    const button = document.querySelector('#submit');
    button.disabled = true;
    button.textContent = 'Membaca lokasi…';
    try {
        const position = await locationNow();
        button.textContent = 'Menyiapkan foto…';
        const data = {
            captured_at: new Date().toISOString(),
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy_meters: Math.round(position.coords.accuracy),
        };
        data.photo = await watermarkedPhoto(capturedBlob, data);
        if (data.photo.size > 5 * 1024 * 1024) {
            throw new Error('Foto setelah diproses melebihi 5 MB. Gunakan kamera dengan resolusi lebih rendah.');
        }

        button.textContent = 'Menyimpan…';
        const payload = await send(data);
        form.setAttribute('hidden', 'hidden');
        setStatus(payload.message || 'Absensi dinas berhasil disimpan.', 'success');
    } catch (error) {
        setStatus(error.message || 'Absensi dinas gagal diproses. Coba lagi.', 'error');
    } finally {
        button.disabled = false;
        button.textContent = 'Ambil lokasi dan simpan absensi dinas';
    }
});

window.addEventListener('online', updateNetwork);
window.addEventListener('offline', updateNetwork);
updateNetwork();
</script>
</body>
</html>
