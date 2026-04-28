-- قم بتشغيل هذا الأمر في قاعدة البيانات لإضافة رقم المقعد للحجوزات
ALTER TABLE `bookings` ADD COLUMN `seat_number` VARCHAR(50) DEFAULT NULL AFTER `departure_time`;
