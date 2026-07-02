-- Add call tracking columns to enquiries table
ALTER TABLE enquiries 
ADD COLUMN IF NOT EXISTS last_call_time DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS call_count INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS call_log TEXT DEFAULT NULL;

-- call_log will store JSON array of call timestamps
-- Example: [{"time": "2025-12-24 10:30:00", "duration": null}, ...]
