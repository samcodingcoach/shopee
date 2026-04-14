<?php
// 1. MULAI BUFFERING: Tahan semua output (termasuk spasi liar dari file lain)
ob_start();

// 2. SET HEADER: Wajib agar halaman yang consume tahu ini murni JSON
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/koneksi.php';

// Get id_app from GET parameter
$id_app = $_GET['id_app'] ?? null;

if (!$id_app) {
    ob_clean(); // Bersihkan layar
    echo json_encode(["success" => false, "message" => "Parameter id_app is required"]);
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
    echo json_encode(["success" => false, "message" => "No access token found. Please authorize first."]);
    exit;
}

// Endpoint Get Channel List
$apiPath = "/api/v2/logistics/get_channel_list";
$timestamp = (string)time();

// Generate Signature
$baseString = $partnerId . $apiPath . $timestamp . $accessToken . $shopId;
$sign = hash_hmac('sha256', $baseString, $partnerKey);

// Rakit URL Sandbox
$baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
$finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&access_token=%s&shop_id=%s&sign=%s",
    $baseUrl, $apiPath, $partnerId, $timestamp, $accessToken, $shopId, $sign
);

// Eksekusi Request GET
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $finalUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

// 3. SEBELUM MENCETAK JSON, BERSIHKAN SEMUA OUTPUT YANG MUNGKIN BOCOR SEBELUMNYA
ob_clean(); 

if(curl_errno($ch)){
    echo json_encode([
        "success" => false,
        "message" => "cURL Error: " . curl_error($ch)
    ]);
} else {
    // 4. PEMBERSIHAN EKSTRIM UNTUK SHOPEE SANDBOX
    // Menghapus SEMUA karakter kontrol ASCII (0-31) yang bentuknya fisik (enter, tab, dsb)
    // yang melanggar standar JSON murni.
    $clean_response = preg_replace('/[\x00-\x1F]/', ' ', $response);
    
    $decoded = json_decode($clean_response);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Jika karena alasan ajaib JSON masih gagal dibaca
        echo json_encode([
            "success" => false,
            "message" => "Gagal memproses JSON dari Shopee: " . json_last_error_msg()
        ]);
    } else {
        // Sukses! Cetak JSON bersih.
        echo json_encode($decoded); 
        // Catatan: Saya hapus JSON_PRETTY_PRINT karena untuk di-consume mesin,
        // format rapat (minified) jauh lebih cepat dan aman.
    }
}

curl_close($ch);
?>