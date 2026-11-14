-- ========================================================================
-- ENGINE 1: DATA & INTEGRITY QUERIES FILE
-- Project: Gym Membership and Slot Register
-- Student USN: 01FE24ICA012 / 01FE24ICA016
-- ========================================================================

-- CREATE TABLES
CREATE TABLE IF NOT EXISTS members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    member_code VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL UNIQUE,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    address TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS gym_slots (
    slot_id INT AUTO_INCREMENT PRIMARY KEY,
    slot_name VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_capacity INT NOT NULL
);

CREATE TABLE IF NOT EXISTS slot_bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    slot_id INT NOT NULL,
    booking_date DATE NOT NULL,
    status ENUM('Confirmed', 'Cancelled') DEFAULT 'Confirmed',
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES gym_slots(slot_id) ON DELETE RESTRICT
);

-- SEED DATA (INITIAL INSERT QUERIES)
INSERT INTO gym_slots (slot_name, start_time, end_time, max_capacity) VALUES
('Morning Batch (6:00 AM - 7:30 AM)', '06:00:00', '07:30:00', 10),
('Evening Batch (6:00 PM - 7:30 PM)', '18:00:00', '19:30:00', 10);