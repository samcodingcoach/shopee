<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

// Fetch list of apps for dropdown
$query = "SELECT id_app, nama_app FROM app ORDER BY nama_app";
$result = $conn->query($query);
$apps = [];
while ($row = $result->fetch_assoc()) {
    $apps[] = $row;
}

$image_id = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    $id_app = $_POST['id_app'] ?? null;
    
    if (!$id_app) {
        $error_message = "Please select an app";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error_message = "Please select an image to upload";
    } else {
        $image_file = $_FILES['image'];
        
        // Validate file size (max 1MB)
        if ($image_file['size'] > 1048576) {
            $error_message = "File size exceeds 1MB limit";
        } else {
            // Validate file type (JPG only)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $image_file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, ['image/jpeg', 'image/jpg'])) {
                $error_message = "Only JPG files are allowed";
            } else {
                // Fetch app credentials from database
                $query = "SELECT partner_id, partner_key, shop_id FROM app WHERE id_app = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id_app);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if (!$row = $result->fetch_assoc()) {
                    $error_message = "App not found";
                } else {
                    $partnerId = $row['partner_id'];
                    $partnerKey = $row['partner_key'];
                    
                    // Generate signature
                    $apiPath = "/api/v2/media_space/upload_image";
                    $timestamp = (string)time();
                    $baseString = $partnerId . $apiPath . $timestamp;
                    $sign = hash_hmac('sha256', $baseString, $partnerKey);
                    
                    // Build API URL
                    $baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
                    $finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s",
                        $baseUrl, $apiPath, $partnerId, $timestamp, $sign
                    );
                    
                    // Call Shopee API
                    $cFile = new CURLFILE($image_file['tmp_name'], 'image/jpeg', $image_file['name']);
                    $postData = array('image' => $cFile);
                    
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $finalUrl,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => $postData,
                    ));
                    
                    $response = curl_exec($curl);
                    $curl_error = curl_error($curl);
                    curl_close($curl);
                    
                    if ($curl_error) {
                        $error_message = "cURL Error: " . $curl_error;
                    } else {
                        $response_data = json_decode($response, true);
                        
                        if (isset($response_data['response']['image_info']['image_id'])) {
                            $image_id = $response_data['response']['image_info']['image_id'];
                        } else {
                            $error_message = "Failed to upload image. Response: " . $response;
                        }
                    }
                }
                
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shopee New Product</title>
</head>
<body>
    <h2>Upload Image to Shopee</h2>
    
    <?php if ($error_message): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <p>
            <label>Select App:</label><br>
            <select name="id_app" required>
                <option value="">-- Select App --</option>
                <?php foreach ($apps as $app): ?>
                    <option value="<?php echo $app['id_app']; ?>" <?php echo (isset($_POST['id_app']) && $_POST['id_app'] == $app['id_app']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($app['nama_app']); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <p>
            <label>Image (JPG, max 1MB):</label><br>
            <input type="file" name="image" accept=".jpg,.jpeg" required>
        </p>
        
        <p>
            <button type="submit" name="upload_image">Upload Image</button>
        </p>
    </form>
    
    <?php if ($image_id): ?>
        <h3>Upload Successful!</h3>
        <p>
            <label>Image ID:</label><br>
            <input type="text" value="<?php echo htmlspecialchars($image_id); ?>" readonly style="width: 400px; font-family: monospace;">
        </p>
    <?php endif; ?>
</body>
</html>
