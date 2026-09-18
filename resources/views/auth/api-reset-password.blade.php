<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>إعادة تعيين كلمة المرور</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Tahoma, sans-serif;
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
        .logo { text-align: center; font-size: 1.5rem; font-weight: 700; color: #1a5276; margin-bottom: 1.5rem; }
        h1 { font-size: 1.15rem; font-weight: 600; color: #1c2a3a; margin-bottom: .35rem; text-align: center; }
        .sub { font-size: .85rem; color: #6c7a89; text-align: center; margin-bottom: 1.8rem; }
        label { display: block; font-size: .82rem; font-weight: 600; color: #374151; margin-bottom: .3rem; }
        input[type="password"], input[type="email"] {
            width: 100%; padding: .65rem .85rem;
            border: 1.5px solid #d1d5db; border-radius: 8px;
            font-size: .95rem; outline: none; margin-bottom: 1rem; font-family: inherit;
        }
        input:focus { border-color: #1a5276; }
        input[readonly] { background: #f9fafb; color: #6b7280; }
        button {
            width: 100%; padding: .75rem;
            background: #1a5276; color: #fff;
            border: none; border-radius: 8px;
            font-size: 1rem; font-weight: 600;
            cursor: pointer; margin-top: .5rem; font-family: inherit;
        }
        button:hover { background: #154360; }
        button:disabled { background: #7f8c8d; cursor: not-allowed; }
        button.secondary {
            background: #fff; color: #1a5276;
            border: 1.5px solid #1a5276; margin-top: .75rem;
        }
        button.secondary:hover { background: #eaf0fb; }
        .alert { padding: .75rem 1rem; border-radius: 8px; font-size: .88rem; margin-bottom: 1rem; }
        .success { background: #d1fae5; color: #065f46; }
        .error   { background: #fee2e2; color: #991b1b; }
        .warning { background: #fef9c3; color: #92400e; }
        #expiredState { display: none; text-align: center; }
        #expiredState p { color: #6c7a89; font-size: .9rem; margin-bottom: 1.25rem; }
        #expiredState .icon { font-size: 2.5rem; margin-bottom: .75rem; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">{{ config('app.name') }}</div>

    {{-- Expired / invalid token state --}}
    <div id="expiredState">
        <div class="icon">⏰</div>
        <h1>انتهت صلاحية الرابط</h1>
        <p>رابط إعادة تعيين كلمة المرور غير صالح أو منتهي الصلاحية.<br>يرجى طلب رابط جديد.</p>
        <button type="button" onclick="requestNewLink()">طلب رابط جديد</button>
    </div>

    {{-- Main reset form --}}
    <div id="formState">
        <h1>إعادة تعيين كلمة المرور</h1>
        <p class="sub">أدخل كلمة المرور الجديدة لحسابك</p>

        <div id="msgBox" style="display:none"></div>

        <form id="resetForm">
            <input type="hidden" id="token">

            <label for="email">البريد الإلكتروني</label>
            <input type="email" id="email" readonly>

            <label for="password">كلمة المرور الجديدة</label>
            <input type="password" id="password" placeholder="كلمة المرور الجديدة" required minlength="6">

            <label for="confirm">تأكيد كلمة المرور</label>
            <input type="password" id="confirm" placeholder="أعد إدخال كلمة المرور" required minlength="6">

            <button type="submit" id="submitBtn">إعادة تعيين كلمة المرور</button>
        </form>
    </div>
</div>

<script>
    var params    = new URLSearchParams(window.location.search);
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    document.getElementById('token').value = params.get('token') || '';
    document.getElementById('email').value  = params.get('email')  || '';

    function showExpired() {
        document.getElementById('formState').style.display    = 'none';
        document.getElementById('expiredState').style.display = 'block';
    }

    function requestNewLink() {
        var email  = params.get('email') || '';
        var btn    = event.target;
        btn.disabled    = true;
        btn.textContent = 'جارٍ الإرسال…';

        fetch('/api/forgot-password', {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ email: email }),
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var div = document.createElement('div');
            div.className   = 'alert ' + (data.mail_error ? 'warning' : 'success');
            div.textContent = data.mail_error
                ? 'تم إنشاء الرابط لكن قد يكون هناك مشكلة في إرسال البريد.'
                : (data.message || 'تم إرسال رابط جديد إلى بريدك الإلكتروني.');
            document.getElementById('expiredState').prepend(div);
            btn.disabled    = false;
            btn.textContent = 'طلب رابط جديد';
        })
        .catch(function() {
            btn.disabled    = false;
            btn.textContent = 'طلب رابط جديد';
        });
    }

    document.getElementById('resetForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        var box     = document.getElementById('msgBox');
        var btn     = document.getElementById('submitBtn');
        var pass    = document.getElementById('password').value;
        var confirm = document.getElementById('confirm').value;

        box.style.display = 'none';
        box.className     = '';
        box.textContent   = '';

        if (pass !== confirm) {
            box.className     = 'alert error';
            box.textContent   = 'كلمتا المرور غير متطابقتين.';
            box.style.display = 'block';
            return;
        }

        btn.disabled    = true;
        btn.textContent = 'جارٍ الإرسال…';

        try {
            var res = await fetch('/api/reset-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-XSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    token:                 document.getElementById('token').value,
                    email:                 document.getElementById('email').value,
                    password:              pass,
                    password_confirmation: confirm,
                }),
            });

            var data = await res.json();

            if (res.ok) {
                box.className     = 'alert success';
                box.textContent   = data.message || 'تم إعادة تعيين كلمة المرور بنجاح.';
                box.style.display = 'block';
                document.getElementById('resetForm').style.display = 'none';
            } else if (res.status === 422 && (
                data.message === 'This password reset token is invalid.' ||
                (data.message && data.message.toLowerCase().includes('token'))
            )) {
                // Token expired or already used -- show the expired state
                // with a "request new link" button instead of a plain error.
                showExpired();
            } else {
                box.className     = 'alert error';
                box.textContent   = data.message
                    || (data.errors && data.errors.password && data.errors.password[0])
                    || 'حدث خطأ، يرجى المحاولة مرة أخرى.';
                box.style.display = 'block';
                btn.disabled      = false;
                btn.textContent   = 'إعادة تعيين كلمة المرور';
            }
        } catch(err) {
            box.className     = 'alert error';
            box.textContent   = 'خطأ في الشبكة. يرجى المحاولة مرة أخرى.';
            box.style.display = 'block';
            btn.disabled      = false;
            btn.textContent   = 'إعادة تعيين كلمة المرور';
        }
    });
</script>
</body>
</html>
