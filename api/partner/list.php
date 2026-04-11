<?php
header("Content-Type: application/json");

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

$sql = "SELECT nama_app, partner_key, partner_id, status_app FROM app";
$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Query failed: " . $conn->error
    ]);
    $conn->close();
    exit;
}

$partners = [];
while ($row = $result->fetch_assoc()) {
    $row["status_app"] = (int)$row["status_app"];
    $partners[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $partners
]);

$conn->close();
?>
