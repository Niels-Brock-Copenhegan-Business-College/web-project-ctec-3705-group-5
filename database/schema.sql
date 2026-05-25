-- =============================================
-- University Course Portal - Database Schema
-- Compatible with MySQL / MariaDB (XAMPP)
-- =============================================

CREATE DATABASE IF NOT EXISTS university_course_portal
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE university_course_portal;

-- =============================================
-- STAFF
-- =============================================
CREATE TABLE staff (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)        NOT NULL,
    email       VARCHAR(200)        NOT NULL UNIQUE,
    bio         TEXT,
    photo       VARCHAR(300),
    created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- PROGRAMMES
-- =============================================
CREATE TABLE programmes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(200)        NOT NULL,
    slug            VARCHAR(200)        NOT NULL UNIQUE,
    level           ENUM('Undergraduate','Postgraduate') NOT NULL,
    description     TEXT,
    duration_years  TINYINT UNSIGNED    NOT NULL DEFAULT 3,
    image           VARCHAR(300),
    is_published    TINYINT(1)          NOT NULL DEFAULT 0,
    leader_id       INT,
    created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (leader_id) REFERENCES staff(id) ON DELETE SET NULL
);

-- =============================================
-- MODULES
-- =============================================
CREATE TABLE modules (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(200)        NOT NULL,
    code            VARCHAR(20)         NOT NULL UNIQUE,
    description     TEXT,
    credits         TINYINT UNSIGNED    NOT NULL DEFAULT 20,
    image           VARCHAR(300),
    leader_id       INT,
    created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (leader_id) REFERENCES staff(id) ON DELETE SET NULL
);

-- =============================================
-- PROGRAMME_MODULES  (many-to-many + year)
-- =============================================
CREATE TABLE programme_modules (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    programme_id    INT             NOT NULL,
    module_id       INT             NOT NULL,
    year_of_study   TINYINT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uq_prog_mod (programme_id, module_id),
    FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id)    REFERENCES modules(id)    ON DELETE CASCADE
);

-- =============================================
-- INTEREST REGISTRATIONS
-- =============================================
CREATE TABLE interest_registrations (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    first_name      VARCHAR(100)    NOT NULL,
    last_name       VARCHAR(100)    NOT NULL,
    email           VARCHAR(200)    NOT NULL,
    phone           VARCHAR(30),
    programme_id    INT             NOT NULL,
    registered_at   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email_prog (email, programme_id),
    FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE
);

-- =============================================
-- ADMINS
-- =============================================
CREATE TABLE admins (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(80)     NOT NULL UNIQUE,
    email           VARCHAR(200)    NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL,
    role            ENUM('superadmin','admin') NOT NULL DEFAULT 'admin',
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- SEED: Default superadmin  (password: Admin@1234)
-- =============================================
INSERT INTO admins (username, email, password_hash, role) VALUES
('admin', 'admin@university.ac.uk',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'superadmin');

-- =============================================
-- SEED: Sample staff
-- =============================================
INSERT INTO staff (name, email, bio) VALUES
('Dr. Alice Johnson',  'a.johnson@university.ac.uk',  'Senior Lecturer in Computer Science'),
('Prof. Mark Davies',  'm.davies@university.ac.uk',   'Professor of Software Engineering'),
('Dr. Sara Ahmed',     's.ahmed@university.ac.uk',    'Lecturer in Cybersecurity');

-- =============================================
-- SEED: Sample programmes
-- =============================================
INSERT INTO programmes (title, slug, level, description, duration_years, is_published, leader_id) VALUES
('BSc Computer Science',
 'bsc-computer-science',
 'Undergraduate',
 'A rigorous three-year programme covering algorithms, software engineering, AI, and systems programming.',
 3, 1, 1),
('MSc Cybersecurity',
 'msc-cybersecurity',
 'Postgraduate',
 'An advanced one-year programme focusing on network security, ethical hacking, and digital forensics.',
 1, 1, 3),
('BSc Software Engineering',
 'bsc-software-engineering',
 'Undergraduate',
 'Build production-grade software with a focus on agile practices, DevOps, and team collaboration.',
 3, 1, 2);

-- =============================================
-- SEED: Sample modules
-- =============================================
INSERT INTO modules (title, code, description, credits, leader_id) VALUES
('Introduction to Programming',  'CS101', 'Fundamentals of programming using Python.',        20, 1),
('Data Structures & Algorithms', 'CS201', 'Core data structures and algorithm analysis.',     20, 1),
('Database Systems',             'CS202', 'Relational databases, SQL, and normalization.',    20, 2),
('Software Engineering',         'SE301', 'Agile, UML, testing, and project management.',    20, 2),
('Network Security',             'CY101', 'TCP/IP, firewalls, VPNs, and threat modelling.',  20, 3),
('Ethical Hacking',              'CY201', 'Penetration testing and vulnerability assessment.',20, 3),
('Web Development',              'CS203', 'HTML, CSS, JavaScript, PHP, and REST APIs.',       20, 1);

-- =============================================
-- SEED: Assign modules to programmes
-- =============================================
-- BSc Computer Science (id=1)
INSERT INTO programme_modules (programme_id, module_id, year_of_study) VALUES
(1, 1, 1),(1, 2, 1),(1, 3, 2),(1, 7, 2),(1, 4, 3);

-- MSc Cybersecurity (id=2)
INSERT INTO programme_modules (programme_id, module_id, year_of_study) VALUES
(2, 5, 1),(2, 6, 1),(2, 3, 1);

-- BSc Software Engineering (id=3)
INSERT INTO programme_modules (programme_id, module_id, year_of_study) VALUES
(3, 1, 1),(3, 7, 1),(3, 3, 2),(3, 4, 2),(3, 2, 3);
