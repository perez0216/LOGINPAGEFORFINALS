<?php
ob_start();
session_start();
require '../database/connection.php';

header('Content-Type: application/json');

set_exception_handler(function(Throwable $e) {
    ob_end_clean();
    error_log('verify_otp.php exception: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Server error. Please try again.']);
    exit;
});

function respond(string $status, string $message, array $extra = []): void {
    ob_end_clean();
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

// ── Only accept POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Invalid request method.');
}

// ── Must have a pending login session ────────────────────────────────────────
if (empty($_SESSION['login_user_id'])) {
    respond('error', 'Session expired. Please log in again.');
}

$otp    = trim($_POST['otp'] ?? '');
$userId = (int) $_SESSION['login_user_id'];

if (!$otp || strlen($otp) !== 6 || !ctype_digit($otp)) {
    respond('error', 'Please enter a valid 6-digit code.');
}

// ── Fetch OTP record ──────────────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT otp_code, otp_expires_at, otp_verified
    FROM login_mfa
    WHERE user_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();

if (!$record) {
    respond('error', 'No OTP found. Please request a new code.');
}

// ── Already used ──────────────────────────────────────────────────────────────
if ((int)$record['otp_verified'] === 1) {
    respond('error', 'This code has already been used. Please request a new one.');
}

// ── Expired ───────────────────────────────────────────────────────────────────
if (strtotime($record['otp_expires_at']) < time()) {
    respond('error', 'Your code has expired. Please request a new one.');
}

// ── Wrong code ────────────────────────────────────────────────────────────────
if (!hash_equals($record['otp_code'], $otp)) {
    respond('error', 'Incorrect code. Please try again.');
}

// ── Mark OTP as verified ──────────────────────────────────────────────────────
$stmt = $conn->prepare("UPDATE login_mfa SET otp_verified = 1 WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

// ── Fully log the user in ─────────────────────────────────────────────────────
session_regenerate_id(true);
$_SESSION['user_id'] = $userId;

// Handle remember me — set a 30-day cookie
if (!empty($_SESSION['login_remember'])) {
    $token = bin2hex(random_bytes(32));
    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    // Optionally store token in DB here for validation on next visit
}

// Clean up partial session keys
unset($_SESSION['login_user_id']);
unset($_SESSION['login_contact']);
unset($_SESSION['login_remember']);
unset($_SESSION['login_otp_sent']);

respond('success', 'Verified successfully.');