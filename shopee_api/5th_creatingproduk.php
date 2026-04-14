<?php

// 1. Kredensial & Token
$partnerId = 1231140;
$partnerKey = "shpk4b64734f78634b7849537747794f4855686168577143656d4d5063694146";
$shopId = 226985445; 
$accessToken = "eyJhbGciOiJIUzI1NiJ9.CKSSSxABGOWLnmwgASjlpfbOBjCbiO6iDTgBQAE.HsiAGXhtTFJ-jQ9_8zCHxjCDIu-RoNgK48FZlY4-HBI"; // Pastikan token masih aktif

// 2. Endpoint Add Item
$apiPath = "/api/v2/product/add_item";
$timestamp = (string)time();

// 3. Generate Signature
$baseString = $partnerId . $apiPath . $timestamp . $accessToken . $shopId;
$sign = hash_hmac('sha256', $baseString, $partnerKey);

// 4. URL Sandbox
$baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
$finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&access_token=%s&shop_id=%s&sign=%s", 
    $baseUrl, $apiPath, $partnerId, $timestamp, $accessToken, $shopId, $sign
);

// 5. RAKIT PAYLOAD PRODUK (JSON)
$productData = [
    "original_price" => 350000, // Harga wajib integer di Indonesia (tanpa desimal)
    "description" => "Jam tangan pria elegan, anti air hingga 30 meter. Cocok untuk acara formal maupun kasual.",
    "weight" => 0.3, // Berat dalam Kilogram (0.3 kg = 300 gram)
    "item_name" => "Jam Tangan Pria Anti Air Premium",
    "item_status" => "NORMAL",
    "seller_stock" => [
        [
            "stock" => 50
        ]
    ],
    "category_id" => 301034, // ID Kategori Jam Tangan
    // --- TAMBAHKAN BLOK BRAND INI ---
    "brand" => [
        "brand_id" => 0,
        "original_brand_name" => "No Brand"
    ],
    // --------------------------------
    "dimension" => [ // Wajib ada karena kurir tipe SIZE_INPUT
        "package_height" => 10,
        "package_length" => 15,
        "package_width" => 10
    ],
    "logistic_info" => [
        [
            "logistic_id" => 81017, // ID Sandbox J&T Express
            "enabled" => true
        ]
    ],
    "image" => [
        "image_id_list" => [
            "sg-11134201-81z1k-mn0u7nz0w5j5eb" // ID Gambar yang Anda dapatkan di Langkah 1
        ]
    ]
];

$jsonBody = json_encode($productData);

// 6. Eksekusi POST via cURL
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