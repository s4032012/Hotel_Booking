<?php 
require_once 'header.php'; 
$msg = "";
$uploads_enabled = app_uploads_enabled();
$default_image = env_value('DEFAULT_ROOM_IMAGE', 'hotel-bg.jpg');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $price = $_POST['price'];
    $address = $_POST['address'];
    $desc = $_POST['description'];
    $status = $_POST['status'];
    $capacity = $_POST['capacity']; 
    
    $has_pool = isset($_POST['has_pool']) ? 1 : 0;
    $has_breakfast = isset($_POST['has_breakfast']) ? 1 : 0;
    $has_parking = isset($_POST['has_parking']) ? 1 : 0;

    // --- XỬ LÝ THỜI GIAN VÀ VỊ TRÍ NGAY LÚC BẤM NÚT LƯU ---
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $capture_time = date('Y-m-d H:i:s');
    
    $lat = isset($_POST['lat']) ? $_POST['lat'] : '';
    $lng = isset($_POST['lng']) ? $_POST['lng'] : '';
    $capture_location = ($lat && $lng) ? "$lat,$lng" : "";
    // --------------------------------------------------------
    
    // 1. Xử lý upload ảnh đại diện (Main Image)
    $main_filename = $default_image;
    $main_image_uploaded = false;

    if ($uploads_enabled && isset($_FILES["main_image"]) && $_FILES["main_image"]["error"] == 0) {
        if (cloudinary_is_configured()) {
            $uploaded = cloudinary_upload($_FILES["main_image"]["tmp_name"], 'hotel_booking/rooms');
            if ($uploaded) {
                $main_filename = $uploaded;
                $main_image_uploaded = true;
            }
        } else {
            $target_dir = "../uploads/";
            $main_filename = time() . "_" . basename($_FILES["main_image"]["name"]);
            $target_main_file = $target_dir . $main_filename;
            $main_image_uploaded = move_uploaded_file($_FILES["main_image"]["tmp_name"], $target_main_file);
        }
    } elseif (!$uploads_enabled) {
        $main_image_uploaded = true;
    }

    if ($main_image_uploaded) {
        
        // --- BẮT ĐẦU: XỬ LÝ ẢNH 360 ĐỘ TỪ CAMERA ---
        $anh_360_name = ""; 
        if ($uploads_enabled && isset($_FILES['anh_360']) && $_FILES['anh_360']['error'] == 0) {
            if (cloudinary_is_configured()) {
                $uploaded360 = cloudinary_upload($_FILES["anh_360"]["tmp_name"], 'hotel_booking/rooms/360');
                if ($uploaded360) {
                    $anh_360_name = $uploaded360;
                }
            } else {
                $target_dir = "../uploads/";
                $file_extension = pathinfo($_FILES["anh_360"]["name"], PATHINFO_EXTENSION);
                $anh_360_name = "360_" . time() . "." . $file_extension; 
                $target_360_file = $target_dir . $anh_360_name;
                move_uploaded_file($_FILES["anh_360"]["tmp_name"], $target_360_file);
            }
        }
        // --- KẾT THÚC: XỬ LÝ ẢNH 360 ĐỘ ---

        // 2. LƯU THÔNG TIN CHÍNH VÀO DB
        $sql = "INSERT INTO rooms (room_name, room_type, price, address, description, image, image_360, capture_time, capture_location, status, capacity, has_pool, has_breakfast, has_parking) 
                VALUES ('$name', '$type', '$price', '$address', '$desc', '$main_filename', '$anh_360_name', '$capture_time', '$capture_location', '$status', '$capacity', '$has_pool', '$has_breakfast', '$has_parking')";
        
        if ($conn->query($sql) === TRUE) {
            $new_room_id = $conn->insert_id;
            $success_message = "Thêm phòng thành công! ID: $new_room_id.";
            
            // 3. XỬ LÝ UPLOAD NHIỀU ẢNH PHỤ (GALLERY)
            if ($uploads_enabled && !empty($_FILES["gallery"]["name"][0])) {
                $count = count($_FILES["gallery"]["name"]);
                $insert_values = [];
                for($i=0; $i<$count; $i++) {
                    $gal_filename = null;
                    if (cloudinary_is_configured()) {
                        $gal_filename = cloudinary_upload($_FILES["gallery"]["tmp_name"][$i], 'hotel_booking/rooms/gallery');
                    } else {
                        $target_gal_dir = "../uploads/";
                        $gal_filename = time() . "_" . $i . "_" . basename($_FILES["gallery"]["name"][$i]);
                        $target_gal_file = $target_gal_dir . $gal_filename;
                        if (!move_uploaded_file($_FILES["gallery"]["tmp_name"][$i], $target_gal_file)) {
                            $gal_filename = null;
                        }
                    }

                    if ($gal_filename) {
                        $insert_values[] = "('$new_room_id', '$gal_filename')";
                    }
                }
                
                if (!empty($insert_values)) {
                    $insert_sql = "INSERT INTO room_images (room_id, image_path) VALUES " . implode(', ', $insert_values);
                    $conn->query($insert_sql);
                    $success_message .= " Đã thêm ".count($insert_values)." ảnh phụ.";
                }
            }

            if (!$uploads_enabled) {
                $success_message .= " Upload ảnh mới đang tắt ở chế độ free, nên hệ thống dùng ảnh mặc định.";
            }

            echo "<script>alert('$success_message'); window.location.href='rooms.php';</script>";
        } else {
            $msg = "Lỗi SQL: " . $conn->error;
        }
    } else {
        $msg = "Lỗi upload ảnh đại diện. Vui lòng kiểm tra lại.";
    }
}
?>

<div style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); max-width: 900px; margin: 0 auto;">
    <h2 style="color: #003580; margin-bottom: 20px; border-bottom: 2px solid #febb02; padding-bottom: 10px; display:inline-block;">
        Thêm Phòng Mới
    </h2>

    <?php if($msg) echo "<p style='color:red;'>$msg</p>"; ?>
    <form method="POST" enctype="multipart/form-data">
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 15px;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Tên khách sạn/Phòng</label>
                <input type="text" name="name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Loại phòng</label>
                <select name="type" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="Standard">Standard</option>
                    <option value="Deluxe">Deluxe</option>
                    <option value="VIP">VIP</option>
                    <option value="Resort">Resort</option>
                    <option value="Căn hộ">Căn hộ</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Địa chỉ</label>
            <input type="text" name="address" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Sức chứa tối đa (Người)</label>
                <input type="number" name="capacity" value="2" min="1" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Giá/Đêm (VNĐ)</label>
                <input type="number" name="price" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Trạng thái</label>
                <select name="status" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="available">Trống (Available)</option>
                    <option value="maintenance">Bảo trì (Maintenance)</option>
                </select>
            </div>
        </div>
        
        <div style="margin-bottom: 30px; border: 1px solid #febb02; padding: 15px; border-radius: 8px;">
            <h4 style="margin-top: 0; color: #0071c2; margin-bottom: 10px;">Tiện ích cơ bản</h4>
            <div style="display: flex; gap: 30px;">
                <label style="font-weight: 600;"><input type="checkbox" name="has_pool" value="1"> Hồ bơi</label>
                <label style="font-weight: 600;"><input type="checkbox" name="has_breakfast" value="1"> Bữa sáng</label>
                <label style="font-weight: 600;"><input type="checkbox" name="has_parking" value="1"> Bãi đỗ xe</label>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Mô tả chi tiết</label>
            <textarea name="description" rows="5" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 700; color: #003580;">1. Ảnh bìa (Chọn từ máy)</label>
                <input type="file" name="main_image" <?php echo $uploads_enabled ? 'required' : 'disabled'; ?> accept="image/*" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 700; color: #003580;">2. Ảnh tham khảo (Mở Camera)</label>
                <input type="file" name="gallery[]" multiple <?php echo $uploads_enabled ? '' : 'disabled'; ?> accept="image/*" capture="environment" style="width: 100%; padding: 10px; border: 1px solid #0071c2; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 700; color: #003580;">3. Ảnh 360° (Mở Camera)</label>
                <input type="file" name="anh_360" <?php echo $uploads_enabled ? '' : 'disabled'; ?> accept="image/*" capture="environment" style="width: 100%; padding: 10px; border: 1px solid #febb02; border-radius: 4px;">
            </div>
        </div>

        <input type="hidden" name="lat" id="lat" value="">
        <input type="hidden" name="lng" id="lng" value="">

        <script>
            // Tự động xin quyền lấy vị trí điện thoại
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('lat').value = position.coords.latitude;
                    document.getElementById('lng').value = position.coords.longitude;
                }, function(error) {
                    console.log("Lỗi hoặc người dùng không cấp quyền vị trí.");
                });
            }
        </script>

        <div style="text-align: right;">
            <button type="submit" style="background: #0071c2; color: white; border: none; padding: 12px 30px; font-weight: bold; border-radius: 4px; cursor: pointer;">
                LƯU PHÒNG MỚI
            </button>
            <a href="rooms.php" style="margin-left: 15px; color: #7f8c8d; text-decoration: none; padding: 12px 0; font-weight: 600;">Hủy</a>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>
