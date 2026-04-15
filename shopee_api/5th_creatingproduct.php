<?php

require_once __DIR__ . '/../config/koneksi.php';

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
    "original_price" => isset($_POST['original_price']) ? (int)$_POST['original_price'] : 350000,
    "description" => $_POST['description'] ?? "Jam tangan pria elegan, anti air hingga 30 meter. Cocok untuk acara formal maupun kasual.",
    "weight" => isset($_POST['weight']) ? (float)$_POST['weight'] : 0.3,
    "item_name" => $_POST['item_name'] ?? "Jam Tangan Pria Anti Air Premium",
    "item_status" => $_POST['item_status'] ?? "NORMAL",
    "item_sku" => $_POST['item_sku'] ?? "",
    "condition" => $_POST['condition'] ?? "NEW",
    "seller_stock" => [
        [
            "stock" => isset($_POST['stock']) ? (int)$_POST['stock'] : 50
        ]
    ],
    "category_id" => isset($_POST['category_id']) ? (int)$_POST['category_id'] : 301034,
    "brand" => [
        "brand_id" => 0,
        "original_brand_name" => "No Brand"
    ],
    "dimension" => [
        "package_height" => isset($_POST['package_height']) ? (int)$_POST['package_height'] : 10,
        "package_length" => isset($_POST['package_length']) ? (int)$_POST['package_length'] : 15,
        "package_width" => isset($_POST['package_width']) ? (int)$_POST['package_width'] : 10
    ],
    "logistic_info" => [
        [
            "logistic_id" => isset($_POST['logistic_id']) ? (int)$_POST['logistic_id'] : 81017,
            "enabled" => true
        ]
    ],
    "image" => [
        "image_id_list" => [
            !empty($_POST['image_id']) ? $_POST['image_id'] : ""
        ]
    ]
];

// Add wholesale only if valid (50%-99% of original price)
$original_price = isset($_POST['original_price']) ? (int)$_POST['original_price'] : 0;
$wholesale_price = isset($_POST['wholesale_price']) ? (int)$_POST['wholesale_price'] : 0;

if ($wholesale_price > 0 && $original_price > 0) {
    $ratio = ($wholesale_price / $original_price) * 100;
    if ($ratio >= 50 && $ratio <= 99) {
        $productData["wholesale"] = [
            [
                "min_count" => isset($_POST['wholesale_min']) ? (int)$_POST['wholesale_min'] : 10,
                "max_count" => isset($_POST['wholesale_max']) ? (int)$_POST['wholesale_max'] : 10,
                "unit_price" => $wholesale_price
            ]
        ];
    }
}

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
    echo json_encode([
        "success" => false,
        "message" => "cURL Error: " . curl_error($ch)
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
}

curl_close($ch);

?>