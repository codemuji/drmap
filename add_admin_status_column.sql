-- Add admin_status column to enquiries table
-- This allows admin to track enquiry status independently from doctors

ALTER TABLE enquiries 
ADD COLUMN admin_status ENUM('new', 'contacted', 'completed', 'closed') DEFAULT 'new' AFTER status;

-- Update existing rows to have default admin_status
UPDATE enquiries SET admin_status = 'new' WHERE admin_status IS NULL;
