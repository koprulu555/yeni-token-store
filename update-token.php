<?php
// update-token.php
function updateToken() {
    $apiUrl = "https://core-api.kablowebtv.com/api/channels";
    $headers = [
        "User-Agent: Mozilla/5.0",
        "Referer: https://tvheryerde.com",
        "Origin: https://tvheryerde.com",
        "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJlbnYiOiJMSVZFIiwiaXBiIjoiMCIsImNnZCI6IjA5M2Q3MjBhLTUwMmMtNDFlZC1hODBmLTJiODE2OTg0ZmI5NSIsImNzaCI6IlRSS1NUIiwiZGN0IjoiRTFDNjQiLCJkaSI6Ijg5MTlmNjYwLTBhZGUtNGYwMS1hMTVlLTc2MDZjNjI4ZTc5MyIsInNnZCI6IjM5MTY0ZjIwLTZlZjUtNDRlZS04ZjAyLWEzODRjOTg1ZTY5MyIsInNwZ2QiOiI5ZjJlYWE1NC01NDM2LTQ0ZTgtYTkyNy00MzQ2NjlkMTU1MWEiLCJpY2giOiIwIiwiaWRtIjoiMCIsImlhIjoiOjpmZmZmOjEwLjAuMC41IiwiYXB2IjoiMS4wLjAiLCJhYm4iOiIxMDAwIiwibmJmIjoxNzQzNDY1MzY5LCJleHAiOjE3NDM0NjU0MjksImlhdCI6MTc0MzNDY1MzY5OX0.YWdVfOL5hEZTrd4f4qkmPCPmUUlaiG7I2REW5H0p6Gw"
    ];

    // API'ye istek yap
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        throw new Exception("API isteği başarısız: HTTP $httpCode");
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['IsSucceeded']) || !$data['IsSucceeded'] || !isset($data['Data']['AllChannels'])) {
        throw new Exception("Geçersiz API yanıtı");
    }

    // ATV kanalını bul
    $atvChannel = null;
    foreach ($data['Data']['AllChannels'] as $channel) {
        if (isset($channel['StreamData']['HlsStreamUrl']) && 
            strpos($channel['StreamData']['HlsStreamUrl'], 'atv_stream') !== false) {
            $atvChannel = $channel;
            break;
        }
    }

    if (!$atvChannel) {
        throw new Exception("ATV kanalı bulunamadı");
    }

    $streamUrl = $atvChannel['StreamData']['HlsStreamUrl'];
    $prefix = "https://ottcdn.kablowebtv.net/live_turksat_sub3/atv_stream/index.m3u8?wmsAuthSign=";
    
    if (strpos($streamUrl, $prefix) !== 0) {
        throw new Exception("ATV URL'si beklenen formatta değil");
    }

    // Token'i al
    $token = substr($streamUrl, strlen($prefix));
    
    // Token'i decode et
    $decodedToken = base64_decode($token);
    if (!$decodedToken) {
        throw new Exception("Token decode edilemedi");
    }

    // Token parametrelerini parse et
    parse_str($decodedToken, $tokenParams);

    // Yeni zamanı ve IP'yi ayarla
    $tokenParams['server_time'] = gmdate('m/d/Y h:i:s A', time() + 3 * 3600); // Türkiye saati (UTC+3)
    $tokenParams['client_ip'] = '176.88.30.202'; // Sabit IP
    
    // Yeni token string'ini oluştur
    $newTokenString = http_build_query($tokenParams);
    
    // Yeni token'i base64 ile encode et
    $newToken = base64_encode($newTokenString);
    
    // Token'i dosyaya yaz
    file_put_contents('token.txt', $newToken);
    
    return $newToken;
}

try {
    $newToken = updateToken();
    echo "Token başarıyla güncellendi: " . $newToken;
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
    exit(1);
}
?>
