FROM mysql:8.0

COPY hotel_booking.sql /docker-entrypoint-initdb.d/01-hotel-booking.sql
COPY database/init/02-seed-admin.sh /docker-entrypoint-initdb.d/02-seed-admin.sh

RUN chmod +x /docker-entrypoint-initdb.d/02-seed-admin.sh
