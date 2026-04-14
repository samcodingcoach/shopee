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

// Endpoint Add Item
$apiPath = "/api/v2/product/add_item";
$timestamp = (string)time();

// Generate Signature
$baseString = $partnerId . $apiPath . $timestamp . $accessToken . $shopId;
$sign = hash_hmac('sha256', $baseString, $partnerKey);

// URL Sandbox
$baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
$finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&access_token=%s&shop_id=%s&sign=%s",
    $baseUrl, $apiPath, $partnerId, $timestamp, $accessToken, $shopId, $sign
);

// RAKIT PAYLOAD PRODUK (JSON)
$productData = [
    "original_price" => $_POST['original_price'] ?? 350000, // Harga wajib integer di Indonesia (tanpa desimal)
    "description" => $_POST['description'] ?? "Jam tangan pria elegan, anti air hingga 30 meter. Cocok untuk acara formal maupun kasual.",
    "weight" => $_POST['weight'] ?? 0.3, // Berat dalam Kilogram
    "item_name" => $_POST['item_name'] ?? "Jam Tangan Pria Anti Air Premium",
    "item_status" => $_POST['item_status'] ?? "NORMAL",
    "seller_stock" => [
        [
            "stock" => $_POST['stock'] ?? 50
        ]
    ],
    "category_id" => $_POST['category_id'] ?? 301034, // ID Kategori
    "brand" => [
        "brand_id" => 0,
        "original_brand_name" => "No Brand"
    ],
    "dimension" => [ // Wajib ada karena kurir tipe SIZE_INPUT
        "package_height" => $_POST['package_height'] ?? 10,
        "package_length" => $_POST['package_length'] ?? 15,
        "package_width" => $_POST['package_width'] ?? 10
    ],
    "logistic_info" => [
        [
            "logistic_id" => $_POST['logistic_id'] ?? 81017, // ID Sandbox J&T Express
            "enabled" => true
        ]
    ],
    "image" => [
        "image_id_list" => [
            $_POST['image_id'] ?? "sg-11134201-81z1k-mn0u7nz0w5j5eb" // ID Gambar yang Anda dapatkan di Langkah 1
        ]
    ]
];

$jsonBody = json_encode($productData);

// Eksekusi POST via cURL
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
    echo "--- STATUS PENAMBAHAN PRODUK ---\n";
    echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
}

curl_close($ch);

?>