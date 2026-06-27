-- PostgreSQL Migration Script for SPK Kayawan
-- Run with: psql -h <host> -U <user> -d <dbname> -f database.postgres.sql

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id_user SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password TEXT NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT NOW()
);

-- Siswa table
CREATE TABLE IF NOT EXISTS siswa (
    id_siswa SERIAL PRIMARY KEY,
    nomor_peserta VARCHAR(30) NOT NULL UNIQUE,
    nama_siswa VARCHAR(150) NOT NULL,
    jk CHAR(1) NOT NULL,
    tempat_lahir VARCHAR(100) DEFAULT NULL,
    tanggal_lahir DATE DEFAULT NULL,
    alamat TEXT DEFAULT NULL,
    asal_sekolah VARCHAR(150) DEFAULT NULL,
    no_hp VARCHAR(20) DEFAULT NULL,
    tahun_ajaran VARCHAR(20) DEFAULT NULL,
    tanggal_daftar DATE DEFAULT CURRENT_DATE
);

-- Nilai Siswa table
CREATE TABLE IF NOT EXISTS nilai_siswa (
    id_nilai SERIAL PRIMARY KEY,
    id_siswa INTEGER NOT NULL,
    nilai_uan DECIMAL(5,2) DEFAULT NULL,
    nilai_raport DECIMAL(5,2) DEFAULT NULL,
    tes_kompetensi DECIMAL(5,2) DEFAULT NULL,
    CONSTRAINT fk_nilai_siswa FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE
);

-- Fuzzy Rules table
CREATE TABLE IF NOT EXISTS rule_fuzzy (
    id_rule SERIAL PRIMARY KEY,
    uan VARCHAR(20) NOT NULL,
    raport VARCHAR(20) NOT NULL,
    kompetensi VARCHAR(20) NOT NULL,
    hasil VARCHAR(20) NOT NULL
);

-- Kuota table
CREATE TABLE IF NOT EXISTS kuota (
    id SERIAL PRIMARY KEY,
    tahun_ajaran VARCHAR(20) NOT NULL UNIQUE,
    jumlah_kuota INTEGER NOT NULL
);

-- Hasil Seleksi table
CREATE TABLE IF NOT EXISTS hasil_seleksi (
    id_hasil SERIAL PRIMARY KEY,
    id_siswa INTEGER NOT NULL,
    nilai_fuzzy DECIMAL(10,4) DEFAULT NULL,
    status_kelulusan VARCHAR(20) DEFAULT 'Pending',
    ranking INTEGER DEFAULT 0,
    tanggal_proses TIMESTAMP DEFAULT NOW(),
    CONSTRAINT fk_hasil_siswa FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE
);

-- Sessions table for DB-based sessions (Vercel stateless fix)
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    data TEXT NOT NULL DEFAULT '',
    last_accessed TIMESTAMP DEFAULT NOW()
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_nilai_siswa_id_siswa ON nilai_siswa(id_siswa);
CREATE INDEX IF NOT EXISTS idx_hasil_seleksi_id_siswa ON hasil_seleksi(id_siswa);
CREATE INDEX IF NOT EXISTS idx_siswa_tahun_ajaran ON siswa(tahun_ajaran);

-- Seed data: default admin user (password: admin123)
INSERT INTO users (username, password, nama, role) VALUES
('admin', '$2y$12$4NtiCkKR.vHYpP4/wvVWb.bAF3sSeQOrMjXdKa35mOMUqKMwVJwtK', 'Administrator', 'admin')
ON CONFLICT (username) DO UPDATE SET password = EXCLUDED.password;
