<?php
require_once 'includes/db.php';
// --- BỎ DÒNG session_start() Ở ĐÂY ĐỂ TRÁNH LỖI NOTICE ---
require_once 'includes/header.php';

if(isset($_GET['id'])) {
    $room_id = $_GET['id'];
    
    // 1. Lấy thông tin phòng
    $room = $conn->query("SELECT * FROM rooms WHERE id = $room_id")->fetch_assoc();
    
    if(!$room) {
        echo "<div class='container' style='padding:50px;'><h3>Phòng không tồn tại!</h3></div>";
        require_once 'includes/footer.php'; exit();
    }

    // 2. Lấy tất cả ảnh vào mảng PHP
    $images = [];
    $images[] = $room['image']; 
    $gallery_sql = "SELECT image_path FROM room_images WHERE room_id = $room_id";
    $gallery_result = $conn->query($gallery_sql);
    while($img = $gallery_result->fetch_assoc()) {
        if ($img['image_path'] != $room['image']) { $images[] = $img['image_path']; }
    }

    // 3. Kiểm tra Yêu thích
    $is_favorite = false;
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        // Kiểm tra bảng favorites có tồn tại không trước khi query để tránh lỗi
        $check_table = $conn->query("SHOW TABLES LIKE 'favorites'");
        if($check_table->num_rows > 0) {
            $check_fav_sql = "SELECT id FROM favorites WHERE user_id = $user_id AND room_id = $room_id";
            if ($conn->query($check_fav_sql)->num_rows > 0) {
                $is_favorite = true; 
            }
        }
    }
} else {
    header("Location: index.php"); exit();
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>

<style>
    /* CSS Cũ */
    .detail-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 20px; }
    .room-gallery-slider { position: relative; width: 100%; height: 500px; border-radius: 8px; overflow: hidden; margin-bottom: 30px; background: #111; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .slider-track { display: flex; height: 100%; transition: transform 0.5s cubic-bezier(0.645, 0.045, 0.355, 1.000); }
    .slider-item { min-width: 100%; height: 100%; position: relative; }
    .slider-item img { width: 100%; height: 100%; object-fit: cover; cursor: zoom-in; transition: transform 0.3s; }
    .slider-item img:hover { transform: scale(1.02); }
    .slider-btn { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255, 255, 255, 0.9); border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 1.2rem; cursor: pointer; z-index: 10; display: flex; align-items: center; justify-content: center; color: #003580; }
    .slider-btn:hover { background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.2); }
    .prev-btn { left: 20px; } .next-btn { right: 20px; }
    .slider-indicators { position: absolute; bottom: 60px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px; z-index: 10; }
    .indicator { width: 8px; height: 8px; background: rgba(255,255,255,0.7); border-radius: 50%; cursor: pointer; }
    .indicator.active { background: #febb02; transform: scale(1.4); }
    .info-box { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); margin-bottom: 20px; border-top: 3px solid #febb02; }
    
    /* --- CSS LIGHTBOX PRO (Màn hình đen xem ảnh full) --- */
    .lightbox-modal {
        display: none; 
        position: fixed; 
        z-index: 9999; 
        left: 0; top: 0;
        width: 100%; height: 100%; 
        background-color: rgba(0,0,0,0.92); 
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    
    .lightbox-content {
        max-width: 85%;
        max-height: 85vh;
        border-radius: 4px;
        box-shadow: 0 0 30px rgba(255,255,255,0.1);
        animation: zoomIn 0.3s;
        object-fit: contain;
    }

    .close-lightbox {
        position: absolute;
        top: 20px; right: 30px;
        color: #fff; font-size: 40px; font-weight: bold;
        transition: 0.3s; cursor: pointer; z-index: 10002;
    }
    .close-lightbox:hover { color: #0071c2; } /* Đã fix lỗi dư dấu thăng */

    .lb-nav-btn {
        position: absolute; top: 50%; transform: translateY(-50%);
        background: rgba(255,255,255,0.15); color: white;
        border: none; font-size: 2rem; padding: 15px 20px;
        cursor: pointer; transition: 0.3s; z-index: 10001; border-radius: 50%;
    }
    .lb-nav-btn:hover { background: rgba(255,255,255,0.4); transform: translateY(-50%) scale(1.1); }
    .lb-prev { left: 30px; }
    .lb-next { right: 30px; }
    .lb-counter { color: #ccc; margin-top: 10px; font-family: sans-serif; font-size: 0.9rem; }

    @keyframes zoomIn { from {transform:scale(0.9); opacity:0} to {transform:scale(1); opacity:1} }
    @media (max-width: 768px) { .detail-layout { grid-template-columns: 1fr; } .room-gallery-slider { height: 300px; } .lb-nav-btn { padding: 10px; font-size: 1.5rem; } }
</style>

<div class="container" style="padding: 30px 0;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1 style="color: #003580; margin-bottom: 5px; font-weight: 700;">
                <?php echo $room['room_name']; ?>
            </h1>
            <p style="color: #0071c2; font-size: 0.95rem; font-weight: 600;">
                <i class="fa fa-map-marker-alt"></i> <?php echo $room['address']; ?>
            </p>
        </div>
        
        <a href="favorite_handler.php?room_id=<?php echo $room_id; ?>" 
           title="<?php echo $is_favorite ? 'Hủy Yêu thích' : 'Thêm vào Yêu thích'; ?>" 
           style="font-size: 2.2rem; color: <?php echo $is_favorite ? '#0071c2' : '#ccc'; ?>; transition: 0.2s;">
            <i class="fa <?php echo $is_favorite ? 'fa-heart' : 'fa-heart-o'; ?>"></i>
        </a>
    </div>

    <div class="detail-layout">
        <div class="left-content">
            <div class="room-gallery-slider">
                <div class="slider-track" id="sliderTrack">
                    <?php foreach($images as $index => $img): ?>
                        <div class="slider-item">
                            <img src="<?php echo media_url($img); ?>" 
                                 onclick="openLightbox(<?php echo $index; ?>)" 
                                 onerror="this.src='assets/images/default-room.jpg'"
                                 title="Bấm để phóng to">
                            
                            <?php if(!empty($room['capture_time']) && !empty($room['capture_location'])): ?>
                            <div style="position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0, 53, 128, 0.85); padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; z-index: 5;">
                                <div style="color: #fff; font-size: 0.95rem; font-weight: 600;">
                                    <i class="fa fa-clock" style="color: #febb02; margin-right: 5px;"></i> 
                                    <?php echo date('H:i - d/m/Y', strtotime($room['capture_time'])); ?>
                                </div>
                                <a href="https://maps.google.com/?q=<?php echo $room['capture_location']; ?>" target="_blank" style="background: #febb02; color: #000; padding: 6px 15px; border-radius: 4px; text-decoration: none; font-weight: 700; font-size: 0.85rem; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                                    <i class="fa fa-map-marker-alt" style="color: #003580; margin-right: 3px;"></i> Bản đồ
                                </a>
                            </div>
                            <?php endif; ?>
                            </div>
                    <?php endforeach; ?>
                </div>
                <?php if(count($images) > 1): ?>
                    <button class="slider-btn prev-btn" onclick="moveSlide(-1)"><i class="fa fa-chevron-left"></i></button>
                    <button class="slider-btn next-btn" onclick="moveSlide(1)"><i class="fa fa-chevron-right"></i></button>
                    <div class="slider-indicators" id="indicators">
                        <?php foreach($images as $index => $img): ?>
                            <div class="indicator <?php echo $index==0?'active':''; ?>" onclick="goToSlide(<?php echo $index; ?>)"></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>  
                <?php if(!empty($room['image_360'])): ?>
                <div class="info-box" style="padding: 0; overflow: hidden; border-top: 3px solid #0071c2;">
                    <h3 style="margin: 20px 25px 10px; display: inline-block; color: #003580;">
                        <i class="fa fa-vr-cardboard" style="color: #0071c2;"></i> Khám phá không gian 360°
                    </h3>
                    
                    <div id="panorama" style="width: 100%; height: 400px; background: #e9ecef; border-top: 1px solid #eee; border-bottom: 1px solid #eee;"></div>
                    <?php if(!empty($room['capture_time']) && !empty($room['capture_location'])): ?>
    <div style="margin: 0 25px 20px 25px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 0.95rem; color: #333; border: 1px dashed #ccc; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <i class="fa fa-clock" style="color: #0071c2;"></i> 
            <strong>Cập nhật lúc:</strong> <?php echo date('H:i - d/m/Y', strtotime($room['capture_time'])); ?>
        </div>
        <div>
            <i class="fa fa-map-marker-alt" style="color: #0071c2;"></i> 
            <a href="https://maps.google.com/?q=<?php echo $room['capture_location']; ?>" target="_blank" style="color: #0071c2; text-decoration: none; font-weight: bold;">
                Xem tọa độ GPS <i class="fa fa-external-link-alt" style="font-size: 0.8rem;"></i>
            </a>
        </div>
    </div>
    <?php endif; ?>
                <p style="text-align: center; font-size: 0.9rem; color: #666; margin: 15px 0;">
                    <i>(Vuốt hoặc dùng chuột kéo để xoay không gian phòng)</i>
                </p>
            </div>
            <?php endif; ?>
            <div class="info-box">
                <h3 style="margin-bottom: 15px; border-bottom: 2px solid #febb02; padding-bottom: 10px; display: inline-block; color: #003580;">Mô tả chỗ nghỉ</h3>
                <p style="line-height: 1.8; color: #333; text-align: justify;"><?php echo nl2br($room['description']); ?></p>
            </div>

            <div class="info-box">
                <h3 style="margin-bottom: 15px; color: #003580;">Tiện nghi chính</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; color: #333;">
                    <?php 
                    echo '<div><i class="fa fa-wifi" style="color: #0071c2; width: 25px;"></i> Wifi tốc độ cao</div>';
                    echo '<div><i class="fa fa-snowflake" style="color: #0071c2; width: 25px;"></i> Điều hòa 2 chiều</div>';
                    if ($room['has_pool'] == 1) echo '<div><i class="fa fa-swimming-pool" style="color: #0071c2; width: 25px;"></i> Hồ bơi</div>';
                    if ($room['has_breakfast'] == 1) echo '<div><i class="fa fa-utensils" style="color: #0071c2; width: 25px;"></i> Bao gồm bữa sáng</div>';
                    if ($room['has_parking'] == 1) echo '<div><i class="fa fa-parking" style="color: #0071c2; width: 25px;"></i> Bãi đỗ xe miễn phí</div>';
                    if ($room['has_pool'] == 0 && $room['has_breakfast'] == 0 && $room['has_parking'] == 0) {
                         echo '<div><i class="fa fa-tv" style="color: #0071c2; width: 25px;"></i> Smart TV</div>';
                         echo '<div><i class="fa fa-concierge-bell" style="color: #0071c2; width: 25px;"></i> Lễ tân 24/7</div>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="info-box">
                <h4 style="margin-bottom: 15px; font-size: 1.1rem; font-weight: 700; color: #003580;"><i class="fa fa-map-marked-alt" style="color: #0071c2;"></i> Vị trí trên bản đồ</h4>
                <div style="height: 350px; border-radius: 4px; overflow: hidden; border: 1px solid #ddd;">
                    <?php $map_address = urlencode($room['address']); ?>
                    <iframe src="https://maps.google.com/maps?q=<?php echo $map_address; ?>&output=embed&z=14" width="100%" height="350" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
                <a href="https://maps.google.com/?q=<?php echo $map_address; ?>" target="_blank" style="display: block; text-align: center; padding: 10px; color: #0071c2; font-size: 0.95rem; text-decoration: none; font-weight: 600; margin-top: 10px;">Xem bản đồ lớn hơn</a>
            </div>
        </div> 

        <div class="right-sidebar">
            <div class="sticky-sidebar-container">
                <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); border: 1px solid #eee; margin-bottom: 20px; border-top: 5px solid #003580;">
                    <?php if($room['capacity'] > 0): ?>
                    <div style="background: #f0f7ff; padding: 10px; border-radius: 4px; margin-bottom: 15px; color: #0071c2; font-weight: 600;">
                        <i class="fa fa-users"></i> Phòng này phù hợp cho tối đa <?php echo $room['capacity']; ?> người.
                    </div>
                    <?php endif; ?>
                    <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                        <span style="color: #333; font-weight: 600;">Giá ưu đãi chỉ từ</span>
                        <div style="font-size: 2rem; font-weight: 900; color: #003580;">
                            <?php echo number_format($room['price'], 0, ',', '.'); ?>đ
                            <small style="font-size: 0.9rem; color: #666; font-weight: 500;">/đêm</small>
                        </div>
                    </div>
                    <form action="booking.php" method="POST">
                        <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                        <input type="hidden" name="price" value="<?php echo $room['price']; ?>">
                        <div style="background: #fff; padding: 15px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #febb02;">
                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: 700; font-size: 0.85rem; color: #333; text-transform: uppercase;">Nhận phòng</label>
                                <input type="date" name="check_in" required style="width: 100%; border: 1px solid #ccc; padding: 10px; border-radius: 4px; margin-top: 5px; font-weight: 600;">
                            </div>
                            <div>
                                <label style="font-weight: 700; font-size: 0.85rem; color: #333; text-transform: uppercase;">Trả phòng</label>
                                <input type="date" name="check_out" required style="width: 100%; border: 1px solid #ccc; padding: 10px; border-radius: 4px; margin-top: 5px; font-weight: 600;">
                            </div>
                        </div>
                        <button type="submit" name="btn_book" class="btn-search-booking" style="border-radius: 4px; width: 100%; padding: 12px; font-size: 1.1rem; color: #000; background: #febb02; border: none; font-weight: bold; cursor: pointer;">ĐẶT PHÒNG NGAY</button>
                    </form>
                </div>
                <div style="background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #eee; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <div style="padding: 15px; border-bottom: 1px solid #eee; background: #f8f9fa;">
                        <h4 style="margin: 0; font-size: 1rem; color: #003580; font-weight: 700;"><i class="fa fa-map-marked-alt" style="color: #0071c2;"></i> Vị trí</h4>
                    </div>
                    <div class="side-map-container">
                        <iframe src="https://maps.google.com/maps?q=<?php echo $map_address; ?>&output=embed&z=14" width="100%" height="200" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                    <a href="https://maps.google.com/?q=<?php echo $map_address; ?>" target="_blank" style="display: block; text-align: center; padding: 10px; color: #0071c2; font-size: 0.9rem; text-decoration: none; font-weight: 600;">Xem bản đồ lớn <i class="fa fa-external-link-alt"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="imgLightbox" class="lightbox-modal">
    <span class="close-lightbox" onclick="closeLightbox()">×</span>
    
    <button class="lb-nav-btn lb-prev" onclick="changeLbSlide(-1)"><i class="fa fa-chevron-left"></i></button>
    <button class="lb-nav-btn lb-next" onclick="changeLbSlide(1)"><i class="fa fa-chevron-right"></i></button>

    <img class="lightbox-content" id="imgFull">
    <div class="lb-counter" id="lbCounter"></div>
</div>

<script>
    // 1. Slider Nhỏ (Trang chính)
    let currentIndex = 0;
    const track = document.getElementById('sliderTrack');
    const totalItems = <?php echo count($images); ?>;
    const indicators = document.querySelectorAll('.indicator');

    function updateSlider() {
        if (track && totalItems > 0) {
            track.style.transform = `translateX(-${currentIndex * 100}%)`;
            indicators.forEach(ind => ind.classList.remove('active'));
            if (indicators.length > 0) indicators[currentIndex].classList.add('active');
        }
    }
    function moveSlide(direction) {
        currentIndex += direction;
        if (currentIndex < 0) currentIndex = totalItems - 1;
        else if (currentIndex >= totalItems) currentIndex = 0;
        updateSlider();
    }
    function goToSlide(index) { currentIndex = index; updateSlider(); }

    // 2. Lightbox (Màn hình đen Full + Next/Prev)
    const galleryImages = <?php echo json_encode($images); ?>; // Lấy mảng ảnh từ PHP sang JS
    let lbIndex = 0;

    function openLightbox(index) {
        lbIndex = index;
        updateLightboxImage();
        document.getElementById('imgLightbox').style.display = "flex";
    }

    function closeLightbox() {
        document.getElementById('imgLightbox').style.display = "none";
    }

    function changeLbSlide(direction) {
        lbIndex += direction;
        if (lbIndex < 0) lbIndex = galleryImages.length - 1;
        else if (lbIndex >= galleryImages.length) lbIndex = 0;
        updateLightboxImage();
    }

    function updateLightboxImage() {
        const fullImg = document.getElementById('imgFull');
        const img = galleryImages[lbIndex];
        fullImg.src = /^https?:\/\//i.test(img) ? img : "uploads/" + img;
        document.getElementById('lbCounter').innerText = (lbIndex + 1) + " / " + galleryImages.length;
    }

    // Đóng khi click ra ngoài
    window.onclick = function(event) {
        if (event.target == document.getElementById('imgLightbox')) closeLightbox();
    }
    
    // Phím tắt (ESC, Mũi tên)
    document.addEventListener('keydown', function(e) {
        if(document.getElementById('imgLightbox').style.display == "flex") {
            if (e.key === "Escape") closeLightbox();
            if (e.key === "ArrowLeft") changeLbSlide(-1);
            if (e.key === "ArrowRight") changeLbSlide(1);
        }
    });

    document.addEventListener('DOMContentLoaded', updateSlider);
    window.moveSlide = moveSlide;
    window.goToSlide = goToSlide;
    window.openLightbox = openLightbox; 
    window.closeLightbox = closeLightbox;
    window.changeLbSlide = changeLbSlide;

    // --- KÍCH HOẠT HIỆU ỨNG 360 ĐỘ ---
    <?php if(!empty($room['image_360'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        pannellum.viewer('panorama', {
            "type": "equirectangular",
            "panorama": "<?php echo media_url($room['image_360']); ?>",
            "autoLoad": true,
            "compass": false,
            "mouseViewMode": "drag"
        });
    });
    <?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>
