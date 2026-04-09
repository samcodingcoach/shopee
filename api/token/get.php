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

$id_app = $_GET['id_app'] ?? '';

if (empty($id_app)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "id_app is required"
    ]);
    exit;
}

try {
    $query = "SELECT t.id_token, t.id_app, a.nama_app, t.access_token, t.refresh_token, t.created_date 
              FROM token t 
              LEFT JOIN app a ON t.id_app = a.id_app 
              WHERE t.id_app = ? 
              ORDER BY t.created_date DESC 
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id_app);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Token retrieved successfully",
            "data" => [
                "id_token" => (int) $row['id_token'],
                "id_app" => (int) $row['id_app'],
                "nama_app" => $row['nama_app'],
                "access_token" => $row['access_token'],
                "refresh_token" => $row['refresh_token'],
                "created_date" => $row['created_date']
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "No token found for this app"
        ]);
    }
    
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

$conn->close();
