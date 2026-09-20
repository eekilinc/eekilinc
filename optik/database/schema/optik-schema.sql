-- Optik v1 MySQL şema (utf8mb4_turkish_ci). Laravel migration yazılana kadar doğrudan kurulabilir.
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL, role ENUM('teacher','admin') DEFAULT 'teacher',
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS classes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  CONSTRAINT fk_classes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS students (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  class_id BIGINT UNSIGNED NOT NULL, student_no VARCHAR(20) NOT NULL,
  first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_class_student (class_id, student_no),
  CONSTRAINT fk_students_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS exams (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL, class_id BIGINT UNSIGNED NULL,
  title VARCHAR(255) NOT NULL, course VARCHAR(255) NULL,
  question_count INT NOT NULL DEFAULT 40, option_count INT NOT NULL DEFAULT 5,
  booklets JSON NOT NULL, status ENUM('draft','published','archived') DEFAULT 'draft',
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  CONSTRAINT fk_exams_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_exams_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS form_templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  exam_id BIGINT UNSIGNED NOT NULL, version INT NOT NULL DEFAULT 1,
  spec JSON NOT NULL, pdf_path VARCHAR(512) NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_exam_version (exam_id, version),
  CONSTRAINT fk_forms_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS answer_keys (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  exam_id BIGINT UNSIGNED NOT NULL, booklet CHAR(1) NOT NULL,
  answers JSON NOT NULL, points JSON NULL, cancelled JSON NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_exam_booklet (exam_id, booklet),
  CONSTRAINT fk_keys_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS scans (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  exam_id BIGINT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NULL,
  student_no_raw VARCHAR(20) NOT NULL, booklet CHAR(1) NOT NULL,
  answers JSON NOT NULL, score DECIMAL(6,2) NOT NULL DEFAULT 0, max_score DECIMAL(6,2) NOT NULL DEFAULT 0,
  confidence TINYINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('ok','review','duplicate') DEFAULT 'ok',
  paper_image_path VARCHAR(512) NULL, scanned_by BIGINT UNSIGNED NOT NULL,
  device_id VARCHAR(64) NULL, created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_scans_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
  CONSTRAINT fk_scans_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
  CONSTRAINT fk_scans_user FOREIGN KEY (scanned_by) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_exam_student (exam_id, student_no_raw)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;
