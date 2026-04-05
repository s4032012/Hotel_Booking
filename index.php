<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
?>

<section class="hero">
    <div class="container">
        <div class="hero-content" data-aos="fade-up">
            <h1 class="hero-title">Tuyệt tác Nghỉ dưỡng Việt Nam</h1>
            <p class="hero-subtitle">Hành trình chạm đến những cảm xúc thăng hoa</p>
        </div>
    </div>
</section>

<div class="search-box">
    <form action="index.php" method="GET">
        <div class="search-grid-row-1">
            <div class="search-item-group">
                <label class="search-label"><i class="fa fa-map-marker-alt"></i> Điểm đến / Tên phòng</label>
                <input type="text" name="keyword" class="form-control-lg" placeholder="Bạn muốn đi đâu?" value="<?php echo isset($_GET['keyword'])?$_GET['keyword']:''; ?>">
            </div>
            <div class="search-item-group">
                <label class="search-label"><i class="fa fa-calendar-alt"></i> Nhận phòng</label>
                <input type="date" name="checkin" class="form-control-lg" value="<?php echo isset($_GET['checkin'])?$_GET['checkin']:''; ?>">
            </div>
            <div class="search-item-group">
                <label class="search-label"><i class="fa fa-calendar-check"></i> Trả phòng</label>
                <input type="date" name="checkout" class="form-control-lg" value="<?php echo isset($_GET['checkout'])?$_GET['checkout']:''; ?>">
            </div>
            <div class="search-item-group">
                <label class="search-label"><i class="fa fa-user"></i> Số khách</label>
                <select name="guests" class="form-control-lg">
                    <option value="1" <?php if(isset($_GET['guests']) && $_GET['guests'] == '1') echo 'selected'; ?>>1 người</option>
                    <option value="2" <?php if(isset($_GET['guests']) && $_GET['guests'] == '2') echo 'selected'; ?>>2 người</option>
                    <option value="4" <?php if(isset($_GET['guests']) && $_GET['guests'] == '4') echo 'selected'; ?>>Gia đình(3-5 người trở lên)</option>
                </select>
            </div>
        </div>

        <div class="search-grid-row-2">
            <div class="search-item-group">
                <label class="search-label"><i class="fa fa-home"></i> Loại hình</label>
                <select name="type" class="form-control-lg">
                    <option value="">Tất cả loại phòng</option>
                <option value="Standard" <?php if(isset($_GET['type']) && $_GET['type'] == 'Standard') echo 'selected'; ?>>Standard</option>
                <option value="Deluxe" <?php if(isset($_GET['type']) && $_GET['type'] == 'Deluxe') echo 'selected'; ?>>Deluxe</option>
                <option value="VIP" <?php if(isset($_GET['type']) && $_GET['type'] == 'VIP') echo 'selected'; ?>>VIP</option>
                <option value="Resort" <?php if(isset($_GET['type']) && $_GET['type'] == 'Resort') echo 'selected'; ?>>Resort</option>
                <option value="Căn hộ" <?php if(isset($_GET['type']) && $_GET['type'] == 'Căn hộ') echo 'selected'; ?>>Căn hộ</option>
            </select>
            </div>
            <div class="search-item-group">
                <label class="search-label"><i class="fa fa-tag"></i> Ngân sách tối đa</label>
                <div class="price-wrapper">
                    <input type="text" id="priceInput" class="price-input" value="<?php echo isset($_GET['max_price'])?number_format($_GET['max_price'],0,',','.'):'5.000.000'; ?>">
                    <input type="range" name="max_price" id="priceRange" class="price-slider" min="0" max="10000000" step="500000" value="<?php echo isset($_GET['max_price']) ? $_GET['max_price'] : '5000000'; ?>">
                </div>
            </div>
            <div class="search-item-group">
                <label class="search-label" style="visibility:hidden">Nut</label>
                <button type="submit" class="btn-search-booking">TÌM KIẾM</button>
            </div>
        </div>
    </form>
</div>

<section class="container" style="margin-top: 80px;">
    <div class="section-header" data-aos="fade-up">
        <h2 class="section-title">Các Phòng Nổi Bật</h2>
        <p class="section-subtitle">Những lựa chọn tốt nhất dành cho bạn</p>
    </div>

    <div id="hotelsGrid" class="hotels-grid">
        <?php
        // 1. Câu lệnh gốc (Phải có WHERE để nối các AND phía sau)
        $sql = "SELECT * FROM rooms WHERE status = 'available'";

        // 2. Tìm theo tên
        if(isset($_GET['keyword']) && !empty($_GET['keyword'])){
            $k = $_GET['keyword'];
            $sql .= " AND (room_name LIKE '%$k%' OR address LIKE '%$k%')";
        }
        
        // 3. Tìm theo loại
        if(isset($_GET['type']) && !empty($_GET['type'])){
            $t = $_GET['type'];
            // Kiểm tra nếu không phải chọn "Tất cả" mới lọc
            if($t != 'Tất cả' && $t != '') {
                 $sql .= " AND room_type = '$t'";
            }
        }
        
        // 4. Tìm theo giá
        if(isset($_GET['max_price']) && !empty($_GET['max_price'])){
            $p = $_GET['max_price'];
            $sql .= " AND price <= $p";
        }
        
        // 5. LỌC THEO SỨC CHỨA (MỚI BỔ SUNG)
        if(isset($_GET['guests']) && $_GET['guests'] != 'Gia đình(3-5 người trở lên)'){
            $g = $_GET['guests']; 
            $sql .= " AND capacity >= $g"; // Lọc phòng có sức chứa LỚN HƠN hoặc BẰNG số khách
        } elseif (isset($_GET['guests']) && $_GET['guests'] == 'Gia đình(3-5 người trở lên)') {
            $sql .= " AND capacity >= 4"; // Giả sử 'Gia đình' cần sức chứa tối thiểu 4 (từ option value=4)
        }
        
        // 6. Lọc ngày (Loại bỏ phòng đã có đơn)
        if(isset($_GET['checkin']) && !empty($_GET['checkin']) && isset($_GET['checkout']) && !empty($_GET['checkout'])){
            $in = $_GET['checkin'];
            $out = $_GET['checkout'];
            $sql .= " AND id NOT IN (SELECT room_id FROM bookings WHERE (check_in_date < '$out' AND check_out_date > '$in') AND status != 'cancelled')";
        }

        $sql .= " ORDER BY RAND() LIMIT 3"; // Chỉ hiển thị 3 phòng nổi bật nhất
        
        // Thực thi câu lệnh
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Tạo link kèm ngày tháng
                $query_date = "";
                if(isset($_GET['checkin']) && !empty($_GET['checkin'])) {
                    $query_date = "&checkin=" . $_GET['checkin'] . "&checkout=" . $_GET['checkout'];
                }
        ?>
            <div class="hotel-card" data-aos="fade-up">
                <div class="hotel-image-container">
                    <img src="uploads/<?php echo $row['image']; ?>" onerror="this.src='assets/images/default-room.jpg'">
                    <span class="hotel-badge"><?php echo $row['room_type']; ?></span>
                </div>
                <div class="hotel-content">
                    <h3 class="hotel-name"><?php echo $row['room_name']; ?></h3>
                    <p style="font-size:0.9rem; color:#666; margin-bottom:10px;">
                        <i class="fa fa-map-marker-alt" style="color:var(--dongson-gold)"></i> 
                        <?php echo substr($row['address'], 0, 35); ?>...
                    </p>
                    <div class="hotel-price">
                        <?php echo number_format($row['price'], 0, ',', '.'); ?> VNĐ <small style="font-size:0.8rem; color:#666">/đêm</small>
                    </div>
                    <a href="room_detail.php?id=<?php echo $row['id']; ?><?php echo $query_date; ?>" class="btn-detail">Xem Chi Tiết</a>
                </div>
            </div>
        <?php 
            }
        } else { 
            // In lỗi ra để debug nếu vẫn không thấy
            echo "<div style='grid-column: 1/-1; text-align:center; padding: 40px; background:#fff; border-radius:12px;'>
                    <p>Không tìm thấy phòng nào.</p>
                    <a href='index.php' style='color:var(--vn-red); font-weight:bold'>Xem tất cả phòng</a>
                    </div>"; 
        } 
        ?>
    </div>
    </div>
</section>

<section class="support-section" style="padding: 60px 0;">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2 class="section-title">Dịch Vụ Khách Hàng 24/7</h2>
        </div>
        <div class="support-grid">
            <div class="support-item" data-aos="fade-up">
                <div class="support-icon"><i class="fa fa-book-open"></i></div>
                <h3 class="feature-title">Hướng Dẫn Đặt Phòng</h3>
                <p>Quy trình đơn giản, xác nhận tức thì qua email.</p>
                <a href="#" class="support-link">Xem chi tiết &rarr;</a>
            </div>
            <div class="support-item" data-aos="fade-up" data-aos-delay="100">
                <div class="support-icon"><i class="fa fa-shield-alt"></i></div>
                <h3 class="feature-title">Chính Sách Đảm Bảo</h3>
                <p>Cam kết giá tốt nhất, hoàn hủy linh hoạt.</p>
                <a href="#" class="support-link">Xem chi tiết &rarr;</a>
            </div>
            <div class="support-item" data-aos="fade-up" data-aos-delay="200">
                <div class="support-icon"><i class="fa fa-headset"></i></div>
                <h3 class="feature-title">Hỗ Trợ Trực Tuyến</h3>
                <p>Giải đáp thắc mắc 24/7 qua Hotline.</p>
                <a href="tel:0765933135" class="support-link">0765.933.135</a>
            </div>
        </div>
    </div>
</section>

<section class="testimonials-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2 class="section-title">Khách Hàng Nói Gì?</h2>
        </div>
        <div class="testimonials-grid">
            <div class="testimonial-card" data-aos="zoom-in">
                <img src="https://ui-avatars.com/api/?name=Nguyen+Van+A&background=random" class="t-img">
                <p style="font-style: italic; color: #555; margin-bottom: 15px;">"Dịch vụ tuyệt vời! Phòng ốc sạch sẽ, view đẹp."</p>
                <h4 class="t-name">Nguyễn Văn A</h4>
                <div class="t-star">⭐⭐⭐⭐⭐</div>
            </div>
            <div class="testimonial-card" data-aos="zoom-in" data-aos-delay="100">
                <img src="https://ui-avatars.com/api/?name=Tran+Thi+B&background=random" class="t-img">
                <p style="font-style: italic; color: #555; margin-bottom: 15px;">"Đặt phòng nhanh, giá tốt. Rất hài lòng."</p>
                <h4 class="t-name">Trần Thị B</h4>
                <div class="t-star">⭐⭐⭐⭐⭐</div>
            </div>
            <div class="testimonial-card" data-aos="zoom-in" data-aos-delay="200">
                <img src="https://ui-avatars.com/api/?name=Le+Van+C&background=random" class="t-img">
                <p style="font-style: italic; color: #555; margin-bottom: 15px;">"Nhân viên hỗ trợ rất nhiệt tình."</p>
                <h4 class="t-name">Lê Văn C</h4>
                <div class="t-star">⭐⭐⭐⭐⭐</div>
            </div>
        </div>
    </div>
</section>

<script>
    const slider = document.getElementById('priceRange');
    const display = document.getElementById('priceInput');
    function formatMoney(num) { return parseInt(num).toLocaleString('vi-VN'); }
    if(slider && display) {
        slider.oninput = function() { display.value = formatMoney(this.value); }
        display.oninput = function() { slider.value = this.value.replace(/\D/g, ''); this.value = formatMoney(slider.value); }
    }
</script>

<div class="container">
    </div>

<?php require_once 'includes/footer.php'; ?>