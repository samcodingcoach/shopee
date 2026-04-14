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
$category_id = "";
$logistics = [];
$selected_logistic = "";
$error_message = "";
$product_result = "";

// Fetch categories for dropdown
$categories_query = "SELECT category_id, display_category_name FROM category_api ORDER BY display_category_name ASC";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

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
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_category'])) {
    $image_id = $_POST['image_id'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $id_app = $_POST['id_app'] ?? '';

    // Fetch logistics from Shopee API
    if ($id_app) {
        // Fetch credentials
        $query = "SELECT a.partner_id, a.partner_key, a.shop_id, t.access_token
                  FROM app a
                  LEFT JOIN token t ON a.id_app = t.id_app
                  WHERE a.id_app = ?
                  ORDER BY t.created_date DESC
                  LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id_app);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $partnerId = $row['partner_id'];
            $partnerKey = $row['partner_key'];
            $shopId = $row['shop_id'];
            $accessToken = $row['access_token'];

            if ($accessToken) {
                // Call logistics API
                $apiPath = "/api/v2/logistics/get_channel_list";
                $timestamp = (string)time();
                $baseString = $partnerId . $apiPath . $timestamp . $accessToken . $shopId;
                $sign = hash_hmac('sha256', $baseString, $partnerKey);

                $baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
                $finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&access_token=%s&shop_id=%s&sign=%s",
                    $baseUrl, $apiPath, $partnerId, $timestamp, $accessToken, $shopId, $sign
                );

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $finalUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);
                $curl_error = curl_error($ch);
                curl_close($ch);

                if ($curl_error) {
                    $error_message = "cURL Error: " . $curl_error;
                } else {
                    $response_data = json_decode($response, true);
                    
                    // Debug: show raw response if needed
                    if (isset($_GET['debug'])) {
                        echo "<pre>Debug - API Response:\n";
                        print_r($response_data);
                        echo "</pre>";
                    }
                    
                    // Try different response structures
                    if (isset($response_data['response']['logistics_channel_list'])) {
                        $logistics = $response_data['response']['logistics_channel_list'];
                    } elseif (isset($response_data['response']['logistics'])) {
                        $logistics = $response_data['response']['logistics'];
                    } elseif (isset($response_data['response'])) {
                        // Some APIs return logistics directly under response
                        $logistics = $response_data['response'];
                    } elseif (isset($response_data['logistics'])) {
                        $logistics = $response_data['logistics'];
                    } elseif (isset($response_data['channel_list'])) {
                        $logistics = $response_data['channel_list'];
                    }

                    // If logistics is empty and debug mode, show raw response
                    if (empty($logistics) && isset($response_data)) {
                        $logistics = [];
                        // Try to find array in response
                        foreach ($response_data as $key => $value) {
                            if (is_array($value) && isset($value[0])) {
                                $logistics = $value;
                                break;
                            }
                            if (is_array($value)) {
                                foreach ($value as $sub_key => $sub_value) {
                                    if (is_array($sub_value) && isset($sub_value[0])) {
                                        $logistics = $sub_value;
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $error_message = "No access token for this app (ID: $id_app)";
            }
        } else {
            $error_message = "App not found (ID: $id_app)";
        }
        $stmt->close();
    } else {
        $error_message = "id_app not provided in POST data";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_logistic'])) {
    $image_id = $_POST['image_id'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $selected_logistic = $_POST['logistic_id'] ?? '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_product'])) {
    // Collect all product data
    $product_data = [
        'id_app' => $_POST['id_app'] ?? '',
        'item_name' => $_POST['item_name'] ?? '',
        'original_price' => $_POST['original_price'] ?? 0,
        'description' => $_POST['description'] ?? '',
        'weight' => $_POST['weight'] ?? 0.3,
        'item_status' => $_POST['item_status'] ?? 'NORMAL',
        'item_sku' => $_POST['item_sku'] ?? '',
        'condition' => $_POST['condition'] ?? 'NEW',
        'stock' => $_POST['stock'] ?? 50,
        'wholesale_min' => $_POST['wholesale_min'] ?? 10,
        'wholesale_max' => $_POST['wholesale_max'] ?? 10,
        'wholesale_price' => $_POST['wholesale_price'] ?? 0,
        'package_height' => $_POST['package_height'] ?? 10,
        'package_length' => $_POST['package_length'] ?? 15,
        'package_width' => $_POST['package_width'] ?? 10,
        'logistic_id' => $_POST['logistic_id'] ?? 81017,
        'image_id' => $_POST['image_id'] ?? '',
        'category_id' => $_POST['category_id'] ?? 301034
    ];

    // Call creating product API
    if ($product_data['id_app']) {
        $ch = curl_init();
        $api_url = "http://" . $_SERVER['HTTP_HOST'] . "/shopee/shopee_api/5th_creatingproduct.php?id_app=" . $product_data['id_app'];
        
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $product_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $error_message = "cURL Error: " . $curl_error;
        } else {
            $create_response = json_decode($response, true);
            $product_result = $response; // Store for display
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
            <input type="text" id="image_id_display" value="<?php echo htmlspecialchars($image_id); ?>" readonly style="width: 400px; font-family: monospace;">
        </p>

        <h3>Select Category</h3>
        <form method="POST">
            <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($image_id); ?>">
            <input type="hidden" name="id_app" value="<?php echo isset($_POST['id_app']) ? htmlspecialchars($_POST['id_app']) : ''; ?>">
            <p>
                <label>Category:</label><br>
                <select name="category_id" required onchange="this.form.submit()">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category_id']); ?>" <?php echo ($category_id == $cat['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['display_category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <input type="hidden" name="select_category" value="1">
        </form>

        <?php if ($category_id): ?>
            <p>
                <label>Category ID:</label><br>
                <input type="text" value="<?php echo htmlspecialchars($category_id); ?>" readonly style="width: 400px; font-family: monospace;">
            </p>

            <?php if (!empty($logistics)): ?>
                <h3>Select Logistics Courier</h3>
                <table border="1" cellpadding="8" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Logistic ID</th>
                            <th>Logistic Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logistics as $logistic):
                            $logistic_id = $logistic['logistics_channel_id'] ?? ($logistic['logistic_id'] ?? ($logistic['channel_id'] ?? 'N/A'));
                            $logistic_name = $logistic['logistics_channel_name'] ?? ($logistic['logistic_name'] ?? ($logistic['name'] ?? '-'));
                        ?>
                            <tr>
                                <td>
                                    <input type="radio" name="logistic_radio" value="<?php echo htmlspecialchars($logistic_id); ?>"
                                           onclick="document.getElementById('logistic_id_field').value = this.value;"
                                           <?php echo ($selected_logistic == $logistic_id) ? 'checked' : ''; ?>>
                                </td>
                                <td><?php echo htmlspecialchars($logistic_id); ?></td>
                                <td><?php echo htmlspecialchars($logistic_name); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <br>
                <p>
                    <label>Logistic ID:</label><br>
                    <input type="text" id="logistic_id_field" value="<?php echo htmlspecialchars($selected_logistic); ?>" readonly style="width: 400px; font-family: monospace;">
                </p>

                <script>
                    // Sync radio button selection to hidden logistic_id field in product form
                    document.querySelectorAll('input[name="logistic_radio"]').forEach(radio => {
                        radio.addEventListener('change', function() {
                            document.getElementById('logistic_id_field').value = this.value;
                            document.getElementById('hidden_logistic_id').value = this.value;
                        });
                    });
                    // Set initial value if radio is pre-checked
                    const checkedRadio = document.querySelector('input[name="logistic_radio"]:checked');
                    if (checkedRadio) {
                        document.getElementById('hidden_logistic_id').value = checkedRadio.value;
                    }
                </script>

                <h3>Product Information</h3>
                <form method="POST">
                    <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($image_id); ?>">
                    <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_id); ?>">
                    <input type="hidden" name="id_app" value="<?php echo htmlspecialchars($_POST['id_app'] ?? ''); ?>">
                    <input type="hidden" name="logistic_id" id="hidden_logistic_id" value="<?php echo htmlspecialchars($selected_logistic); ?>">

                    <p>
                        <label>Item Name: *</label><br>
                        <input type="text" name="item_name" required style="width: 400px;">
                    </p>

                    <p>
                        <label>Original Price: *</label><br>
                        <input type="number" name="original_price" required style="width: 400px;" placeholder="e.g. 350000">
                    </p>

                    <p>
                        <label>Description: *</label><br>
                        <textarea name="description" rows="5" required style="width: 400px;"></textarea>
                    </p>

                    <p>
                        <label>Weight (kg): *</label><br>
                        <input type="number" step="0.01" name="weight" required style="width: 400px;" placeholder="e.g. 0.3">
                    </p>

                    <p>
                        <label>Item Status:</label><br>
                        <select name="item_status" style="width: 400px;">
                            <option value="NORMAL">NORMAL</option>
                            <option value="UNLIST">UNLIST</option>
                        </select>
                    </p>

                    <hr>

                    <p>
                        <label>Item SKU:</label><br>
                        <input type="text" name="item_sku" style="width: 400px;" placeholder="e.g. JAM-PRIA-001">
                    </p>

                    <p>
                        <label>Condition:</label><br>
                        <select name="condition" style="width: 400px;">
                            <option value="NEW">NEW</option>
                            <option value="USED">USED</option>
                        </select>
                    </p>

                    <p>
                        <label>Stock: *</label><br>
                        <input type="number" name="stock" required style="width: 400px;" value="50">
                    </p>

                    <hr>

                    <h4>Wholesale (Optional)</h4>
                    <p>
                        <label>Wholesale Min Count:</label><br>
                        <input type="number" name="wholesale_min" style="width: 400px;" value="10">
                    </p>

                    <p>
                        <label>Wholesale Max Count:</label><br>
                        <input type="number" name="wholesale_max" style="width: 400px;" value="10">
                    </p>

                    <p>
                        <label>Wholesale Unit Price:</label><br>
                        <input type="number" name="wholesale_price" style="width: 400px;" placeholder="Must be below original price">
                    </p>

                    <hr>

                    <h4>Package Dimensions</h4>
                    <p>
                        <label>Package Height (cm):</label><br>
                        <input type="number" name="package_height" style="width: 400px;" value="10">
                    </p>

                    <p>
                        <label>Package Length (cm):</label><br>
                        <input type="number" name="package_length" style="width: 400px;" value="15">
                    </p>

                    <p>
                        <label>Package Width (cm):</label><br>
                        <input type="number" name="package_width" style="width: 400px;" value="10">
                    </p>

                    <hr>

                    <p>
                        <button type="submit" name="create_product">Create Product</button>
                    </p>
                </form>

            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_category'])): ?>
                <p style="color: orange;">
                    No logistics available.
                    <a href="?debug=1&id_app=<?php echo htmlspecialchars($_POST['id_app']); ?>">Click here for debug info</a>
                    <?php if (isset($_GET['debug'])): ?>
                        <br><br>
                        <strong>Debug Info:</strong><br>
                        id_app = <?php echo htmlspecialchars($id_app); ?><br>
                        accessToken = <?php echo $accessToken ? 'YES' : 'NO'; ?><br>
                        API Response count = <?php echo isset($response_data) ? count($response_data) : 'N/A'; ?><br>
                        <pre><?php echo isset($response_data) ? htmlspecialchars(print_r($response_data, true)) : ''; ?></pre>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($product_result): ?>
        <h3>Product Creation Result</h3>
        <textarea rows="15" cols="80" readonly style="width: 100%; font-family: monospace;"><?php echo htmlspecialchars($product_result); ?></textarea>
    <?php endif; ?>
</body>
</html>
