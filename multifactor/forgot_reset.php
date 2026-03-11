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

if (empty($_SESSION['recovery_user_id']) || empty($_SESSION['recovery_token'])) {
    respond('error', 'Session expired. Please start over.');
}

$token    = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';

if (!$token) {
    respond('error', 'Missing reset token.');
}

if (strlen($password) < 8) {
    respond('error', 'Password must be at least 8 characters.');
}

// Double-check token matches session
if (!hash_equals($_SESSION['recovery_token'], $token)) {
    respond('error', 'Invalid reset token. Please start over.');
}

$userId = (int) $_SESSION['recovery_user_id'];

// Verify token in DB and check expiry
$stmt = $conn->prepare("SELECT reset_token, reset_token_expires FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !hash_equals((string)$user['reset_token'], $token)) {
    respond('error', 'Invalid reset token. Please start over.');
}

if (strtotime($user['reset_token_expires']) < time()) {
    respond('error', 'Reset session has expired. Please start over.');
}

// Reject if new password is the same as the current one
$currentStmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
$currentStmt->bind_param("i", $userId);
$currentStmt->execute();
$current = $currentStmt->get_result()->fetch_assoc();

if ($current && password_verify($password, $current['password_hash'])) {
    respond('error', 'Your new password cannot be the same as your current password.');
}

// Update password and clear all reset fields
$newHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
$stmt->bind_param("si", $newHash, $userId);

if (!$stmt->execute()) {
    respond('error', 'Failed to update password. Please try again.');
}

// Clear recovery session
unset(
    $_SESSION['recovery_user_id'],
    $_SESSION['recovery_email'],
    $_SESSION['recovery_answer_verified'],
    $_SESSION['recovery_token']
);

respond('success', 'Password reset successfully.');