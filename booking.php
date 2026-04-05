<?php
require_once 'includes/db.php';
session_start();

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Vui lòng đăng nhập để đặt phòng!'); window.location.href='login.php';</script>";
    exit();
}

if (isset($_POST['btn_book'])) {
    $user_id = $_SESSION['user_id'];
    $room_id = $_POST['room_id'];
    $price_per_night = $_POST['price'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];

    // Validate ngày
    if (strtotime($check_in) >= strtotime($check_out)) {
        echo "<script>alert('Ngày trả phòng phải sau ngày nhận phòng!'); window.history.back();</script>";
        exit();
    }

    // --- LOGIC KIỂM TRA PHÒNG TRỐNG (QUAN TRỌNG) ---
    // Đếm xem có đơn nào trùng lịch không
    $check_sql = "SELECT count(*) as total FROM bookings 
                  WHERE room_id = '$room_id' 
                  AND status != 'cancelled'
                  AND (check_in_date < '$check_out' AND check_out_date > '$check_in')";
    
    $check_result = $conn->query($check_sql);
    $row = $check_result->fetch_assoc();

    if ($row['total'] > 0) {
        // Đã có người đặt => Báo lỗi và quay lại
        echo "<script>
                alert('Rất tiếc! Phòng này đã kín lịch trong khoảng thời gian bạn chọn. Vui lòng chọn ngày khác.');
                window.history.back();
              </script>";
        exit();
    }

    // Nếu trống => Tiến hành đặt
    $date1 = date_create($check_in);
    $date2 = date_create($check_out);
    $days = date_diff($date1, $date2)->format("%a");
    $total_price = $price_per_night * $days;

    $sql = "INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, status) 
            VALUES ('$user_id', '$room_id', '$check_in', '$check_out', '$total_price', 'pending')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>
                alert('Đặt phòng thành công! Chúng tôi sẽ liên hệ sớm.'); 
                window.location.href='profile.php?tab=history';
              </script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}
?>