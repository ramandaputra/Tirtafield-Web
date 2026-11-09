-- ============================================================
-- SQL SCRIPT SCHEMA & SEED DATA TIRTAFIELD (LARAGON / PHPMYADMIN)
-- Database: tirtafield
-- Karakter: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS `tirtafield` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tirtafield`;

-- 1. Tabel users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nik` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pin` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '123456',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '123456',
  `role` enum('karyawan','direktur','staffIT') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'karyawan',
  `department` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Bidang Hubungan Langganan',
  `position` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Seksi Pembacaan Meter',
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '081234567890',
  `office_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hq',
  `office_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Kantor Pusat Perumda Air Minum Tirta Kepri Tanjung Pinang',
  `avatar_url` longtext COLLATE utf8mb4_unicode_ci,
  `status_pegawai` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Karyawan Tetap',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_nik_unique` (`nik`),
  KEY `users_nik_index` (`nik`),
  KEY `users_role_index` (`role`),
  KEY `users_office_id_index` (`office_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel offices
DROP TABLE IF EXISTS `offices`;
CREATE TABLE `offices` (
  `id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_kantor` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(11,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `radius` int NOT NULL DEFAULT '100',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel ref_bidang & bidang
DROP TABLE IF EXISTS `ref_bidang`;
CREATE TABLE `ref_bidang` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `urutan` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ref_bidang_nama_unique` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bidang`;
CREATE TABLE `bidang` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `urutan` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bidang_nama_unique` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel ref_seksi_bidang & seksi_bidang
DROP TABLE IF EXISTS `ref_seksi_bidang`;
CREATE TABLE `ref_seksi_bidang` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bidang_id` bigint unsigned NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `urutan` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ref_seksi_bidang_bidang_id_foreign` (`bidang_id`),
  CONSTRAINT `ref_seksi_bidang_bidang_id_foreign` FOREIGN KEY (`bidang_id`) REFERENCES `ref_bidang` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `seksi_bidang`;
CREATE TABLE `seksi_bidang` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bidang_id` bigint unsigned NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `urutan` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `seksi_bidang_bidang_id_foreign` (`bidang_id`),
  CONSTRAINT `seksi_bidang_bidang_id_foreign` FOREIGN KEY (`bidang_id`) REFERENCES `bidang` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabel presensi
DROP TABLE IF EXISTS `presensi`;
CREATE TABLE `presensi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pgw_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nik` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pgw_nup` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pgw_nama` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal` date NOT NULL,
  `slot_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `waktu` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lokasi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(11,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `jarak_hq` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_url` longtext COLLATE utf8mb4_unicode_ci,
  `biometric_score` decimal(5,2) NOT NULL DEFAULT '99.40',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Tepat Waktu',
  `approval_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `tipe_pengajuan` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dalamRadius',
  `reviewed_by` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `presensi_pgw_id_index` (`pgw_id`),
  KEY `presensi_nik_index` (`nik`),
  KEY `presensi_tanggal_index` (`tanggal`),
  KEY `presensi_slot_type_index` (`slot_type`),
  KEY `presensi_approval_status_index` (`approval_status`),
  KEY `presensi_nik_tanggal_index` (`nik`,`tanggal`),
  KEY `presensi_pgw_id_tanggal_index` (`pgw_id`,`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabel system_settings
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` json NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA AWAL
-- ============================================================

-- Seed Kantor
INSERT INTO `offices` (`id`, `nama_kantor`, `alamat`, `latitude`, `longitude`, `radius`, `is_primary`, `is_active`) VALUES
('hq', 'Kantor Pusat Perumda Air Minum Tirta Kepri Tanjung Pinang', 'Jl. M.T Haryono, Km.3, Tanjungpinang, Kepulauan Riau', 0.92031000, 104.46069000, 200, 1, 1),
('bintan_center', 'Unit Pelayanan Bintan Centre', 'Komp. Bintan Centre Blok A No. 5-6, Tanjung Pinang', 0.92550000, 104.47120000, 60, 0, 1),
('ipa_sei_pulai', 'Instalasi Pengolahan Air (IPA) Sei Pulai', 'Kawasan Waduk Sei Pulai, Bintan', 0.90810000, 104.43250000, 100, 0, 1),
('ipa_sei_gesek', 'IPA SEI GESEK', 'Kawasan Waduk Gesek, Bintan', 0.97378400, 104.55858000, 100, 0, 1)
ON DUPLICATE KEY UPDATE `nama_kantor` = VALUES(`nama_kantor`);

-- Seed Akun Pengguna
INSERT INTO `users` (`id`, `nik`, `name`, `email`, `pin`, `password`, `role`, `department`, `position`, `phone`, `office_id`, `office_name`, `status_pegawai`, `is_active`) VALUES
(1, 'P242101', 'Pusimpel', 'pusimpel@tirtakepri.co.id', '123456', '123456', 'staffIT', 'PUSIMPEL IT & System', 'Superadmin Pusimpel', '081234567890', 'hq', 'Kantor Pusat Perumda Air Minum Tirta Kepri Tanjung Pinang', 'Karyawan Tetap', 1),
(2, 'D001', 'IR. H. ABDUL KHOLIK F, M.M.', 'abdul.kholik@tirtakepri.co.id', '123456', '123456', 'direktur', 'Dewan Direksi', 'Direktur', '081234567890', 'hq', 'Kantor Pusat Perumda Air Minum Tirta Kepri Tanjung Pinang', 'Direksi', 1),
(3, 'EMP001', 'Budi Santoso', 'budi.santoso@tirtakepri.co.id', '123456', '123456', 'karyawan', 'Bidang Hubungan Langganan', 'Seksi Pembacaan Meter', '081298765432', 'hq', 'Kantor Pusat Perumda Air Minum Tirta Kepri Tanjung Pinang', 'Karyawan Tetap', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Seed Bidang & Seksi
INSERT INTO `ref_bidang` (`id`, `nama`, `kode`, `urutan`) VALUES
(1, 'Dewan Direksi', 'DIREKSI', 1),
(2, 'Bidang Hubungan Langganan', 'HUB_LANGGANAN', 2),
(3, 'Bidang Teknik', 'TEKNIK', 3),
(4, 'Satuan Pengendalian Kehilangan Air', 'SPKA', 4),
(5, 'PUSIMPEL IT & System', 'PUSIMPEL_IT', 5)
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`);

INSERT INTO `bidang` (`id`, `nama`, `kode`, `urutan`) VALUES
(1, 'Dewan Direksi', 'DIREKSI', 1),
(2, 'Bidang Hubungan Langganan', 'HUB_LANGGANAN', 2),
(3, 'Bidang Teknik', 'TEKNIK', 3),
(4, 'Satuan Pengendalian Kehilangan Air', 'SPKA', 4),
(5, 'PUSIMPEL IT & System', 'PUSIMPEL_IT', 5)
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`);

INSERT INTO `ref_seksi_bidang` (`bidang_id`, `nama`, `kode`, `urutan`) VALUES
(1, 'Direktur', 'DIREKTUR', 1),
(2, 'Seksi Pembacaan Meter', 'SEKSI_PEMBACAAN_METER', 1),
(2, 'Seksi Perawatan Meter', 'SEKSI_PERAWATAN_METER', 2),
(3, 'Seksi Perencanaan', 'SEKSI_PERENCANAAN', 1),
(3, 'Seksi Distribusi', 'SEKSI_DISTRIBUSI', 2),
(3, 'Seksi Survei & Verifikasi Teknis', 'SEKSI_SURVEI_VERIFIKASI_TEKNIS', 3),
(4, 'Bidang Kehilangan Air', 'BIDANG_KEHILANGAN_AIR', 1),
(4, 'Bidang Analisa Jaringan & GIS', 'BIDANG_ANALISA_JARINGAN_GIS', 2),
(5, 'Superadmin Pusimpel', 'SUPERADMIN_PUSIMPEL', 1),
(5, 'Staff IT & Jaringan', 'STAFF_IT_JARINGAN', 2);

INSERT INTO `seksi_bidang` (`bidang_id`, `nama`, `kode`, `urutan`) VALUES
(1, 'Direktur', 'DIREKTUR', 1),
(2, 'Seksi Pembacaan Meter', 'SEKSI_PEMBACAAN_METER', 1),
(2, 'Seksi Perawatan Meter', 'SEKSI_PERAWATAN_METER', 2),
(3, 'Seksi Perencanaan', 'SEKSI_PERENCANAAN', 1),
(3, 'Seksi Distribusi', 'SEKSI_DISTRIBUSI', 2),
(3, 'Seksi Survei & Verifikasi Teknis', 'SEKSI_SURVEI_VERIFIKASI_TEKNIS', 3),
(4, 'Bidang Kehilangan Air', 'BIDANG_KEHILANGAN_AIR', 1),
(4, 'Bidang Analisa Jaringan & GIS', 'BIDANG_ANALISA_JARINGAN_GIS', 2),
(5, 'Superadmin Pusimpel', 'SUPERADMIN_PUSIMPEL', 1),
(5, 'Staff IT & Jaringan', 'STAFF_IT_JARINGAN', 2);

-- 7. Tabel Cache & Jobs Laravel
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_01_01_000001_create_offices_table', 1),
(5, '2026_01_01_000002_create_bidang_tables', 1),
(6, '2026_01_01_000003_create_presensi_table', 1),
(7, '2026_01_01_000004_create_system_settings_table', 1)
ON DUPLICATE KEY UPDATE `batch` = VALUES(`batch`);

