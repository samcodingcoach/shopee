<?php

// 1. Fetch data dari API listwithtoken
$apiUrl = "http://" . $_SERVER['HTTP_HOST'] . "/shopee/api/app/listwithtoken.php";
$apiResponse = @file_get_contents($apiUrl);

if ($apiResponse === false) {
    die("Error: Unable to fetch app list");
}

$apiData = json_decode($apiResponse, true);

if (!isset($apiData['success']) || !$apiData['success'] || empty($apiData['data'])) {
    die("Error: No apps with tokens found");
}

$appList = $apiData['data'];

// 2. Gunakan app pertama sebagai default (bisa diubah sesuai kebutuhan)
$app = $appList[0];

$partnerId = $app['partner_id'];
$partnerKey = $app['partner_key'];
$shopId = $app['shop_id'];
$refreshToken = $app['refresh_token'];
$appName = $app['nama_app'];

// 3. Endpoint Refresh Token
$apiPath = "/api/v2/auth/access_token/get";
$timestamp = (string)time();

// 4. Generate Signature
$baseString = $partnerId . $apiPath . $timestamp;
$sign = hash_hmac('sha256', $baseString, $partnerKey);

// 5. Rakit URL Sandbox
$baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
$finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s",
    $baseUrl, $apiPath, $partnerId, $timestamp, $sign
);

// 6. Siapkan Body JSON
$bodyData = [
    "refresh_token" => $refreshToken,
    "shop_id" => (int)$shopId,
    "partner_id" => (int)$partnerId
];
$jsonBody = json_encode($bodyData);

// 7. Eksekusi Request dengan cURL
$ch = curl_init($finalUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);

if(curl_errno($ch)){
    echo 'Error: ' . curl_error($ch);
} else {
    $responseData = json_decode($response, true);
    
    echo "--- TOKEN BARU UNTUK: " . strtoupper($appName) . " ---\n";
    echo "Access Token: " . ($responseData['access_token'] ?? 'N/A') . "\n";
    echo "Refresh Token: " . ($responseData['refresh_token'] ?? 'N/A') . "\n";
    echo "Expire In: " . ($responseData['expire_in'] ?? 'N/A') . " seconds\n\n";
    
    // Update token ke database jika berhasil
    if (isset($responseData['success']) || isset($responseData['access_token'])) {
        $updateUrl = "http://" . $_SERVER['HTTP_HOST'] . "/shopee/api/token/update.php";
        $updateData = json_encode([
            "id_app" => $app['id_app'],
            "access_token" => $responseData['access_token'] ?? '',
            "refresh_token" => $responseData['refresh_token'] ?? ''
        ]);
        
        $ch2 = curl_init($updateUrl);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $updateData);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $updateResponse = curl_exec($ch2);
        curl_close($ch2);
        
        if ($updateResponse) {
            echo "Token updated in database successfully!\n";
        }
    }
}

curl_close($ch);

?>
