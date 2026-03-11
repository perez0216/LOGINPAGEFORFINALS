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

$email = trim($_POST['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond('error', 'Please enter a valid email address.');
}

$stmt = $conn->prepare("SELECT id, security_question FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || empty($user['security_question'])) {
    // Vague on purpose — don't reveal whether email exists
    respond('error', 'No account found with that email address.');
}

// Store in session for step 2
$_SESSION['recovery_user_id'] = $user['id'];
$_SESSION['recovery_email']   = $email;

respond('success', 'Question found.', ['question' => $user['security_question']]);