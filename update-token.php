<?php
// update-token.php
function updateToken() {
    $apiUrl = "https://core-api.kablowebtv.com/api/channels";
    $headers = [
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
        "Referer: https://tvheryerde.com",
        "Origin: https://tvheryerde.com",
        "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJlbnYiOiJMSVZFIiwiaXBiIjoiMCIsImNnZCI6IjA5M2Q3MjBhLTUwMmMtNDFlZC1hODBmLTJiODE2OTg0ZmI5NSIsImNzaHI6IlRSS1NUIiwiZGN0IjoiRTFDNjQiLCJkaSI6Ijg5MTlmNjYwLTBhZGUtNGYwMS1hMTVlLTc2MDZjNjI4ZTc5MyIsInNnZCI6IjM5MTY0ZjIwLTZlZjUtNDRlZS04ZjAyLWEzODRjOTg1ZTY5MyIsInNwZ2QiOiI5ZjJlYWE1NC01NDM2LTQ0ZTgtYTkyNy00MzQ2NjlkMTU1MWEiLCJpY2giOiIwIiwiaWRtIjoiMCIsImlhIjoiOjpmZmZmOjEwLjAuMC41IiwiYXB2IjoiMS4wLjAiLCJhYm4iOiIxMDAwIiwibmJmIjoxNzQzNDY1MzY5LCJleHAiOjE3NDM0NjU0MjksImlhdCI6MTc0MzQ2NTM2OX0.YWdVfOL5hEZTrd4f4qkmPCPmUUlaiG7I2REW5H0p6Gw",
        "Accept: application/json, text/plain, */*",
        "Accept-Language: tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7",
        "Connection: keep-alive"
    ];

    // API'ye istek yap
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("cURL hatası: " . $error);
    }

    if ($httpCode !== 200) {
        throw new Exception("API isteği başarısız: HTTP $httpCode");
    }

    if (!$response) {
        throw new Exception("Boş API yanıtı");
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON parse hatası: " . json_last_error_msg());
    }

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
    $originalToken = substr($streamUrl, strlen($prefix));
    
    // Orijinal token'i decode et
    $decodedOriginalToken = base64_decode($originalToken);
    
    // Orijinal token parametrelerini parse et (URL decode yapmadan)
    parse_str($decodedOriginalToken, $originalTokenParams);
    
    // Hash value'yi al (orijinal formatta tutalım)
    $hashValue = $originalTokenParams['hash_value'];
    
    // Yeni zamanı ve IP'yi ayarla (URL encoding OLMADAN)
    $serverTime = gmdate('m/d/Y h:i:s A', time() + 3 * 3600); // Türkiye saati (UTC+3)
    $clientIp = '176.88.30.202'; // Sabit IP
    
    // Yeni token string'ini MANUEL olarak oluştur (URL encoding YAPMADAN)
    $newTokenString = "server_time=" . $serverTime . 
                     "&hash_value=" . $hashValue . 
                     "&validminutes=2880" . 
                     "&id=9f2eaa54-5436-44e8-a927-434669d1551a" . 
                     "&client_ip=" . $clientIp . 
                     "&checkip=true";
    
    // Yeni token'i base64 ile encode et
    $newToken = base64_encode($newTokenString);
    
    // Debug için
    echo "Orijinal Token: " . $originalToken . "\n";
    echo "Orijinal Decoded: " . $decodedOriginalToken . "\n";
    echo "Yeni Token String: " . $newTokenString . "\n";
    echo "Yeni Token: " . $newToken . "\n";
    
    // Token'i dosyaya yaz
    if (file_put_contents('token.txt', $newToken) === false) {
        throw new Exception("Dosya yazma hatası");
    }
    
    return $newToken;
}

try {
    $newToken = updateToken();
    echo "Token başarıyla güncellendi!\n";
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
    exit(1);
}
?>
