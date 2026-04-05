<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Vui lòng đăng nhập để dùng tính năng yêu thích!'); window.location.href='login.php';</script>";
    exit();
}

if (!isset($_GET['room_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$room_id = (int) $_GET['room_id'];

$check_sql = "SELECT id FROM favorites WHERE user_id = $user_id AND room_id = $room_id";
$existing = $conn->query($check_sql);

if ($existing && $existing->num_rows > 0) {
    $conn->query("DELETE FROM favorites WHERE user_id = $user_id AND room_id = $room_id");
} else {
    $conn->query("INSERT INTO favorites (user_id, room_id) VALUES ($user_id, $room_id)");
}

header("Location: room_detail.php?id=$room_id");
exit();
?>
