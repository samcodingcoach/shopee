<?php

// 1. Kredensial TEST Anda
$partnerId = 1231140;
$partnerKey = trim("shpk4b64734f78634b7849537747794f4855686168577143656d4d5063694146");

// 2. Data dari URL Callback (GANTI DENGAN DATA ASLI ANDA)
$code = 446451424179424e595a4d496e755745; 
$shopId = 226985445; // Pastikan ini angka (integer), JANGAN pakai tanda kutip jika di-hardcode

// 3. Endpoint Token
$apiPath = "/api/v2/auth/token/get";
$timestamp = (string)time();

// 4. Generate Signature
// PERHATIAN: Rumus signature untuk endpoint ini HANYA partner_id + api_path + timestamp
$baseString = $partnerId . $apiPath . $timestamp;
$sign = hash_hmac('sha256', $baseString, $partnerKey);

// 5. URL Sandbox
$baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
$finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s", 
    $baseUrl, $apiPath, $partnerId, $timestamp, $sign
);

// 6. Siapkan Body JSON
$bodyData = [
    "code" => $code,
    "shop_id" => (int)$shopId, // Tipe data wajib integer
    "partner_id" => (int)$partnerId
];
$jsonBody = json_encode($bodyData);

// 7. Eksekusi Request menggunakan cURL
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
    echo "--- RESPONSE DARI SHOPEE ---\n";
    echo $response . "\n";
}

curl_close($ch);

?>