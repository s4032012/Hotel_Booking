<?php 
require_once 'header.php'; 

// --- XỬ LÝ HÀNH ĐỘNG (DUYỆT / HỦY / XÓA) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id']; // Đã sửa khoảng trắng lạ
    $action = $_GET['action'];
    $sql = "";

    if ($action == 'confirm') {
        $sql = "UPDATE bookings SET status = 'confirmed' WHERE id = $id";
    } elseif ($action == 'cancel') {
        $sql = "UPDATE bookings SET status = 'cancelled' WHERE id = $id";
    } elseif ($action == 'delete') {
        $sql = "DELETE FROM bookings WHERE id = $id";
    }

    if ($sql != "" && $conn->query($sql) === TRUE) {
        echo "<script>window.location.href='bookings.php';</script>";
    }
}
?>

<h2 style="color: #2c3e50; margin-bottom: 20px;">Quản lý Đơn Đặt Phòng</h2>

<table style="width: 100%; background: #fff; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
    <thead>
        <tr style="background: #34495e; color: white; text-align: left;">
            <th style="padding: 12px;">ID</th>
            <th style="padding: 12px;">Khách hàng</th>
            <th style="padding: 12px;">Phòng</th>
            <th style="padding: 12px;">Lịch trình</th>
            <th style="padding: 12px;">Tổng tiền</th>
            <th style="padding: 12px;">Trạng thái</th>
            <th style="padding: 12px;">Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Lấy danh sách booking (Nối bảng user và room để lấy tên)
        $sql = "SELECT b.*, u.full_name, u.phone, r.room_name 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                JOIN rooms r ON b.room_id = r.id 
                ORDER BY b.created_at DESC";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                
                // --- ĐÃ SỬA: THÊM white-space:nowrap ĐỂ CHỮ KHÔNG BỊ RỚT DÒNG ---
                $status_badges = [
                    'pending'   => '<span style="white-space:nowrap; background:#f39c12; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.8rem; font-weight:bold;">Chờ duyệt</span>',
                    'confirmed' => '<span style="white-space:nowrap; background:#27ae60; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.8rem; font-weight:bold;">Đã duyệt</span>',
                    'cancelled' => '<span style="white-space:nowrap; background:#c0392b; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.8rem; font-weight:bold;">Đã hủy</span>',
                    'completed' => '<span style="white-space:nowrap; background:#2980b9; color:#fff; padding:4px 8px; border-radius:4px; font-size:0.8rem; font-weight:bold;">Hoàn thành</span>'
                ];

                $badge_html = isset($status_badges[$row['status']]) ? $status_badges[$row['status']] : $row['status'];
                ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">#<?php echo $row['id']; ?></td>
                    <td style="padding: 12px;">
                        <strong><?php echo $row['full_name']; ?></strong><br>
                        <span style="font-size: 0.9rem; color: #7f8c8d;"><?php echo $row['phone']; ?></span>
                    </td>
                    <td style="padding: 12px;"><?php echo $row['room_name']; ?></td>
                    <td style="padding: 12px; font-size: 0.9rem;">
                        In: <?php echo date('d/m', strtotime($row['check_in_date'])); ?><br>
                        Out: <?php echo date('d/m', strtotime($row['check_out_date'])); ?>
                    </td>
                    <td style="padding: 12px; font-weight: bold; color: #d35400;">
                        <?php echo number_format($row['total_price'], 0, ',', '.'); ?>đ
                    </td>
                    <td style="padding: 12px;">
                        <?php echo $badge_html; ?>
                    </td>
                    <td style="padding: 12px;">
                        <?php if($row['status'] == 'pending'): ?>
                            <a href="bookings.php?action=confirm&id=<?php echo $row['id']; ?>" title="Duyệt đơn" style="color: #27ae60; margin-right: 10px; font-size: 1.2rem;"><i class="fa fa-check-circle"></i></a>
                            <a href="bookings.php?action=cancel&id=<?php echo $row['id']; ?>" title="Hủy đơn" onclick="return confirm('Hủy đơn này?')" style="color: #e67e22; margin-right: 10px; font-size: 1.2rem;"><i class="fa fa-times-circle"></i></a>
                        <?php endif; ?>
                        
                        <a href="bookings.php?action=delete&id=<?php echo $row['id']; ?>" title="Xóa vĩnh viễn" onclick="return confirm('Xóa vĩnh viễn đơn này?')" style="color: #c0392b; font-size: 1.2rem;"><i class="fa fa-trash"></i></a>
                    </td>
                </tr>
                <?php
            }
        } else {
            echo "<tr><td colspan='7' style='text-align: center; padding: 20px;'>Chưa có đơn hàng nào.</td></tr>";
        }
        ?>
    </tbody>
</table>

<?php require_once 'footer.php'; ?>