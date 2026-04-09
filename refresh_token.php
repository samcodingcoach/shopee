<?php

// 1. Kredensial
$partnerId = 1231140;
$partnerKey = "shpk4b64734f78634b7849537747794f4855686168577143656d4d5063694146";

// 2. Ambil Refresh Token dan Shop ID dari Database Anda
$shopId = 226985445; // Sesuai dengan ID toko Anda sebelumnya
$refreshToken = "7947594a4a4f55425045574b6e6e6a4d"; // Masukkan refresh_token terakhir yang Anda miliki

// 3. Endpoint Refresh Token
$apiPath = "/api/v2/auth/access_token/get";
$timestamp = (string)time();

// 4. Generate Signature (Rumus Pendek)
// Rumus: partner_id + api_path + timestamp
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
    echo "--- TOKEN BARU ANDA ---\n";
    echo $response . "\n";
}

curl_close($ch);

?>