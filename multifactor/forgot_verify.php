<?php
ob_start();
session_start();
require '../database/connection.php';

header('Content-Type: application/json');

function respond(string $status, string $message, array $extra = []): void {
    ob_end_clean();
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Invalid request method.');
}

// ── Resend flow ───────────────────────────────────────────────────────────────
if (isset($_POST['resend']) && $_POST['resend'] === '1') {
    if (empty($_SESSION['recovery_user_id']) || empty($_SESSION['recovery_answer_verified'])) {
        respond('error', 'Session expired. Please start over.');
    }

    $userId = (int) $_SESSION['recovery_user_id'];
    $stmt   = $conn->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) respond('error', 'Account not found.');

    $otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', time() + 300);

    $stmt = $conn->prepare("UPDATE users SET reset_otp = ?, reset_otp_expires = ? WHERE id = ?");
    $stmt->bind_param("ssi", $otp, $expires, $userId);
    $stmt->execute();

    require_once __DIR__ . '/send_email.php';
    send_otp_email($user['email'], $otp);

    respond('success', 'New code sent.', ['sent_to' => maskEmail($user['email'])]);
}

// ── Normal verify flow ────────────────────────────────────────────────────────
if (empty($_SESSION['recovery_user_id']) || empty($_SESSION['recovery_email'])) {
    respond('error', 'Session expired. Please start over.');
}

$email  = trim($_POST['email'] ?? '');
$answer = strtolower(trim($_POST['answer'] ?? ''));

if ($email !== $_SESSION['recovery_email']) {
    respond('error', 'Session mismatch. Please start over.');
}

if (!$answer) {
    respond('error', 'Please provide your answer.');
}

$userId = (int) $_SESSION['recovery_user_id'];

$stmt = $conn->prepare("SELECT email, security_answer_hash FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    respond('error', 'Account not found. Please start over.');
}

if (!password_verify($answer, $user['security_answer_hash'])) {
    respond('error', 'Incorrect answer. Please try again.');
}

// ── Answer correct — generate OTP and send it ─────────────────────────────────
$otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date('Y-m-d H:i:s', time() + 300); // 5 minutes

$stmt = $conn->prepare("UPDATE users SET reset_otp = ?, reset_otp_expires = ? WHERE id = ?");
if (!$stmt) {
    respond('error', 'Server error. Run the required DB migration (see comment at bottom).');
}
$stmt->bind_param("ssi", $otp, $expires, $userId);
$stmt->execute();

$_SESSION['recovery_answer_verified'] = true;

require_once __DIR__ . '/send_email.php';
$sent = send_otp_email($user['email'], $otp);

if (!$sent) {
    respond('error', 'Answer verified but could not send OTP email. Please try again.');
}

respond('success', 'OTP sent.', ['sent_to' => maskEmail($user['email'])]);

// ── Helper ────────────────────────────────────────────────────────────────────
function maskEmail(string $email): string {
    [$local, $domain] = explode('@', $email);
    $masked = substr($local, 0, 2) . str_repeat('*', max(strlen($local) - 4, 2)) . substr($local, -2);
    return $masked . '@' . $domain;
}

/*
 * ─── REQUIRED MIGRATION ───────────────────────────────────────────────────────
 * Run once in phpMyAdmin if you haven't already:
 *
 * ALTER TABLE users
 *   ADD COLUMN reset_otp VARCHAR(6) NULL DEFAULT NULL,
 *   ADD COLUMN reset_otp_expires DATETIME NULL DEFAULT NULL,
 *   ADD COLUMN reset_token VARCHAR(64) NULL DEFAULT NULL,
 *   ADD COLUMN reset_token_expires DATETIME NULL DEFAULT NULL;
 * ─────────────────────────────────────────────────────────────────────────────
 */