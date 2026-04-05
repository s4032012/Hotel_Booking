<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
$uploads_enabled = app_uploads_enabled();

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>"; exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// --- XỬ LÝ LOGIC CẬP NHẬT ---
if (isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    
    $conn->query("UPDATE users SET full_name = '$full_name', phone = '$phone' WHERE id = $user_id");
    $_SESSION['user_name'] = $full_name;

    // Xử lý upload ảnh (đã được crop)
    if ($uploads_enabled && isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $target_dir = "uploads/avatars/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $new_filename = "user_" . $user_id . "_" . time() . ".jpg";
        
        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_dir . $new_filename)) {
            $conn->query("UPDATE users SET avatar = '$new_filename' WHERE id = $user_id");
        }
    }
    if (!$uploads_enabled) {
        $message = "<div class='alert-success'>✅ Đã cập nhật thông tin. Upload avatar đang tắt ở chế độ free.</div>";
    } else {
    $message = "<div class='alert-success'>✅ Cập nhật thành công!</div>";
    }
}

// --- XỬ LÝ HỦY ĐƠN ---
if (isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'];
    $conn->query("UPDATE bookings SET status = 'cancelled' WHERE id = $booking_id AND user_id = $user_id AND status = 'pending'");
    echo "<script>alert('Đã hủy đơn!'); window.location.href='profile.php?tab=history';</script>";
}

// --- KHẮC PHỤC LỖI TRYING TO ACCESS ARRAY... ---
$result_user = $conn->query("SELECT * FROM users WHERE id = $user_id");
if ($result_user && $result_user->num_rows > 0) {
    $user = $result_user->fetch_assoc();
} else {
    // Nếu không tìm thấy người dùng (Session bị kẹt hoặc tài khoản đã xóa)
    session_unset();
    session_destroy();
    echo "<script>alert('Lỗi dữ liệu hoặc tài khoản không tồn tại. Vui lòng đăng nhập lại!'); window.location.href='login.php';</script>";
    exit();
}

$avatar_url = (!empty($user['avatar']) && file_exists("uploads/avatars/" . $user['avatar'])) ? "uploads/avatars/" . $user['avatar'] : "https://ui-avatars.com/api/?name=" . urlencode($user['full_name']) . "&background=0078d4&color=fff&size=128";

// --- XỬ LÝ LỊCH SỬ ĐẶT PHÒNG ---
$sql_bookings = "SELECT b.*, r.room_name, r.image FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE b.user_id = $user_id ORDER BY b.created_at DESC";
$bookings = $conn->query($sql_bookings);

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'info';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<style>
    .profile-container { display: grid; grid-template-columns: 250px 1fr; gap: 30px; padding: 40px 0; min-height: 600px; }
    .profile-sidebar { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); height: fit-content; }
    .user-box { padding: 30px; text-align: center; background: #f8faff; border-bottom: 1px solid #eee; }
    .user-box img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .menu-list a { display: block; padding: 15px 20px; color: #555; text-decoration: none; font-weight: 600; border-left: 4px solid transparent; border-bottom: 1px solid #f5f5f5; transition: 0.3s; }
    .menu-list a:hover, .menu-list a.active { background: #f0f7ff; color: #0071c2; border-left-color: #0071c2; }
    .tab-content { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: none; }
    .tab-content.active { display: block; }
    .alert-success { background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid #28a745; }
    
    /* CSS MODAL CROP */
    .crop-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); }
    .crop-content { position: relative; margin: 5% auto; background-color: #fff; width: 90%; max-width: 500px; border-radius: 8px; overflow: hidden; }
    .crop-body { height: 400px; background: #333; }
    .crop-body img { max-width: 100%; display: block; }
    .crop-footer { padding: 15px; text-align: right; background: #eee; }
    .btn-crop { background: #0071c2; color: white; padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .btn-close { background: #666; color: white; padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px; font-weight: bold; }
</style>

<div class="container profile-container">
    <aside class="profile-sidebar">
        <div class="user-box">
            <img id="avatarDisplay" src="<?php echo $avatar_url; ?>">
            <h3 style="margin-top: 10px; font-size: 1.1rem; color: #003580;"><?php echo $user['full_name']; ?></h3>
            <p style="color: #888; font-size: 0.9rem;">Thành viên</p>
        </div>
        <div class="menu-list">
            <a href="?tab=info" class="<?php echo ($current_tab == 'info') ? 'active' : ''; ?>"><i class="fa fa-user"></i> Hồ sơ cá nhân</a>
            <a href="?tab=history" class="<?php echo ($current_tab == 'history') ? 'active' : ''; ?>"><i class="fa fa-history"></i> Lịch sử đặt phòng</a>
            <a href="logout.php" style="color: #e34c26;"><i class="fa fa-sign-out-alt"></i> Đăng xuất</a>
        </div>
    </aside>

    <main class="profile-content">
        <div id="tab-info" class="tab-content <?php echo ($current_tab == 'info') ? 'active' : ''; ?>">
            <h2 style="margin-bottom: 20px; border-bottom: 2px solid #febb02; padding-bottom: 15px; color: #003580; display: inline-block;">Hồ sơ của tôi</h2>
            <?php echo $message; ?>
            <?php if(!$uploads_enabled): ?>
                <div class='alert-success' style="background:#fff8e1; color:#8a6d00; border-left-color:#d4a017;">
                    Ở chế độ free trên Render, avatar mới không được upload vì web service không có persistent disk.
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 25px; text-align: center;">
                    <label for="avatarInput" style="cursor: pointer; color: #0071c2; font-weight: bold; padding: 10px 20px; border: 2px dashed #0071c2; border-radius: 6px; display: inline-block; transition: 0.3s;">
                        <i class="fa fa-camera"></i> Chọn ảnh đại diện mới
                    </label>
                    <input type="file" name="avatar" id="avatarInput" style="display: none;" accept="image/*" <?php echo $uploads_enabled ? '' : 'disabled'; ?>>
                </div>

                <div class="form-group">
                    <label style="font-weight: bold; color: #333; margin-bottom: 5px; display: block;">Họ và tên</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo $user['full_name']; ?>" required style="width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="form-group">
                    <label style="font-weight: bold; color: #333; margin-bottom: 5px; display: block;">Email</label>
                    <input type="email" class="form-control" value="<?php echo $user['email']; ?>" disabled style="width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #ddd; background: #f5f5f5; border-radius: 4px;">
                </div>
                <div class="form-group">
                    <label style="font-weight: bold; color: #333; margin-bottom: 5px; display: block;">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo isset($user['phone']) ? $user['phone'] : ''; ?>" style="width: 100%; padding: 12px; margin-bottom: 25px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <button type="submit" name="update_profile" style="width: 100%; padding: 12px; border: none; border-radius: 4px; background: #0071c2; color: white; font-weight: bold; font-size: 1.1rem; cursor: pointer; transition: 0.3s;">Lưu thay đổi</button>
            </form>
        </div>

        <div id="tab-history" class="tab-content <?php echo ($current_tab == 'history') ? 'active' : ''; ?>">
            <h2 style="margin-bottom: 20px; border-bottom: 2px solid #febb02; padding-bottom: 15px; color: #003580; display: inline-block;">Lịch sử đặt phòng</h2>
            
            <?php if ($bookings && $bookings->num_rows > 0): ?>
                <?php while($row = $bookings->fetch_assoc()): ?>
                    
                    <?php 
                        $status_vn = [
                            'pending'   => 'Chờ duyệt',
                            'confirmed' => 'Đã duyệt',
                            'cancelled' => 'Đã hủy',
                            'completed' => 'Hoàn thành'
                        ];
                        $status_bg = [
                            'pending'   => '#febb02', 
                            'confirmed' => '#27ae60', 
                            'cancelled' => '#e34c26', 
                            'completed' => '#0071c2'  
                        ];
                        
                        $stt_text = isset($status_vn[$row['status']]) ? $status_vn[$row['status']] : $row['status'];
                        $stt_color = isset($status_bg[$row['status']]) ? $status_bg[$row['status']] : '#666';
                    ?>

                    <div style="display: flex; gap: 20px; border-bottom: 1px solid #eee; padding: 20px 0;">
                        <img src="uploads/<?php echo $row['image']; ?>" style="width: 120px; height: 90px; object-fit: cover; border-radius: 8px;" onerror="this.src='assets/images/default-room.jpg'">
                        <div style="flex: 1;">
                            <h4 style="color: #0071c2; margin: 0 0 8px; font-size: 1.1rem;"><?php echo $row['room_name']; ?></h4>
                            <p style="margin: 0; font-size: 0.95rem; color: #555;">
                                <i class="fa fa-calendar-alt"></i> <?php echo date("d/m/Y", strtotime($row['check_in_date'])); ?> - <?php echo date("d/m/Y", strtotime($row['check_out_date'])); ?>
                            </p>
                            <p style="margin: 8px 0 0; font-weight: bold; font-size: 1.1rem; color: #000;"><?php echo number_format($row['total_price'], 0, ',', '.'); ?> VNĐ</p>
                        </div>
                        <div style="text-align: right; display: flex; flex-direction: column; justify-content: space-between; align-items: flex-end;">
                            <span style="background: <?php echo $stt_color; ?>; color: <?php echo ($row['status'] == 'pending') ? '#000' : '#fff'; ?>; padding: 5px 12px; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">
                                <?php echo $stt_text; ?>
                            </span>
                            
                            <?php if($row['status'] == 'pending'): ?>
                                <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn đặt phòng này không?');" style="margin-top: 10px;">
                                    <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="cancel_booking" style="background: #e34c26; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: bold;">Hủy đơn</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <i class="fa fa-bed" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                    <p style="color: #666; font-size: 1.1rem;">Chưa có đơn đặt phòng nào.</p>
                    <a href="rooms.php" style="display: inline-block; margin-top: 15px; background: #0071c2; color: #fff; padding: 10px 20px; border-radius: 4px; font-weight: bold;">Đặt phòng ngay</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<div id="cropModal" class="crop-modal">
    <div class="crop-content">
        <div class="crop-body">
            <img id="imageToCrop" src="">
        </div>
        <div class="crop-footer">
            <button type="button" class="btn-close" onclick="closeModal()">Hủy</button>
            <button type="button" class="btn-crop" id="btnCrop">Cắt & Lưu</button>
        </div>
    </div>
</div>

<script>
    let cropper;
    const input = document.getElementById('avatarInput');
    const modal = document.getElementById('cropModal');
    const img = document.getElementById('imageToCrop');
    
    input.addEventListener('change', (e) => {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                img.src = e.target.result;
                modal.style.display = 'block';
                if(cropper) cropper.destroy();
                cropper = new Cropper(img, { aspectRatio: 1, viewMode: 1 });
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    function closeModal() {
        modal.style.display = 'none';
        input.value = ''; 
    }

    document.getElementById('btnCrop').addEventListener('click', () => {
        const canvas = cropper.getCroppedCanvas({ width: 300, height: 300 });
        canvas.toBlob((blob) => {
            const file = new File([blob], "avatar.jpg", { type: "image/jpeg" });
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            
            document.getElementById('avatarDisplay').src = URL.createObjectURL(blob);
            modal.style.display = 'none';
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
