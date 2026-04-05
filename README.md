# Hotel Booking on Render Free + MongoDB Atlas

Project nay da duoc chuyen sang huong:

- Render web service `free`
- MongoDB Atlas `free` (M0 cluster)
- Khong dung Render database
- Khong dung persistent disk

## Cac thay doi chinh

- `render.yaml`: web free, nhan ket noi qua `MONGODB_URI`
- `includes/db.php`: them adapter MongoDB Atlas cho dung tap query hien tai
- `hotel_booking.sql`: du lieu goc, duoc app tu dong seed vao MongoDB khi collection `users` dang rong
- Upload file moi van bi tat trong che do free de tranh mat du lieu local

## Cach deploy

1. Tao MongoDB Atlas free cluster.
2. Tao database user trong Atlas.
3. Trong `Network Access`, them IP `0.0.0.0/0` de Render co the ket noi.
4. Lay connection string dang `mongodb+srv://...`
5. Tren Render, chon `New` -> `Blueprint`
6. Chon repo nay.
7. O bien moi truong `MONGODB_URI`, dan connection string Atlas.
8. Deploy.

## Co che seed du lieu

- Lan chay dau tien, neu collection `users` dang rong, app se doc `hotel_booking.sql`
- App se seed cac collection:
  - `users`
  - `rooms`
  - `room_images`
  - `bookings`
  - `favorites`
  - `payments`
- Tai khoan admin mac dinh:
  - `admin@gmail.com`
  - `123456`

## Gioi han che do free

- Avatar moi: tat
- Upload anh phong moi: tat
- Them sua du lieu text: duoc
- Booking, login, admin dashboard: duoc

## Luu y

- App hien van dung plain-text password theo logic cu.
- Render free web co the sleep khi khong co traffic.
- MongoDB Atlas free co gioi han dung luong.
