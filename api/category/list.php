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
    // Get optional filter parameters
    $parent_category_id = $_GET['parent_category_id'] ?? null;
    $has_children = $_GET['has_children'] ?? null;
    $aktif = $_GET['aktif'] ?? null;

    $query = "SELECT 
                parent_category_id,
                category_id,
                original_category_name,
                display_category_name,
                aktif,
                has_children
              FROM category_api
              WHERE 1=1";

    $params = [];
    $types = "";

    if ($parent_category_id !== null) {
        $query .= " AND parent_category_id = ?";
        $params[] = $parent_category_id;
        $types .= "s";
    }

    if ($has_children !== null) {
        $query .= " AND has_children = ?";
        $params[] = (int)$has_children;
        $types .= "i";
    }

    if ($aktif !== null) {
        $query .= " AND aktif = ?";
        $params[] = (int)$aktif;
        $types .= "i";
    }

    $query .= " ORDER BY category_id ASC";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "parent_category_id" => $row['parent_category_id'],
            "category_id" => $row['category_id'],
            "original_category_name" => $row['original_category_name'],
            "display_category_name" => $row['display_category_name'],
            "aktif" => (int)$row['aktif'],
            "aktif_label" => $row['aktif'] == 1 ? "Active" : "Inactive",
            "has_children" => (int)$row['has_children'],
            "has_children_label" => $row['has_children'] == 1 ? "TRUE" : "FALSE"
        ];
    }

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Categories retrieved successfully",
        "data" => $data,
        "total" => count($data)
    ]);

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

$conn->close();
?>
