<?php

// 1. Kredensial & Token
$partnerId = 1231140;
$partnerKey = "shpk4b64734f78634b7849537747794f4855686168577143656d4d5063694146";
$shopId = 226985445; 
$accessToken = "eyJhbGciOiJIUzI1NiJ9.CKSSSxABGOWLnmwgASjlpfbOBjCbiO6iDTgBQAE.HsiAGXhtTFJ-jQ9_8zCHxjCDIu-RoNgK48FZlY4-HBI"; // Pastikan token masih aktif

// 2. Endpoint Get Channel List
$apiPath = "/api/v2/logistics/get_channel_list";
$timestamp = (string)time();

// 3. Generate Signature (Rumus Panjang)
$baseString = $partnerId . $apiPath . $timestamp . $accessToken . $shopId;
$sign = hash_hmac('sha256', $baseString, $partnerKey);

// 4. Rakit URL Sandbox (Tanpa parameter tambahan)
$baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
$finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&access_token=%s&shop_id=%s&sign=%s", 
    $baseUrl, $apiPath, $partnerId, $timestamp, $accessToken, $shopId, $sign
);

// 5. Eksekusi Request GET
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $finalUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if(curl_errno($ch)){
    echo 'Error: ' . curl_error($ch);
} else {
    echo "--- DAFTAR KURIR LOGISTIK SHOPEE SANDBOX ---\n";
    echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
}

curl_close($ch);

?>