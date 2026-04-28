-- Script to add payment columns to the existing 'bookings' table

ALTER TABLE bookings
ADD COLUMN payment_status VARCHAR(50) DEFAULT 'pending',
ADD COLUMN transaction_id VARCHAR(100) NULL;
