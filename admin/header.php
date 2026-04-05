<?php
session_start();
// Chặn không cho khách thường vào
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php"); // Đá về trang đăng nhập
    exit();
}
require_once '../includes/db.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Quản Trị - Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .admin-header { background: #2c3e50; color: #fff; padding: 15px 0; }
        .admin-nav a { color: #ecf0f1; margin-right: 20px; text-decoration: none; font-weight: 600; }
        .admin-nav a:hover { color: #f1c40f; }
        .stat-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #2c3e50; }
        .stat-label { color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin:0;">ADMIN PANEL</h2>
            <nav class="admin-nav">
                <a href="../index.php" target="_blank" style="color: #3498db;">
                    <i class="fa fa-globe"></i> Xem Trang Chủ
                </a>

                <a href="index.php"><i class="fa fa-chart-line"></i> Thống kê</a>
                <a href="rooms.php"><i class="fa fa-hotel"></i> Quản lý Phòng</a>
                <a href="bookings.php"><i class="fa fa-list-alt"></i> Đơn đặt phòng</a>
                
                <a href="../logout.php" style="background: #c0392b; padding: 5px 10px; border-radius: 4px;">
                    <i class="fa fa-sign-out-alt"></i> Đăng xuất
                </a>
            </nav>
        </div>
    </div>
    <div class="container" style="margin-top: 30px;">
