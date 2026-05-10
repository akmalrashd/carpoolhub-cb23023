<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CarpoolHub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('build/assets/branding/icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('build/assets/branding/icon.png') }}">
    <style>
        :root {
            --bg: #eef2f7;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #dbe2ea;
            --input-bg: #f8fafc;
            --accent: #111827;
            --accent-hover: #030712;
            --danger: #b91c1c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top, #f9fafb, #e5e7eb),
                linear-gradient(135deg, #f3f4f6, #e5e7eb);
            background-attachment: fixed;
            color: var(--text);
            font-family: Inter, sans-serif;
        }

        .login-wrapper {
            min-height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px 14px;
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at center, rgba(250, 204, 21, 0.18), transparent 42%),
                linear-gradient(135deg, rgba(243, 244, 246, 0.92), rgba(229, 231, 235, 0.92));
        }

        .card {
            width: min(420px, 100vw - 28px);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px 18px 18px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 1;
        }

        .brand {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .brand img {
            width: min(220px, 75%);
            height: auto;
            display: block;
        }

        h1 {
            margin: 0 0 6px;
            font-family: Poppins, sans-serif;
            font-size: 24px;
            line-height: 1.2;
            letter-spacing: -0.01em;
        }

        .subtitle {
            margin: 0 0 16px;
            color: var(--muted);
            font-size: 14px;
        }

        label {
            display: block;
            margin: 12px 0 7px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--input-bg);
            color: var(--text);
            padding: 12px 13px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.15s ease, background-color 0.15s ease;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #94a3b8;
            background: #fff;
        }

        .remember-row {
            margin-top: 14px;
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 13px;
            color: #475569;
        }

        .remember-row input {
            width: 16px;
            height: 16px;
            accent-color: #0f172a;
            margin: 0;
        }

        button {
            width: 100%;
            margin-top: 16px;
            border: none;
            border-radius: 12px;
            padding: 12px;
            background: var(--accent);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        button:hover {
            background: var(--accent-hover);
        }

        .error {
            margin-top: 10px;
            color: var(--danger);
            font-size: 13px;
            line-height: 1.4;
            border: 1px solid rgba(185, 28, 28, 0.25);
            background: rgba(185, 28, 28, 0.06);
            border-radius: 10px;
            padding: 9px 10px;
        }

        @media (min-width: 640px) {
            .login-wrapper {
                padding: 24px;
            }

            .card {
                border-radius: 22px;
                padding: 26px 24px 22px;
            }

            h1 {
                font-size: 26px;
            }

            .subtitle {
                font-size: 15px;
                margin-bottom: 18px;
            }
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="card">
        <div class="brand">
            <img src="{{ asset('build/assets/branding/logo-horizontal-b.png') }}" alt="CarpoolHub">
        </div>
        <h1>Login</h1>
        <p class="subtitle">Log in to continue to your CarpoolHub account.</p>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>

            <label class="remember-row" for="remember">
                <input id="remember" type="checkbox" name="remember" value="1" {{ old('remember', '1') ? 'checked' : '' }}>
                <span>Ingat saya</span>
            </label>

            <button type="submit">Login</button>
        </form>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
    </div>
</div>
</body>
</html>
