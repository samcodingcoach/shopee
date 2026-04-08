<?php
$host = 'localhost';
$username = 'matos';
$password = '1234';
$database = 'shopee';

date_default_timezone_set("Asia/Makassar");

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    // Return JSON error for API calls
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header("Content-Type: application/json");
        echo json_encode([
            "success" => false,
            "message" => "Database connection failed: " . $conn->connect_error
        ]);
        exit;
    }
    die("Koneksi Gagal: " . $conn->connect_error);
}
?>