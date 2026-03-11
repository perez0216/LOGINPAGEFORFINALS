<?php
ob_start(); // Buffer any stray output so it doesn't corrupt JSON
session_start();
require '../database/connection.php';

header('Content-Type: application/json');

// Catch any PHP fatal errors and return JSON so jQuery doesn't choke
set_exception_handler(function(Throwable $e) {
    error_log('process_login.php exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    // DEBUG: showing real error — remove before going live
    echo json_encode([
        'status'  => 'error',
        'message' => 'DEBUG: ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ' line ' . $e->getLine()
    ]);
    exit;
});

// ─── Helper: send JSON response ───────────────────────────────────────────────
function respond(string $status, string $message, array $extra = []): void {
    ob_end_clean(); // Discard any stray output before JSON
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

// ─── Helper: mask contact for display ─────────────────────────────────────────
function maskContact(string $contact): string {
    if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
        [$local, $domain] = explode('@', $contact);
        $masked = substr($local, 0, 2) . str_repeat('*', max(strlen($local) - 4, 2)) . substr($local, -2);
        return $masked . '@' . $domain;
    }
    // Phone: show last 4 digits
    $digits = preg_replace('/\D/', '', $contact);
    return '+' . substr($digits, 0, strlen($digits) - 4) . '****';
}

// ─── Helper: generate 6-digit OTP ─────────────────────────────────────────────
function generateOtp(): string {
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// ─── Only accept POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Invalid request method.');
}

$isResend = isset($_POST['resend']) && $_POST['resend'] === '1';

// ════════════════════════════════════════════════════════════════
//  RESEND FLOW — just regenerate & resend OTP for existing session
// ════════════════════════════════════════════════════════════════
if ($isResend) {
    if (empty($_SESSION['login_user_id'])) {
        respond('error', 'Session expired. Please log in again.');
    }

    $userId  = $_SESSION['login_user_id'];
    $contact = $_SESSION['login_contact'] ?? '';

    $otp     = generateOtp();
    $expires = date('Y-m-d H:i:s', time() + 300); // 5 minutes

    // Save new OTP
    $stmt = $conn->prepare("UPDATE login_mfa SET otp_code = ?, otp_expires_at = ?, otp_verified = 0 WHERE user_id = ?");
    if (!$stmt) {
        error_log('resend prepare failed: ' . $conn->error);
        respond('error', 'Failed to resend OTP. Please try again.');
    }
    $stmt->bind_param("ssi", $otp, $expires, $userId);
    $stmt->execute();

    // Re-send via email always
    $sent = sendOtpEmail($contact, $otp);
    if (!$sent) {
        respond('error', 'Failed to resend OTP. Please try again.');
    }

    respond('success', 'A new code has been sent.', ['sent_to' => maskContact($contact)]);
}

// ════════════════════════════════════════════════════════════════
//  STEP 1 — Validate inputs
// ════════════════════════════════════════════════════════════════
$contact  = trim($_POST['contact']  ?? '');
$password = trim($_POST['password'] ?? '');
$remember = ($_POST['remember'] ?? '0') === '1';
$recaptchaToken = $_POST['recaptcha'] ?? '';

if (!$contact || !$password) {
    respond('error', 'Please fill in all fields.');
}

// ════════════════════════════════════════════════════════════════
//  STEP 2 — Verify reCAPTCHA (bypassed on localhost for dev)
// ════════════════════════════════════════════════════════════════
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);

if (!$isLocalhost) {
    $secretKey = '6LfVbYYsAAAAAEDnSyhc390HYD27e41OIxDxeIUZ';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://www.google.com/recaptcha/api/siteverify',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => $secretKey,
            'response' => $recaptchaToken,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => 'PHP-reCAPTCHA-Verify',
    ]);
    $captchaVerify = curl_exec($ch);
    $httpCode      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError     = curl_error($ch);
    curl_close($ch);

    if ($captchaVerify === false || $httpCode !== 200) {
        error_log('reCAPTCHA cURL error: ' . $curlError . ' | HTTP: ' . $httpCode);
        respond('error', 'Could not reach CAPTCHA server. Please try again.');
    }

    $captchaData = json_decode($captchaVerify);
    if (!$captchaData || !$captchaData->success) {
        $errorCodes = isset($captchaData->{'error-codes'}) ? implode(', ', $captchaData->{'error-codes'}) : 'unknown';
        error_log('reCAPTCHA failed — codes: ' . $errorCodes . ' | token: ' . substr($recaptchaToken, 0, 20));
        respond('error', 'CAPTCHA verification failed. Please try again.');
    }
}

// ════════════════════════════════════════════════════════════════
//  STEP 3 — Look up user by email or phone number
// ════════════════════════════════════════════════════════════════
$isEmail = filter_var($contact, FILTER_VALIDATE_EMAIL);

if ($isEmail) {
    $stmt = $conn->prepare("SELECT id, full_name, email, phone_number, password_hash FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $contact);
} else {
    // Normalise phone: strip spaces/dashes for comparison
    $phoneClean = preg_replace('/[\s\-\(\)]/', '', $contact);
    $stmt = $conn->prepare("SELECT id, full_name, email, phone_number, password_hash FROM users WHERE REPLACE(REPLACE(phone_number,' ',''),'-','') = ? LIMIT 1");
    $stmt->bind_param("s", $phoneClean);
}

$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();

if (!$user) {
    respond('error', 'No account found with those credentials.');
}

// ════════════════════════════════════════════════════════════════
//  STEP 4 — Verify password
// ════════════════════════════════════════════════════════════════
if (!password_verify($password, $user['password_hash'])) {
    respond('error', 'Incorrect password. Please try again.');
}

// ════════════════════════════════════════════════════════════════
//  STEP 5 — Generate & store OTP
// ════════════════════════════════════════════════════════════════
$otp     = generateOtp();
$expires = date('Y-m-d H:i:s', time() + 300); // 5 minutes
$userId  = $user['id'];

// Upsert into login_mfa
$sql  = "INSERT INTO login_mfa (user_id, otp_code, otp_expires_at, otp_verified)
         VALUES (?, ?, ?, 0)
         ON DUPLICATE KEY UPDATE otp_code = VALUES(otp_code), otp_expires_at = VALUES(otp_expires_at), otp_verified = 0";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('login_mfa prepare failed: ' . $conn->error);
    respond('error', 'DEBUG: login_mfa prepare failed — ' . $conn->error . '. Run the ALTER TABLE migration.');
}
$stmt->bind_param("iss", $userId, $otp, $expires);
if (!$stmt->execute()) {
    respond('error', 'Failed to generate OTP. Please try again.');
}

// ════════════════════════════════════════════════════════════════
//  STEP 6 — Send OTP via email always (SMS not configured yet)
// ════════════════════════════════════════════════════════════════
$sendTo = $user['email']; // Always use email until SMS is configured

$sent = sendOtpEmail($sendTo, $otp);
if (!$sent) {
    respond('error', 'Account verified but failed to send OTP. Please try again.');
}

// ════════════════════════════════════════════════════════════════
//  STEP 7 — Store partial session (not fully logged in yet)
// ════════════════════════════════════════════════════════════════
session_regenerate_id(true);
$_SESSION['login_user_id']   = $userId;
$_SESSION['login_contact']   = $sendTo;
$_SESSION['login_remember']  = $remember;
$_SESSION['login_otp_sent']  = time();

respond('success', 'OTP sent successfully.', ['sent_to' => maskContact($sendTo)]);


// ════════════════════════════════════════════════════════════════
//  sendOtp() — routes to email or SMS based on contact format
// ════════════════════════════════════════════════════════════════
function sendOtp(string $contact, string $otp): bool {
    $isEmail = filter_var($contact, FILTER_VALIDATE_EMAIL);
    return $isEmail ? sendOtpEmail($contact, $otp) : sendOtpSms($contact, $otp);
}

function sendOtpEmail(string $email, string $otp): bool {
    require_once __DIR__ . '/send_email.php';
    return send_otp_email($email, $otp);
}

function sendOtpSms(string $phone, string $otp): bool {
    require_once __DIR__ . '/send_sms.php';
    return send_otp_sms($phone, $otp);
}