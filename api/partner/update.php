<?php
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

$host = "localhost";
$username = "root";
$password = "";
$database = "shopee";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Connection failed: " . $conn->connect_error
    ]);
    exit;
}

$id_app = $_POST["id_app"] ?? "";
$nama_app = $_POST["nama_app"] ?? "";
$partner_key = $_POST["partner_key"] ?? "";
$partner_id = $_POST["partner_id"] ?? "";
$status_app = $_POST["status_app"] ?? 0;

if (empty($id_app)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "id_app is required"
    ]);
    $conn->close();
    exit;
}

$stmt = $conn->prepare("UPDATE app SET nama_app = ?, partner_key = ?, partner_id = ?, status_app = ? WHERE id_app = ?");
$stmt->bind_param("sssii", $nama_app, $partner_key, $partner_id, $status_app, $id_app);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Partner updated successfully"
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Partner not found"
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to update partner: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
