<?php 
require_once 'header.php'; 
$uploads_enabled = app_uploads_enabled();

if (!isset($_GET['id'])) {
    header("Location: rooms.php");
    exit();
}

$id = $_GET['id'];
$msg = "";

// 1. XỬ LÝ XÓA ẢNH PHỤ (Gallery)
if (isset($_GET['delete_img_id'])) {
    $img_id = $_GET['delete_img_id'];
    $conn->query("DELETE FROM room_images WHERE id = $img_id");
    header("Location: room_edit.php?id=$id&msg=deleted"); 
    exit();
}

// 2. XỬ LÝ LƯU CẬP NHẬT (Update Main Room Info, Gallery, and Amenities)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $price = $_POST['price'];
    $address = $_POST['address'];
    $desc = $_POST['description'];
    $status = $_POST['status'];
    $capacity = $_POST['capacity']; // MỚI: Lấy sức chứa
    
    // Lấy giá trị checkbox Tiện ích (Checked = 1, Unchecked = 0)
    $has_pool = isset($_POST['has_pool']) ? 1 : 0;
    $has_breakfast = isset($_POST['has_breakfast']) ? 1 : 0;
    $has_parking = isset($_POST['has_parking']) ? 1 : 0;
    
    $image_query = ""; 
    
    // Xử lý upload ảnh đại diện mới (nếu có)
    if ($uploads_enabled && !empty($_FILES["main_image"]["name"])) {
        if (cloudinary_is_configured()) {
            $filename = cloudinary_upload($_FILES["main_image"]["tmp_name"], 'hotel_booking/rooms');
            if ($filename) {
                $image_query = ", image = '$filename'";
            }
        } else {
            $target_dir = "../uploads/";
            $filename = time() . "_" . basename($_FILES["main_image"]["name"]); 
            $target_file = $target_dir . $filename;
            
            if (move_uploaded_file($_FILES["main_image"]["tmp_name"], $target_file)) {
                $image_query = ", image = '$filename'";
            }
        }
    }

    // New gallery images upload logic (Multi-upload)
    if ($uploads_enabled && !empty($_FILES["gallery"]["name"][0])) {
        $count = count($_FILES["gallery"]["name"]);
        $insert_values = [];
        for($i=0; $i<$count; $i++) {
            $gal_filename = null;
            if (cloudinary_is_configured()) {
                $gal_filename = cloudinary_upload($_FILES["gallery"]["tmp_name"][$i], 'hotel_booking/rooms/gallery');
            } else {
                $target_dir = "../uploads/";
                $gal_filename = time() . "_" . $i . "_" . basename($_FILES["gallery"]["name"][$i]);
                $target_gal_file = $target_dir . $gal_filename;
                
                if (!move_uploaded_file($_FILES["gallery"]["tmp_name"][$i], $target_gal_file)) {
                    $gal_filename = null;
                }
            }

            if ($gal_filename) {
                $insert_values[] = "('$id', '$gal_filename')";
            }
        }
        
        if (!empty($insert_values)) {
            $insert_sql = "INSERT INTO room_images (room_id, image_path) VALUES " . implode(', ', $insert_values);
            $conn->query($insert_sql);
        }
    }


    // Câu lệnh Update thông tin chính (bao gồm Capacity và Amenities)
    $sql = "UPDATE rooms SET 
            room_name = '$name', 
            room_type = '$type', 
            capacity = '$capacity', 
            price = '$price', 
            address = '$address', 
            description = '$desc', 
            status = '$status',
            has_pool = '$has_pool',
            has_breakfast = '$has_breakfast',
            has_parking = '$has_parking'
            $image_query 
            WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Cập nhật phòng thành công!'); window.location.href='rooms.php';</script>";
    } else {
        $msg = "Lỗi: " . $conn->error;
    }
}


// 3. LẤY DỮ LIỆU HIỂN THỊ
$room = $conn->query("SELECT * FROM rooms WHERE id = $id")->fetch_assoc();
$gallery_sql = "SELECT * FROM room_images WHERE room_id = $id";
$gallery_result = $conn->query($gallery_sql);
?>

<div style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); max-width: 900px; margin: 0 auto;">
    <h2 style="color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #f1c40f; padding-bottom: 10px; display:inline-block;">
        Sửa Phòng: <?php echo $room['room_name']; ?>
    </h2>
    <?php if(isset($_GET['msg']) == 'deleted') echo "<p style='color:green; background:#e8f5e9; padding:10px;'>Đã xóa ảnh gallery!</p>"; ?>
    <?php if($msg) echo "<p style='color:red;'>$msg</p>"; ?>
    <?php if(!$uploads_enabled): ?>
        <p style="background:#fff8e1; color:#8a6d00; padding:12px 15px; border-radius:6px; margin-bottom:20px;">
            Chế độ free không lưu được file upload bền vững. Bạn vẫn sửa được thông tin text, nhưng thay ảnh mới đang bị khóa.
        </p>
    <?php endif; ?>
    <?php if(cloudinary_is_configured()): ?>
        <p style="background:#e8f5e9; color:#1b5e20; padding:12px 15px; border-radius:6px; margin-bottom:20px;">
            Cloudinary đang bật: thay ảnh mới sẽ lưu lên cloud.
        </p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 15px;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Tên phòng</label>
                <input type="text" name="name" value="<?php echo $room['room_name']; ?>" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Loại phòng</label>
                <select name="type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="Standard" <?php if($room['room_type']=='Standard') echo 'selected'; ?>>Standard</option>
                    <option value="Deluxe" <?php if($room['room_type']=='Deluxe') echo 'selected'; ?>>Deluxe</option>
                    <option value="VIP" <?php if($room['room_type']=='VIP') echo 'selected'; ?>>VIP</option>
                    <option value="Resort" <?php if($room['room_type']=='Resort') echo 'selected'; ?>>Resort</option>
                    <option value="Căn hộ" <?php if($room['room_type']=='Căn hộ') echo 'selected'; ?>>Căn hộ</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Địa chỉ</label>
            <input type="text" name="address" value="<?php echo $room['address']; ?>" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 15px;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Sức chứa (Người)</label>
                <input type="number" name="capacity" value="<?php echo $room['capacity']; ?>" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Giá (VNĐ)</label>
                <input type="number" name="price" value="<?php echo $room['price']; ?>" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Trạng thái</label>
                <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="available" <?php if($room['status']=='available') echo 'selected'; ?>>Trống (Available)</option>
                    <option value="maintenance" <?php if($room['status']=='maintenance') echo 'selected'; ?>>Bảo trì (Maintenance)</option>
                </select>
            </div>
        </div>
        
        <div style="margin-bottom: 20px; border: 1px solid #f1c40f; padding: 15px; border-radius: 8px;">
            <h4 style="margin-top: 0; color: #f39c12; margin-bottom: 10px;">Tiện ích phòng</h4>
            <div style="display: flex; gap: 30px;">
                <label style="font-weight: 600;"><input type="checkbox" name="has_pool" value="1" <?php if($room['has_pool']=='1') echo 'checked'; ?>> Hồ bơi</label>
                <label style="font-weight: 600;"><input type="checkbox" name="has_breakfast" value="1" <?php if($room['has_breakfast']=='1') echo 'checked'; ?>> Bữa sáng</label>
                <label style="font-weight: 600;"><input type="checkbox" name="has_parking" value="1" <?php if($room['has_parking']=='1') echo 'checked'; ?>> Bãi đỗ xe</label>
            </div>
        </div>


        <div style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 700; color: #333;">Ảnh đại diện hiện tại</label>
            <img src="<?php echo media_url($room['image'], '../uploads/'); ?>" style="height: 80px; margin: 5px 0 10px; border-radius: 4px; border: 1px solid #eee;">
            <input type="file" name="main_image" <?php echo $uploads_enabled ? '' : 'disabled'; ?> accept="image/*" style="width: 100%;">
        </div>
        
        <div style="margin-bottom: 30px;">
            <h4 style="margin-top: 0; color: #2c3e50; margin-bottom: 10px;">Thư Viện Ảnh (Gallery)</h4>
            
            <?php if($gallery_result->num_rows > 0): ?>
                <p style="font-weight: 600; margin-bottom: 10px;">Ảnh hiện có (Bấm X để xóa):</p>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                    <?php while($img = $gallery_result->fetch_assoc()): ?>
                        <div style="position: relative; border: 1px solid #ddd; padding: 5px; border-radius: 4px; background: #fff;">
                            <img src="<?php echo media_url($img['image_path'], '../uploads/'); ?>" style="height: 60px; width: 80px; object-fit: cover;">
                            <a href="room_edit.php?id=<?php echo $id; ?>&delete_img_id=<?php echo $img['id']; ?>" 
                               onclick="return confirm('Xóa ảnh này khỏi thư viện?');" 
                               style="position: absolute; top: -10px; right: -10px; background: #c0392b; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 0.8rem; line-height: 18px; text-align: center; text-decoration: none;">
                                &times;
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="color:#7f8c8d;">Chưa có ảnh phụ nào trong thư viện.</p>
            <?php endif; ?>

            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2980b9;">Thêm ảnh mới vào Gallery</label>
            <input type="file" name="gallery[]" multiple <?php echo $uploads_enabled ? '' : 'disabled'; ?> accept="image/*" style="width: 100%; padding: 10px; border: 1px solid #2980b9; border-radius: 4px; background: #f8f8ff;">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Mô tả</label>
            <textarea name="description" rows="5" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"><?php echo $room['description']; ?></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; padding-top: 20px;">
            <button type="submit" style="background: #27ae60; color: white; border: none; padding: 10px 30px; font-weight: bold; border-radius: 4px; cursor: pointer;">
                <i class="fa fa-save"></i> Lưu Thay Đổi
            </button>
            <a href="rooms.php" style="margin-left: 15px; color: #7f8c8d; text-decoration: none; padding: 10px 0;">Hủy</a>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>
