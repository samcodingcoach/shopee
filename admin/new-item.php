<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

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
$attributes = [];
$form_data = [];
$debug_attr_error = "";

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

        if ($image_file['size'] > 1048576) {
            $error_message = "File size exceeds 1MB limit";
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $image_file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, ['image/jpeg', 'image/jpg'])) {
                $error_message = "Only JPG files are allowed";
            } else {
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

                    $apiPath = "/api/v2/media_space/upload_image";
                    $timestamp = (string)time();
                    $baseString = $partnerId . $apiPath . $timestamp;
                    $sign = hash_hmac('sha256', $baseString, $partnerKey);

                    $baseUrl = "https://openplatform.sandbox.test-stable.shopee.sg";
                    $finalUrl = sprintf("%s%s?partner_id=%s&timestamp=%s&sign=%s",
                        $baseUrl, $apiPath, $partnerId, $timestamp, $sign
                    );

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

    if ($id_app) {
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
                // --- GET LOGISTICS ---
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
                    if (isset($response_data['response']['logistics_channel_list'])) {
                        $logistics = $response_data['response']['logistics_channel_list'];
                    } elseif (isset($response_data['response']['logistics'])) {
                        $logistics = $response_data['response']['logistics'];
                    } elseif (isset($response_data['response'])) {
                        $logistics = $response_data['response'];
                    } elseif (isset($response_data['logistics'])) {
                        $logistics = $response_data['logistics'];
                    }

                    // --- GET ATTRIBUTE TREE ---
                    $apiPathAttr = "/api/v2/product/get_attribute_tree"; 
                    $timestampAttr = (string)time();
                    $baseStringAttr = $partnerId . $apiPathAttr . $timestampAttr . $accessToken . $shopId;
                    $signAttr = hash_hmac('sha256', $baseStringAttr, $partnerKey);

                    $finalUrlAttr = sprintf("%s%s?partner_id=%s&timestamp=%s&access_token=%s&shop_id=%s&sign=%s&language=id&category_id_list=%s",
                        $baseUrl, $apiPathAttr, $partnerId, $timestampAttr, $accessToken, $shopId, $signAttr, $category_id
                    );

                    $chAttr = curl_init();
                    curl_setopt($chAttr, CURLOPT_URL, $finalUrlAttr);
                    curl_setopt($chAttr, CURLOPT_RETURNTRANSFER, true);
                    $responseAttr = curl_exec($chAttr);
                    curl_close($chAttr);

                    // DATA CADANGAN (FALLBACK) UNTUK SANDBOX YANG CACAT
                    $sandbox_fallback_data = [
                        '301942' => [ // ID Kategori Laptop
                            200388 => [ // Tipe Laptop
                                ['value_id' => 11032792, 'display_value_name' => 'Thin and Light'],
                                ['value_id' => 11032793, 'display_value_name' => 'Gaming'],
                                ['value_id' => 11032794, 'display_value_name' => '2 in 1']
                            ],
                            200370 => [ // Jenis Garansi
                                ['value_id' => 10921, 'display_value_name' => 'Garansi Resmi'],
                                ['value_id' => 10922, 'display_value_name' => 'Garansi Distributor']
                            ]
                        ]
                    ];

                    if ($responseAttr) {
                        $attr_data = json_decode($responseAttr, true);
                        if (isset($attr_data['error']) && $attr_data['error'] !== "") {
                            $debug_attr_error = $attr_data['message'];
                        } elseif (isset($attr_data['response']['list'][0]['attribute_tree'])) {
                            foreach ($attr_data['response']['list'][0]['attribute_tree'] as $attr) {
                                
                                // Ambil nama atribut (utamakan bahasa Indonesia)
                                $display_name = $attr['name'];
                                if (isset($attr['multi_lang'][0]['value'])) {
                                    $display_name = $attr['multi_lang'][0]['value'];
                                }
                                $attr['display_attribute_name'] = $display_name;
                                $attr['is_mandatory'] = $attr['mandatory'] ?? false;

                                // PROSES NILAI (DROPDOWN)
                                $processed_values = [];
                                
                                // 1. Coba ambil dari API dulu
                                if (isset($attr['attribute_value_list']) && is_array($attr['attribute_value_list'])) {
                                    foreach ($attr['attribute_value_list'] as $val) {
                                        $val_name = $val['name'];
                                        if (isset($val['multi_lang'][0]['value'])) {
                                            $val_name = $val['multi_lang'][0]['value'];
                                        }
                                        $processed_values[] = [
                                            'value_id' => $val['value_id'],
                                            'display_value_name' => $val_name
                                        ];
                                    }
                                }

                                // 2. Jika dari API kosong (cacat), coba cek data cadangan kita
                                if (empty($processed_values) && isset($sandbox_fallback_data[$category_id][$attr['attribute_id']])) {
                                    $processed_values = $sandbox_fallback_data[$category_id][$attr['attribute_id']];
                                }

                                // Simpan kembali nilai yang sudah diproses ke array utama
                                $attr['attribute_value_list'] = $processed_values;
                                $attributes[] = $attr;
                            }
                        }
                    } else {
                        $debug_attr_error = "Tidak ada respons dari API Atribut Shopee.";
                    }
                    // --------------------------------------

                    $form_data = [
                        'item_name' => '', 'original_price' => '', 'description' => '',
                        'weight' => '', 'item_status' => 'NORMAL', 'item_sku' => '',
                        'condition' => 'NEW', 'stock' => '50', 'wholesale_min' => '10',
                        'wholesale_max' => '10', 'wholesale_price' => '', 'package_height' => '10',
                        'package_length' => '15', 'package_width' => '10', 'attributes' => []
                    ];
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
        'category_id' => $_POST['category_id'] ?? 301034,
        'attributes' => $_POST['attributes'] ?? []
    ];

    if ($product_data['id_app']) {
        $ch = curl_init();
        $api_url = "http://" . $_SERVER['HTTP_HOST'] . "/shopee/shopee_api/5th_creatingproduct.php?id_app=" . $product_data['id_app'];
        
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        // Membungkus payload post dengan http_build_query
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($product_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $error_message = "cURL Error: " . $curl_error;
        } else {
            $product_result = $response;
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
            <?php endif; ?>

            <?php if (!empty($attributes) || $category_id): ?>
                <h3>Product Information</h3>
                <form method="POST">
                    <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($image_id); ?>" id="hidden_image_id">
                    <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_id); ?>">
                    <input type="hidden" name="id_app" value="<?php echo htmlspecialchars($_POST['id_app'] ?? ($id_app ?? '')); ?>">
                    <input type="hidden" name="logistic_id" id="hidden_logistic_id" value="<?php echo htmlspecialchars($selected_logistic); ?>">

                    <p>
                        <label>Item Name: *</label><br>
                        <input type="text" name="item_name" required style="width: 400px;" value="<?php echo htmlspecialchars($form_data['item_name'] ?? ''); ?>">
                    </p>

                    <p>
                        <label>Original Price: *</label><br>
                        <input type="number" name="original_price" required style="width: 400px;" placeholder="e.g. 350000" value="<?php echo htmlspecialchars($form_data['original_price'] ?? ''); ?>">
                    </p>

                    <p>
                        <label>Description: *</label><br>
                        <textarea name="description" rows="5" required style="width: 400px;"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                    </p>

                    <p>
                        <label>Weight (kg): *</label><br>
                        <input type="number" step="0.01" name="weight" required style="width: 400px;" placeholder="e.g. 0.3" value="<?php echo htmlspecialchars($form_data['weight'] ?? ''); ?>">
                    </p>

                    <p>
                        <label>Item Status:</label><br>
                        <select name="item_status" style="width: 400px;">
                            <option value="NORMAL" <?php echo ($form_data['item_status'] ?? 'NORMAL') == 'NORMAL' ? 'selected' : ''; ?>>NORMAL</option>
                            <option value="UNLIST" <?php echo ($form_data['item_status'] ?? '') == 'UNLIST' ? 'selected' : ''; ?>>UNLIST</option>
                        </select>
                    </p>

                    <hr>

                    <p>
                        <label>Item SKU:</label><br>
                        <input type="text" name="item_sku" style="width: 400px;" placeholder="e.g. JAM-PRIA-001" value="<?php echo htmlspecialchars($form_data['item_sku'] ?? ''); ?>">
                    </p>

                    <p>
                        <label>Condition:</label><br>
                        <select name="condition" style="width: 400px;">
                            <option value="NEW" <?php echo ($form_data['condition'] ?? 'NEW') == 'NEW' ? 'selected' : ''; ?>>NEW</option>
                            <option value="USED" <?php echo ($form_data['condition'] ?? '') == 'USED' ? 'selected' : ''; ?>>USED</option>
                        </select>
                    </p>

                    <p>
                        <label>Stock: *</label><br>
                        <input type="number" name="stock" required style="width: 400px;" value="<?php echo htmlspecialchars($form_data['stock'] ?? '50'); ?>">
                    </p>

                    <hr>

                    <h4>Wholesale (Optional)</h4>
                    <p style="color: gray; font-size: 12px;">Note: Wholesale price must be between 50% - 99% of original price. Leave empty to disable wholesale.</p>
                    <p>
                        <label>Wholesale Min Count:</label><br>
                        <input type="number" name="wholesale_min" style="width: 400px;" value="<?php echo htmlspecialchars($form_data['wholesale_min'] ?? '10'); ?>">
                    </p>

                    <p>
                        <label>Wholesale Max Count:</label><br>
                        <input type="number" name="wholesale_max" style="width: 400px;" value="<?php echo htmlspecialchars($form_data['wholesale_max'] ?? '10'); ?>">
                    </p>

                    <p>
                        <label>Wholesale Unit Price:</label><br>
                        <input type="number" name="wholesale_price" style="width: 400px;" placeholder="50%-99% of original price, e.g. 175000" value="<?php echo htmlspecialchars($form_data['wholesale_price'] ?? ''); ?>">
                    </p>

                    <hr>

                    <h4>Package Dimensions</h4>
                    <p>
                        <label>Package Height (cm):</label><br>
                        <input type="number" name="package_height" style="width: 400px;" value="<?php echo htmlspecialchars($form_data['package_height'] ?? '10'); ?>">
                    </p>

                    <p>
                        <label>Package Length (cm):</label><br>
                        <input type="number" name="package_length" style="width: 400px;" value="<?php echo htmlspecialchars($form_data['package_length'] ?? '15'); ?>">
                    </p>

                    <p>
                        <label>Package Width (cm):</label><br>
                        <input type="number" name="package_width" style="width: 400px;" value="<?php echo htmlspecialchars($form_data['package_width'] ?? '10'); ?>">
                    </p>

                    <?php if (!empty($debug_attr_error)): ?>
                        <p style="color: red; font-weight: bold;">Error API Atribut: <?php echo htmlspecialchars($debug_attr_error); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($attributes)): ?>
                        <hr>
                        <h4>Atribut Kategori</h4>
                        <p style="color: gray; font-size: 12px;">Isi atribut yang ditandai bintang merah (*). Kosongkan yang tidak perlu.</p>
                        
                        <?php foreach ($attributes as $attr): ?>
                            <?php 
                            $is_req = ($attr['is_mandatory'] ?? false) ? true : false; 
                            $display_name = $attr['display_attribute_name'] ?? $attr['name'] ?? 'Atribut';
                            ?>
                            <p>
                                <label><?php echo htmlspecialchars($display_name); ?>: <?php echo $is_req ? '<span style="color:red">*</span>' : ''; ?></label><br>
                                
                                <?php if (!empty($attr['attribute_value_list'])): ?>
                                    <select name="attributes[<?php echo $attr['attribute_id']; ?>]" <?php echo $is_req ? 'required' : ''; ?> style="width: 400px;">
                                        <option value="">-- Pilih --</option>
                                        <?php foreach ($attr['attribute_value_list'] as $val): ?>
                                            <?php $vName = $val['display_value_name'] ?? $val['name'] ?? 'Opsi'; ?>
                                            <option value="<?php echo $val['value_id'] . '|' . htmlspecialchars($vName); ?>"
                                                <?php echo ($form_data['attributes'][$attr['attribute_id']] ?? '') == ($val['value_id'] . '|' . $vName) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($vName); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="text" name="attributes[<?php echo $attr['attribute_id']; ?>]" <?php echo $is_req ? 'required' : ''; ?> style="width: 400px;"
                                           value="<?php echo htmlspecialchars($form_data['attributes'][$attr['attribute_id']] ?? ''); ?>"
                                           placeholder="Isi nilai kustom...">
                                <?php endif; ?>
                            </p>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <hr>

                    <p>
                        <button type="submit" name="create_product">Create Product</button>
                    </p>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($product_result): ?>
        <h3>Product Creation Result</h3>
        <textarea rows="15" cols="80" readonly style="width: 100%; font-family: monospace;"><?php echo htmlspecialchars($product_result); ?></textarea>
    <?php endif; ?>

    <script>
        document.querySelectorAll('input[name="logistic_radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('logistic_id_field').value = this.value;
                const hiddenLogistic = document.getElementById('hidden_logistic_id');
                if (hiddenLogistic) {
                    hiddenLogistic.value = this.value;
                }
            });
        });
        const checkedRadio = document.querySelector('input[name="logistic_radio"]:checked');
        if (checkedRadio) {
            const hiddenLogistic = document.getElementById('hidden_logistic_id');
            if (hiddenLogistic) {
                hiddenLogistic.value = checkedRadio.value;
            }
        }
        const imageIdDisplay = document.getElementById('image_id_display');
        const hiddenImage = document.getElementById('hidden_image_id');
        if (imageIdDisplay && hiddenImage) {
            hiddenImage.value = imageIdDisplay.value;
        }
    </script>
</body>
</html>