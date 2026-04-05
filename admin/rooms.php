<?php require_once 'header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="color: #2c3e50;">Quản lý Phòng</h2>
    <a href="room_add.php" style="background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">
        <i class="fa fa-plus"></i> Thêm Phòng Mới
    </a>
</div>

<table style="width: 100%; background: #fff; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
    <thead>
        <tr style="background: #34495e; color: white; text-align: left;">
            <th style="padding: 12px;">ID</th>
            <th style="padding: 12px;">Ảnh</th>
            <th style="padding: 12px;">Tên phòng</th>
            <th style="padding: 12px;">Loại</th>
            <th style="padding: 12px;">Giá/Đêm</th>
            <th style="padding: 12px;">Trạng thái</th>
            <th style="padding: 12px;">Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT * FROM rooms ORDER BY id DESC";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $status_color = ($row['status'] == 'available') ? '#27ae60' : '#c0392b';
                ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">#<?php echo $row['id']; ?></td>
                    <td style="padding: 12px;">
                        <img src="<?php echo media_url($row['image'], '../uploads/'); ?>" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                    </td>
                    <td style="padding: 12px; font-weight: 600;"><?php echo $row['room_name']; ?></td>
                    <td style="padding: 12px;"><?php echo $row['room_type']; ?></td>
                    <td style="padding: 12px; color: #d35400; font-weight: bold;">
                        <?php echo number_format($row['price'], 0, ',', '.'); ?>đ
                    </td>
                    <td style="padding: 12px; color: <?php echo $status_color; ?>;">
                        <?php echo ($row['status'] == 'available') ? 'Trống' : 'Bảo trì'; ?>
                    </td>
                    <td style="padding: 12px;">
                        <a href="room_edit.php?id=<?php echo $row['id']; ?>" style="color: #2980b9; margin-right: 10px;" title="Sửa">
                            <i class="fa fa-edit"></i>
                        </a>
                        <a href="room_delete.php?id=<?php echo $row['id']; ?>" style="color: #c0392b;" title="Xóa" onclick="return confirm('Bạn chắc chắn muốn xóa phòng này?');">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php
            }
        } else {
            echo "<tr><td colspan='7' style='padding: 20px; text-align: center;'>Chưa có dữ liệu phòng.</td></tr>";
        }
        ?>
    </tbody>
</table>

<?php require_once 'footer.php'; ?>
