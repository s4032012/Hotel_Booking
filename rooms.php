<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// --- 1. NHẬN DỮ LIỆU TỪ URL (GET) ---
$limit = 6; 
$page = isset($_GET['page']) ? $_GET['page'] : 1; 
$start = ($page - 1) * $limit;

// Lấy tham số tìm kiếm & lọc
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$price_filters = isset($_GET['price']) ? $_GET['price'] : [];
$type_filters = isset($_GET['type']) ? $_GET['type'] : [];

// --- 2. XÂY DỰNG CÂU SQL (WHERE CLAUSE) ---
$where_conditions = ["status = 'available'"]; // Mặc định chỉ lấy phòng trống

// A. Lọc theo từ khóa
if (!empty($keyword)) {
    $where_conditions[] = "(room_name LIKE '%$keyword%' OR address LIKE '%$keyword%')";
}

// B. Lọc theo Loại phòng (Có thể chọn nhiều)
if (!empty($type_filters)) {
    $types_str = "'" . implode("','", $type_filters) . "'";
    $where_conditions[] = "room_type IN ($types_str)";
}

// C. Lọc theo Giá (Logic phức tạp hơn vì là khoảng)
if (!empty($price_filters)) {
    $price_sqls = [];
    foreach ($price_filters as $p) {
        if ($p == 'low') $price_sqls[] = "price < 500000";
        if ($p == 'medium') $price_sqls[] = "price BETWEEN 500000 AND 2000000";
        if ($p == 'high') $price_sqls[] = "price > 2000000";
    }
    if (!empty($price_sqls)) {
        // Dùng OR giữa các khoảng giá (VD: Tìm phòng < 500k HOẶC > 2 triệu)
        $where_conditions[] = "(" . implode(" OR ", $price_sqls) . ")";
    }
}

// Gộp tất cả điều kiện bằng AND
$where_sql = "WHERE " . implode(" AND ", $where_conditions);

// --- 3. ĐẾM TỔNG SỐ (Phân trang) ---
$sql_count = "SELECT count(id) AS total FROM rooms $where_sql";
$result_count = $conn->query($sql_count);
$total_rows = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// --- 4. LẤY DỮ LIỆU HIỂN THỊ ---
$sql = "SELECT * FROM rooms $where_sql ORDER BY id DESC LIMIT $start, $limit";
$result = $conn->query($sql);
?>

<style>
    /* 1. Nền đen mờ bao phủ web khi mở bộ lọc */
    #filterOverlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100vh;
        background: rgba(0,35,80,0.6); 
        z-index: 1040;
        visibility: hidden; opacity: 0; transition: 0.3s;
    }
    #filterOverlay.active { visibility: visible; opacity: 1; }

    /* 2. Thanh ngăn kéo chứa bộ lọc (Giấu bên trái màn hình) */
    #sideFilter {
        position: fixed; top: 0; left: -350px; 
        width: 320px; height: 100vh; background: #fff;
        z-index: 1050; transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-y: auto; padding: 20px;
        box-shadow: 5px 0 25px rgba(0,0,0,0.15);
    }
    #sideFilter.active { left: 0; }
    
    /* Ẩn bớt thanh cuộn cho đẹp */
    #sideFilter::-webkit-scrollbar { width: 5px; }
    #sideFilter::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }

    /* 3. Nút X để đóng bộ lọc */
    .close-filter-btn {
        position: absolute; top: 10px; right: 20px;
        font-size: 32px; cursor: pointer; color: #333; font-weight: bold;
        transition: 0.2s; z-index: 10;
    }
    .close-filter-btn:hover { color: #0071c2; }
</style>

<div style="background: #f9f9f9; padding: 15px 0; border-bottom: 1px solid #eee;">
    <div class="container">
        <span style="color: #666;">Trang chủ > </span> <strong style="color: #2c3e50;">Danh sách phòng & Suites</strong>
    </div>
</div>

<div class="container page-container" style="padding: 40px 0;">
    
    <div id="filterOverlay" onclick="toggleFilter()"></div>

    <aside id="sideFilter">
        <span class="close-filter-btn" onclick="toggleFilter()">×</span>
        
        <form action="rooms.php" method="GET" style="margin-top: 30px;">
            
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; border-top: 3px solid #0071c2;">
                <h4 style="margin-top: 0; margin-bottom: 15px; color: #2c3e50;">Tìm kiếm nhanh</h4>
                <div style="display: flex; gap: 5px;">
                    <input type="text" name="keyword" value="<?php echo $keyword; ?>" placeholder="Tên phòng, địa chỉ..." 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; outline: none;">
                    <button type="submit" style="background: #0071c2; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #2c3e50; font-family: 'Merriweather', serif; font-size: 1.2rem;">Bộ lọc</h3>
                    <a href="rooms.php" style="font-size: 0.85rem; color: #0071c2; text-decoration: underline;">Xóa lọc</a>
                </div>
                
                <div class="filter-group" style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                    <div style="font-weight: 700; margin-bottom: 10px; color: #333;">Khoảng giá</div>
                    <label style="display:block; margin-bottom:8px; cursor: pointer;">
                        <input type="checkbox" name="price[]" value="low" <?php if(in_array('low', $price_filters)) echo 'checked'; ?>> Dưới 500k
                    </label>
                    <label style="display:block; margin-bottom:8px; cursor: pointer;">
                        <input type="checkbox" name="price[]" value="medium" <?php if(in_array('medium', $price_filters)) echo 'checked'; ?>> 500k - 2 triệu
                    </label>
                    <label style="display:block; margin-bottom:8px; cursor: pointer;">
                        <input type="checkbox" name="price[]" value="high" <?php if(in_array('high', $price_filters)) echo 'checked'; ?>> Trên 2 triệu
                    </label>
                </div>

                <div class="filter-group" style="margin-bottom: 20px;">
                    <div style="font-weight: 700; margin-bottom: 10px; color: #333;">Loại phòng</div>
                    <label style="display:block; margin-bottom:8px; cursor: pointer;">
                        <input type="checkbox" name="type[]" value="Standard" <?php if(in_array('Standard', $type_filters)) echo 'checked'; ?>> Standard
                    </label>
                    <label style="display:block; margin-bottom:8px; cursor: pointer;">
                        <input type="checkbox" name="type[]" value="Deluxe" <?php if(in_array('Deluxe', $type_filters)) echo 'checked'; ?>> Deluxe
                    </label>
                    <label style="display:block; margin-bottom:8px; cursor: pointer;">
                        <input type="checkbox" name="type[]" value="VIP" <?php if(in_array('VIP', $type_filters)) echo 'checked'; ?>> VIP Suite
                    </label>
                    <label style="display:block; margin-bottom:8px; cursor: pointer;">
                        <input type="checkbox" name="type[]" value="Resort" <?php if(in_array('Resort', $type_filters)) echo 'checked'; ?>> Resort
                    </label>
                    <label style="display:block; margin-bottom:8px; cursor: pointer;">
                        <input type="checkbox" name="type[]" value="Căn hộ" <?php if(in_array('Căn hộ', $type_filters)) echo 'checked'; ?>> Căn hộ
                    </label>
                </div>

                <button type="submit" style="width: 100%; background: #0071c2; color: white; padding: 10px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.3s;">
                    ÁP DỤNG BỘ LỌC
                </button>
            </div>
        </form>
    </aside>

    <main class="room-list" style="width: 100%;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 15px;">
            <h2 style="margin: 0; font-family: 'Merriweather', serif; color: #003580; font-size: 1.6rem; font-weight: 700;">
                <?php if($keyword || !empty($price_filters) || !empty($type_filters)): ?>
                    Kết quả tìm kiếm: <?php echo $total_rows; ?> phòng
                <?php else: ?>
                    Khám phá các phòng nghỉ
                <?php endif; ?>
            </h2>
            
            <button onclick="toggleFilter()" style="background: #febb02; color: #000; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 700; font-size: 0.95rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: 0.3s;">
                <i class="fa fa-filter" style="color: #003580;"></i> BỘ LỌC
            </button>
        </div>
        
        <?php if ($result->num_rows > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(800px, 1fr)); gap: 20px;">
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="pro-room-card" style="display: flex; background: #fff; border: 1px solid #e7e7e7; border-radius: 8px; overflow: hidden; transition: 0.3s; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                        <div class="pro-img-container" style="width: 35%; position: relative;">
                            <img src="uploads/<?php echo $row['image']; ?>" style="width: 100%; height: 100%; object-fit: cover; min-height: 220px;" onerror="this.src='assets/images/default-room.jpg'">
                            <span style="position: absolute; top: 10px; left: 10px; background: #0071c2; color: white; padding: 4px 10px; font-size: 0.8rem; border-radius: 4px; font-weight: bold;">
                                Ưu đãi
                            </span>
                        </div>
                        
                        <div class="pro-content" style="width: 65%; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="display: flex; justify-content: space-between;">
                                    <h3 style="margin: 0; font-size: 1.3rem; color: #0071c2; font-weight: 700; cursor: pointer;"><?php echo $row['room_name']; ?></h3>
                                    <span style="background: #febb02; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; height: fit-content;">
                                        <?php echo $row['room_type']; ?>
                                    </span>
                                </div>
                                <p style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                                    <i class="fa fa-map-marker-alt" style="color: #0071c2;"></i> <?php echo $row['address']; ?>
                                </p>
                                <p style="color: #555; font-size: 0.9rem; margin-top: 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo $row['description']; ?>
                                </p>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 15px;">
                                <div style="color: #27ae60; font-size: 0.85rem; font-weight: 600;">
                                    <i class="fa fa-check"></i> Miễn phí hủy phòng
                                </div>
                                <div style="text-align: right;">
                                    <div style="text-decoration: line-through; color: #e34c26; font-size: 0.9rem;">
                                        <?php echo number_format($row['price'] * 1.2, 0, ',', '.'); ?>đ
                                    </div>
                                    <div style="font-size: 1.4rem; font-weight: 700; color: #000;">
                                        <?php echo number_format($row['price'], 0, ',', '.'); ?> VNĐ
                                    </div>
                                    <a href="room_detail.php?id=<?php echo $row['id']; ?>" class="btn-register" style="display: inline-block; margin-top: 5px; font-size: 0.9rem; padding: 8px 20px; background: #0071c2; color: #fff; border-radius: 4px; font-weight: bold;">
                                        Xem phòng <i class="fa fa-chevron-right" style="font-size: 0.8rem; margin-left: 5px;"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php if($total_pages > 1): ?>
            <div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 40px;">
                <?php 
                    $params = $_GET;
                    unset($params['page']); 
                    $query_string = http_build_query($params);
                    if($query_string) $query_string = "&" . $query_string;
                ?>

                <?php if($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?><?php echo $query_string; ?>" class="page-link" style="padding: 8px 15px; border: 1px solid #0071c2; border-radius: 4px; color: #0071c2; font-weight: bold;">&laquo; Trước</a>
                <?php endif; ?>

                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $query_string; ?>" class="page-link" style="padding: 8px 15px; border: 1px solid #0071c2; border-radius: 4px; font-weight: bold; <?php if($page == $i) echo 'background: #0071c2; color: #fff;'; else echo 'color: #0071c2;'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?><?php echo $query_string; ?>" class="page-link" style="padding: 8px 15px; border: 1px solid #0071c2; border-radius: 4px; color: #0071c2; font-weight: bold;">Sau &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div style="text-align: center; padding: 50px; background: #fff; border-radius: 8px; border: 1px solid #eee;">
                <img src="assets/images/no-result.png" onerror="this.style.display='none'" style="width: 100px; margin-bottom: 20px;">
                <h3 style="color: #666;">Không tìm thấy phòng nào!</h3>
                <p>Hãy thử bỏ bớt bộ lọc hoặc tìm từ khóa khác.</p>
                <a href="rooms.php" style="display: inline-block; margin-top: 10px; padding: 10px 20px; background: #0071c2; color: #fff; border-radius: 4px; font-weight: bold;">Xóa toàn bộ lọc</a>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
    function toggleFilter() {
        document.getElementById('sideFilter').classList.toggle('active');
        document.getElementById('filterOverlay').classList.toggle('active');
    }
</script>

<?php require_once 'includes/footer.php'; ?>