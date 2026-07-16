<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Masuk · {{ config('app.name') }}</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --blue: #2563eb;
            --ink: #0f172a;
            --muted: #64748b;
            --border: #dbe4f0;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(circle at 15% 10%, rgba(147, 197, 253, .38), transparent 24rem),
                radial-gradient(circle at 85% 90%, rgba(191, 219, 254, .42), transparent 26rem),
                #f1f5f9;
            color: var(--ink);
        }

        main {
            width: min(480px, 100%);
            padding: clamp(28px, 5vw, 42px);
            border: 1px solid rgba(203, 213, 225, .9);
            border-radius: 24px;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 24px 65px rgba(15, 23, 42, .13);
            backdrop-filter: blur(14px);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 34px;
            font-weight: 800;
            letter-spacing: -.025em;
        }

        .brand-mark {
            display: grid;
            width: 44px;
            height: 44px;
            place-items: center;
            border-radius: 14px;
            background: linear-gradient(145deg, #2563eb, #1e40af);
            color: white;
            box-shadow: 0 9px 20px rgba(37, 99, 235, .25);
            font-size: 12px;
            letter-spacing: .05em;
        }

        h1 {
            margin: 0;
            font-size: clamp(29px, 7vw, 36px);
            line-height: 1.15;
            letter-spacing: -.04em;
        }

        .lead {
            margin: 10px 0 30px;
            color: var(--muted);
            line-height: 1.6;
        }

        .field { margin-bottom: 20px; }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            min-height: 50px;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: white;
            color: inherit;
            font: inherit;
            transition: border-color .15s, box-shadow .15s;
        }

        input:hover { border-color: #94a3b8; }

        input:focus {
            outline: 0;
            border-color: var(--blue);
            box-shadow: 0 0 0 4px #dbeafe;
        }

        .password-wrap { position: relative; }
        .password-wrap input { padding-right: 78px; }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            border: 0;
            background: transparent;
            color: #475569;
            font: inherit;
            font-size: 12px;
            font-weight: 750;
            cursor: pointer;
            transform: translateY(-50%);
        }

        .password-toggle:hover { color: var(--blue); }

        .password-toggle:focus-visible {
            outline: 2px solid var(--blue);
            outline-offset: 3px;
            border-radius: 4px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 2px 0 24px;
            color: #475569;
            font-weight: 500;
        }

        .remember input {
            width: 17px;
            height: 17px;
            margin: 0;
            accent-color: var(--blue);
        }

        .error {
            margin-top: 7px;
            color: #b91c1c;
            font-size: 14px;
            line-height: 1.45;
        }

        .submit {
            width: 100%;
            min-height: 51px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 22px rgba(37, 99, 235, .25);
            transition: box-shadow .15s, transform .15s;
        }

        .submit:hover {
            box-shadow: 0 13px 28px rgba(37, 99, 235, .32);
            transform: translateY(-1px);
        }

        .submit:active { transform: translateY(0); }
        .submit:disabled { cursor: wait; opacity: .72; transform: none; }

        details {
            margin-top: 22px;
            border-top: 1px solid #e2e8f0;
            padding-top: 17px;
            color: var(--muted);
            font-size: 12px;
        }

        summary {
            cursor: pointer;
            color: #475569;
            font-weight: 700;
        }

        details p { margin: 9px 0 0; line-height: 1.7; }
        code { border-radius: 5px; background: #f1f5f9; padding: 2px 5px; color: var(--ink); }

        @media (max-width: 520px) {
            body { padding: 0; background: white; }
            main {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                border: 0;
                border-radius: 0;
                box-shadow: none;
                padding: 28px 20px 40px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * { scroll-behavior: auto !important; transition: none !important; }
        }
    </style>
</head>
<body>
    <main>
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">SDM</span>
            <span>{{ config('app.name') }}</span>
        </div>

        <h1>Masuk ke akun</h1>
        <p class="lead">Gunakan email dan kata sandi kantor.</p>

        <form method="POST" action="{{ route('login.store') }}" data-login-form>
            @csrf

            <div class="field">
                <label for="email">Email kantor</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@perusahaan.com" required autofocus autocomplete="username"
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email') <div id="email-error" class="error" role="alert">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="password">Kata sandi</label>
                <div class="password-wrap">
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                    <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-pressed="false">Lihat</button>
                </div>
                @error('password') <div id="password-error" class="error" role="alert">{{ $message }}</div> @enderror
            </div>

            <label class="remember">
                <input name="remember" type="checkbox" value="1" @checked(old('remember'))>
                Tetap masuk di perangkat ini
            </label>

            <button class="submit" type="submit" data-submit>Masuk</button>
        </form>

        @env('local')
            <details>
                <summary>Akun demo lokal</summary>
                <p><code>pegawai@example.com</code>, <code>atasan@example.com</code>, atau <code>hr@example.com</code><br>Kata sandi: <code>password</code></p>
            </details>
        @endenv
    </main>

    <script>
        const password = document.querySelector('#password');
        const passwordToggle = document.querySelector('[data-password-toggle]');

        passwordToggle.addEventListener('click', () => {
            const showing = password.type === 'text';
            password.type = showing ? 'password' : 'text';
            passwordToggle.textContent = showing ? 'Lihat' : 'Sembunyikan';
            passwordToggle.setAttribute('aria-pressed', String(! showing));
        });

        document.querySelector('[data-login-form]').addEventListener('submit', () => {
            const button = document.querySelector('[data-submit]');
            button.disabled = true;
            button.textContent = 'Memeriksa akun…';
        });
    </script>
</body>
</html>
