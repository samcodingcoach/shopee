<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$id_app = $input['id_app'] ?? '';
$access_token = $input['access_token'] ?? '';
$refresh_token = $input['refresh_token'] ?? '';

if (empty($id_app) || empty($access_token) || empty($refresh_token)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "id_app, access_token, and refresh_token are required"
    ]);
    exit;
}

try {
    $query = "INSERT INTO token (id_app, access_token, refresh_token, created_date) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("iss", $id_app, $access_token, $refresh_token);
    
    if ($stmt->execute()) {
        $insert_id = $stmt->insert_id;
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Token saved successfully",
            "data" => [
                "id_token" => (int) $insert_id,
                "id_app" => (int) $id_app,
                "access_token" => $access_token,
                "refresh_token" => $refresh_token,
                "created_date" => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
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
