CREATE DATABASE IF NOT EXISTS tesda_directory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tesda_directory;

CREATE TABLE offices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  office_type ENUM('Central Office','Regional Office','Provincial Office','TESDA Technology Institution') NOT NULL,
  region_code VARCHAR(20) NULL,
  region_name VARCHAR(120) NULL,
  address TEXT NULL,
  telephone VARCHAR(120) NULL,
  fax VARCHAR(120) NULL,
  email VARCHAR(190) NULL,
  website VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_office_type (office_type),
  INDEX idx_region_code (region_code),
  FULLTEXT INDEX ft_office_search (name, address)
) ENGINE=InnoDB;

CREATE TABLE officials (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  office_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  position VARCHAR(190) NOT NULL,
  email VARCHAR(190) NULL,
  photo_path VARCHAR(255) NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_official_office FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE CASCADE,
  INDEX idx_official_office (office_id),
  FULLTEXT INDEX ft_official_search (name, position)
) ENGINE=InnoDB;

CREATE TABLE administrators (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO offices (name, office_type, region_code, region_name, address, telephone, fax, email, sort_order)
VALUES ('TESDA National Capital Region (NCR)', 'Regional Office', 'NCR', 'National Capital Region', '3F RTC Building, Gate 2 TESDA Complex, East Service Rd., South Superhighway, Taguig, Metro Manila', '8811-3499', NULL, 'NCR@tesda.gov.ph', 10);

SET @ncr_office_id = LAST_INSERT_ID();
INSERT INTO officials (office_id, name, position, is_primary)
VALUES (@ncr_office_id, 'ANGELINA M. CARREON', 'Regional Director', 1);

-- Replace this password hash during installation using scripts/create-admin.php.

