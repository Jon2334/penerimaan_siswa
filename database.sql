-- SQL Database Schema for SPK Seleksi Penerimaan Siswa Baru
-- Database: spk_siswa_fuzzy

CREATE DATABASE IF NOT EXISTS `spk_siswa_fuzzy`;
USE `spk_siswa_fuzzy`;

-- 1. Table users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id_user` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'Admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Admin (password: admin123)
-- Hash generated using password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `users` (`username`, `password`, `nama`, `role`) VALUES
('admin', '$2y$12$CMJjE4vT5RwPXGG.drDlketBpZsaQn5AKC9wEyy05cERlTuCyTJmi', 'Administrator SPK', 'Admin');

-- 2. Table siswa
DROP TABLE IF EXISTS `siswa`;
CREATE TABLE `siswa` (
  `id_siswa` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_peserta` VARCHAR(50) NOT NULL UNIQUE,
  `nama_siswa` VARCHAR(150) NOT NULL,
  `jk` ENUM('L','P') NOT NULL,
  `tempat_lahir` VARCHAR(100) NOT NULL,
  `tanggal_lahir` DATE NOT NULL,
  `alamat` TEXT NOT NULL,
  `asal_sekolah` VARCHAR(150) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  `tahun_ajaran` VARCHAR(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table nilai_siswa
-- 3. Table nilai_siswa
DROP TABLE IF EXISTS `nilai_siswa`;
CREATE TABLE `nilai_siswa` (
  `id_nilai` INT AUTO_INCREMENT PRIMARY KEY,
  `id_siswa` INT NOT NULL,
  `nilai_uan` DOUBLE NOT NULL,
  `nilai_raport` DOUBLE NOT NULL,
  `tes_kompetensi` DOUBLE NOT NULL,
  FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table rule_fuzzy
DROP TABLE IF EXISTS `rule_fuzzy`;
CREATE TABLE `rule_fuzzy` (
  `id_rule` INT AUTO_INCREMENT PRIMARY KEY,
  `uan` VARCHAR(20) NOT NULL, -- Rendah, Sedang, Tinggi
  `raport` VARCHAR(20) NOT NULL, -- Rendah, Sedang, Tinggi
  `kompetensi` VARCHAR(20) NOT NULL, -- Rendah, Sedang, Tinggi
  `hasil` VARCHAR(20) NOT NULL -- Lulus, Tidak Lulus
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed initial rules
INSERT INTO `rule_fuzzy` (`uan`, `raport`, `kompetensi`, `hasil`) VALUES
('Rendah', 'Rendah', 'Rendah', 'Tidak Lulus'),
('Tinggi', 'Tinggi', 'Tinggi', 'Lulus');

-- 5. Table hasil_seleksi
DROP TABLE IF EXISTS `hasil_seleksi`;
CREATE TABLE `hasil_seleksi` (
  `id_hasil` INT AUTO_INCREMENT PRIMARY KEY,
  `id_siswa` INT NOT NULL,
  `nilai_fuzzy` DOUBLE NOT NULL,
  `status_kelulusan` VARCHAR(20) NOT NULL, -- Lulus, Tidak Lulus
  `ranking` INT DEFAULT NULL,
  `tanggal_proses` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Table kuota
DROP TABLE IF EXISTS `kuota`;
CREATE TABLE `kuota` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tahun_ajaran` VARCHAR(9) NOT NULL UNIQUE,
  `jumlah_kuota` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert initial sample kuota
INSERT INTO `kuota` (`tahun_ajaran`, `jumlah_kuota`) VALUES
('2025/2026', 10),
('2026/2027', 15);
