-- Church Management Information System Database
-- Drop database if exists and create new
DROP DATABASE IF EXISTS church_cmis;
CREATE DATABASE church_cmis;
USE church_cmis;

-- Roles table
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Ministries table
CREATE TABLE ministries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Members table
CREATE TABLE members (
    mem_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    middle_initials VARCHAR(10),
    last_name VARCHAR(50) NOT NULL,
    dob DATE,
    gender ENUM('Male', 'Female', 'Other'),
    home_address_1 VARCHAR(255),
    home_address_2 VARCHAR(255),
    town VARCHAR(100),
    parish VARCHAR(100),
    contact_home VARCHAR(20),
    contact_work VARCHAR(20),
    email VARCHAR(100),
    next_of_kin_name VARCHAR(100),
    next_of_kin_address VARCHAR(255),
    next_of_kin_relation VARCHAR(50),
    next_of_kin_contact VARCHAR(20),
    next_of_kin_email VARCHAR(100),
    status ENUM('member', 'adherent', 'visitor') DEFAULT 'visitor',
    date_joined DATE,
    baptism_date DATE,
    membership_start DATE,
    passing_date DATE,
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Ministry members (allows up to 5 ministries per member)
CREATE TABLE ministry_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    ministry_id INT NOT NULL,
    role VARCHAR(50),
    date_joined DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(mem_id) ON DELETE CASCADE,
    FOREIGN KEY (ministry_id) REFERENCES ministries(id) ON DELETE CASCADE,
    UNIQUE KEY unique_member_ministry (member_id, ministry_id)
);

-- Attendance table
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    ministry_id INT,
    service_type VARCHAR(50),
    count INT DEFAULT 0,
    recorded_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ministry_id) REFERENCES ministries(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- Sunday School Attendance
CREATE TABLE sunday_school_attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    date DATE NOT NULL,
    category ENUM('3 and under', '9-11', '12 and above') NOT NULL,
    attended BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(mem_id) ON DELETE CASCADE
);

-- Ministers Vestry Hours
CREATE TABLE vestry_hours (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    minister_name VARCHAR(100),
    hours_detail TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_type ENUM('wedding', 'birthday', 'anniversary', 'baptism', 'death', 'other') NOT NULL,
    event_date DATE NOT NULL,
    member_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(mem_id) ON DELETE SET NULL
);

-- Insert default roles
INSERT INTO roles (role_name, description) VALUES
('Administrator', 'Full system access, manage users, generate all reports'),
('Pastor/Clergy', 'View and edit membership, view attendance, generate member reports'),
('Ministry Leader', 'Update group attendance, view only their ministry records'),
('Clerk/Secretary', 'Enter members and event data, generate standard reports'),
('Member', 'Update limited personal info');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, role_id, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'active');

-- Insert sample ministries
INSERT INTO ministries (name, description) VALUES
('Senior Choir', 'Main church choir for Sunday services'),
('Youth Ministry', 'Ministry for young people ages 12-25'),
('Ushering Team', 'Welcomes and assists congregation members'),
('Womens Fellowship', 'Fellowship and support group for women'),
('Mens Ministry', 'Fellowship and support group for men'),
('Sunday School', 'Children and youth Bible study program');

-- Create indexes for better performance
CREATE INDEX idx_member_status ON members(status);
CREATE INDEX idx_member_dob ON members(dob);
CREATE INDEX idx_attendance_date ON attendance(date);
CREATE INDEX idx_events_date ON events(event_date);
CREATE INDEX idx_events_type ON events(event_type);
CREATE INDEX idx_ministry_members ON ministry_members(member_id, ministry_id);