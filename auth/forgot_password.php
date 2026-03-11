<?php
session_start();
require '../database/connection.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$protocol    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host        = $_SERVER['HTTP_HOST'];
$scriptDir   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$projectRoot = dirname($scriptDir);
$apiBase     = $protocol . '://' . $host . $projectRoot . '/multifactor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
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
            --info:    #6ea8d4;
        }
        html, body { height: 100%; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--ink); color: var(--text);
            min-height: 100vh; display: flex;
            align-items: center; justify-content: center; padding: 2rem;
        }
        body::before {
            content: ''; position: fixed; inset: 0;
            background: radial-gradient(ellipse 60% 50% at 20% 20%, rgba(201,169,110,0.06) 0%, transparent 60%),
                        radial-gradient(ellipse 50% 60% at 80% 80%, rgba(100,80,180,0.05) 0%, transparent 60%);
            pointer-events: none; z-index: 0;
        }
        body::after {
            content: ''; position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            background-size: 200px; pointer-events: none; z-index: 0; opacity: 0.4;
        }
        .wrapper {
            position: relative; z-index: 1; width: 100%; max-width: 440px;
            animation: fadeUp 0.7s cubic-bezier(0.22,1,0.36,1) both;
        }
        @keyframes fadeUp { from { opacity:0; transform:translateY(28px); } to { opacity:1; transform:translateY(0); } }
        .ornament { display: flex; align-items: center; gap: 12px; margin-bottom: 2.5rem; }
        .ornament::before, .ornament::after {
            content: ''; flex: 1; height: 1px;
            background: linear-gradient(to right, transparent, var(--gold), transparent); opacity: 0.4;
        }
        .ornament span {
            font-family: 'Cormorant Garamond', serif; font-size: 0.7rem;
            letter-spacing: 0.25em; text-transform: uppercase; color: var(--gold); opacity: 0.8;
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
        .card-header { margin-bottom: 2rem; text-align: center; }
        .card-header h1 {
            font-family: 'Cormorant Garamond', serif; font-size: 2.4rem; font-weight: 500;
            letter-spacing: -0.01em; margin-bottom: 0.5rem;
        }
        .card-header p { font-size: 0.825rem; color: var(--muted); font-weight: 300; }
        .progress-dots { display: flex; justify-content: center; gap: 6px; margin-bottom: 1.75rem; }
        .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: rgba(255,255,255,0.12); transition: background 0.3s, transform 0.3s;
        }
        .dot.active   { background: var(--gold); transform: scale(1.3); }
        .dot.complete { background: rgba(201,169,110,0.4); }
        .step { display: none; }
        .step.active { display: block; animation: fadeUp 0.4s cubic-bezier(0.22,1,0.36,1) both; }
        .alert {
            padding: 0.875rem 1.1rem; border-radius: 8px; font-size: 0.825rem;
            margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.6rem;
        }
        .alert.success { background: rgba(95,207,128,0.08);  border: 1px solid rgba(95,207,128,0.2);  color: var(--success); }
        .alert.error   { background: rgba(224,108,117,0.08); border: 1px solid rgba(224,108,117,0.2); color: var(--error); }
        .alert.info    { background: rgba(201,169,110,0.07); border: 1px solid rgba(201,169,110,0.2); color: var(--gold); }
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
        input[type=text], input[type=email], input[type=password] {
            width: 100%; background: rgba(255,255,255,0.03);
            border: 1px solid var(--border); border-radius: 8px;
            padding: 0.8rem 1rem; color: var(--text);
            font-family: 'DM Sans', sans-serif; font-size: 0.9rem; font-weight: 300;
            outline: none; transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
            -webkit-appearance: none;
        }
        input.with-icon { padding-left: 2.6rem; }
        input::placeholder { color: rgba(255,255,255,0.15); font-size: 0.82rem; }
        input:focus {
            border-color: rgba(201,169,110,0.5); background: rgba(201,169,110,0.04);
            box-shadow: 0 0 0 3px rgba(201,169,110,0.08);
        }
        input:hover:not(:focus) { border-color: rgba(255,255,255,0.12); }
        .question-box {
            background: rgba(201,169,110,0.06); border: 1px solid rgba(201,169,110,0.2);
            border-radius: 8px; padding: 0.9rem 1.1rem; margin-bottom: 1.25rem;
            display: flex; align-items: flex-start; gap: 0.75rem;
        }
        .question-box svg { flex-shrink: 0; color: var(--gold); margin-top: 2px; }
        .question-box p {
            font-family: 'Cormorant Garamond', serif; font-size: 1rem;
            font-style: italic; color: var(--text); line-height: 1.5;
        }
        .contact-badge {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: rgba(201,169,110,0.08); border: 1px solid rgba(201,169,110,0.2);
            border-radius: 20px; padding: 0.2rem 0.75rem;
            font-size: 0.78rem; color: var(--gold); margin-bottom: 1.5rem;
        }
        .back-link {
            display: inline-flex; align-items: center; gap: 0.3rem;
            font-size: 0.78rem; color: var(--muted); cursor: pointer;
            background: none; border: none; padding: 0;
            font-family: 'DM Sans', sans-serif; transition: color 0.2s; margin-bottom: 1.5rem;
        }
        .back-link:hover { color: var(--gold); }
        .otp-row { display: flex; gap: 0.6rem; justify-content: center; margin: 0.5rem 0 1.25rem; }
        .otp-cell {
            width: 48px; height: 56px; background: rgba(255,255,255,0.03);
            border: 1px solid var(--border); border-radius: 8px;
            color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: 1.4rem; font-weight: 400; text-align: center;
            outline: none; caret-color: var(--gold);
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
            -webkit-appearance: none;
        }
        .otp-cell:focus {
            border-color: rgba(201,169,110,0.5); background: rgba(201,169,110,0.04);
            box-shadow: 0 0 0 3px rgba(201,169,110,0.08);
        }
        .otp-cell.filled { border-color: rgba(201,169,110,0.35); }
        .otp-meta {
            text-align: center; font-size: 0.78rem; color: var(--muted);
            margin-bottom: 1.25rem; line-height: 1.6;
        }
        .otp-meta strong { color: var(--text); font-weight: 400; }
        .resend-btn {
            background: none; border: none; color: var(--gold);
            font-family: 'DM Sans', sans-serif; font-size: 0.78rem;
            cursor: pointer; padding: 0; transition: opacity 0.2s;
        }
        .resend-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .resend-btn:not(:disabled):hover { opacity: 0.7; }
        .pass-wrap { position: relative; }
        .pass-wrap input { padding-right: 3rem; }
        .toggle-pass {
            position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--muted); transition: color 0.2s; display: flex; align-items: center; padding: 0;
        }
        .toggle-pass:hover { color: var(--gold); }
        .strength-bar { height: 3px; border-radius: 2px; background: rgba(255,255,255,0.06); margin-top: 0.5rem; overflow: hidden; }
        .strength-fill { height: 100%; width: 0%; border-radius: 2px; transition: width 0.3s, background 0.3s; }
        .strength-hint { font-size: 0.68rem; color: var(--muted); margin-top: 0.3rem; height: 1em; }
        .field-hint {
            font-size: 0.68rem; color: var(--muted); margin-top: 0.35rem;
            display: flex; align-items: center; gap: 0.3rem; min-height: 1em;
        }
        .success-icon {
            width: 64px; height: 64px; border-radius: 50%;
            background: rgba(95,207,128,0.1); border: 1px solid rgba(95,207,128,0.25);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            animation: popIn 0.5s cubic-bezier(0.22,1,0.36,1) both;
        }
        @keyframes popIn { from { opacity:0; transform:scale(0.6); } to { opacity:1; transform:scale(1); } }
        .success-icon svg { color: var(--success); }
        .success-text { text-align: center; margin-bottom: 1.75rem; }
        .success-text h2 { font-family: 'Cormorant Garamond', serif; font-size: 1.6rem; font-weight: 500; margin-bottom: 0.5rem; }
        .success-text p { font-size: 0.825rem; color: var(--muted); line-height: 1.6; }
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
        .footer-link { text-align: center; margin-top: 1.75rem; font-size: 0.8rem; color: var(--muted); }
        .footer-link a { color: var(--gold); text-decoration: none; font-weight: 500; transition: opacity 0.2s; }
        .footer-link a:hover { opacity: 0.75; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="ornament"><span>Account Recovery</span></div>

    <div class="card">
        <div class="card-header">
            <h1>Reset Password</h1>
            <p>Verify your identity to regain access</p>
        </div>

        <div class="progress-dots">
            <div class="dot active" id="dot-1"></div>
            <div class="dot"        id="dot-2"></div>
            <div class="dot"        id="dot-3"></div>
            <div class="dot"        id="dot-4"></div>
        </div>

        <div id="alert-box" style="display:none;"></div>

        <div class="step active" id="step-1">
            <div class="field">
                <label for="email">Email Address</label>
                <div class="input-wrap">
                    <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="2,4 12,13 22,4"/></svg>
                    <input class="with-icon" id="email" type="email" placeholder="you@example.com" autocomplete="email" required>
                </div>
            </div>
            <button class="btn-submit" id="btn-step1" onclick="submitEmail()">
                <span class="btn-text">Continue</span>
                <div class="spinner"></div>
            </button>
        </div>

        <div class="step" id="step-2">
            <button type="button" class="back-link" onclick="goToStep(1)">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                Back
            </button>
            <div style="text-align:center; margin-bottom:1.25rem;">
                <p style="font-size:0.78rem; color:var(--muted); margin-bottom:0.5rem;">Recovering account for</p>
                <span class="contact-badge">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="2,4 12,13 22,4"/></svg>
                    <span id="email-display">—</span>
                </span>
            </div>
            <div class="question-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <p id="question-display">Loading…</p>
            </div>
            <div class="field">
                <label for="sec-answer">Your Answer</label>
                <div class="input-wrap">
                    <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input class="with-icon" id="sec-answer" type="text" placeholder="Your answer" autocomplete="off" required>
                </div>
                <p class="field-hint">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    Case-insensitive
                </p>
            </div>
            <button class="btn-submit" id="btn-step2" onclick="submitAnswer()">
                <span class="btn-text">Verify &amp; Send OTP</span>
                <div class="spinner"></div>
            </button>
        </div>

        <div class="step" id="step-3">
            <div style="text-align:center; margin-bottom:1.5rem;">
                <p style="font-size:0.82rem; color:var(--muted); margin-bottom:0.6rem;">We sent a one-time code to</p>
                <span class="contact-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="2,4 12,13 22,4"/></svg>
                    <span id="otp-email-display">—</span>
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
            <button class="btn-submit" id="btn-step3" onclick="verifyOtp()">
                <span class="btn-text">Verify Code</span>
                <div class="spinner"></div>
            </button>
        </div>

        <div class="step" id="step-4">
            <div class="field">
                <label for="new-password">New Password</label>
                <div class="pass-wrap">
                    <input id="new-password" type="password" placeholder="Min. 8 characters" minlength="8" oninput="checkStrength(this.value)" required>
                    <button type="button" class="toggle-pass" onclick="togglePass('new-password','eye-new')">
                        <svg id="eye-new" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
                <div class="strength-hint" id="strength-hint"></div>
            </div>
            <div class="field">
                <label for="confirm-password">Confirm New Password</label>
                <div class="pass-wrap">
                    <input id="confirm-password" type="password" placeholder="Re-enter your password" required>
                    <button type="button" class="toggle-pass" onclick="togglePass('confirm-password','eye-confirm')">
                        <svg id="eye-confirm" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <div id="match-hint" class="field-hint"></div>
            </div>
            <button class="btn-submit" id="btn-step4" onclick="submitNewPassword()">
                <span class="btn-text">Reset Password</span>
                <div class="spinner"></div>
            </button>
        </div>

        <div class="step" id="step-5">
            <div class="success-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="success-text">
                <h2>Password Reset!</h2>
                <p>Your password has been updated successfully.<br>You can now sign in with your new password.</p>
            </div>
            <a href="login.php" class="btn-submit" style="display:block;text-align:center;text-decoration:none;line-height:1;padding:0.95rem;">
                Sign In Now
            </a>
        </div>

    </div>
    <p class="footer-link">Remember your password? <a href="login.php">Sign in</a></p>
</div>

<script>
const API_BASE = '<?= $apiBase ?>';
let recoveryEmail = '';
let recoveryToken = '';
let timerInterval = null;

function showAlert(msg, type = 'error') {
    const box = document.getElementById('alert-box');
    const icons = {
        error:   `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
        success: `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
        info:    `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`
    };
    box.className = `alert ${type}`;
    box.innerHTML = (icons[type] || '') + msg;
    box.style.display = 'flex';
}
function hideAlert() { document.getElementById('alert-box').style.display = 'none'; }
function setLoading(id, on) { document.getElementById(id).classList.toggle('loading', on); }

function goToStep(n) {
    document.querySelectorAll('.step').forEach((s, i) => s.classList.toggle('active', i + 1 === n));
    [1,2,3,4,5].forEach(i => {
        const d = document.getElementById('dot-' + i);
        if (!d) return;
        d.classList.toggle('active',   i === n);
        d.classList.toggle('complete', i < n);
    });
    hideAlert();
}

function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    const hide  = input.type === 'password';
    input.type  = hide ? 'text' : 'password';
    icon.innerHTML = hide
        ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`
        : `<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>`;
}

function checkStrength(val) {
    const fill = document.getElementById('strength-fill');
    const hint = document.getElementById('strength-hint');
    let score = 0;
    if (val.length >= 8)          score++;
    if (/[A-Z]/.test(val))        score++;
    if (/[0-9]/.test(val))        score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
        { w:'0%',   bg:'transparent', text:'' },
        { w:'25%',  bg:'#e06c75',     text:'Weak' },
        { w:'50%',  bg:'#e5a550',     text:'Fair' },
        { w:'75%',  bg:'#6ea8d4',     text:'Good' },
        { w:'100%', bg:'#5fcf80',     text:'Strong ✓' },
    ];
    fill.style.width      = levels[score].w;
    fill.style.background = levels[score].bg;
    hint.textContent      = levels[score].text;
    hint.style.color      = levels[score].bg;
}

function post(url, params) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params).toString()
    }).then(r => r.json());
}

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
            el.textContent  = '0:00';
            resend.disabled = false;
        }
    }, 1000);
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
            otpCells[idx-1].value = ''; otpCells[idx-1].classList.remove('filled'); otpCells[idx-1].focus();
        }
    });
    cell.addEventListener('paste', e => {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        [...pasted].slice(0,6).forEach((ch,i) => { if (otpCells[i]) { otpCells[i].value=ch; otpCells[i].classList.add('filled'); } });
        otpCells[Math.min(pasted.length,5)].focus();
        if (pasted.length >= 6) verifyOtp();
    });
});

document.getElementById('confirm-password').addEventListener('input', function() {
    const hint = document.getElementById('match-hint');
    const newPass = document.getElementById('new-password').value;
    if (!this.value) { hint.innerHTML = ''; return; }
    if (this.value === newPass) {
        hint.innerHTML   = `<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Passwords match`;
        hint.style.color = 'var(--success)';
    } else {
        hint.innerHTML   = `<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Passwords do not match`;
        hint.style.color = 'var(--error)';
    }
});

function submitEmail() {
    const email = document.getElementById('email').value.trim();
    if (!email) { showAlert('Please enter your email address.'); return; }
    setLoading('btn-step1', true);
    post(API_BASE + '/forgot_lookup.php', { email })
    .then(data => {
        setLoading('btn-step1', false);
        if (data.status === 'success') {
            recoveryEmail = email;
            document.getElementById('email-display').textContent    = email;
            document.getElementById('question-display').textContent = data.question;
            goToStep(2);
            document.getElementById('sec-answer').focus();
        } else {
            showAlert(data.message || 'No account found with that email.');
        }
    })
    .catch(() => { setLoading('btn-step1', false); showAlert('Connection error. Please try again.'); });
}

function submitAnswer() {
    const answer = document.getElementById('sec-answer').value.trim();
    if (!answer) { showAlert('Please enter your answer.'); return; }
    setLoading('btn-step2', true);
    post(API_BASE + '/forgot_verify.php', { email: recoveryEmail, answer })
    .then(data => {
        setLoading('btn-step2', false);
        if (data.status === 'success') {
            document.getElementById('otp-email-display').textContent = data.sent_to || recoveryEmail;
            goToStep(3);
            startTimer(300);
            otpCells[0].focus();
        } else {
            showAlert(data.message || 'Incorrect answer. Please try again.');
            document.getElementById('sec-answer').value = '';
            document.getElementById('sec-answer').focus();
        }
    })
    .catch(() => { setLoading('btn-step2', false); showAlert('Connection error. Please try again.'); });
}

function verifyOtp() {
    const otp = [...otpCells].map(c => c.value).join('');
    if (otp.length < 6) { showAlert('Please enter the full 6-digit code.'); return; }
    setLoading('btn-step3', true);
    post(API_BASE + '/forgot_otp_verify.php', { otp })
    .then(data => {
        setLoading('btn-step3', false);
        if (data.status === 'success') {
            recoveryToken = data.token;
            clearInterval(timerInterval);
            goToStep(4);
            document.getElementById('new-password').focus();
        } else {
            showAlert(data.message || 'Incorrect code. Please try again.');
            otpCells.forEach(c => { c.value=''; c.classList.remove('filled'); });
            otpCells[0].focus();
        }
    })
    .catch(() => { setLoading('btn-step3', false); showAlert('Connection error. Please try again.'); });
}

function resendOtp() {
    post(API_BASE + '/forgot_verify.php', { email: recoveryEmail, resend: '1' })
    .then(data => {
        if (data.status === 'success') {
            showAlert('A new code has been sent.', 'info');
            startTimer(300);
            otpCells.forEach(c => { c.value=''; c.classList.remove('filled'); });
            otpCells[0].focus();
        } else {
            showAlert(data.message || 'Could not resend. Please start over.');
        }
    })
    .catch(() => showAlert('Connection error.'));
}

function submitNewPassword() {
    const newPass     = document.getElementById('new-password').value;
    const confirmPass = document.getElementById('confirm-password').value;
    if (newPass.length < 8)     { showAlert('Password must be at least 8 characters.'); return; }
    if (newPass !== confirmPass) { showAlert('Passwords do not match.'); return; }
    setLoading('btn-step4', true);
    post(API_BASE + '/forgot_reset.php', { token: recoveryToken, password: newPass })
    .then(data => {
        setLoading('btn-step4', false);
        if (data.status === 'success') {
            goToStep(5);
        } else {
            showAlert(data.message || 'Could not reset password. Please start over.');
        }
    })
    .catch(() => { setLoading('btn-step4', false); showAlert('Connection error. Please try again.'); });
}

document.getElementById('email').addEventListener('keydown',      e => { if (e.key==='Enter') submitEmail(); });
document.getElementById('sec-answer').addEventListener('keydown', e => { if (e.key==='Enter') submitAnswer(); });
</script>
</body>
</html>