-- SQL Migration for DrMap Upgrades

-- 1. Create specialties table
CREATE TABLE IF NOT EXISTS specialties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL UNIQUE,
    icon VARCHAR(100) DEFAULT 'fa-user-doctor',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create hospitals table
CREATE TABLE IF NOT EXISTS hospitals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    address TEXT,
    city VARCHAR(191) DEFAULT 'Guwahati',
    phone VARCHAR(50),
    email VARCHAR(191),
    image VARCHAR(255),
    map_embed_url TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create doctor_hospital join table (many-to-many relationship)
CREATE TABLE IF NOT EXISTS doctor_hospital (
    doctor_id INT NOT NULL,
    hospital_id INT NOT NULL,
    PRIMARY KEY (doctor_id, hospital_id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed initial specialties
INSERT IGNORE INTO specialties (name, icon, sort_order) VALUES
('Cardiologist', 'fa-heartbeat', 1),
('Dermatologist', 'fa-user-md', 2),
('Orthopedic Surgeon', 'fa-bone', 3),
('Pediatrician', 'fa-baby', 4),
('Neurologist', 'fa-brain', 5),
('Ophthalmologist', 'fa-eye', 6),
('Dentist', 'fa-tooth', 7),
('General Physician', 'fa-stethoscope', 8),
('Gynecologist', 'fa-venus', 9),
('Pulmonologist', 'fa-lungs', 10),
('Urologist', 'fa-stethoscope', 11),
('Oncologist', 'fa-ribbon', 12),
('Gastroenterologist', 'fa-virus-covid', 13),
('ENT Specialist', 'fa-ear-listen', 14),
('Psychiatrist', 'fa-head-side-virus', 15);

-- 4. Create cities table (Req 32)
CREATE TABLE IF NOT EXISTS cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed initial cities
INSERT IGNORE INTO cities (name) VALUES 
('Guwahati'), 
('Tezpur'), 
('Kolkata'), 
('Delhi'),
('Dibrugarh');


-- Seed initial hospitals/clinics
INSERT IGNORE INTO hospitals (name, address, city, phone, email, image, description) VALUES
('Guwahati Metro Hospital', 'G.S. Road, Christian Basti, Guwahati, Assam 781005', 'Guwahati', '+91 361 234 5678', 'info@metrohospital.com', 'https://images.unsplash.com/photo-1587351021759-3e566b6af7cc?w=600&h=400&fit=crop', 'A state-of-the-art multi-specialty healthcare facility dedicated to providing top-quality medical care in Guwahati.'),
('Assam Valley Medical Center', 'VIP Road, Sixmile, Guwahati, Assam 781022', 'Guwahati', '+91 361 987 6543', 'contact@assamvalleymed.in', 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=600&h=400&fit=crop', 'Providing compassionate family and specialized medical treatments to the northeastern community.'),
('Northeast Heart and Brain Institute', 'Bhangagarh, near Medical College, Guwahati, Assam 781032', 'Guwahati', '+91 361 555 1234', 'care@nehbi.org', 'https://images.unsplash.com/photo-1586773860418-d37222d8fce3?w=600&h=400&fit=crop', 'Specialized institute offering world-class cardiac and neurological care under one roof.'),
('City Dental & Skin Clinic', 'Zoo Road, Tiniali, Guwahati, Assam 781003', 'Guwahati', '+91 361 444 8888', 'appointment@citydentalskin.com', 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?w=600&h=400&fit=crop', 'A modern, hygiene-focused dental and skin clinic offering cosmetic and general treatment.');
