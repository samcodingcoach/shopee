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

$nama_app = $_POST["nama_app"] ?? "";
$partner_key = $_POST["partner_key"] ?? "";
$partner_id = $_POST["partner_id"] ?? "";
$status_app = $_POST["status_app"] ?? 0;

if (empty($nama_app) || empty($partner_key) || empty($partner_id)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "nama_app, partner_key, and partner_id are required"
    ]);
    $conn->close();
    exit;
}

$stmt = $conn->prepare("INSERT INTO app (nama_app, partner_key, partner_id, status_app) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssi", $nama_app, $partner_key, $partner_id, $status_app);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Partner created successfully"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to create partner: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
