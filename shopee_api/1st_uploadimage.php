<?php

require_once __DIR__ . '/../config/koneksi.php';

// Get id_app from POST or GET parameter
$id_app = $_POST['id_app'] ?? $_GET['id_app'] ?? null;

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

// Check if image was uploaded via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    // Validate file size (max 1MB)
    if ($_FILES['image']['size'] > 1048576) {
        echo json_encode([
            "success" => false,
            "message" => "File size exceeds 1MB limit"
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Validate file type (JPG only)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $_FILES['image']['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, ['image/jpeg', 'image/jpg'])) {
        echo json_encode([
            "success" => false,
            "message" => "Only JPG files are allowed"
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    $imagePath = $_FILES['image']['tmp_name'];
    $cFile = new CURLFILE($imagePath, 'image/jpeg', $_FILES['image']['name']);
} else {
    // Fallback for direct script access (use default image)
    $imagePath = __DIR__ . '/../images/JamTangan1.jpg';
    if (!file_exists($imagePath)) {
        echo json_encode([
            "success" => false,
            "message" => "No image uploaded or default image not found"
        ], JSON_PRETTY_PRINT);
        exit;
    }
    $cFile = new CURLFILE($imagePath);
}

// 2. Endpoint & Waktu Dinamis
$apiPath = "/api/v2/media_space/upload_image";
$timestamp = (string)time(); // Akan selalu mengikuti waktu server saat skrip dijalankan

// 3. Generate Signature (Rumus Pendek)
$baseString = $partnerId . $apiPath . $timestamp;
$sign = hash_hmac('sha256', $baseString, $partnerKey);

// 4. Rakit URL Final
$baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
$finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s",
    $baseUrl, $apiPath, $partnerId, $timestamp, $sign
);

// 5. Siapkan POST Data
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