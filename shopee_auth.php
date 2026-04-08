<?php

// 1. Kredensial TEST Anda
$partnerId = 1231140;
$partnerKey = "shpk4b64734f78634b7849537747794f4855686168577143656d4d5063694146";

// 2. Endpoint Otorisasi
$apiPath = "/api/v2/shop/auth_partner";
$timestamp = time();

// 3. Generate Signature
// Rumus wajib: partner_id + api_path + timestamp
$baseString = $partnerId . $apiPath . $timestamp;
$sign = hash_hmac('sha256', $baseString, $partnerKey);

// 4. URL FINAL (Menggunakan environment Sandbox Test Shopee Terbaru)
$baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";

// 5. Setup Redirect
// Ini alamat kembalinya setelah sukses. Pastikan di-encode.
$redirectUrl = urlencode("http://127.0.0.1/");

// 6. Rakit URL
// PERHATIKAN: Menggunakan parameter &redirect=
$finalUrl = $baseUrl . $apiPath . "?partner_id=" . $partnerId . "&timestamp=" . $timestamp . "&sign=" . $sign . "&redirect=" . $redirectUrl;

// 7. Eksekusi Redirect otomatis ke halaman Otorisasi Shopee
header("Location: " . $finalUrl);
exit();

?>