# Hotel Booking on Render Free

Project nay da duoc chuyen sang huong deploy free-friendly tren Render:

- 1 web service `free`
- 1 Render Postgres database `free`
- Khong dung private MySQL service
- Khong dung persistent disk

## Cac thay doi chinh

- `render.yaml`: dung Render Postgres free va web service free.
- `includes/db.php`: tu dong chon Postgres khi co `DATABASE_URL`.
- `database/postgres_schema.sql`: schema Postgres cho runtime free.
- `hotel_booking.sql`: du lieu goc, duoc app tu dong seed vao Postgres khi DB rong.
- Upload file moi bi tat trong che do free de tranh mat du lieu do khong co persistent disk.

## Cach deploy

1. Push project len GitHub.
2. Tren Render, chon `New` -> `Blueprint`.
3. Chon repo nay.
4. Render se tao:
   - Postgres database: `hotel-booking-db`
   - Web service: `hotel-booking-web`
5. Bấm deploy.

## Co che seed du lieu

- Lan chay dau tien, app se tao bang Postgres neu chua co.
- Neu bang `users` dang rong, app se doc `hotel_booking.sql` va seed du lieu vao Postgres.
- Tai khoan admin mac dinh van la:
  - `admin@gmail.com`
  - `123456`

## Gioi han cua ban free

- Render free web khong co persistent disk.
- Vi vay:
  - khong upload avatar moi
  - khong upload anh phong moi len server
  - khi them phong moi o admin, he thong dung anh mac dinh co san trong repo
  - sua thong tin text van hoat dong

## Neu muon day du hon sau nay

Neu ban muon upload anh that su hoat dong ben vung, co 2 huong:

1. Nang cap len paid plan de dung persistent disk.
2. Chuyen upload sang Cloudinary/S3.

## Luu y

- App hien van dung plain-text password theo logic cu.
- Render free web co the sleep khi khong co traffic.
- Render free Postgres co gioi han dung luong va chinh sach het han theo tai khoan free hien hanh.
