<?php

// require_once __DIR__ . '/../config/koneksi.php';

// Get id_app from GET parameter
$id_app = $_GET['id_app'] ?? null;

if (!$id_app) {
    echo json_encode([
        "success" => false,
        "message" => "Parameter id_app is required"
    ]);
    exit;
}

// Fetch app credentials from database
$query = "SELECT partner_id, partner_key, shop_id FROM app WHERE id_app = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_app);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    echo json_encode([
        "success" => false,
        "message" => "App not found with id_app: " . $id_app
    ]);
    exit;
}

$partnerId = $row['partner_id'];
$partnerKey = $row['partner_key'];
$shopId = $row['shop_id'];

$stmt->close();

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
    echo json_encode([
        "success" => false,
        "message" => "cURL Error: " . $err
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
}

?>