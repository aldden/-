-- Script to ensure 'bookings' table has all necessary columns for payments, status, and times
-- Run this in your database (e.g., phpMyAdmin) if you encounter missing column errors

ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_status VARCHAR(50) DEFAULT 'pending';
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(100) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS account_name VARCHAR(150) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS account_number VARCHAR(100) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS arrival_time TIME DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS departure_time TIME DEFAULT NULL;
