<?php session_start();?>
<!DOCTYPE html>
<html lang="vi">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking.com - Đặt Phòng Khách Sạn</title>
    
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php" style="text-decoration: none; display: flex; align-items: center;">
                        <img src="assets/images/logo.jpg" alt="Logo" class="site-logo" onerror="this.src='https://via.placeholder.com/50'">
                        <span class="logo-text">Booking.com</span>
                    </a>
                </div>

                <nav class="nav">
                    <a href="index.php" class="nav-link">Trang chủ</a>
                    <a href="rooms.php" class="nav-link">Phòng & Khách sạn</a>
                    <a href="#" class="nav-link">Liên hệ</a>
                </nav>
                
                <div class="auth-buttons">
    <?php if(isset($_SESSION['user_id'])): ?>
        
            <div class="user-dropdown">
                <div class="dropdown-toggle">
                   <span style="font-weight: 600; margin-right: 8px; color: #333;">
                        Chào, <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Khách'; ?>
                    </span>
                    <div class="menu-icon"><i class="fa fa-bars"></i></div>
                </div>
                <div class="dropdown-menu">
        <a href="profile.php?tab=info" class="dropdown-item">
            <i class="fa fa-user-circle"></i> Hồ sơ cá nhân
        </a>
        <a href="profile.php?tab=history" class="dropdown-item">
            <i class="fa fa-history"></i> Lịch sử đặt phòng
        </a>

        <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
        <div class="dropdown-divider"></div>
        
        <a href="admin/index.php" class="dropdown-item" style="color: #d35400; font-weight: bold;">
            <i class="fa fa-crown" style="color: #f1c40f;"></i> Trang Quản Trị
        </a>
        
    <?php endif; ?>
        <div class="dropdown-divider"></div>
        
        <a href="logout.php" class="dropdown-item logout-item">
            <i class="fa fa-sign-out-alt"></i> Đăng xuất
        </a>
    </div>
        </div>

    <?php else: ?>
        <a href="login.php" class="btn-login">Đăng nhập</a>
        <a href="register.php" class="btn-register">Đăng ký</a>
    <?php endif; ?>
</div>
    </header>
    