-- Add practice_city column to doctors table
-- Run this once in your MySQL database (via CLI or phpMyAdmin)

ALTER TABLE doctors 
ADD COLUMN practice_city VARCHAR(191) DEFAULT NULL AFTER qualification;

-- Optional: populate existing rows if you want a default value
-- UPDATE doctors SET practice_city = 'Unknown' WHERE practice_city IS NULL;
