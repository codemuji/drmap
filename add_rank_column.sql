-- Add rank column to doctors table for custom ordering
-- Doctors with a rank number will appear first (sorted by rank ASC)
-- Doctors with NULL rank will appear after ranked doctors
-- Run this SQL in your MySQL database

ALTER TABLE doctors 
ADD COLUMN rank INT DEFAULT NULL AFTER practice_city,
ADD INDEX idx_rank (rank);

-- Optional: Set some initial ranks
-- UPDATE doctors SET rank = 1 WHERE id = 1;
-- UPDATE doctors SET rank = 2 WHERE id = 2;
