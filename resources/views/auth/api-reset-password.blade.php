<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'ar' ? 'ar' : 'en' }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('lang.reset_password_subject') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 420px;
        }

        .logo {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a5276;
            margin-bottom: 1.5rem;
            letter-spacing: .5px;
        }

        h1 {
            font-size: 1.15rem;
            font-weight: 600;
            color: #1c2a3a;
            margin-bottom: .35rem;
            text-align: center;
        }

        .sub {
            font-size: .85rem;
            color: #6c7a89;
            text-align: center;
            margin-bottom: 1.8rem;
        }

        label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: .3rem;
        }

        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: .65rem .85rem;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: .95rem;
            transition: border .2s;
            outline: none;
            margin-bottom: 1rem;
        }

        input:focus { border-color: #1a5276; }

        button {
            width: 100%;
            padding: .75rem;
            background: #1a5276;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
            margin-top: .5rem;
        }

        button:hover { background: #154360; }
        button:disabled { background: #7f8c8d; cursor: not-allowed; }

        .alert {
            padding: .75rem 1rem;
            border-radius: 8px;
            font-size: .88rem;
            margin-bottom: 1rem;
            display: none;
        }

        .alert.success { background: #d1fae5; color: #065f46; display: block; }
        .alert.error   { background: #fee2e2; color: #991b1b; display: block; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">{{ config('app.name') }}</div>
    <h1>{{ __('lang.reset_password_subject') }}</h1>
    <p class="sub">{{ __('lang.reset_password_line_1') }}</p>

    <div id="alert" class="alert"></div>

    <form id="resetForm">
        <input type="hidden" id="token" value="{{ request('token') }}">

        <label for="email">{{ __('lang.email') }}</label>
        <input type="email" id="email" value="{{ request('email') }}" required readonly
               style="background:#f9fafb; color:#6b7280;">

        <label for="password">{{ __('lang.password') }}</label>
        <input type="password" id="password" required minlength="6"
               placeholder="{{ __('lang.password') }}">

        <label for="password_confirmation">{{ __('lang.password_confirmation') }}</label>
        <input type="password" id="password_confirmation" required minlength="6"
               placeholder="{{ __('lang.password_confirmation') }}">

        <button type="submit" id="submitBtn">
            {{ __('lang.reset_password_action') }}
        </button>
    </form>
</div>

<script>
document.getElementById('resetForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const alert   = document.getElementById('alert');
    const btn     = document.getElementById('submitBtn');
    const pass    = document.getElementById('password').value;
    const confirm = document.getElementById('password_confirmation').value;

    alert.className = 'alert';
    alert.style.display = 'none';
    alert.textContent = '';

    if (pass !== confirm) {
        alert.className = 'alert error';
        alert.textContent = '{{ __("lang.password_confirmation_mismatch") }}';
        return;
    }

    btn.disabled = true;
    btn.textContent = '{{ __("lang.loading") }}';

    try {
        const res = await fetch('/api/reset-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                token:                 document.getElementById('token').value,
                email:                 document.getElementById('email').value,
                password:              pass,
                password_confirmation: confirm,
            }),
        });

        const data = await res.json();

        if (res.ok) {
            alert.className = 'alert success';
            alert.textContent = data.message;
            document.getElementById('resetForm').style.display = 'none';
        } else {
            alert.className = 'alert error';
            alert.textContent = data.message || data.errors?.password?.[0] || 'An error occurred.';
            btn.disabled = false;
            btn.textContent = '{{ __("lang.reset_password_action") }}';
        }
    } catch (err) {
        alert.className = 'alert error';
        alert.textContent = 'Network error. Please try again.';
        btn.disabled = false;
        btn.textContent = '{{ __("lang.reset_password_action") }}';
    }
});
</script>
</body>
</html>
