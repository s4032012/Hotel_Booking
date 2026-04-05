-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th3 31, 2026 lúc 08:55 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `hotel_booking`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `room_id`, `check_in_date`, `check_out_date`, `total_price`, `status`, `created_at`) VALUES
(15, 1, 11, '2025-12-11', '2025-12-12', 3000000.00, 'confirmed', '2025-12-09 20:27:05'),
(21, 1, 33, '2026-03-31', '2026-04-02', 3000000.00, 'confirmed', '2026-03-29 07:32:01');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','credit_card','transfer') DEFAULT 'cash',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('paid','unpaid') DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `room_type` varchar(50) NOT NULL,
  `capacity` int(11) DEFAULT 2,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('available','maintenance') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `has_pool` tinyint(1) DEFAULT 0,
  `has_breakfast` tinyint(1) DEFAULT 0,
  `has_parking` tinyint(1) DEFAULT 0,
  `image_360` varchar(255) DEFAULT NULL,
  `capture_time` datetime DEFAULT NULL,
  `capture_location` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `rooms`
--

INSERT INTO `rooms` (`id`, `room_name`, `address`, `room_type`, `capacity`, `price`, `description`, `image`, `status`, `created_at`, `has_pool`, `has_breakfast`, `has_parking`, `image_360`, `capture_time`, `capture_location`) VALUES
(11, 'Kin Hotel Onsen Edition', '40 Hai Bà Trưng, Quận 1, 700000 TP. Hồ Chí Minh, Việt Nam', 'Standard', 10, 300000.00, 'Nằm ở TP. Hồ Chí Minh và cách Nhà hát Thành phố 2 phút đi bộ, Kin Hotel Onsen Edition cung cấp dịch vụ tiền sảnh, các phòng không hút thuốc, sân hiên, Wi-Fi miễn phí ở toàn bộ chỗ nghỉ và quầy bar. Khách sạn 4 sao này có dịch vụ phòng và quầy lễ tân 24 giờ. Du khách có thể thưởng thức đồ uống tại bar thức ăn nhẹ.\r\n\r\nKhách sạn sẽ cung cấp cho khách các phòng được trang bị điều hòa có bàn làm việc, ấm đun nước, minibar, két an toàn, TV màn hình phẳng và phòng tắm riêng với vòi sen. Tại Kin Hotel Onsen Edition, các phòng được thiết kế có ga trải giường và khăn tắm.\r\n\r\nCác điểm tham quan nổi tiếng gần chỗ nghỉ bao gồm Trung tâm Thương mại Vincom Center A, Ủy ban nhân dân Thành phố Hồ Chí Minh và Bưu điện Trung tâm. Sân bay Quốc tế Tân Sơn Nhất cách 6 km.\r\n\r\nCác cặp đôi đặc biệt thích địa điểm này — họ cho điểm 9,6 khi đánh giá chuyến đi hai người.', '1765311875_759441561.jpg', 'available', '2025-12-09 20:24:35', 1, 1, 1, NULL, NULL, NULL),
(12, 'La Siesta Premium Saigon', '182 Lý Tự Trọng, Quận 1, TP. Hồ Chí Minh, Việt Nam', 'Standard', 2, 650000.00, 'La Siesta Premium Saigon có hồ bơi ngoài trời, trung tâm thể dục, khu vườn và sân hiên ở TP. Hồ Chí Minh. Chỗ nghỉ này có các tiện nghi như nhà hàng và quầy bar. Chỗ nghỉ cung cấp lễ tân 24/24, dịch vụ đưa đón sân bay, dịch vụ phòng và Wi-Fi miễn phí ở toàn bộ chỗ nghỉ.\r\n\r\nCác căn tại khách sạn được trang bị điều hòa, khu vực ghế ngồi, TV màn hình phẳng có truyền hình cáp, két an toàn, phòng tắm riêng, vòi xịt/chậu rửa vệ sinh, đồ vệ sinh cá nhân miễn phí và máy sấy tóc. Các phòng đều có ấm đun nước, trong đó một số phòng có ban công và một số khác thì nhìn ra thành phố. Tại La Siesta Premium Saigon, các phòng đều đi kèm với ga trải giường và khăn tắm.\r\n\r\nChỗ nghỉ có phục vụ bữa sáng thực đơn buffet, thực đơn à la carte hoặc kiểu Á.\r\n\r\nĐi xe đạp là hoạt động được ưa chuộng trong khu vực. Ngoài ra, khách sạn 5 sao này có dịch vụ thuê xe đạp.\r\n\r\nCác điểm tham quan nổi tiếng gần chỗ nghỉ bao gồm Chợ Bến Thành, Bảo tàng Thành phố Hồ Chí Minh và Trung tâm mua sắm Takashimaya Việt Nam. Sân bay Quốc tế Tân Sơn Nhất cách 7 km.', '1765314391_462029553.jpg', 'available', '2025-12-09 21:06:31', 0, 1, 0, NULL, NULL, NULL),
(13, '9TRIP STAY in Soho Residence - Service Apartment', '100 Cô Giang, Quận 1, TP. Hồ Chí Minh, Việt Nam ', 'Standard', 4, 650000.00, 'Cách Bảo tàng Mỹ thuật chưa đến 1 km, 9TRIP STAY in Soho Residence - Service Apartment có khu vườn, nhà hàng, điều hòa, sân hiên và Wi-Fi miễn phí.\r\n\r\nVới bếp gồm tủ lạnh và lò vi sóng, mỗi căn cũng được thiết kế có két an toàn, TV màn hình phẳng truyền hình cáp, tiện nghi ủi, bàn làm việc và khu vực ghế ngồi với giường sofa. Tất cả các căn đều được thiết kế có ban công nhìn ra thành phố.\r\n\r\nCăn hộ có cả dịch vụ cho thuê xe đạp và dịch vụ cho thuê ô tô.\r\n\r\nCác điểm tham quan nổi tiếng gần 9TRIP STAY in Soho Residence - Service Apartment bao gồm Trung tâm mua sắm Takashimaya Việt Nam, Chợ Bến Thành và Công viên Tao Đàn. Chỗ nghỉ cách Sân bay Quốc tế Tân Sơn Nhất 8 km và cung cấp dịch vụ đưa đón sân bay mất phí.', '1765314603_725777778.jpg', 'available', '2025-12-09 21:10:03', 0, 1, 1, NULL, NULL, NULL),
(14, 'Melia Ho Tram Beach Resort', 'Coastal Road, Ho Tram Hamlet, Ho Tram Commune,  TP. Hồ Chí Minh, Vietnam, Ho Tram, Việt Nam', 'Căn hộ', 2, 560000.00, 'Trải nghiệm dịch vụ đẳng cấp thế giới ở Melia Ho Tram Beach Resort\r\nQuay mặt ra bãi biển ở khu du lịch Hồ Tràm, Melia Ho Tram Beach Resort cung cấp chỗ nghỉ 5 sao và có hồ bơi ngoài trời, khu vườn, phòng xông hơi khô cũng như nhà hàng. Chỗ nghỉ có một loạt tiện nghi thể thao dưới nước, khu vực bãi biển riêng cũng như quầy bar và sân tennis. Nơi đây cung cấp dịch vụ lễ tân 24 giờ, dịch vụ đưa đón sân bay, CLB trẻ em và WiFi miễn phí trong toàn bộ khuôn viên.\r\n\r\nPhòng nghỉ tại resort được trang bị máy điều hòa, tủ để quần áo, máy pha cà phê, minibar, két an toàn, TV màn hình phẳng, ấm đun nước và phòng tắm riêng với vòi sen. Một số phòng có sân hiên trong khi các phòng khác nhìn ra biển.\r\n\r\nChỗ nghỉ phục vụ bữa sáng buffet, bữa sáng kiểu lục địa hoặc kiểu Á hằng ngày.\r\n\r\nMelia Ho Tram Beach Resort có sân chơi cho trẻ em. Du khách có thể chơi golf mini cũng như thuê xe đạp/xe hơi tại chỗ nghỉ.\r\n\r\nMelia Ho Tram Beach Resort nằm cách Khu du lịch sinh thái và văn hóa Hồ Mây 44 km trong khi Bạch Dinh cách đó 45 km. Sân bay gần nhất là sân bay quốc tế Tân Sơn Nhất, nằm trong bán kính 105 km từ resort.', '1765314742_550957544.jpg', 'available', '2025-12-09 21:12:22', 1, 1, 1, NULL, NULL, NULL),
(15, '9Trip Stay in Dalat Center Residence - 4 Star Service Apartment', '10 Phan Bội Châu, Đà Lạt, Việt Nam', 'Căn hộ', 6, 500000.00, 'Nằm cách Quảng trường Lâm Viên 17 phút đi bộ, 9Trip Stay in Dalat Center Residence - 4 Star Service Apartment cung cấp chỗ nghỉ có xe đạp miễn phí, quầy bar và dịch vụ đưa đón miễn phí. WiFi miễn phí có sẵn ở toàn bộ chỗ nghỉ.\r\n\r\nMột số căn còn có bếp được trang bị tủ lạnh, lò vi sóng và minibar.\r\n\r\nCăn hộ có dịch vụ cho thuê ô tô.\r\n\r\nCác điểm tham quan nổi tiếng gần 9Trip Stay in Dalat Center Residence - 4 Star Service Apartment bao gồm Hồ Xuân Hương, Công viên Yersin và Khách sạn Hằng Nga. Sân bay Liên Khương cách 28 km.', '1765314917_499919891.jpg', 'available', '2025-12-09 21:15:17', 0, 1, 1, NULL, NULL, NULL),
(16, 'Fusion Original Saigon Centre', '65 Đường Lê Lợi Takashimaya Saigon Centre, Quận 1, TP. Hồ Chí Minh, Việt Nam ', 'VIP', 2, 1500000.00, 'Tận hưởng dịch vụ đỉnh cao, đẳng cấp thế giới tại Fusion Original Saigon Centre\r\nNằm tại vị trí thuận tiện ở trung tâm TP. Hồ Chí Minh, Fusion Original Saigon Centre cung cấp Wi-Fi miễn phí ở toàn bộ chỗ nghỉ và quầy bar. Ngoài nhà hàng, chỗ nghỉ còn có hồ bơi ngoài trời, trung tâm thể dục và phòng xông hơi khô. Đây là chỗ nghỉ không hút thuốc và nằm cách Trung tâm mua sắm Takashimaya Việt Nam 3 phút đi bộ.\r\n\r\nTất cả các phòng tại khách sạn được trang bị điều hòa, khu vực ghế ngồi, TV màn hình phẳng có truyền hình cáp, két an toàn, phòng tắm riêng, vòi sen, đồ vệ sinh cá nhân miễn phí và máy sấy tóc. Tại Fusion Original Saigon Centre, các phòng đều có ga trải giường và khăn tắm.\r\n\r\nChỗ nghỉ có các lựa chọn thực đơn buffet, thực đơn à la carte hoặc kiểu lục địa cho bữa sáng.\r\n\r\nChỗ nghỉ còn cung cấp dịch vụ văn phòng để khách sử dụng máy ATM trong khuôn viên tại Fusion Original Saigon Centre. Thành thạo tiếng Anh và tiếng Việt, đội ngũ nhân viên tại lễ tân luôn túc trực để hỗ trợ khách.\r\n\r\nCác điểm tham quan nổi tiếng gần khách sạn bao gồm Trung tâm thương mại Vincom, Ủy ban nhân dân Thành phố Hồ Chí Minh và Bảo tàng Thành phố Hồ Chí Minh. Sân bay Quốc tế Tân Sơn Nhất cách 7 km.', '1765315153_506468119.jpg', 'available', '2025-12-09 21:19:13', 1, 1, 1, NULL, NULL, NULL),
(17, 'Silverland Mây Hotel', '28-30 Thi Sach Street, Sai Gon Ward, Quận 1, TP. Hồ Chí Minh, Việt Nam', 'Resort', 2, 850000.00, 'Nằm cách Sông Bạch Đằng 500 m, May Hotel có hồ bơi trong nhà, trung tâm thể dục và tiện nghi xông hơi khô. Chỗ nghỉ này cung cấp WiFi miễn phí ở tất cả các khu vực và chỗ đỗ xe miễn phí trong khuôn viên.\r\n\r\nPhòng nghỉ rộng rãi tại đây có máy điều hòa, tủ để quần áo, két an toàn cá nhân, TV, khu vực ghế ngồi và sàn lát gạch. Trong phòng còn được trang bị ấm đun nước điện và minibar. Phòng tắm riêng đi kèm máy sấy tóc, bồn tắm, dép đi trong phòng và đồ vệ sinh cá nhân miễn phí.\r\n\r\nNơi đây cung cấp dịch vụ lễ tân 24 giờ. Du khách có thể thuê xe đạp/xe hơi để khám phá khu vực hoặc đến bàn đặt tour để được hỗ trợ thu xếp việc tham quan và đi lại.\r\n\r\nSilverland May hotel có nhà hàng phục vụ các món ngon của Việt Nam và quốc tế trong khi quán bar cung cấp tuyển chọn các loại đồ uống. Du khách cũng có thể dùng bữa trong sự riêng tư thông qua dịch vụ phòng.\r\n\r\nMay Hotel nằm ở trung tâm Thành phố Hồ Chí Minh, chỉ cách Nhà Hát Lớn và Trung tâm mua sắm Vincom 300 m. Dinh Thống Nhất và Chợ Bến Thành nổi tiếng đều nằm trong bán kính 7 phút lái xe từ chỗ nghỉ này.', '1765315969_472383216.jpg', 'available', '2025-12-09 21:32:49', 1, 1, 1, NULL, NULL, NULL),
(18, 'Mercure Nha Trang Beach', '88A Trần Phú, Lộc Thọ, Nha Trang, Khánh Hòa 57100', 'Deluxe', 4, 1400000.00, 'Tọa lạc ở Nha Trang, cách Bãi biển Nha Trang 4 phút đi bộ, Mercure Nha Trang Beach cung cấp chỗ nghỉ giáp biển, có các tiện nghi như nhà hàng. Ngoài trung tâm thể dục, khách sạn 4 sao này có các phòng được trang bị điều hòa và Wi-Fi miễn phí, trong đó mỗi phòng sẽ có phòng tắm riêng. Chỗ nghỉ này cung cấp dịch vụ phòng, quầy lễ tân 24 giờ và dịch vụ thu đổi ngoại tệ cho khách.\r\n\r\nTại khách sạn, phòng nào cũng có bàn làm việc. Mỗi phòng đều có két an toàn và một số phòng nhìn ra thành phố. Các phòng ở Mercure Nha Trang Beach được trang bị TV và máy sấy tóc.\r\n\r\nKhách tại chỗ nghỉ có thể thưởng thức bữa sáng thực đơn buffet hoặc kiểu lục địa.\r\n\r\nTại Mercure Nha Trang Beach, khách có thể sử dụng hồ bơi trong nhà.\r\n\r\nKhách sạn cách Tháp Trầm Hương 16 phút đi bộ. Sân bay quốc tế Cam Ranh cách 33 km, đồng thời chỗ nghỉ có cung cấp dịch vụ đưa đón sân bay mất phí.', '1767986441_770051981.jpg', 'available', '2026-01-09 19:20:41', 0, 1, 1, NULL, NULL, NULL),
(19, 'Hue Serene Palace Hotel', '21 Kiệt 42 Nguyễn Công Trứ, Phú Hội, Huế, Thành phố Huế, Việt Nam', 'Deluxe', 2, 1350000.00, 'Hue Serene Palace Hotel is strategically located within 1 km from the famous Huong River and Trang Tien Bridge. It features modern rooms equipped and offers free Wi-Fi access throughout the property.\r\n\r\nThe hotel is just 2 km to Hue Citadel and about 3 km to Hue Train Station. Hue Airport is 15 km away, while Da Nang International Airport is approximately 90 km away.\r\n\r\nFitted with parquet flooring, air-conditioned rooms are well-furnished with a wardrobe, personal safe, a flat-screen cable TV and seating area. Complimentary tea and coffee making facilities are also included. The en suite bathroom comes with a hairdryer, bathrobes and free toiletries.\r\n\r\nHue Serene Palace Hotel has a tour desk that can assist with sightseeing and travel arrangements. Luggage storage, laundry services can be requested from the 24-hour front desk. Other conveniences include a business center, currency exchange and bicycle/car rental services.', '1767986548_529941314.jpg', 'available', '2026-01-09 19:22:28', 0, 0, 1, NULL, NULL, NULL),
(20, 'Grand Fusion Point Hotel', '37 Trần Quang Khải, Phú Hội, Huế, Thành phố Huế 531310, Việt Nam', 'Standard', 5, 650000.00, 'Chỗ nghỉ thoải mái: Grand Fusion Point Hotel ở Huế có phòng gia đình với máy điều hòa, phòng tắm riêng và tầm nhìn ra thành phố. Mỗi phòng đều có bàn làm việc, TV và WiFi miễn phí.\r\n\r\nTiện nghi giải trí: Du khách có thể thư giãn trên sân thượng tắm nắng hoặc trong vườn. Khách sạn cung cấp WiFi miễn phí ở khu vực công cộng, đảm bảo kết nối trong suốt thời gian lưu trú.\r\n\r\nDịch vụ tiện lợi: Khách sạn cung cấp dịch vụ nhận phòng và trả phòng riêng, lễ tân 24 giờ, dịch vụ concierge và quầy hỗ trợ tour du lịch. Các dịch vụ bổ sung bao gồm dịch vụ phòng, cho thuê xe đạp và bãi đậu xe miễn phí tại chỗ.\r\n\r\nĐiểm tham quan lân cận: Cầu Trường Tiền cách đó 12 phút đi bộ. Chợ Đông Ba nằm cách khách sạn 1,7 km. Các điểm khác gần đó bao gồm Cung An Định và Nhà thờ Dòng Chúa Cứu Thế. Sân bay Quốc tế Phú Bài cách đó 13 km.', '1767986663_739207852.jpg', 'available', '2026-01-09 19:24:23', 0, 0, 1, NULL, NULL, NULL),
(21, 'Colline Dalat', '10 Phan Bội Châu, phường Xuân Hương, Đà Lạt', 'VIP', 2, 17500000.00, 'Nằm ở trung tâm thành phố Đà Lạt, cách Quảng trường Lâm Viên 500 m, Colline Dalat có quầy bar và các phòng với truy cập Wi-Fi miễn phí. Khách sạn này nằm cách Hồ Xuân Hương 200 m và cách công viên Yersin 500 m.\r\n\r\nCác phòng tại đây được trang bị máy điều hòa, TV truyền hình cáp màn hình phẳng, ấm đun nước, chậu rửa vệ sinh, đồ vệ sinh cá nhân miễn phí, bàn làm việc, phòng tắm riêng và tầm nhìn ra quang cảnh thành phố. Ngoài ra còn có tủ quần áo.\r\n\r\nKhách nghỉ tại Colline Dalat có thể thưởng thức bữa sáng tự chọn.\r\n\r\nNhân viên thông thạo tiếng Anh và tiếng Việt tại lễ tân 24 giờ luôn sẵn sàng hỗ trợ quý khách.\r\n', '1767986841_784359102.jpg', 'available', '2026-01-09 19:27:21', 1, 1, 1, NULL, NULL, NULL),
(22, 'Le Macaron City Center - Boutique Hotel Đà Lạt', '52 Đường Trương Công Định, Phường 1, Đà Lạt, Lâm Đồng 66000, Việt Nam', 'Deluxe', 7, 1200000.00, 'Chỗ nghỉ thoải mái: Le Macaron City Center - Boutique Hotel Đà Lạt ở Đà Lạt cung cấp các phòng nghỉ thoải mái với phòng tắm riêng, ban công và tầm nhìn ra thành phố. Mỗi phòng đều có áo choàng tắm, đồ vệ sinh cá nhân miễn phí và cách âm để đảm bảo kỳ nghỉ yên tĩnh.\r\n\r\nTiện nghi đặc biệt: Du khách có thể tận hưởng sân hiên, ban công và WiFi miễn phí. Các tiện nghi bổ sung bao gồm quán cà phê, khu vực ghế ngồi ngoài trời và bảo vệ 24/24. Nhận phòng và trả phòng riêng, thang máy, dịch vụ concierge và dịch vụ phòng giúp nâng cao trải nghiệm lưu trú.\r\n\r\nVị trí đắc địa: Nằm cách sân bay Liên Khương 29 km, khách sạn chỉ cách Quảng trường Lâm Viên một quãng đi bộ ngắn (19 phút) và gần Hồ Xuân Hương (2 km). Các điểm tham quan lân cận bao gồm Công viên Yersin và Nhà Điên (Crazy House), mỗi nơi cách đó 2 km. Du khách có thể tham gia hoạt động chèo thuyền trong khu vực xung quanh.', '1767986950_485314689.jpg', 'available', '2026-01-09 19:29:10', 0, 1, 1, NULL, NULL, NULL),
(28, 'Phòng khách', 'Bùi Quang Là', 'Standard', 2, 200000.00, 'Phòng ', '1774682487_IMG_8131.jpeg', 'available', '2026-03-28 07:21:27', 0, 1, 0, '360_1774682487.jpg', '2026-03-28 14:21:27', ''),
(29, 'Jd', 'Gò Vấp', 'Standard', 2, 300000.00, 'Phòng', '1774682691_IMG_8130.jpeg', 'available', '2026-03-28 07:24:51', 0, 1, 1, '', '2026-03-28 14:24:51', ''),
(31, 'ABC', '40 Hoang Hoa Tham', 'Standard', 2, 500000.00, 'Vipp', '1774768907_IMG_0564.png', 'available', '2026-03-29 07:21:47', 1, 0, 1, '360_1774768907.jpg', '2026-03-29 14:21:47', '10.85714954867563,106.77473873945057'),
(32, 'TRI', '76 Le Thi Rieng', 'Standard', 2, 750000.00, 'yt', '1774769173_IMG_0564.png', 'available', '2026-03-29 07:26:13', 1, 0, 1, '', '2026-03-29 14:26:13', ''),
(33, 'LLL', '111 le loi', 'Standard', 4, 1500000.00, 'Phong doi rong rai', '1774769434_Anh1.jpg', 'available', '2026-03-29 07:30:34', 1, 1, 1, '360_1774769434.jpg', '2026-03-29 14:30:34', '10.857251020803584,106.77480235765762'),
(35, 'Group 4', '112 Quang Trung', 'Standard', 2, 500000.00, 'Phòng view', '1774772309_fxn 2026-03-19 093812C6E6AB602BEB.jpeg', 'available', '2026-03-29 08:18:29', 1, 1, 1, '360_1774772309.jpeg', '2026-03-29 15:18:29', ''),
(36, 'Hekkoi', '555', 'Deluxe', 2, 5000000.00, 'Đẹp', '1774772625_73ed8810-3e43-4d8d-a4c5-6ad82963f491.jpeg', 'available', '2026-03-29 08:23:45', 1, 1, 1, '360_1774772625.jpeg', '2026-03-29 15:23:45', '10.830862919091368,106.64203540751625'),
(37, 'Mường Thanh', '777', 'VIP', 2, 10000000.00, 'Quá đẹp', '1774773635_image.jpg', 'available', '2026-03-29 08:40:35', 1, 1, 1, '360_1774773635.jpeg', '2026-03-29 15:40:35', '10.830773515351941,106.64192937594768');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_images`
--

CREATE TABLE `room_images` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `room_images`
--

INSERT INTO `room_images` (`id`, `room_id`, `image_path`) VALUES
(42, 11, '1765311875_0_759440925.jpg'),
(43, 11, '1765311875_1_769077368.jpg'),
(44, 11, '1765311875_2_769077363.jpg'),
(45, 11, '1765311875_3_759440855.jpg'),
(46, 11, '1765311875_4_759437489.jpg'),
(47, 11, '1765311875_5_759437612.jpg'),
(48, 11, '1765311875_6_759437621.jpg'),
(49, 11, '1765311875_7_760777567.jpg'),
(50, 11, '1765311875_8_759440890.jpg'),
(51, 12, '1765314391_0_462029569.jpg'),
(52, 12, '1765314391_1_466280097.jpg'),
(53, 12, '1765314391_2_466279758.jpg'),
(54, 12, '1765314391_3_466281899.jpg'),
(55, 12, '1765314391_4_466279523.jpg'),
(56, 12, '1765314391_5_466279510.jpg'),
(57, 12, '1765314391_6_466569819.jpg'),
(58, 12, '1765314391_7_466281836.jpg'),
(59, 12, '1765314391_8_462029574.jpg'),
(60, 13, '1765314603_0_727105074.jpg'),
(61, 13, '1765314603_1_711040787.jpg'),
(62, 13, '1765314603_2_730457942.jpg'),
(63, 13, '1765314603_3_730506085.jpg'),
(64, 13, '1765314603_4_496380223.jpg'),
(65, 13, '1765314603_5_496380232.jpg'),
(66, 13, '1765314603_6_496380103.jpg'),
(67, 13, '1765314603_7_496381302.jpg'),
(68, 14, '1765314742_0_554649151.jpg'),
(69, 14, '1765314742_1_203459267.jpg'),
(70, 14, '1765314742_2_203459258.jpg'),
(71, 14, '1765314742_3_431598033.jpg'),
(72, 14, '1765314742_4_544611295.jpg'),
(73, 14, '1765314742_5_544609913.jpg'),
(74, 14, '1765314742_6_544609916.jpg'),
(75, 14, '1765314742_7_544609911.jpg'),
(76, 14, '1765314742_8_544609899.jpg'),
(77, 15, '1765314917_0_634122437.jpg'),
(78, 15, '1765314917_1_499719139.jpg'),
(79, 15, '1765314917_2_774251267.jpg'),
(80, 15, '1765314917_3_505344942.jpg'),
(81, 15, '1765314917_4_774251268.jpg'),
(82, 15, '1765314917_5_774251273.jpg'),
(83, 15, '1765314917_6_634122441.jpg'),
(84, 15, '1765314917_7_634122981.jpg'),
(85, 16, '1765315153_0_358169184.jpg'),
(86, 16, '1765315153_1_358169338.jpg'),
(87, 16, '1765315153_2_358169138.jpg'),
(88, 16, '1765315153_3_358169099.jpg'),
(89, 16, '1765315153_4_358169239.jpg'),
(90, 16, '1765315153_5_359474876.jpg'),
(91, 16, '1765315153_6_358169355.jpg'),
(92, 16, '1765315153_7_358169296.jpg'),
(93, 16, '1765315153_8_359493269.jpg'),
(94, 16, '1765315153_9_358169129.jpg'),
(95, 17, '1765315969_0_472382205.jpg'),
(96, 17, '1765315969_1_509753580.jpg'),
(97, 17, '1765315969_2_470976036.jpg'),
(98, 17, '1765315969_3_486230054.jpg'),
(99, 17, '1765315969_4_470975997.jpg'),
(100, 17, '1765315969_5_486230140.jpg'),
(101, 17, '1765315969_6_470976045.jpg'),
(102, 17, '1765315969_7_472383298.jpg'),
(103, 17, '1765315969_8_470353675.jpg'),
(104, 18, '1767986441_0_765302323.jpg'),
(105, 18, '1767986441_1_776472327.jpg'),
(106, 18, '1767986441_2_765302314.jpg'),
(107, 18, '1767986441_3_765302315.jpg'),
(108, 18, '1767986441_4_765302312.jpg'),
(109, 18, '1767986441_5_770051979.jpg'),
(110, 18, '1767986441_6_770051987.jpg'),
(111, 18, '1767986441_7_770051984.jpg'),
(112, 18, '1767986441_8_770051980.jpg'),
(113, 18, '1767986441_9_770051981.jpg'),
(114, 18, '1767986441_10_800625697 (1).jpg'),
(115, 18, '1767986441_11_800625697.jpg'),
(116, 18, '1767986441_12_790250808.jpg'),
(117, 19, '1767986548_0_524401580.jpg'),
(118, 19, '1767986548_1_524401597.jpg'),
(119, 19, '1767986548_2_524401586.jpg'),
(120, 19, '1767986548_3_524401498.jpg'),
(121, 19, '1767986548_4_524401593.jpg'),
(122, 19, '1767986548_5_524401594.jpg'),
(123, 19, '1767986548_6_524401496.jpg'),
(124, 19, '1767986548_7_524401582.jpg'),
(125, 19, '1767986548_8_529941314.jpg'),
(126, 19, '1767986548_9_524401448.jpg'),
(127, 20, '1767986663_0_739209737.jpg'),
(128, 20, '1767986663_1_739207869.jpg'),
(129, 20, '1767986663_2_739207872.jpg'),
(130, 20, '1767986663_3_739212652.jpg'),
(131, 20, '1767986663_4_739212705.jpg'),
(132, 20, '1767986663_5_739212694.jpg'),
(133, 20, '1767986663_6_739212725.jpg'),
(134, 20, '1767986663_7_739212642.jpg'),
(135, 20, '1767986663_8_739207852.jpg'),
(136, 21, '1767986841_0_531924670.jpg'),
(137, 21, '1767986841_1_531924669.jpg'),
(138, 21, '1767986841_2_531924674.jpg'),
(139, 21, '1767986841_3_531916479.jpg'),
(140, 21, '1767986841_4_530958334.jpg'),
(141, 21, '1767986841_5_756473040.jpg'),
(142, 21, '1767986841_6_756472995.jpg'),
(143, 21, '1767986841_7_756472604.jpg'),
(144, 21, '1767986841_8_784359102.jpg'),
(145, 22, '1767986950_0_478517490.jpg'),
(146, 22, '1767986950_1_476246326.jpg'),
(147, 22, '1767986950_2_476246354.jpg'),
(148, 22, '1767986950_3_476246327.jpg'),
(149, 22, '1767986950_4_478517380.jpg'),
(150, 22, '1767986950_5_484199323.jpg'),
(151, 22, '1767986950_6_476261563.jpg'),
(152, 22, '1767986950_7_522955541.jpg'),
(158, 28, '1774682487_0_image.jpg'),
(159, 29, '1774682691_0_image.jpg'),
(162, 31, '1774768957_0_IMG_0570.jpeg'),
(163, 32, '1774769239_0_IMG_0570.jpeg'),
(164, 33, '1774769434_0_Anh2.jpg'),
(165, 33, '1774769450_0_Anh360.jpg'),
(166, 35, '1774772309_0_image.jpg'),
(167, 36, '1774772625_0_c2de9ba8-3263-4d40-941f-b87ed57b6296.jpeg'),
(168, 37, '1774773635_0_image.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `avatar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `role`, `created_at`, `avatar`) VALUES
(1, 'Group 4', 'admin@gmail.com', '123456', '0765933135', 'admin', '2025-11-25 19:31:13', 'user_1_1764985487.jpg'),
(18, 'Group 4', 'group4@gmail.com', '123456', '012345678', 'customer', '2026-03-29 11:54:11', NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Chỉ mục cho bảng `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_room` (`user_id`,`room_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Chỉ mục cho bảng `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Chỉ mục cho bảng `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `room_images`
--
ALTER TABLE `room_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT cho bảng `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT cho bảng `room_images`
--
ALTER TABLE `room_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `room_images`
--
ALTER TABLE `room_images`
  ADD CONSTRAINT `room_images_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
