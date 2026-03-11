<?php
session_start();
require '../database/connection.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$justRegistered = isset($_GET['registered']) && $_GET['registered'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --ink:     #0e0e12;
            --surface: #16161e;
            --border:  rgba(255,255,255,0.07);
            --gold:    #c9a96e;
            --text:    #e8e4dc;
            --muted:   #7a7880;
            --success: #5fcf80;
            --error:   #e06c75;
        }

        html, body { height: 100%; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--ink); color: var(--text);
            min-height: 100vh; display: flex;
            align-items: center; justify-content: center;
            padding: 2rem;
        }

        body::before {
            content: ''; position: fixed; inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 20% 20%, rgba(201,169,110,0.06) 0%, transparent 60%),
                radial-gradient(ellipse 50% 60% at 80% 80%, rgba(100,80,180,0.05) 0%, transparent 60%);
            pointer-events: none; z-index: 0;
        }

        body::after {
            content: ''; position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            background-size: 200px; pointer-events: none; z-index: 0; opacity: 0.4;
        }

        .wrapper {
            position: relative; z-index: 1;
            width: 100%; max-width: 440px;
            animation: fadeUp 0.7s cubic-bezier(0.22,1,0.36,1) both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .ornament {
            display: flex; align-items: center; gap: 12px; margin-bottom: 2.5rem;
        }
        .ornament::before, .ornament::after {
            content: ''; flex: 1; height: 1px;
            background: linear-gradient(to right, transparent, var(--gold), transparent);
            opacity: 0.4;
        }
        .ornament span {
            font-family: 'Cormorant Garamond', serif; font-size: 0.7rem;
            letter-spacing: 0.25em; text-transform: uppercase;
            color: var(--gold); opacity: 0.8;
        }

        .card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; padding: 3rem 2.75rem; position: relative;
            box-shadow: 0 0 0 1px rgba(255,255,255,0.03), 0 24px 60px rgba(0,0,0,0.5);
        }
        .card::before {
            content: ''; position: absolute; top: 0; left: 10%; right: 10%; height: 1px;
            background: linear-gradient(to right, transparent, var(--gold), transparent); opacity: 0.5;
        }

        .card-header { margin-bottom: 2.25rem; text-align: center; }
        .card-header h1 {
            font-family: 'Cormorant Garamond', serif; font-size: 2.4rem; font-weight: 500;
            letter-spacing: -0.01em; margin-bottom: 0.5rem;
        }
        .card-header p { font-size: 0.825rem; color: var(--muted); font-weight: 300; }

        .step { display: none; }
        .step.active {
            display: block;
            animation: fadeUp 0.4s cubic-bezier(0.22,1,0.36,1) both;
        }

        .progress-dots {
            display: flex; justify-content: center; gap: 6px; margin-bottom: 1.75rem;
        }
        .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: rgba(255,255,255,0.12);
            transition: background 0.3s, transform 0.3s;
        }
        .dot.active   { background: var(--gold); transform: scale(1.3); }
        .dot.complete { background: rgba(201,169,110,0.4); }

        .message {
            padding: 0.875rem 1.1rem; border-radius: 8px; font-size: 0.825rem;
            margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.6rem;
            animation: fadeIn 0.3s ease both;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .message.success { background: rgba(95,207,128,0.08);  border: 1px solid rgba(95,207,128,0.2);  color: var(--success); }
        .message.error   { background: rgba(224,108,117,0.08); border: 1px solid rgba(224,108,117,0.2); color: var(--error);   }
        .message.info    { background: rgba(201,169,110,0.07); border: 1px solid rgba(201,169,110,0.2); color: var(--gold);    }

        .field { margin-bottom: 1.25rem; }

        label {
            display: block; font-size: 0.72rem; font-weight: 500;
            letter-spacing: 0.12em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 0.5rem; transition: color 0.2s;
        }
        .field:focus-within label { color: var(--gold); }

        .input-wrap { position: relative; }
        .input-icon {
            position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%);
            color: var(--muted); pointer-events: none; transition: color 0.2s;
        }
        .field:focus-within .input-icon { color: var(--gold); }

        input[type=text],
        input[type=email],
        input[type=password],
        input[type=tel] {
            width: 100%; background: rgba(255,255,255,0.03);
            border: 1px solid var(--border); border-radius: 8px;
            padding: 0.8rem 1rem; color: var(--text);
            font-family: 'DM Sans', sans-serif; font-size: 0.9rem; font-weight: 300;
            outline: none;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
            -webkit-appearance: none;
        }
        input.with-icon { padding-left: 2.6rem; }
        input::placeholder { color: rgba(255,255,255,0.15); font-size: 0.82rem; }
        input:focus {
            border-color: rgba(201,169,110,0.5);
            background: rgba(201,169,110,0.04);
            box-shadow: 0 0 0 3px rgba(201,169,110,0.08);
        }
        input:hover:not(:focus) { border-color: rgba(255,255,255,0.12); }

        .pass-wrap { position: relative; }
        .pass-wrap input { padding-right: 3rem; }
        .toggle-pass {
            position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--muted); transition: color 0.2s; display: flex; align-items: center; padding: 0;
        }
        .toggle-pass:hover { color: var(--gold); }

        .row-extras {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: -0.25rem; margin-bottom: 1.25rem;
        }
        .remember {
            display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.78rem; color: var(--muted); cursor: pointer; user-select: none;
        }
        .remember input[type=checkbox] {
            width: 14px; height: 14px; accent-color: var(--gold);
            border-radius: 3px; cursor: pointer; padding: 0;
        }
        .forgot-link {
            font-size: 0.78rem; color: var(--gold); text-decoration: none;
            transition: opacity 0.2s;
        }
        .forgot-link:hover { opacity: 0.7; }

        .recaptcha-wrap {
            display: flex; justify-content: center; margin: 1.25rem 0;
            transform: scale(0.9); transform-origin: center;
        }

        .otp-row {
            display: flex; gap: 0.6rem; justify-content: center; margin: 0.5rem 0 1.25rem;
        }
        .otp-cell {
            width: 48px; height: 56px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border); border-radius: 8px;
            color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: 1.4rem; font-weight: 400; text-align: center;
            outline: none; caret-color: var(--gold);
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
            -webkit-appearance: none;
        }
        .otp-cell:focus {
            border-color: rgba(201,169,110,0.5);
            background: rgba(201,169,110,0.04);
            box-shadow: 0 0 0 3px rgba(201,169,110,0.08);
        }
        .otp-cell.filled { border-color: rgba(201,169,110,0.35); }

        .otp-meta {
            text-align: center; font-size: 0.78rem; color: var(--muted); margin-bottom: 1.25rem; line-height: 1.6;
        }
        .otp-meta strong { color: var(--text); font-weight: 400; }
        .otp-meta .resend-btn {
            background: none; border: none; color: var(--gold);
            font-family: 'DM Sans', sans-serif; font-size: 0.78rem;
            cursor: pointer; padding: 0; transition: opacity 0.2s;
        }
        .otp-meta .resend-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .otp-meta .resend-btn:not(:disabled):hover { opacity: 0.7; }

        .try-another {
            text-align: center; margin-top: 0.75rem; font-size: 0.78rem; color: var(--muted);
        }
        .try-another a { color: var(--gold); text-decoration: none; transition: opacity 0.2s; }
        .try-another a:hover { opacity: 0.7; }

        .contact-badge {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: rgba(201,169,110,0.08); border: 1px solid rgba(201,169,110,0.2);
            border-radius: 20px; padding: 0.2rem 0.75rem;
            font-size: 0.78rem; color: var(--gold); margin-bottom: 1.5rem;
        }

        .back-link {
            display: inline-flex; align-items: center; gap: 0.3rem;
            font-size: 0.78rem; color: var(--muted); cursor: pointer;
            background: none; border: none; padding: 0; font-family: 'DM Sans', sans-serif;
            transition: color 0.2s; margin-bottom: 1.5rem;
        }
        .back-link:hover { color: var(--gold); }

        .btn-submit {
            width: 100%; padding: 0.95rem;
            background: linear-gradient(135deg, #c9a96e 0%, #a07c45 100%);
            border: none; border-radius: 8px; color: #0e0e12;
            font-family: 'DM Sans', sans-serif; font-size: 0.875rem;
            font-weight: 500; letter-spacing: 0.06em; text-transform: uppercase;
            cursor: pointer; overflow: hidden; position: relative;
            transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
        }
        .btn-submit::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 60%);
            opacity: 0; transition: opacity 0.2s;
        }
        .btn-submit:hover { opacity: 0.92; transform: translateY(-1px); box-shadow: 0 8px 24px rgba(201,169,110,0.25); }
        .btn-submit:hover::before { opacity: 1; }
        .btn-submit:active { transform: translateY(0); box-shadow: none; }
        .btn-submit .spinner {
            display: none; width: 16px; height: 16px;
            border: 2px solid rgba(14,14,18,0.3); border-top-color: #0e0e12;
            border-radius: 50%; animation: spin 0.6s linear infinite; margin: 0 auto;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .btn-submit.loading .btn-text { display: none; }
        .btn-submit.loading .spinner  { display: block; }

        .footer-link {
            text-align: center; margin-top: 1.75rem; font-size: 0.8rem; color: var(--muted);
        }
        .footer-link a { color: var(--gold); text-decoration: none; font-weight: 500; transition: opacity 0.2s; }
        .footer-link a:hover { opacity: 0.75; }

        .g-recaptcha > div { border-radius: 8px; overflow: hidden; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="ornament"><span>Welcome Back</span></div>

    <div class="card">
        <div class="card-header">
            <h1>Sign In</h1>
            <p>Access your account securely</p>
        </div>

        <div class="progress-dots">
            <div class="dot active" id="dot-1"></div>
            <div class="dot"        id="dot-2"></div>
        </div>

        <?php if ($justRegistered): ?>
        <div class="message success" style="margin-bottom:1.5rem;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Account created successfully — sign in below!
        </div>
        <?php endif; ?>

        <div id="alert-box" style="display:none;"></div>

        <div class="step active" id="step-1">
            <div class="field">
                <label for="contact">Email or Phone Number</label>
                <div class="input-wrap">
                    <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                        <polyline points="2,4 12,13 22,4"/>
                    </svg>
                    <input class="with-icon" id="contact" name="contact" type="text"
                           placeholder="you@example.com or +60 12 345 6789"
                           autocomplete="username" required>
                </div>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="pass-wrap">
                    <input id="password" name="password" type="password"
                           placeholder="Your password" autocomplete="current-password" required>
                    <button type="button" class="toggle-pass" onclick="togglePass()">
                        <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="row-extras">
                <label class="remember">
                    <input type="checkbox" id="remember"> Remember me
                </label>
                <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
            </div>

            <div class="recaptcha-wrap">
                <div class="g-recaptcha" data-sitekey="6LfVbYYsAAAAAIxhFk1wiKsO1z8_85GBN6Jz_XmH" data-theme="dark"></div>
            </div>

            <button class="btn-submit" id="btn-step1" onclick="submitCredentials()">
                <span class="btn-text">Continue</span>
                <div class="spinner"></div>
            </button>
        </div>

        <div class="step" id="step-2">
            <button type="button" class="back-link" onclick="goBack()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                Back
            </button>

            <div style="text-align:center; margin-bottom:1.5rem;">
                <p style="font-size:0.82rem; color:var(--muted); margin-bottom:0.6rem;">
                    We sent a one-time code to
                </p>
                <span class="contact-badge" id="otp-target-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.77 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 17z"/>
                    </svg>
                    <span id="otp-target-text">—</span>
                </span>
            </div>

            <div class="otp-row" id="otp-row">
                <input class="otp-cell" maxlength="1" type="text" inputmode="numeric" pattern="[0-9]">
                <input class="otp-cell" maxlength="1" type="text" inputmode="numeric" pattern="[0-9]">
                <input class="otp-cell" maxlength="1" type="text" inputmode="numeric" pattern="[0-9]">
                <input class="otp-cell" maxlength="1" type="text" inputmode="numeric" pattern="[0-9]">
                <input class="otp-cell" maxlength="1" type="text" inputmode="numeric" pattern="[0-9]">
                <input class="otp-cell" maxlength="1" type="text" inputmode="numeric" pattern="[0-9]">
            </div>

            <div class="otp-meta">
                Code expires in <strong id="otp-timer">5:00</strong> &nbsp;·&nbsp;
                <button class="resend-btn" id="resend-btn" disabled onclick="resendOtp()">Resend code</button>
            </div>

            <button class="btn-submit" id="btn-step2" onclick="verifyOtp()">
                <span class="btn-text">Verify &amp; Sign In</span>
                <div class="spinner"></div>
            </button>

            <div class="try-another">
                Didn't receive it? <a href="#" onclick="tryAnotherWay(event)">Try another way</a>
            </div>
        </div>

    </div>

    <p class="footer-link">Don't have an account? <a href="../Register.php">Create one</a></p>
</div>

<script>
function showAlert(msg, type = 'error') {
    const box = document.getElementById('alert-box');
    const icons = {
        error:   `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
        success: `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
        info:    `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`
    };
    box.className = `message ${type}`;
    box.innerHTML = (icons[type] || '') + msg;
    box.style.display = 'flex';
}
function hideAlert() {
    document.getElementById('alert-box').style.display = 'none';
}

function setLoading(btnId, on) {
    document.getElementById(btnId).classList.toggle('loading', on);
}

function goToStep(n) {
    document.querySelectorAll('.step').forEach((s, i) => {
        s.classList.toggle('active', i + 1 === n);
    });
    [1, 2].forEach(i => {
        const dot = document.getElementById('dot-' + i);
        dot.classList.toggle('active',   i === n);
        dot.classList.toggle('complete', i < n);
    });
    hideAlert();
}

function goBack() { goToStep(1); }

function togglePass() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eye-icon');
    const hide  = input.type === 'password';
    input.type  = hide ? 'text' : 'password';
    icon.innerHTML = hide
        ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`
        : `<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>`;
}

const otpCells = document.querySelectorAll('.otp-cell');

otpCells.forEach((cell, idx) => {
    cell.addEventListener('input', e => {
        const val = e.target.value.replace(/\D/g, '');
        cell.value = val ? val[0] : '';
        cell.classList.toggle('filled', !!cell.value);
        if (cell.value && idx < otpCells.length - 1) otpCells[idx + 1].focus();
        if ([...otpCells].every(c => c.value)) verifyOtp();
    });

    cell.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !cell.value && idx > 0) {
            otpCells[idx - 1].value = '';
            otpCells[idx - 1].classList.remove('filled');
            otpCells[idx - 1].focus();
        }
    });

    cell.addEventListener('paste', e => {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        [...pasted].slice(0, 6).forEach((ch, i) => {
            if (otpCells[i]) {
                otpCells[i].value = ch;
                otpCells[i].classList.add('filled');
            }
        });
        const next = Math.min(pasted.length, 5);
        otpCells[next].focus();
        if (pasted.length >= 6) verifyOtp();
    });
});

let timerInterval = null;

function startTimer(seconds = 300) {
    clearInterval(timerInterval);
    const el = document.getElementById('otp-timer');
    const resend = document.getElementById('resend-btn');
    resend.disabled = true;

    timerInterval = setInterval(() => {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        el.textContent = m + ':' + String(s).padStart(2, '0');
        if (--seconds < 0) {
            clearInterval(timerInterval);
            el.textContent = '0:00';
            resend.disabled = false;
        }
    }, 1000);
}

function submitCredentials() {
    const contact  = document.getElementById('contact').value.trim();
    const password = document.getElementById('password').value;
    const remember = document.getElementById('remember').checked;

    if (!contact || !password) {
        showAlert('Please fill in all fields.'); return;
    }

    const recaptcha = typeof grecaptcha !== 'undefined' ? grecaptcha.getResponse() : 'dev';
    if (!recaptcha) {
        showAlert('Please complete the CAPTCHA.'); return;
    }

    setLoading('btn-step1', true);

    $.ajax({
        url: '../multifactor/process_login.php',
        type: 'POST',
        dataType: 'json',
        data: { contact, password, remember: remember ? '1' : '0', recaptcha },
        success: function(data) {
            setLoading('btn-step1', false);
            if (data.status === 'success') {
                document.getElementById('otp-target-text').textContent = data.sent_to || contact;
                goToStep(2);
                startTimer(300);
                otpCells[0].focus();
            } else {
                showAlert(data.message || 'Invalid credentials.');
                if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
            }
        },
        error: function(xhr) {
            setLoading('btn-step1', false);
            try {
                const data = JSON.parse(xhr.responseText.trim());
                showAlert(data.message || 'Connection error. Please try again.');
            } catch(e) {
                showAlert('Connection error. Please try again.');
            }
            if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
        }
    });
}

function verifyOtp() {
    const otp = [...otpCells].map(c => c.value).join('');
    if (otp.length < 6) { showAlert('Please enter the full 6-digit code.'); return; }

    setLoading('btn-step2', true);

    $.ajax({
        url: '../multifactor/verify_otp.php',
        type: 'POST',
        dataType: 'json',
        data: { otp },
        success: function(data) {
            setLoading('btn-step2', false);
            if (data.status === 'success') {
                showAlert('Verified! Redirecting…', 'success');
                clearInterval(timerInterval);
                setTimeout(() => { window.location.href = '../dashboard.php'; }, 900);
            } else {
                showAlert(data.message || 'Incorrect code. Please try again.');
                otpCells.forEach(c => { c.value = ''; c.classList.remove('filled'); });
                otpCells[0].focus();
            }
        },
        error: function(xhr) {
            setLoading('btn-step2', false);
            try {
                const data = JSON.parse(xhr.responseText.trim());
                showAlert(data.message || 'Connection error. Please try again.');
            } catch(e) {
                showAlert('Connection error. Please try again.');
            }
        }
    });
}

function resendOtp() {
    const contact = document.getElementById('contact').value.trim();
    $.ajax({
        url: '../multifactor/process_login.php',
        type: 'POST',
        data: { contact, resend: '1' },
        success: function(res) {
            let data;
            try { data = JSON.parse(res); } catch(e) { return; }
            if (data.status === 'success') {
                showAlert('A new code has been sent.', 'info');
                startTimer(300);
                otpCells.forEach(c => { c.value = ''; c.classList.remove('filled'); });
                otpCells[0].focus();
            } else {
                showAlert(data.message || 'Could not resend code.');
            }
        }
    });
}

function tryAnotherWay(e) {
    e.preventDefault();
    showAlert('Please contact support or try signing in with your email for OTP delivery.', 'info');
}

document.getElementById('contact').addEventListener('keydown',  e => { if (e.key === 'Enter') document.getElementById('password').focus(); });
document.getElementById('password').addEventListener('keydown', e => { if (e.key === 'Enter') submitCredentials(); });
</script>
</body>
</html>