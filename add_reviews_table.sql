-- Create reviews table for customer ratings and feedback
-- Run this SQL in your MySQL database

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    customer_name VARCHAR(191) NOT NULL,
    customer_email VARCHAR(191) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    INDEX idx_doctor_status (doctor_id, status),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add some sample data (optional - remove if not needed)
-- INSERT INTO reviews (doctor_id, customer_name, customer_email, rating, review_text, status) 
-- VALUES (1, 'John Doe', 'john@example.com', 5, 'Excellent doctor! Very caring and professional.', 'approved');
