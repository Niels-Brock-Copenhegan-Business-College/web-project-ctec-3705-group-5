-- Add password_hash column to staff table
ALTER TABLE staff ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) NULL AFTER bio;

-- Set passwords for seeded staff members (password: Staff@1234)
UPDATE staff SET password_hash = '$2y$10$TKh8H1.PfZOsV0R5J5a8XuSLwnbKF6w8lzFH4zCZ.mXaH8v5b5Wqe' WHERE email = 'a.johnson@university.ac.uk';
UPDATE staff SET password_hash = '$2y$10$TKh8H1.PfZOsV0R5J5a8XuSLwnbKF6w8lzFH4zCZ.mXaH8v5b5Wqe' WHERE email = 'm.davies@university.ac.uk';
UPDATE staff SET password_hash = '$2y$10$TKh8H1.PfZOsV0R5J5a8XuSLwnbKF6w8lzFH4zCZ.mXaH8v5b5Wqe' WHERE email = 's.ahmed@university.ac.uk';
