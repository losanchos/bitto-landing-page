<?php
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['sender'], $input['amount'], $input['token'], $input['timestamp'], $input['txid'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Sanitize inputs
$sender = filter_var($input['sender'], FILTER_SANITIZE_STRING);
$amount = floatval($input['amount']);
$token = filter_var($input['token'], FILTER_SANITIZE_STRING);
$timestamp = intval($input['timestamp']);
$txid = filter_var($input['txid'], FILTER_SANITIZE_STRING);

// Load existing contributions
$contributions_file = 'contributions.json';
$contributions = [];
if (file_exists($contributions_file)) {
    $contributions = json_decode(file_get_contents($contributions_file), true) ?: [];
}

// Add new contribution
$contributions[] = [
    'sender' => $sender,
    'amount' => $amount,
    'token' => $token,
    'timestamp' => $timestamp,
    'txid' => $txid
];

// Save contributions
file_put_contents($contributions_file, json_encode($contributions, JSON_PRETTY_PRINT));

http_response_code(200);
echo json_encode(['success' => true]);
?>