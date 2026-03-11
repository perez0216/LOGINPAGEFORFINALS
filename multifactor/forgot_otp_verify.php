<?php
session_start();
header('Content-Type: application/json');

require '../database/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$otp = trim($_POST['otp'] ?? '');

if (!$otp || strlen($otp) !== 6 || !ctype_digit($otp)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid OTP format.']);
    exit;
}

if (!isset($_SESSION['login_user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please sign in again.']);
    exit;
}

$user_id = $_SESSION['login_user_id'];

$stmt = $conn->prepare("
    SELECT otp_code, otp_expires_at, otp_verified 
    FROM login_mfa 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$mfa = $result->fetch_assoc();
$stmt->close();

if (!$mfa) {
    echo json_encode(['status' => 'error', 'message' => 'No OTP found. Please sign in again.']);
    exit;
}

if ($mfa['otp_verified']) {
    echo json_encode(['status' => 'error', 'message' => 'OTP already used. Please sign in again.']);
    exit;
}

if (new DateTime() > new DateTime($mfa['otp_expires_at'])) {
    echo json_encode(['status' => 'error', 'message' => 'OTP has expired. Please request a new one.']);
    exit;
}

if ($otp !== $mfa['otp_code']) {
    echo json_encode(['status' => 'error', 'message' => 'Incorrect code. Please try again.']);
    exit;
}

// Mark OTP as verified
$stmt = $conn->prepare("UPDATE login_mfa SET otp_verified = 1 WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// Handle remember me cookie
if (isset($_SESSION['login_remember']) && $_SESSION['login_remember']) {
    $token = bin2hex(random_bytes(32));
    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    // TODO: store $token in DB against $user_id for validation on return visit
}

// Clear login session vars and set real session
$contact  = $_SESSION['login_contact']  ?? '';
$remember = $_SESSION['login_remember'] ?? false;

unset(
    $_SESSION['login_user_id'],
    $_SESSION['login_contact'],
    $_SESSION['login_remember'],
    $_SESSION['login_otp_sent']
);

session_regenerate_id(true);

$_SESSION['user_id'] = $user_id;

echo json_encode(['status' => 'success', 'message' => 'Verified successfully.']);
exit;