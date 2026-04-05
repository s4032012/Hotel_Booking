<?php require_once 'header.php'; ?>

<?php
// --- 1. XỬ LÝ LỌC NGÀY ---
// Mặc định: Lấy từ đầu năm đến ngày hiện tại
$current_year = date('Y');
$default_start = $current_year . '-01-01'; // Đầu năm 2025 (hoặc năm hiện tại)

$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : $default_start;
$end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Chuỗi SQL WHERE clause (Lưu ý: Dùng created_at trong bảng bookings làm mốc)
$date_filter_sql = " AND created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";

// Chuỗi hiển thị trên Dashboard
$date_display = "Từ ngày " . date('d/m/Y', strtotime($start_date)) . " đến " . date('d/m/Y', strtotime($end_date));
?>

<h2 style="margin-bottom: 20px; color: #2c3e50;">Tổng quan hệ thống</h2>

<form method="GET" action="index.php" style="margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 8px;">
    <h4 style="margin-top: 0; color: #3498db; margin-bottom: 15px;">Lọc Doanh thu theo khoảng thời gian</h4>
    <div style="display:flex; gap:15px; align-items:center;">
        <label style="font-weight: 600;">Từ ngày:</label>
        <input type="date" name="start_date" value="<?php echo $start_date; ?>" required style="padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        <label style="font-weight: 600;">Đến ngày:</label>
        <input type="date" name="end_date" value="<?php echo $end_date; ?>" required style="padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        <button type="submit" style="background:#3498db; color:white; padding: 10px 20px; border:none; border-radius:4px; font-weight: bold;">
            Xem Doanh Thu
        </button>
    </div>
    <p style="color:#7f8c8d; font-size: 0.9rem; margin-top: 10px;">Đang hiển thị: <?php echo $date_display; ?></p>
</form>


<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px;">
    
    <?php 
        $rooms_count = $conn->query("SELECT COUNT(*) as c FROM rooms")->fetch_assoc()['c']; 
    ?>
    <div class="stat-card">
        <div class="stat-number"><?php echo $rooms_count; ?></div>
        <div class="stat-label">Tổng số phòng</div>
    </div>

    <?php 
        // Đơn chờ duyệt TRONG KHOẢNG NGÀY
        $pending_count = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='pending' $date_filter_sql")->fetch_assoc()['c']; 
    ?>
    <div class="stat-card" style="border-left: 5px solid #f1c40f;">
        <div class="stat-number" style="color: #f39c12;"><?php echo $pending_count; ?></div>
        <div class="stat-label">Đơn chờ duyệt</div>
    </div>

    <?php 
        // Đơn thành công TRONG KHOẢNG NGÀY
        $success_count = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE (status='confirmed' OR status='completed') $date_filter_sql")->fetch_assoc()['c']; 
    ?>
    <div class="stat-card" style="border-left: 5px solid #2ecc71;">
        <div class="stat-number" style="color: #27ae60;"><?php echo $success_count; ?></div>
        <div class="stat-label">Đơn thành công</div>
    </div>

    <?php 
        // TÍNH TỔNG DOANH THU TRONG KHOẢNG NGÀY
        $revenue_query = "SELECT SUM(total_price) as s FROM bookings WHERE (status='confirmed' OR status='completed') $date_filter_sql";
        $revenue = $conn->query($revenue_query)->fetch_assoc()['s']; 
    ?>
    <div class="stat-card" style="border-left: 5px solid #3498db;">
        <div class="stat-number" style="color: #2980b9; font-size: 1.5rem;">
            <?php echo number_format($revenue ? $revenue : 0, 0, ',', '.'); ?>đ
        </div>
        <div class="stat-label">Tổng doanh thu</div>
    </div>
</div>

<h3 style="margin-bottom: 15px; color: #2c3e50;">Đơn đặt phòng mới nhất (Toàn thời gian)</h3>
<table style="width: 100%; background: #fff; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
    <thead>
        <tr style="background: #ecf0f1; text-align: left;">
            <th style="padding: 12px;">ID</th>
            <th style="padding: 12px;">Khách hàng</th>
            <th style="padding: 12px;">Phòng</th>
            <th style="padding: 12px;">Ngày đặt</th>
            <th style="padding: 12px;">Tổng tiền</th>
            <th style="padding: 12px;">Trạng thái</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Lấy 5 đơn mới nhất (Toàn thời gian)
        $sql = "SELECT b.*, u.full_name, r.room_name 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                JOIN rooms r ON b.room_id = r.id 
                ORDER BY b.created_at DESC LIMIT 5";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
           while($row = $result->fetch_assoc()) {
                // 1. Dịch và chọn màu
                $stt_vn = '';
                $stt_color = 'black';
                
                switch($row['status']) {
                    case 'pending': 
                        $stt_vn = 'Chờ duyệt'; $stt_color = '#f39c12'; break;
                    case 'confirmed': 
                        $stt_vn = 'Đã duyệt'; $stt_color = '#27ae60'; break;
                    case 'cancelled': 
                        $stt_vn = 'Đã hủy'; $stt_color = '#c0392b'; break;
                    default: 
                        $stt_vn = 'Hoàn thành'; $stt_color = '#2980b9';
                }
                
                // 2. Hiển thị
                echo "<tr style='border-bottom: 1px solid #eee;'>
                        <td style='padding: 12px;'>#".$row['id']."</td>
                        <td style='padding: 12px;'>".$row['full_name']."</td>
                        <td style='padding: 12px;'>".$row['room_name']."</td>
                        <td style='padding: 12px;'>".date('d/m/Y', strtotime($row['created_at']))."</td>
                        <td style='padding: 12px; font-weight: bold;'>".number_format($row['total_price'], 0, ',', '.')."đ</td>
                        <td style='padding: 12px; color: $stt_color; font-weight: bold;'>$stt_vn</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='6' style='padding: 20px; text-align: center;'>Chưa có đơn đặt phòng nào.</td></tr>";
        }
        ?>
    </tbody>
</table>

<?php require_once 'footer.php'; ?>