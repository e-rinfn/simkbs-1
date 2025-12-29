-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 29, 2025 at 03:32 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `simkbs_fix`
--

-- --------------------------------------------------------

--
-- Table structure for table `tabel_control`
--

CREATE TABLE `tabel_control` (
  `id` int(11) NOT NULL,
  `nama_desa` varchar(255) NOT NULL,
  `logo_desa` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `maps` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `kodepos` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tabel_control`
--

INSERT INTO `tabel_control` (`id`, `nama_desa`, `logo_desa`, `alamat`, `maps`, `email`, `telepon`, `kodepos`, `created_at`, `updated_at`) VALUES
(1, 'Desa Contoh', 'logo.png', 'Jl. Contoh No. 1', 'https://maps.google.com', 'desa@contoh.com', '08123456789', '12345', '2025-12-23 14:03:21', '2025-12-23 14:03:21');

-- --------------------------------------------------------

--
-- Table structure for table `tabel_dusun`
--

CREATE TABLE `tabel_dusun` (
  `id` int(11) NOT NULL,
  `dusun` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tabel_dusun`
--

INSERT INTO `tabel_dusun` (`id`, `dusun`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 'Dusun Utara', NULL, '2025-12-23 14:03:21', '2025-12-23 14:03:21'),
(2, 'Dusun Selatan', NULL, '2025-12-23 14:03:21', '2025-12-23 14:03:21'),
(3, 'Dusun Timur', NULL, '2025-12-23 14:03:21', '2025-12-23 14:03:21'),
(4, 'Dusun Barat', NULL, '2025-12-23 14:03:21', '2025-12-23 14:03:21');

-- --------------------------------------------------------

--
-- Table structure for table `tabel_keluarga`
--

CREATE TABLE `tabel_keluarga` (
  `id` int(11) NOT NULL,
  `NO_KK` char(16) NOT NULL,
  `NIK_KEPALA` char(16) NOT NULL,
  `alamat_kk` text NOT NULL,
  `rt_kk` varchar(5) NOT NULL,
  `rw_kk` varchar(5) NOT NULL,
  `dusun_kk` int(11) NOT NULL,
  `jumlah_anggota` int(3) DEFAULT 1,
  `tanggal_kk` date DEFAULT NULL,
  `status_ekonomi` enum('SANGAT MISKIN','MISKIN','MENENGAH','MAMPU') DEFAULT 'MENENGAH',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tabel_keluarga`
--

INSERT INTO `tabel_keluarga` (`id`, `NO_KK`, `NIK_KEPALA`, `alamat_kk`, `rt_kk`, `rw_kk`, `dusun_kk`, `jumlah_anggota`, `tanggal_kk`, `status_ekonomi`, `created_at`, `updated_at`) VALUES
(0, '1234567890123457', '1234567890123457', 'HELLO WORLD', '7', '8', 4, 1, '2025-12-29', 'MENENGAH', '2025-12-29 02:03:00', '2025-12-29 02:03:00');

-- --------------------------------------------------------

--
-- Table structure for table `tabel_kependudukan`
--

CREATE TABLE `tabel_kependudukan` (
  `id` int(11) NOT NULL,
  `NO_KK` char(16) NOT NULL,
  `NIK` char(16) NOT NULL,
  `NAMA_LGKP` varchar(100) NOT NULL,
  `NAMA_PANGGILAN` varchar(50) DEFAULT NULL,
  `HBKEL` enum('KEPALA KELUARGA','ISTRI','ANAK','FAMILI LAIN') NOT NULL,
  `JK` enum('L','P') NOT NULL,
  `TMPT_LHR` varchar(30) NOT NULL,
  `TGL_LHR` date NOT NULL,
  `AGAMA` enum('ISLAM','KRISTEN','KATOLIK','HINDU','BUDDHA','KONGHUCU','LAINNYA') NOT NULL,
  `STATUS_KAWIN` enum('BELUM KAWIN','KAWIN','CERAI HIDUP','CERAI MATI') NOT NULL DEFAULT 'BELUM KAWIN',
  `PENDIDIKAN` enum('TIDAK/BELUM SEKOLAH','SD/SEDERAJAT','SMP/SEDERAJAT','SMA/SEDERAJAT','D1/D2/D3','S1','S2','S3') DEFAULT NULL,
  `PEKERJAAN` varchar(50) DEFAULT NULL,
  `NAMA_LGKP_AYAH` varchar(100) NOT NULL,
  `NAMA_LGKP_IBU` varchar(100) NOT NULL,
  `KECAMATAN` varchar(30) NOT NULL,
  `KELURAHAN` varchar(30) NOT NULL,
  `DSN` int(11) NOT NULL,
  `rt` varchar(5) DEFAULT NULL,
  `rw` varchar(5) DEFAULT NULL,
  `ALAMAT` text DEFAULT NULL,
  `GOL_DARAH` enum('A','B','AB','O','TIDAK TAHU') DEFAULT 'TIDAK TAHU',
  `KEWARGANEGARAAN` enum('WNI','WNA') DEFAULT 'WNI',
  `STATUS_TINGGAL` enum('TETAP','SEMENTARA','PENDATANG') DEFAULT 'TETAP',
  `DISABILITAS` enum('YA','TIDAK') DEFAULT 'TIDAK',
  `JENIS_DISABILITAS` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tabel_kependudukan`
--

INSERT INTO `tabel_kependudukan` (`id`, `NO_KK`, `NIK`, `NAMA_LGKP`, `NAMA_PANGGILAN`, `HBKEL`, `JK`, `TMPT_LHR`, `TGL_LHR`, `AGAMA`, `STATUS_KAWIN`, `PENDIDIKAN`, `PEKERJAAN`, `NAMA_LGKP_AYAH`, `NAMA_LGKP_IBU`, `KECAMATAN`, `KELURAHAN`, `DSN`, `rt`, `rw`, `ALAMAT`, `GOL_DARAH`, `KEWARGANEGARAAN`, `STATUS_TINGGAL`, `DISABILITAS`, `JENIS_DISABILITAS`, `created_at`, `updated_at`) VALUES
(38, '1234567890123457', '1234567890123457', 'PRABOWOOOO', 'OWO', 'KEPALA KELUARGA', 'L', 'KAWALU', '1990-12-12', 'ISLAM', 'BELUM KAWIN', 'TIDAK/BELUM SEKOLAH', 'PRESIDEN', 'OWI', 'MAYTED', 'CISEWU', 'NEGLASARI', 4, '7', '8', 'HELLO WORLD', 'TIDAK TAHU', 'WNI', 'TETAP', 'TIDAK', '', '2025-12-29 02:03:00', '2025-12-29 02:30:56');

--
-- Triggers `tabel_kependudukan`
--
DELIMITER $$
CREATE TRIGGER `trg_after_delete_penduduk` AFTER DELETE ON `tabel_kependudukan` FOR EACH ROW BEGIN
    DECLARE count_anggota INT;
    
    -- Hitung sisa anggota
    SELECT COUNT(*) INTO count_anggota 
    FROM tabel_kependudukan 
    WHERE NO_KK = OLD.NO_KK;
    
    -- Update jumlah anggota
    UPDATE tabel_keluarga 
    SET jumlah_anggota = count_anggota 
    WHERE NO_KK = OLD.NO_KK;
    
    -- Jika tidak ada anggota lagi, hapus dari tabel_keluarga
    IF count_anggota = 0 THEN
        DELETE FROM tabel_keluarga WHERE NO_KK = OLD.NO_KK;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_insert_penduduk` AFTER INSERT ON `tabel_kependudukan` FOR EACH ROW BEGIN
    DECLARE count_anggota INT;
    
    -- Hitung jumlah anggota untuk NO_KK ini
    SELECT COUNT(*) INTO count_anggota 
    FROM tabel_kependudukan 
    WHERE NO_KK = NEW.NO_KK;
    
    -- Update atau insert ke tabel_keluarga
    IF EXISTS (SELECT 1 FROM tabel_keluarga WHERE NO_KK = NEW.NO_KK) THEN
        UPDATE tabel_keluarga 
        SET jumlah_anggota = count_anggota 
        WHERE NO_KK = NEW.NO_KK;
    ELSE
        -- Jika kepala keluarga (HBKEL = 'KEPALA KELUARGA')
        IF NEW.HBKEL = 'KEPALA KELUARGA' THEN
            INSERT INTO tabel_keluarga (
                NO_KK, 
                NIK_KEPALA, 
                alamat_kk, 
                rt_kk, 
                rw_kk, 
                dusun_kk, 
                jumlah_anggota,
                tanggal_kk
            ) VALUES (
                NEW.NO_KK,
                NEW.NIK,
                COALESCE(NEW.ALAMAT, ''),
                COALESCE(NEW.rt, ''),
                COALESCE(NEW.rw, ''),
                NEW.DSN,
                1,
                CURDATE()
            );
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tabel_log_aktivitas`
--

CREATE TABLE `tabel_log_aktivitas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `modul` varchar(50) NOT NULL,
  `aksi` enum('CREATE','UPDATE','DELETE','VIEW','LOGIN','LOGOUT','EXPORT','IMPORT') NOT NULL,
  `data_id` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tabel_rumah`
--

CREATE TABLE `tabel_rumah` (
  `id` int(11) NOT NULL,
  `NIK` char(16) NOT NULL,
  `NO_KK` char(16) NOT NULL,
  `nama_pemilik` varchar(255) NOT NULL,
  `status_tempat_tinggal` enum('MILIK SENDIRI','SEWA','KONTRAK','NUMPANG') NOT NULL,
  `luas_lantai` decimal(5,2) NOT NULL DEFAULT 0.00,
  `jenis_lantai` enum('KERAMIK','SEMEN','TANAH','KAYU','LAINNYA') NOT NULL,
  `jenis_dinding` enum('TEMBOK','KAYU','BAMBU','LAINNYA') NOT NULL,
  `fasilitas_bab` enum('JAMBAN SENDIRI','JAMBAN BERSAMA','TIDAK ADA') NOT NULL,
  `sumber_penerangan` enum('PLN','GENSET','LAMPU MINYAK','TIDAK ADA') NOT NULL,
  `sumber_air_minum` enum('PDAM','SUMUR BOR','MATA AIR','AIR KEMASAN','LAINNYA') NOT NULL,
  `bahan_bakar_memasak` enum('GAS','KAYU BAKAR','LISTRIK','MINYAK TANAH','LAINNYA') NOT NULL,
  `kondisi_rumah` enum('LAYAK HUNI','RUSAK RINGAN','RUSAK BERAT') DEFAULT 'LAYAK HUNI',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tabel_surat_keluar`
--

CREATE TABLE `tabel_surat_keluar` (
  `id` int(11) NOT NULL,
  `nomor_surat` varchar(100) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `tujuan` varchar(200) NOT NULL,
  `perihal` text NOT NULL,
  `sifat_surat` enum('BIASA','PENTING','RAHASIA','SANGAT RAHASIA') DEFAULT 'BIASA',
  `file_surat` varchar(255) DEFAULT NULL,
  `status` enum('DRAFT','TERBIT','TERKIRIM','ARSIP') DEFAULT 'DRAFT',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tabel_surat_keluar`
--

INSERT INTO `tabel_surat_keluar` (`id`, `nomor_surat`, `tanggal_surat`, `tujuan`, `perihal`, `sifat_surat`, `file_surat`, `status`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, '212/1212/12121', '2025-12-24', 'PT UNPER', 'test hello world', 'BIASA', '1766512635_694ad7fb24a09.pdf', 'DRAFT', NULL, '2025-12-23 17:57:15', '2025-12-23 17:57:15'),
(2, '12/12/1221', '2025-12-24', 'UNPER', 'helo world', 'PENTING', '1766513703_694adc271caeb.pdf', 'ARSIP', '', '2025-12-23 18:15:03', '2025-12-23 23:42:10');

-- --------------------------------------------------------

--
-- Table structure for table `tabel_surat_masuk`
--

CREATE TABLE `tabel_surat_masuk` (
  `id` int(11) NOT NULL,
  `nomor_surat` varchar(100) NOT NULL,
  `tanggal_surat` date NOT NULL,
  `tanggal_diterima` date NOT NULL,
  `pengirim` varchar(200) NOT NULL,
  `perihal` text NOT NULL,
  `sifat_surat` enum('BIASA','PENTING','RAHASIA','SANGAT RAHASIA') DEFAULT 'BIASA',
  `file_surat` varchar(255) DEFAULT NULL,
  `status` enum('BARU','DIPROSES','SELESAI','ARSIP') DEFAULT 'BARU',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tabel_surat_masuk`
--

INSERT INTO `tabel_surat_masuk` (`id`, `nomor_surat`, `tanggal_surat`, `tanggal_diterima`, `pengirim`, `perihal`, `sifat_surat`, `file_surat`, `status`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, '01/10/12/1212', '2025-12-24', '2025-12-24', 'Pengirim', 'Unper', 'BIASA', '1766512271_694ad68f516af.pdf', 'BARU', 'helo world', '2025-12-23 17:51:11', '2025-12-23 17:51:11'),
(2, '12/12/12221', '2025-12-24', '2025-12-24', 'UNPER 22', 'UNdangan', 'BIASA', '1766512303_694ad6af6acb1.pdf', 'BARU', '', '2025-12-23 17:51:43', '2025-12-23 23:15:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','kepala_desa') NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `kontak` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `role`, `nama_lengkap`, `kontak`, `created_at`) VALUES
(1, 'superadmin', '$2y$10$rV9Nbxbr3hN98fetlI1LlOB3qzQzKp4jH7SG21gsqayy6YZsTKac2', 'admin', 'Admin Utama', '081234567890', '2025-05-21 01:20:50'),
(4, 'kepaladesa', '$2y$10$ucn/VfVaw7EOkIFdyRoy8OlIoN9GrIRW6sKqqakt3ZGDYRbaoX4.y', 'kepala_desa', 'Kepala Desa Mamat', '0878', '2025-12-28 10:06:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tabel_control`
--
ALTER TABLE `tabel_control`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tabel_dusun`
--
ALTER TABLE `tabel_dusun`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dusun` (`dusun`);

--
-- Indexes for table `tabel_keluarga`
--
ALTER TABLE `tabel_keluarga`
  ADD PRIMARY KEY (`NO_KK`),
  ADD UNIQUE KEY `unique_nik_kepala` (`NIK_KEPALA`),
  ADD KEY `idx_dusun_kk` (`dusun_kk`),
  ADD KEY `idx_id` (`id`);

--
-- Indexes for table `tabel_kependudukan`
--
ALTER TABLE `tabel_kependudukan`
  ADD PRIMARY KEY (`NIK`),
  ADD UNIQUE KEY `unique_nik` (`NIK`),
  ADD KEY `idx_no_kk` (`NO_KK`),
  ADD KEY `idx_nama` (`NAMA_LGKP`),
  ADD KEY `idx_dusun` (`DSN`),
  ADD KEY `idx_rt_rw` (`rt`,`rw`),
  ADD KEY `idx_tgl_lhr` (`TGL_LHR`),
  ADD KEY `idx_id` (`id`);

--
-- Indexes for table `tabel_rumah`
--
ALTER TABLE `tabel_rumah`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nik_rumah` (`NIK`);

--
-- Indexes for table `tabel_surat_keluar`
--
ALTER TABLE `tabel_surat_keluar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nomor_surat_keluar` (`nomor_surat`),
  ADD KEY `idx_tanggal_surat_keluar` (`tanggal_surat`),
  ADD KEY `idx_tujuan` (`tujuan`),
  ADD KEY `idx_status_keluar` (`status`);

--
-- Indexes for table `tabel_surat_masuk`
--
ALTER TABLE `tabel_surat_masuk`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nomor_surat` (`nomor_surat`),
  ADD KEY `idx_tanggal_surat` (`tanggal_surat`),
  ADD KEY `idx_pengirim` (`pengirim`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tabel_control`
--
ALTER TABLE `tabel_control`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tabel_dusun`
--
ALTER TABLE `tabel_dusun`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tabel_kependudukan`
--
ALTER TABLE `tabel_kependudukan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `tabel_rumah`
--
ALTER TABLE `tabel_rumah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tabel_surat_keluar`
--
ALTER TABLE `tabel_surat_keluar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tabel_surat_masuk`
--
ALTER TABLE `tabel_surat_masuk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tabel_kependudukan`
--
ALTER TABLE `tabel_kependudukan`
  ADD CONSTRAINT `fk_kependudukan_dusun` FOREIGN KEY (`DSN`) REFERENCES `tabel_dusun` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tabel_rumah`
--
ALTER TABLE `tabel_rumah`
  ADD CONSTRAINT `fk_rumah_kependudukan` FOREIGN KEY (`NIK`) REFERENCES `tabel_kependudukan` (`NIK`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
