<?php

// 1. Kredensial Anda
$partnerId = 1231140;
$partnerKey = "shpk4b64734f78634b7849537747794f4855686168577143656d4d5063694146";

// 2. Endpoint & Waktu Dinamis
$apiPath = "/api/v2/media_space/upload_image";
$timestamp = (string)time(); // Akan selalu mengikuti waktu server saat skrip dijalankan

// 3. Generate Signature (Rumus Pendek)
$baseString = $partnerId . $apiPath . $timestamp;
$sign = hash_hmac('sha256', $baseString, $partnerKey);

// 4. Rakit URL Final Sandbox
$baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
$finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s", 
    $baseUrl, $apiPath, $partnerId, $timestamp, $sign
);

// 5. Siapkan File Gambar menggunakan CURLFILE
// Sesuaikan absolute path ini dengan struktur direktori di server Debian Anda
// Contoh: '/var/www/html/project/assets/gambar1.jpg'
$imagePath = '../images/JamTangan1.jpg'; // Pastikan path ini benar dan file gambar ada
$cFile = new CURLFILE($imagePath);
$postData = array('image' => $cFile);

// 6. Eksekusi cURL
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $finalUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    // Header Content-Type multipart/form-data tidak perlu ditulis manual, 
    // cURL PHP otomatis mengaturnya saat melihat CURLFILE
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    echo "--- HASIL UPLOAD --- \n";
    // Trik agar JSON tampil rapi
    echo json_encode(json_decode($response), JSON_PRETTY_PRINT); 
}

?>