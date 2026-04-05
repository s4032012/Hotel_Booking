document.addEventListener('DOMContentLoaded', function() {
    
    // --- XỬ LÝ THANH GIÁ TIỀN & Ô NHẬP LIỆU ---
    const priceRange = document.getElementById('priceRange');
    const priceInput = document.getElementById('priceInput');

    if (priceRange && priceInput) {
        
        // Hàm tạo dấu chấm phân cách hàng nghìn (VD: 1000000 -> 1.000.000)
        function formatCurrency(str) {
            // Xóa hết ký tự không phải số, chỉ giữ lại số
            let num = str.toString().replace(/\D/g, '');
            // Dùng Regex để thêm dấu chấm
            return num.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Hàm lấy số nguyên từ chuỗi có dấu chấm (VD: 1.000.000 -> 1000000)
        function parseCurrency(str) {
            return parseInt(str.replace(/\./g, '')) || 0;
        }

        // 1. KHI KÉO THANH SLIDER -> Cập nhật số tiền lên ô input (có dấu chấm)
        priceRange.addEventListener('input', function(e) {
            priceInput.value = formatCurrency(e.target.value);
        });

        // 2. KHI GÕ VÀO Ô INPUT -> Tự động thêm dấu chấm ngay lập tức
        priceInput.addEventListener('input', function(e) {
            // Lấy vị trí con trỏ chuột hiện tại (để tránh bị nhảy con trỏ khi format)
            let cursorPosition = this.selectionStart;
            let oldLength = this.value.length;

            // Format lại giá trị
            let formattedValue = formatCurrency(this.value);
            this.value = formattedValue;

            // Tính toán lại vị trí con trỏ (giúp gõ mượt hơn)
            let newLength = this.value.length;
            cursorPosition = cursorPosition + (newLength - oldLength);
            this.setSelectionRange(cursorPosition, cursorPosition);

            // Cập nhật thanh Slider bên dưới chạy theo
            let rawNumber = parseCurrency(this.value);
            if (rawNumber > 5000000) rawNumber = 5000000; // Giới hạn max
            priceRange.value = rawNumber;
        });
    }

    // --- CÁC HIỆU ỨNG KHÁC (Header Sticky...) ---
    const header = document.querySelector('.header');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.style.boxShadow = "0 4px 10px rgba(0,0,0,0.1)";
            } else {
                header.style.boxShadow = "0 2px 10px rgba(0,0,0,0.05)";
            }
        });
    }
});
/* --- XỬ LÝ MENU DROPDOWN (CLICK) --- */
document.addEventListener('DOMContentLoaded', function() {
    const userDropdown = document.querySelector('.user-dropdown');
    
    if (userDropdown) {
        const toggleBtn = userDropdown.querySelector('.dropdown-toggle');
        const menu = userDropdown.querySelector('.dropdown-menu');

        // 1. Khi bấm vào nút -> Bật/Tắt menu
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation(); // Ngăn chặn sự kiện lan ra ngoài
            menu.classList.toggle('show'); // Hiện/Ẩn menu
            userDropdown.classList.toggle('active'); // Để xoay icon
        });

        // 2. Khi bấm ra ngoài -> Tắt menu
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target)) {
                menu.classList.remove('show');
                userDropdown.classList.remove('active');
            }
        });
    }
});