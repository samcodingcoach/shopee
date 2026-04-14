<?php

// 1. Kredensial & Token (Ganti dengan token yang aktif)
$partnerId = 1231140;
$partnerKey = "shpk4b64734f78634b7849537747794f4855686168577143656d4d5063694146";
$shopId = 226985445; 
$accessToken = "eyJhbGciOiJIUzI1NiJ9.CKSSSxABGOWLnmwgASjlpfbOBjCbiO6iDTgBQAE.HsiAGXhtTFJ-jQ9_8zCHxjCDIu-RoNgK48FZlY4-HBI"; // Wajib token yang belum kedaluwarsa

// 2. Endpoint Get Category
$apiPath = "/api/v2/product/get_category";
$timestamp = (string)time();

// 3. Generate Signature (Rumus Panjang)
// Urutan wajib: partner_id + api_path + timestamp + access_token + shop_id
$baseString = $partnerId . $apiPath . $timestamp . $accessToken . $shopId;
$sign = hash_hmac('sha256', $baseString, $partnerKey);

// 4. Parameter Bahasa (Gunakan 'id' untuk Bahasa Indonesia)
$language = "id"; 

// 5. Rakit URL Sandbox
$baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
$finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&access_token=%s&shop_id=%s&sign=%s&language=%s", 
    $baseUrl, $apiPath, $partnerId, $timestamp, $accessToken, $shopId, $sign, $language
);

// 6. Eksekusi Request GET dengan cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $finalUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if(curl_errno($ch)){
    echo 'Error: ' . curl_error($ch);
} else {
    echo "--- DAFTAR KATEGORI SHOPEE SANDBOX ---\n";
    // Menampilkan JSON agar rapi
    echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
}

curl_close($ch);

?>