<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Masuk · {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: radial-gradient(circle at top left, #dbeafe 0, transparent 34rem), #f8fafc; color: #0f172a; }
        .shell { width: min(960px, 100%); display: grid; grid-template-columns: 1.05fr .95fr; overflow: hidden; border: 1px solid #dbe4f0; border-radius: 24px; background: white; box-shadow: 0 28px 70px rgba(15, 23, 42, .14); }
        .intro { display: flex; min-height: 610px; flex-direction: column; justify-content: space-between; padding: 48px; background: linear-gradient(145deg, #1d4ed8, #1e3a8a); color: white; }
        .brand { display: flex; align-items: center; gap: 12px; font-weight: 800; letter-spacing: -.02em; }
        .brand-mark { display: grid; width: 42px; height: 42px; place-items: center; border: 1px solid #ffffff55; border-radius: 13px; background: #ffffff1a; font-size: 13px; letter-spacing: .04em; }
        .eyebrow { margin: 0 0 12px; color: #bfdbfe; font-size: 13px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        .intro-title { max-width: 12ch; margin: 0; font-size: clamp(34px, 5vw, 52px); font-weight: 700; line-height: 1.05; letter-spacing: -.045em; }
        .intro-copy { max-width: 42ch; margin: 22px 0 0; color: #dbeafe; font-size: 17px; line-height: 1.65; }
        .features { display: grid; gap: 12px; margin: 32px 0 0; padding: 0; list-style: none; color: #eff6ff; }
        .features li { display: flex; align-items: center; gap: 10px; }
        .features li::before { content: ""; width: 8px; height: 8px; flex: 0 0 auto; border-radius: 999px; background: #93c5fd; box-shadow: 0 0 0 4px #ffffff18; }
        .copyright { margin: 0; color: #bfdbfe; font-size: 13px; }
        main { display: flex; flex-direction: column; justify-content: center; padding: 48px; }
        main .eyebrow { color: #1d4ed8; }
        main h1 { margin: 0; font-size: 32px; letter-spacing: -.035em; }
        .lead { margin: 10px 0 30px; color: #64748b; line-height: 1.6; }
        .field { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 700; }
        input[type="email"], input[type="password"] { width: 100%; min-height: 48px; padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 11px; background: #fff; color: inherit; font: inherit; transition: border-color .15s, box-shadow .15s; }
        input:hover { border-color: #94a3b8; }
        input:focus { outline: 0; border-color: #2563eb; box-shadow: 0 0 0 4px #dbeafe; }
        .remember { display: flex; align-items: center; gap: 9px; margin: 2px 0 24px; color: #475569; font-weight: 500; }
        .remember input { width: 17px; height: 17px; margin: 0; accent-color: #2563eb; }
        .error { margin-top: 7px; color: #b91c1c; font-size: 14px; line-height: 1.45; }
        button { width: 100%; min-height: 49px; border: 0; border-radius: 11px; background: #2563eb; color: white; font: inherit; font-weight: 800; cursor: pointer; box-shadow: 0 8px 18px #2563eb33; transition: background .15s, transform .15s; }
        button:hover { background: #1d4ed8; }
        button:active { transform: translateY(1px); }
        button:disabled { cursor: wait; opacity: .72; }
        details { margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 18px; color: #64748b; font-size: 13px; }
        summary { cursor: pointer; color: #334155; font-weight: 700; }
        details p { margin: 10px 0 0; line-height: 1.7; }
        code { border-radius: 5px; background: #f1f5f9; padding: 2px 5px; color: #0f172a; }
        @media (max-width: 760px) {
            body { align-items: start; padding: 0; background: white; }
            .shell { display: block; border: 0; border-radius: 0; box-shadow: none; }
            .intro { min-height: auto; padding: 28px 24px; }
            .intro > div:nth-child(2), .copyright { display: none; }
            main { padding: 36px 24px 48px; }
        }
        @media (prefers-reduced-motion: reduce) { * { scroll-behavior: auto !important; transition: none !important; } }
    </style>
</head>
<body>
    <div class="shell">
        <aside class="intro" aria-label="Tentang portal">
            <div class="brand">
                <span class="brand-mark" aria-hidden="true">SDM</span>
                <span>{{ config('app.name') }}</span>
            </div>
            <div>
                <p class="eyebrow">Ruang kerja karyawan</p>
                <p class="intro-title">Urus pekerjaan, bukan formulir.</p>
                <p class="intro-copy">Dinas, kinerja, dan pengembangan karier tersedia dalam satu akun sesuai peran Anda.</p>
                <ul class="features">
                    <li>Absensi dinas berbasis lokasi</li>
                    <li>Target dan hasil kinerja yang jelas</li>
                    <li>Pelatihan serta mentoring terpantau</li>
                </ul>
            </div>
            <p class="copyright">Akses khusus pegawai dan pengelola SDM.</p>
        </aside>

        <main>
            <p class="eyebrow">Selamat datang</p>
            <h1>Masuk ke akun</h1>
            <p class="lead">Gunakan email dan kata sandi kantor. Portal akan membuka menu sesuai peran Anda.</p>

            <form method="POST" action="{{ route('login.store') }}" data-login-form>
                @csrf

                <div class="field">
                    <label for="email">Email kantor</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email') <div id="email-error" class="error" role="alert">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="password">Kata sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                    @error('password') <div id="password-error" class="error" role="alert">{{ $message }}</div> @enderror
                </div>

                <label class="remember">
                    <input name="remember" type="checkbox" value="1" @checked(old('remember'))>
                    Tetap masuk di perangkat ini
                </label>

                <button type="submit" data-submit>Masuk ke portal</button>
            </form>

            @env('local')
                <details>
                    <summary>Akun demo lokal</summary>
                    <p><code>pegawai@example.com</code>, <code>atasan@example.com</code>, atau <code>hr@example.com</code><br>Kata sandi: <code>password</code></p>
                </details>
            @endenv
        </main>
    </div>

    <script>
        document.querySelector('[data-login-form]').addEventListener('submit', () => {
            const button = document.querySelector('[data-submit]');
            button.disabled = true;
            button.textContent = 'Memeriksa akun…';
        });
    </script>
</body>
</html>
