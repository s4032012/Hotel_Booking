# Hotel Booking on Render

Project nay da duoc chuan bi de deploy len Render bang Docker.

## Thanh phan da them

- `render.yaml`: tao 1 web service PHP va 1 private MySQL service.
- `Dockerfile`: chay app PHP/Apache tren Render.
- `database/`: image MySQL import truc tiep tu file dump `hotel_booking.sql`.
- `healthz.php`: endpoint health check cho Render.
- `includes/db.php`: doc cau hinh DB tu bien moi truong.

## Cach deploy

1. Day toan bo project, bao gom file `hotel_booking.sql`, len GitHub.
2. Tren Render, chon `New` -> `Blueprint`.
3. Ket noi repo chua project nay.
4. Render se doc `render.yaml` va tao 2 service:
   - `hotel-booking-mysql`
   - `hotel-booking-web`
5. Deploy Blueprint.

## Du lieu duoc import

- MySQL service se khoi tao tu file `hotel_booking.sql` trong lan chay dau tien.
- Dump hien tai da co san du lieu phong, room images, users, bookings va admin.
- Tai khoan admin trong dump hien tai:
  - Email: `admin@gmail.com`
  - Mat khau: `123456`

## Sau khi deploy

- Dang nhap admin bang tai khoan da co san trong DB dump.
- Neu ban muon giu du lieu uploads sau moi lan redeploy, giu nguyen persistent disk nhu trong `render.yaml`.
- Neu sau nay ban thay doi DB local va muon dong bo lai, can cap nhat file `hotel_booking.sql` roi tao moi MySQL service hoac import lai thu cong.

## Luu y

- App hien dang dung mat khau dang nhap dang plain text theo logic san co trong code.
- File SQL chi duoc Render import o lan khoi tao volume dau tien. Neu MySQL disk da co du lieu roi, Render se khong import lai dump nay tu dong.
