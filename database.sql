-- =============================================================================
-- Skema Database Portofolio Raya Mahardika Ibrahim
-- Tabel: pesan_kontak
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `portofolio` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `portofolio`;

CREATE TABLE IF NOT EXISTS `pesan_kontak` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `telepon` VARCHAR(30) DEFAULT NULL,
    `kategori` VARCHAR(50) NOT NULL,
    `layanan` VARCHAR(50) NOT NULL,
    `pesan` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
