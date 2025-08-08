<?php
// ===== CONFIG =====
$telegramToken = "7965165409:AAGa1hlKEYTtaqfFLe1i9q9LOHgnilbIKwM";
$telegramChatID = "7394958970";
$logFile = __DIR__ . "/log.csv";
$geoApi = "http://ip-api.com/json/";

// ===== GET BASIC INFO =====
date_default_timezone_set("UTC");
$date = date("Y-m-d");
$time = date("H:i:s");

$ip = $_SERVER['REMOTE_ADDR'];
$userAgent = $_POST['userAgent'] ?? '';
$emails = $_POST['emails'] ?? '';

// ===== GEOLOCATION =====
$country = $city = "Unknown";
$geoData = @file_get_contents($geoApi . $ip);
if ($geoData) {
    $geoJson = json_decode($geoData, true);
    if ($geoJson && $geoJson['status'] === 'success') {
        $country = $geoJson['country'];
        $city = $geoJson['city'];
    }
}

// ===== PARSE BROWSER & OS =====
function parseUserAgent($ua) {
    $browser = "Unknown";
    $os = "Unknown";

    if (preg_match('/Windows NT 10/i', $ua)) $os = "Windows 10";
    elseif (preg_match('/Windows NT 6.3/i', $ua)) $os = "Windows 8.1";
    elseif (preg_match('/Windows NT 6.2/i', $ua)) $os = "Windows 8";
    elseif (preg_match('/Mac OS X/i', $ua)) $os = "MacOS";
    elseif (preg_match('/Linux/i', $ua)) $os = "Linux";
    elseif (preg_match('/Android/i', $ua)) $os = "Android";
    elseif (preg_match('/iPhone|iPad/i', $ua)) $os = "iOS";

    if (preg_match('/Chrome\/([0-9.]+)/i', $ua, $matches)) $browser = "Chrome " . $matches[1];
    elseif (preg_match('/Firefox\/([0-9.]+)/i', $ua, $matches)) $browser = "Firefox " . $matches[1];
    elseif (preg_match('/Safari\/([0-9.]+)/i', $ua) && !preg_match('/Chrome/i', $ua)) $browser = "Safari";
    elseif (preg_match('/Edge\/([0-9.]+)/i', $ua, $matches)) $browser = "Edge " . $matches[1];

    return [$browser, $os];
}
list($browser, $os) = parseUserAgent($userAgent);

// ===== SAVE TO CSV =====
$newLine = [$date, $time, $ip, $country, $city, $browser, $os, $emails];
$fileExists = file_exists($logFile);
$fp = fopen($logFile, 'a');
if (!$fileExists) {
    fputcsv($fp, ["Date","Time","IP","Country","City","Browser","OS","Processed Emails"]);
}
fputcsv($fp, $newLine);
fclose($fp);

// ===== SEND TELEGRAM MESSAGE =====
$message = "📩 *New Usage on email cleaner*\n";

$telegramUrl = "https://api.telegram.org/bot{$telegramToken}/sendMessage";
$params = [
    'chat_id' => $telegramChatID,
    'text' => $message,
    'parse_mode' => 'Markdown'
];
file_get_contents($telegramUrl . '?' . http_build_query($params));

// ===== RESPONSE =====
echo "  successfully.";
?>
