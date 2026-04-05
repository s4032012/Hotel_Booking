<?php
session_start();
// Check Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once '../includes/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Xóa phòng (Do mình đã cài ON DELETE CASCADE trong SQL cho bảng con, nên xóa phòng là ảnh/booking bay theo luôn)
    $sql = "DELETE FROM rooms WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Đã xóa phòng thành công!'); window.location.href='rooms.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
} else {
    header("Location: rooms.php");
}
?>