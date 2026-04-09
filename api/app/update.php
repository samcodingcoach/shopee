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
$code = $input['code'] ?? '';
$shop_id = $input['shop_id'] ?? '';

if (empty($id_app)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "ID App is required"
    ]);
    exit;
}

try {
    $query = "UPDATE app SET code = ?, shop_id = ? WHERE id_app = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ssi", $code, $shop_id, $id_app);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "App updated successfully",
            "data" => [
                "id_app" => (int) $id_app,
                "code" => $code,
                "shop_id" => $shop_id
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
