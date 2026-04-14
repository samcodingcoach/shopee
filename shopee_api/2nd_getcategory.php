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

// Fetch app credentials and token from database
$query = "SELECT a.partner_id, a.partner_key, a.shop_id, t.access_token 
          FROM app a
          LEFT JOIN token t ON a.id_app = t.id_app
          WHERE a.id_app = ?
          ORDER BY t.created_date DESC
          LIMIT 1";
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
$accessToken = $row['access_token'];

if (!$accessToken) {
    echo json_encode([
        "success" => false,
        "message" => "No access token found for this app. Please authorize first."
    ]);
    exit;
}

$stmt->close();

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