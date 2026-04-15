<?php
// Tahan output agar tidak ada spasi bocor yang merusak format JSON
ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/koneksi.php';

$id_app = $_GET['id_app'] ?? null;

if (!$id_app) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Parameter id_app is required"]);
    exit;
}

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
    ob_clean();
    echo json_encode(["success" => false, "message" => "App not found with id_app: " . $id_app]);
    exit;
}

$partnerId = $row['partner_id'];
$partnerKey = $row['partner_key'];
$shopId = $row['shop_id'];
$accessToken = $row['access_token'];
$stmt->close();

if (!$accessToken) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "No access token found for this app. Please authorize first."]);
    exit;
}

$apiPath = "/api/v2/product/add_item";
$timestamp = (string)time();
$baseString = $partnerId . $apiPath . $timestamp . $accessToken . $shopId;
$sign = hash_hmac('sha256', $baseString, $partnerKey);

$baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
$finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&access_token=%s&shop_id=%s&sign=%s",
    $baseUrl, $apiPath, $partnerId, $timestamp, $accessToken, $shopId, $sign
);

// --- RAKIT ATRIBUT DINAMIS ---
$attribute_list = [];
if (isset($_POST['attributes']) && is_array($_POST['attributes'])) {
    foreach ($_POST['attributes'] as $attr_id => $attr_raw) {
        if (trim($attr_raw) === '') {
            continue; // Abaikan atribut kosong
        }

        $val_id = 0;
        $val_name = $attr_raw;

        if (strpos($attr_raw, '|') !== false) {
            $parts = explode('|', $attr_raw, 2);
            $val_id = (int)$parts[0];
            $val_name = $parts[1];
        }

        $attr_entry = [
            "attribute_id" => (int)$attr_id,
            "attribute_value_list" => [
                ["value_id" => $val_id]
            ]
        ];

        if ($val_id === 0) {
            $attr_entry["attribute_value_list"][0]["original_value_name"] = $val_name;
        }

        $attribute_list[] = $attr_entry;
    }
}

// --- RAKIT PAYLOAD PRODUK ---
$productData = [
    "original_price" => isset($_POST['original_price']) ? (int)$_POST['original_price'] : 350000,
    "description" => $_POST['description'] ?? "Deskripsi produk.",
    "weight" => isset($_POST['weight']) ? (float)$_POST['weight'] : 0.3,
    "item_name" => $_POST['item_name'] ?? "Produk Baru",
    "item_status" => $_POST['item_status'] ?? "NORMAL",
    "item_sku" => $_POST['item_sku'] ?? "",
    "condition" => $_POST['condition'] ?? "NEW",
    "seller_stock" => [
        ["stock" => isset($_POST['stock']) ? (int)$_POST['stock'] : 50]
    ],
    "category_id" => isset($_POST['category_id']) ? (int)$_POST['category_id'] : 301034,
    "attribute_list" => $attribute_list,
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

// Eksekusi POST
$ch = curl_init($finalUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);

ob_clean(); // Bersihkan sebelum output akhir

if(curl_errno($ch)){
    echo json_encode(["success" => false, "message" => "cURL Error: " . curl_error($ch)]);
} else {
    // Hilangkan karakter kontrol cacat Sandbox jika ada
    $clean_response = preg_replace('/[\x00-\x1F]/', ' ', $response);
    echo json_encode(json_decode($clean_response));
}

curl_close($ch);
?>