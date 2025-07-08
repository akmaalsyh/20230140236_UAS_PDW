-- Tabel untuk menyimpan data mata praktikum
CREATE TABLE `courses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk menyimpan pendaftaran mahasiswa ke praktikum
CREATE TABLE `enrollments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `course_id` INT(11) NOT NULL,
  `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_course` (`user_id`, `course_id`), -- Memastikan satu mahasiswa hanya bisa mendaftar satu kali ke satu praktikum
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk menyimpan data modul/pertemuan dalam setiap praktikum
CREATE TABLE `modules` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_id` INT(11) NOT NULL,
  `module_name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `material_file` VARCHAR(255), -- Path ke file materi
  `due_date` DATETIME, -- Batas waktu pengumpulan laporan untuk modul ini
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk menyimpan pengumpulan laporan oleh mahasiswa
CREATE TABLE `submissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `module_id` INT(11) NOT NULL,
  `submission_file` VARCHAR(255) NOT NULL, -- Path ke file laporan
  `submission_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('submitted', 'graded') DEFAULT 'submitted', -- Status laporan
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_module` (`user_id`, `module_id`), -- Memastikan satu mahasiswa hanya bisa mengumpulkan satu laporan per modul
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk menyimpan nilai dan feedback laporan
CREATE TABLE `grades` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `submission_id` INT(11) NOT NULL,
  `grade_value` DECIMAL(5,2), -- Nilai laporan
  `feedback` TEXT,
  `graded_by` INT(11) NOT NULL, -- ID asisten yang memberi nilai
  `graded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_submission_grade` (`submission_id`), -- Memastikan satu laporan hanya memiliki satu nilai
  FOREIGN KEY (`submission_id`) REFERENCES `submissions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`graded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

