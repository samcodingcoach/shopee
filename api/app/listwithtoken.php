<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

try {
    $query = "SELECT 
                app.id_app, 
                app.nama_app, 
                app.partner_key, 
                app.partner_id, 
                app.status_app, 
                app.`code`, 
                app.shop_id, 
                app.created_date, 
                token.refresh_token 
              FROM app 
              INNER JOIN token 
              ON app.id_app = token.id_app 
              ORDER BY app.created_date DESC";
    
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "id_app" => (int) $row['id_app'],
            "nama_app" => $row['nama_app'],
            "partner_key" => $row['partner_key'],
            "partner_id" => $row['partner_id'],
            "status_app" => (int) $row['status_app'],
            "status_label" => $row['status_app'] == 1 ? "Live Production" : "Developing",
            "code" => $row['code'],
            "shop_id" => $row['shop_id'],
            "refresh_token" => $row['refresh_token'],
            "created_date" => $row['created_date']
        ];
    }

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Data retrieved successfully",
        "data" => $data,
        "total" => count($data)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

$conn->close();
