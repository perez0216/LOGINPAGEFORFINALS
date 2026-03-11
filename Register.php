<?php
require 'database/connection.php';

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $hash  = password_hash($_POST['pass'], PASSWORD_DEFAULT);
    $phone = trim($_POST['phone_prefix'] ?? '') . trim($_POST['phone'] ?? '');

    $security_question = trim($_POST['security_question'] ?? '');
    $security_answer   = strtolower(trim($_POST['security_answer'] ?? ''));
    $security_hash     = password_hash($security_answer, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone_number, age, password_hash, security_question, security_answer_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisss", $_POST['name'], $_POST['email'], $phone, $_POST['age'], $hash, $security_question, $security_hash);

    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        $conn->query("INSERT INTO login_mfa (user_id) VALUES ($user_id)");
        header('Location: auth/login.php?registered=1');
        exit;
    } else {
        $message      = "This email is already registered.";
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
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
            width: 100%; max-width: 480px;
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

        .message {
            padding: 0.875rem 1.1rem; border-radius: 8px; font-size: 0.825rem;
            margin-bottom: 1.75rem; display: flex; align-items: center; gap: 0.6rem;
            animation: fadeIn 0.3s ease both;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .message.success { background: rgba(95,207,128,0.08); border: 1px solid rgba(95,207,128,0.2); color: var(--success); }
        .message.error   { background: rgba(224,108,117,0.08); border: 1px solid rgba(224,108,117,0.2); color: var(--error); }
        .message a { color: inherit; font-weight: 500; text-decoration: underline; text-underline-offset: 2px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; }

        .field {
            position: relative;
            animation: fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) both;
        }
        .field.full { grid-column: 1 / -1; }
        .field:nth-child(1) { animation-delay: 0.06s; }
        .field:nth-child(2) { animation-delay: 0.10s; }
        .field:nth-child(3) { animation-delay: 0.14s; }
        .field:nth-child(4) { animation-delay: 0.18s; }
        .field:nth-child(5) { animation-delay: 0.22s; }
        .field:nth-child(6) { animation-delay: 0.26s; }
        .field:nth-child(7) { animation-delay: 0.30s; }

        label {
            display: block; font-size: 0.72rem; font-weight: 500;
            letter-spacing: 0.12em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 0.5rem; transition: color 0.2s;
        }
        .label-sub {
            text-transform: none; letter-spacing: 0;
            font-size: 0.68rem; opacity: 0.7;
        }
        .field:focus-within label { color: var(--gold); }

        .input-wrap { position: relative; }
        .input-icon {
            position: absolute; left: 0.9rem; top: 50%;
            transform: translateY(-50%); color: var(--muted);
            pointer-events: none; transition: color 0.2s;
        }
        .field:focus-within .input-icon { color: var(--gold); }

        input, select {
            width: 100%; background: rgba(255,255,255,0.03);
            border: 1px solid var(--border); border-radius: 8px;
            padding: 0.8rem 1rem; color: var(--text);
            font-family: 'DM Sans', sans-serif; font-size: 0.9rem; font-weight: 300;
            outline: none;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
            -webkit-appearance: none;
        }
        select {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%237a7880' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }
        select option { background: #1e1e28; color: var(--text); }
        input.with-icon { padding-left: 2.6rem; }
        input::placeholder { color: rgba(255,255,255,0.15); font-size: 0.82rem; }
        input:focus, select:focus {
            border-color: rgba(201,169,110,0.5);
            background: rgba(201,169,110,0.04);
            box-shadow: 0 0 0 3px rgba(201,169,110,0.08);
        }
        input:hover:not(:focus), select:hover:not(:focus) { border-color: rgba(255,255,255,0.12); }

        .section-divider {
            grid-column: 1 / -1;
            display: flex; align-items: center; gap: 10px;
            margin: 0.5rem 0 0.25rem;
        }
        .section-divider::before, .section-divider::after {
            content: ''; flex: 1; height: 1px;
            background: linear-gradient(to right, transparent, rgba(201,169,110,0.2), transparent);
        }
        .section-divider span {
            font-size: 0.65rem; letter-spacing: 0.2em; text-transform: uppercase;
            color: var(--gold); opacity: 0.7; white-space: nowrap;
            font-family: 'Cormorant Garamond', serif;
            display: flex; align-items: center; gap: 5px;
        }

        .answer-hint {
            font-size: 0.68rem; color: var(--muted); margin-top: 0.35rem;
            display: flex; align-items: center; gap: 0.3rem;
        }
        .answer-hint svg { flex-shrink: 0; }

        .phone-row { display: flex; position: relative; }

        .cc-btn {
            flex-shrink: 0;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border); border-right: none;
            border-radius: 8px 0 0 8px;
            padding: 0 0.85rem;
            color: var(--text);
            font-size: 0.85rem; font-family: 'DM Sans', sans-serif; font-weight: 400;
            display: flex; align-items: center; gap: 0.5rem;
            cursor: pointer; white-space: nowrap;
            transition: background 0.2s, border-color 0.2s;
            user-select: none;
            min-width: 72px;
        }
        .cc-btn:hover { background: rgba(201,169,110,0.08); border-color: rgba(201,169,110,0.3); }
        .cc-btn.open  { background: rgba(201,169,110,0.08); border-color: rgba(201,169,110,0.4); }
        .cc-btn .cc-flag { font-size: 1.05rem; line-height: 1; }
        .cc-btn .cc-code { font-size: 0.82rem; color: var(--text); }
        .cc-btn .cc-caret { margin-left: auto; color: var(--muted); transition: transform 0.2s; }
        .cc-btn.open .cc-caret { transform: rotate(180deg); }

        .phone-row input { border-radius: 0 8px 8px 0; flex: 1; }

        .cc-dropdown {
            position: absolute; top: calc(100% + 6px); left: 0; width: 300px;
            background: #1e1e28; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px; box-shadow: 0 16px 48px rgba(0,0,0,0.6);
            z-index: 999; overflow: hidden; display: none;
            animation: dropIn 0.18s cubic-bezier(0.22,1,0.36,1) both;
        }
        .cc-dropdown.open { display: block; }
        @keyframes dropIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }

        .cc-search-wrap { padding: 0.6rem 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.06); position: relative; }
        .cc-search {
            width: 100%; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.09); border-radius: 6px;
            padding: 0.5rem 0.75rem 0.5rem 2rem; color: var(--text);
            font-family: 'DM Sans', sans-serif; font-size: 0.82rem; outline: none;
        }
        .cc-search:focus { border-color: rgba(201,169,110,0.4); background: rgba(201,169,110,0.04); }
        .cc-search-icon { position: absolute; left: 1.3rem; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }

        .cc-list { max-height: 220px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: rgba(201,169,110,0.2) transparent; }
        .cc-list::-webkit-scrollbar { width: 4px; }
        .cc-list::-webkit-scrollbar-thumb { background: rgba(201,169,110,0.2); border-radius: 2px; }

        .cc-item { display: flex; align-items: center; gap: 0.65rem; padding: 0.55rem 0.85rem; cursor: pointer; transition: background 0.15s; font-size: 0.83rem; }
        .cc-item:hover { background: rgba(201,169,110,0.08); }
        .cc-item.active { background: rgba(201,169,110,0.12); }
        .cc-item .ci-flag { font-size: 1.1rem; flex-shrink: 0; }
        .cc-item .ci-name { color: var(--text); flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ci-name mark { background: none; color: var(--gold); font-weight: 500; }
        .cc-item .ci-dial { color: var(--muted); font-size: 0.78rem; flex-shrink: 0; }
        .cc-empty { padding: 1.2rem; text-align: center; color: var(--muted); font-size: 0.82rem; }

        .cc-suggestions { display: flex; gap: 0.4rem; flex-wrap: wrap; margin-top: 0.45rem; min-height: 0; }
        .cc-chip {
            display: inline-flex; align-items: center; gap: 0.3rem;
            background: rgba(201,169,110,0.08); border: 1px solid rgba(201,169,110,0.2);
            border-radius: 20px; padding: 0.22rem 0.65rem;
            font-size: 0.75rem; color: var(--gold); cursor: pointer;
            transition: background 0.15s, border-color 0.15s; animation: fadeIn 0.2s ease both;
        }
        .cc-chip:hover { background: rgba(201,169,110,0.16); border-color: rgba(201,169,110,0.4); }
        .cc-chip .chip-flag { font-size: 0.85rem; }

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

        .terms-note {
            text-align: center; font-size: 0.72rem; color: var(--muted);
            margin-top: 1.25rem; line-height: 1.6;
            animation: fadeUp 0.5s 0.32s cubic-bezier(0.22,1,0.36,1) both;
        }
        .terms-note a { color: var(--gold); text-decoration: none; }

        .btn-submit {
            width: 100%; margin-top: 1.25rem; padding: 0.95rem;
            background: linear-gradient(135deg, #c9a96e 0%, #a07c45 100%);
            border: none; border-radius: 8px; color: #0e0e12;
            font-family: 'DM Sans', sans-serif; font-size: 0.875rem;
            font-weight: 500; letter-spacing: 0.06em; text-transform: uppercase;
            cursor: pointer; overflow: hidden; position: relative;
            transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
            animation: fadeUp 0.5s 0.36s cubic-bezier(0.22,1,0.36,1) both;
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
            animation: fadeUp 0.5s 0.40s cubic-bezier(0.22,1,0.36,1) both;
        }
        .footer-link a { color: var(--gold); text-decoration: none; font-weight: 500; transition: opacity 0.2s; }
        .footer-link a:hover { opacity: 0.75; }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; }
        input[type=number] { -moz-appearance: textfield; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="ornament"><span>New Account</span></div>
    <div class="card">
        <div class="card-header">
            <h1>Create Account</h1>
            <p>Fill in your details to get started</p>
        </div>

        <?php if ($message): ?>
        <div class="message <?= $message_type ?>">
            <?php if ($message_type === 'error'): ?>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?php endif; ?>
            <?= $message ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="reg-form" autocomplete="off">
            <div class="form-grid">
                <div class="field">
                    <label for="name">Full Name</label>
                    <div class="input-wrap">
                        <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input class="with-icon" id="name" name="name" type="text" placeholder="Jane Doe" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                </div>
                <div class="field">
                    <label for="age">Age</label>
                    <input id="age" name="age" type="number" placeholder="25" min="1" max="120" required value="<?= htmlspecialchars($_POST['age'] ?? '') ?>">
                </div>
                <div class="field full">
                    <label for="email">Email Address</label>
                    <div class="input-wrap">
                        <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="2,4 12,13 22,4"/></svg>
                        <input class="with-icon" id="email" name="email" type="email" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>
                <div class="field full">
                    <label for="phone">Phone Number <span class="label-sub">(used for OTP verification)</span></label>
                    <div class="phone-row" id="phone-row">
                        <input type="hidden" name="phone_prefix" id="phone_prefix" value="+60">
                        <button type="button" class="cc-btn" id="cc-btn" aria-haspopup="listbox" aria-expanded="false">
                            <span class="cc-flag" id="cc-flag">🇲🇾</span>
                            <span class="cc-code" id="cc-code">+60</span>
                            <svg class="cc-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="cc-dropdown" id="cc-dropdown" role="listbox">
                            <div class="cc-search-wrap">
                                <svg class="cc-search-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input class="cc-search" id="cc-search" type="text" placeholder="Search country or code…" autocomplete="off">
                            </div>
                            <div class="cc-list" id="cc-list" role="listbox"></div>
                        </div>
                        <input id="phone" name="phone" type="tel" placeholder="12 345 6789" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" inputmode="tel">
                    </div>
                    <div class="cc-suggestions" id="cc-suggestions"></div>
                </div>
                <div class="field full">
                    <label for="pass">Password</label>
                    <div class="pass-wrap">
                        <input id="pass" name="pass" type="password" placeholder="Min. 8 characters" minlength="8" required oninput="checkStrength(this.value)">
                        <button type="button" class="toggle-pass" onclick="togglePass()">
                            <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
                    <div class="strength-hint" id="strength-hint"></div>
                </div>
                <div class="section-divider">
                    <span>
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Security Question
                    </span>
                </div>
                <div class="field full">
                    <label for="security_question">Choose a Security Question</label>
                    <select id="security_question" name="security_question" required>
                        <option value="" disabled <?= empty($_POST['security_question']) ? 'selected' : '' ?>>Select a question…</option>
                        <option value="What is your mother's maiden name?" <?= ($_POST['security_question'] ?? '') === "What is your mother's maiden name?" ? 'selected' : '' ?>>What is your mother's maiden name?</option>
                        <option value="What was the name of your first pet?" <?= ($_POST['security_question'] ?? '') === "What was the name of your first pet?" ? 'selected' : '' ?>>What was the name of your first pet?</option>
                        <option value="What is your favorite color?" <?= ($_POST['security_question'] ?? '') === "What is your favorite color?" ? 'selected' : '' ?>>What is your favorite color?</option>
                        <option value="What city were you born in?" <?= ($_POST['security_question'] ?? '') === "What city were you born in?" ? 'selected' : '' ?>>What city were you born in?</option>
                        <option value="What was the name of your childhood best friend?" <?= ($_POST['security_question'] ?? '') === "What was the name of your childhood best friend?" ? 'selected' : '' ?>>What was the name of your childhood best friend?</option>
                        <option value="What was the make of your first car?" <?= ($_POST['security_question'] ?? '') === "What was the make of your first car?" ? 'selected' : '' ?>>What was the make of your first car?</option>
                        <option value="What primary school did you attend?" <?= ($_POST['security_question'] ?? '') === "What primary school did you attend?" ? 'selected' : '' ?>>What primary school did you attend?</option>
                        <option value="What is your favorite food?" <?= ($_POST['security_question'] ?? '') === "What is your favorite food?" ? 'selected' : '' ?>>What is your favorite food?</option>
                    </select>
                </div>
                <div class="field full">
                    <label for="security_answer">Your Answer <span class="label-sub">(used for account recovery)</span></label>
                    <div class="input-wrap">
                        <svg class="input-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <input class="with-icon" id="security_answer" name="security_answer" type="text" placeholder="Your answer here" required value="<?= htmlspecialchars($_POST['security_answer'] ?? '') ?>" autocomplete="off">
                    </div>
                    <p class="answer-hint">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        Answers are case-insensitive and stored securely. Keep it memorable.
                    </p>
                </div>
            </div>
            <p class="terms-note">By registering you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</p>
            <button type="submit" class="btn-submit" id="submit-btn">
                <span class="btn-text">Create Account</span>
                <div class="spinner"></div>
            </button>
        </form>
        <p class="footer-link">Already have an account? <a href="auth/login.php">Sign in</a></p>
    </div>
</div>
<script>
const COUNTRIES = [
    { flag:'🇦🇫', name:'Afghanistan', dial:'+93' },{ flag:'🇦🇱', name:'Albania', dial:'+355' },{ flag:'🇩🇿', name:'Algeria', dial:'+213' },{ flag:'🇦🇩', name:'Andorra', dial:'+376' },{ flag:'🇦🇴', name:'Angola', dial:'+244' },{ flag:'🇦🇷', name:'Argentina', dial:'+54' },{ flag:'🇦🇲', name:'Armenia', dial:'+374' },{ flag:'🇦🇺', name:'Australia', dial:'+61' },{ flag:'🇦🇹', name:'Austria', dial:'+43' },{ flag:'🇦🇿', name:'Azerbaijan', dial:'+994' },{ flag:'🇧🇭', name:'Bahrain', dial:'+973' },{ flag:'🇧🇩', name:'Bangladesh', dial:'+880' },{ flag:'🇧🇾', name:'Belarus', dial:'+375' },{ flag:'🇧🇪', name:'Belgium', dial:'+32' },{ flag:'🇧🇿', name:'Belize', dial:'+501' },{ flag:'🇧🇯', name:'Benin', dial:'+229' },{ flag:'🇧🇹', name:'Bhutan', dial:'+975' },{ flag:'🇧🇴', name:'Bolivia', dial:'+591' },{ flag:'🇧🇦', name:'Bosnia & Herzegovina', dial:'+387' },{ flag:'🇧🇷', name:'Brazil', dial:'+55' },{ flag:'🇧🇳', name:'Brunei', dial:'+673' },{ flag:'🇧🇬', name:'Bulgaria', dial:'+359' },{ flag:'🇰🇭', name:'Cambodia', dial:'+855' },{ flag:'🇨🇲', name:'Cameroon', dial:'+237' },{ flag:'🇨🇦', name:'Canada', dial:'+1' },{ flag:'🇨🇱', name:'Chile', dial:'+56' },{ flag:'🇨🇳', name:'China', dial:'+86' },{ flag:'🇨🇴', name:'Colombia', dial:'+57' },{ flag:'🇨🇷', name:'Costa Rica', dial:'+506' },{ flag:'🇭🇷', name:'Croatia', dial:'+385' },{ flag:'🇨🇺', name:'Cuba', dial:'+53' },{ flag:'🇨🇾', name:'Cyprus', dial:'+357' },{ flag:'🇨🇿', name:'Czech Republic', dial:'+420' },{ flag:'🇩🇰', name:'Denmark', dial:'+45' },{ flag:'🇩🇴', name:'Dominican Republic', dial:'+1' },{ flag:'🇪🇨', name:'Ecuador', dial:'+593' },{ flag:'🇪🇬', name:'Egypt', dial:'+20' },{ flag:'🇸🇻', name:'El Salvador', dial:'+503' },{ flag:'🇪🇪', name:'Estonia', dial:'+372' },{ flag:'🇪🇹', name:'Ethiopia', dial:'+251' },{ flag:'🇫🇮', name:'Finland', dial:'+358' },{ flag:'🇫🇷', name:'France', dial:'+33' },{ flag:'🇬🇪', name:'Georgia', dial:'+995' },{ flag:'🇩🇪', name:'Germany', dial:'+49' },{ flag:'🇬🇭', name:'Ghana', dial:'+233' },{ flag:'🇬🇷', name:'Greece', dial:'+30' },{ flag:'🇬🇹', name:'Guatemala', dial:'+502' },{ flag:'🇭🇳', name:'Honduras', dial:'+504' },{ flag:'🇭🇰', name:'Hong Kong', dial:'+852' },{ flag:'🇭🇺', name:'Hungary', dial:'+36' },{ flag:'🇮🇸', name:'Iceland', dial:'+354' },{ flag:'🇮🇳', name:'India', dial:'+91' },{ flag:'🇮🇩', name:'Indonesia', dial:'+62' },{ flag:'🇮🇷', name:'Iran', dial:'+98' },{ flag:'🇮🇶', name:'Iraq', dial:'+964' },{ flag:'🇮🇪', name:'Ireland', dial:'+353' },{ flag:'🇮🇱', name:'Israel', dial:'+972' },{ flag:'🇮🇹', name:'Italy', dial:'+39' },{ flag:'🇯🇲', name:'Jamaica', dial:'+1' },{ flag:'🇯🇵', name:'Japan', dial:'+81' },{ flag:'🇯🇴', name:'Jordan', dial:'+962' },{ flag:'🇰🇿', name:'Kazakhstan', dial:'+7' },{ flag:'🇰🇪', name:'Kenya', dial:'+254' },{ flag:'🇰🇷', name:'South Korea', dial:'+82' },{ flag:'🇰🇼', name:'Kuwait', dial:'+965' },{ flag:'🇰🇬', name:'Kyrgyzstan', dial:'+996' },{ flag:'🇱🇦', name:'Laos', dial:'+856' },{ flag:'🇱🇻', name:'Latvia', dial:'+371' },{ flag:'🇱🇧', name:'Lebanon', dial:'+961' },{ flag:'🇱🇾', name:'Libya', dial:'+218' },{ flag:'🇱🇮', name:'Liechtenstein', dial:'+423' },{ flag:'🇱🇹', name:'Lithuania', dial:'+370' },{ flag:'🇱🇺', name:'Luxembourg', dial:'+352' },{ flag:'🇲🇴', name:'Macau', dial:'+853' },{ flag:'🇲🇾', name:'Malaysia', dial:'+60' },{ flag:'🇲🇻', name:'Maldives', dial:'+960' },{ flag:'🇲🇹', name:'Malta', dial:'+356' },{ flag:'🇲🇽', name:'Mexico', dial:'+52' },{ flag:'🇲🇩', name:'Moldova', dial:'+373' },{ flag:'🇲🇳', name:'Mongolia', dial:'+976' },{ flag:'🇲🇦', name:'Morocco', dial:'+212' },{ flag:'🇲🇲', name:'Myanmar', dial:'+95' },{ flag:'🇳🇵', name:'Nepal', dial:'+977' },{ flag:'🇳🇱', name:'Netherlands', dial:'+31' },{ flag:'🇳🇿', name:'New Zealand', dial:'+64' },{ flag:'🇳🇬', name:'Nigeria', dial:'+234' },{ flag:'🇳🇴', name:'Norway', dial:'+47' },{ flag:'🇴🇲', name:'Oman', dial:'+968' },{ flag:'🇵🇰', name:'Pakistan', dial:'+92' },{ flag:'🇵🇦', name:'Panama', dial:'+507' },{ flag:'🇵🇾', name:'Paraguay', dial:'+595' },{ flag:'🇵🇪', name:'Peru', dial:'+51' },{ flag:'🇵🇭', name:'Philippines', dial:'+63' },{ flag:'🇵🇱', name:'Poland', dial:'+48' },{ flag:'🇵🇹', name:'Portugal', dial:'+351' },{ flag:'🇶🇦', name:'Qatar', dial:'+974' },{ flag:'🇷🇴', name:'Romania', dial:'+40' },{ flag:'🇷🇺', name:'Russia', dial:'+7' },{ flag:'🇸🇦', name:'Saudi Arabia', dial:'+966' },{ flag:'🇸🇳', name:'Senegal', dial:'+221' },{ flag:'🇷🇸', name:'Serbia', dial:'+381' },{ flag:'🇸🇬', name:'Singapore', dial:'+65' },{ flag:'🇸🇰', name:'Slovakia', dial:'+421' },{ flag:'🇸🇮', name:'Slovenia', dial:'+386' },{ flag:'🇸🇴', name:'Somalia', dial:'+252' },{ flag:'🇿🇦', name:'South Africa', dial:'+27' },{ flag:'🇪🇸', name:'Spain', dial:'+34' },{ flag:'🇱🇰', name:'Sri Lanka', dial:'+94' },{ flag:'🇸🇩', name:'Sudan', dial:'+249' },{ flag:'🇸🇪', name:'Sweden', dial:'+46' },{ flag:'🇨🇭', name:'Switzerland', dial:'+41' },{ flag:'🇸🇾', name:'Syria', dial:'+963' },{ flag:'🇹🇼', name:'Taiwan', dial:'+886' },{ flag:'🇹🇯', name:'Tajikistan', dial:'+992' },{ flag:'🇹🇿', name:'Tanzania', dial:'+255' },{ flag:'🇹🇭', name:'Thailand', dial:'+66' },{ flag:'🇹🇳', name:'Tunisia', dial:'+216' },{ flag:'🇹🇷', name:'Turkey', dial:'+90' },{ flag:'🇹🇲', name:'Turkmenistan', dial:'+993' },{ flag:'🇺🇬', name:'Uganda', dial:'+256' },{ flag:'🇺🇦', name:'Ukraine', dial:'+380' },{ flag:'🇦🇪', name:'United Arab Emirates', dial:'+971' },{ flag:'🇬🇧', name:'United Kingdom', dial:'+44' },{ flag:'🇺🇸', name:'United States', dial:'+1' },{ flag:'🇺🇾', name:'Uruguay', dial:'+598' },{ flag:'🇺🇿', name:'Uzbekistan', dial:'+998' },{ flag:'🇻🇳', name:'Vietnam', dial:'+84' },{ flag:'🇾🇪', name:'Yemen', dial:'+967' },{ flag:'🇿🇲', name:'Zambia', dial:'+260' },{ flag:'🇿🇼', name:'Zimbabwe', dial:'+263' }
];

let selectedCountry = COUNTRIES.find(c => c.dial === '+60') || COUNTRIES[0];
let dropdownOpen = false;

const ccBtn       = document.getElementById('cc-btn');
const ccDropdown  = document.getElementById('cc-dropdown');
const ccSearch    = document.getElementById('cc-search');
const ccList      = document.getElementById('cc-list');
const ccFlag      = document.getElementById('cc-flag');
const ccCode      = document.getElementById('cc-code');
const prefixInput = document.getElementById('phone_prefix');
const phoneInput  = document.getElementById('phone');
const suggestBox  = document.getElementById('cc-suggestions');

function highlight(text, query) {
    if (!query) return text;
    const idx = text.toLowerCase().indexOf(query.toLowerCase());
    if (idx === -1) return text;
    return text.slice(0, idx) + '<mark>' + text.slice(idx, idx + query.length) + '</mark>' + text.slice(idx + query.length);
}

function renderList(filter = '') {
    const q = filter.trim().toLowerCase();
    const items = COUNTRIES.filter(c => c.name.toLowerCase().includes(q) || c.dial.includes(q) || (q.startsWith('+') && c.dial.startsWith(q)));
    if (items.length === 0) { ccList.innerHTML = '<div class="cc-empty">No results found</div>'; return; }
    ccList.innerHTML = items.map(c => `<div class="cc-item${c === selectedCountry ? ' active' : ''}" role="option" data-dial="${c.dial}" data-flag="${c.flag}" data-name="${c.name}"><span class="ci-flag">${c.flag}</span><span class="ci-name">${highlight(c.name, q)}</span><span class="ci-dial">${c.dial}</span></div>`).join('');
    ccList.querySelectorAll('.cc-item').forEach(el => {
        el.addEventListener('click', () => {
            const country = COUNTRIES.find(c => c.name === el.dataset.name && c.dial === el.dataset.dial);
            if (country) selectCountry(country);
            closeDropdown();
        });
    });
}

function selectCountry(country) {
    selectedCountry   = country;
    ccFlag.textContent = country.flag;
    ccCode.textContent = country.dial;
    prefixInput.value  = country.dial;
}

function openDropdown() {
    dropdownOpen = true;
    ccDropdown.classList.add('open');
    ccBtn.classList.add('open');
    ccBtn.setAttribute('aria-expanded', 'true');
    ccSearch.value = '';
    renderList('');
    setTimeout(() => ccSearch.focus(), 50);
}

function closeDropdown() {
    dropdownOpen = false;
    ccDropdown.classList.remove('open');
    ccBtn.classList.remove('open');
    ccBtn.setAttribute('aria-expanded', 'false');
}

ccBtn.addEventListener('click', (e) => { e.stopPropagation(); dropdownOpen ? closeDropdown() : openDropdown(); });
ccSearch.addEventListener('input', () => renderList(ccSearch.value));
document.addEventListener('click', (e) => { if (dropdownOpen && !ccDropdown.contains(e.target) && e.target !== ccBtn && !ccBtn.contains(e.target)) closeDropdown(); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && dropdownOpen) closeDropdown(); });

phoneInput.addEventListener('input', () => {
    const val = phoneInput.value.trim();
    suggestBox.innerHTML = '';
    if (!val) return;
    const digits = val.replace(/\D/g, '');
    if (!digits) return;
    const seen = new Set();
    const matches = [];
    for (let len = Math.min(4, digits.length); len >= 1; len--) {
        const prefix = '+' + digits.slice(0, len);
        COUNTRIES.forEach(c => { if (c.dial === prefix && !seen.has(c.dial + c.name)) { seen.add(c.dial + c.name); matches.push(c); } });
    }
    if (matches.length === 0) return;
    matches.slice(0, 5).forEach(c => {
        const chip = document.createElement('span');
        chip.className = 'cc-chip';
        chip.innerHTML = `<span class="chip-flag">${c.flag}</span>${c.dial} ${c.name}`;
        chip.title = `Switch to ${c.name} (${c.dial})`;
        chip.addEventListener('click', () => { selectCountry(c); const stripped = val.replace(/^\+?\d{1,4}/, '').trim(); phoneInput.value = stripped; suggestBox.innerHTML = ''; });
        suggestBox.appendChild(chip);
    });
});

function togglePass() {
    const input = document.getElementById('pass');
    const icon  = document.getElementById('eye-icon');
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
        { w:'100%', bg:'#5fcf80',     text:'Strong ✓' }
    ];
    fill.style.width      = levels[score].w;
    fill.style.background = levels[score].bg;
    hint.textContent      = levels[score].text;
    hint.style.color      = levels[score].bg;
}

document.getElementById('reg-form').addEventListener('submit', () => {
    document.getElementById('submit-btn').classList.add('loading');
});

renderList();
</script>
</body>
</html>