<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password']; 
    
    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if($check->num_rows > 0){
        $message = "<div style='background:#ffebee; color:#c62828; padding:10px; border-radius:5px; text-align:center; margin-bottom:15px;'>❌ Email này đã được sử dụng!</div>";
    } else {
        $sql = "INSERT INTO users (full_name, email, phone, password, role) VALUES ('$fullname', '$email', '$phone', '$password', 'customer')";
        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Đăng ký thành công! Đăng nhập ngay.'); window.location.href='login.php';</script>";
        } else {
            $message = "Lỗi: " . $conn->error;
        }
    }
}
?>

<div class="auth-body">
    
    <div class="auth-box">
        <h2 class="auth-title">Tạo Tài Khoản</h2>
        <p class="auth-subtitle">Trở thành thành viên để nhận nhiều ưu đãi</p>
        
        <?php echo $message; ?>

        <form method="POST" action="">
            <div class="search-field" style="margin-bottom: 15px;">
                <label class="search-label">Họ và tên</label>
                <input type="text" name="fullname" class="form-control-lg" required placeholder="Ví dụ: Phan Hồng Thái">
            </div>
            
            <div class="search-field" style="margin-bottom: 15px;">
                <label class="search-label">Email</label>
                <input type="email" name="email" class="form-control-lg" required placeholder="Ví dụ: thai@gmail.com">
            </div>

            <div class="search-field" style="margin-bottom: 15px;">
                <label class="search-label">Số điện thoại</label>
                <input type="text" name="phone" class="form-control-lg" required placeholder="Nhập số điện thoại...">
            </div>

            <div class="search-field" style="margin-bottom: 30px;">
                <label class="search-label">Mật khẩu</label>
                <input type="password" name="password" class="form-control-lg" required placeholder="Tự đặt mật khẩu...">
            </div>
            
            <button type="submit" class="btn-search-booking" style="border-radius: 30px;">ĐĂNG KÝ TÀI KHOẢN</button>
        </form>

        <p style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
            Đã có tài khoản? <a href="login.php" class="auth-link">Đăng nhập ngay</a>
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>