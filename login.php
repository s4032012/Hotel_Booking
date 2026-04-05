<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role']; 
        echo "<script>alert('Xin chào " . $user['full_name'] . "!'); window.location.href='index.php';</script>";
    } else {
        $message = "<div style='background:#ffebee; color:#c62828; padding:10px; border-radius:5px; text-align:center; margin-bottom:15px;'>❌ Sai email hoặc mật khẩu!</div>";
    }
}
?>

<div class="auth-body">
    
    <div class="auth-box">
        <h2 class="auth-title">Đăng Nhập</h2>
        <p class="auth-subtitle">Chào mừng bạn quay trở lại với Booking.com</p>
        
        <?php echo $message; ?>

        <form method="POST" action="">
            <div class="search-field" style="margin-bottom: 20px;">
                <label class="search-label">Email</label>
                <input type="email" name="email" class="form-control-lg" required placeholder="Nhập email của bạn">
            </div>
            
            <div class="search-field" style="margin-bottom: 30px;">
                <label class="search-label">Mật khẩu</label>
                <input type="password" name="password" class="form-control-lg" required placeholder="Nhập mật khẩu">
            </div>
            
            <button type="submit" class="btn-search-booking" style="border-radius: 30px;">ĐĂNG NHẬP NGAY</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
            Chưa có tài khoản? <a href="register.php" class="auth-link">Đăng ký miễn phí</a>
        </p>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>