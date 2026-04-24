-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2025 at 05:54 AM
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
-- Database: `db_absensi`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `waktu` datetime NOT NULL,
  `tgl` date DEFAULT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `todo` text DEFAULT NULL,
  `status` enum('masuk','pulang') NOT NULL,
  `telat_menit` int(11) DEFAULT 0,
  `approval_status` enum('Pending','Disetujui','Ditolak') DEFAULT 'Pending',
  `foto` varchar(255) DEFAULT NULL,
  `lat` varchar(50) DEFAULT NULL,
  `lng` varchar(50) DEFAULT NULL,
  `lokasi_text` varchar(200) DEFAULT NULL,
  `ip_client` varchar(100) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `tanggal` date NOT NULL,
  `is_telat` tinyint(1) NOT NULL DEFAULT 0,
  `devisi` varchar(100) DEFAULT NULL,
  `kendala_hari_ini` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id`, `user_id`, `waktu`, `tgl`, `shift_id`, `keterangan`, `todo`, `status`, `telat_menit`, `approval_status`, `foto`, `lat`, `lng`, `lokasi_text`, `ip_client`, `user_agent`, `tanggal`, `is_telat`, `devisi`, `kendala_hari_ini`, `created_at`) VALUES
(113, 43, '2025-10-22 15:28:25', '2025-10-22', 11, '', '', 'masuk', 114, 'Pending', 'uploads/foto_43_1761121705.jpg', '-7.813608', '110.367659', '-7.813608, 110.367659', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22', 1, 'PT mencari cinta sejati', NULL, '2025-10-22 15:28:25'),
(114, 43, '2025-10-22 15:30:34', '2025-10-22', 11, NULL, NULL, 'pulang', 0, 'Pending', 'uploads/foto_43_1761121834.png', '-7.813608', '110.367659', '-7.813608, 110.367659', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22', 0, 'PT mencari cinta sejati', 'asd', '2025-10-22 15:30:34');

--
-- Triggers `absensi`
--
DELIMITER $$
CREATE TRIGGER `tr_absensi_denorm_devisi` BEFORE INSERT ON `absensi` FOR EACH ROW BEGIN
    DECLARE v_devisi VARCHAR(100);
    SELECT devisi INTO v_devisi FROM users WHERE id=NEW.user_id LIMIT 1;
    SET NEW.devisi = v_devisi;
    -- sinkronkan tgl bila null
    IF NEW.tgl IS NULL THEN SET NEW.tgl = NEW.tanggal; END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `absensi_backup`
--

CREATE TABLE `absensi_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `waktu` datetime NOT NULL,
  `tgl` date DEFAULT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `todo` text DEFAULT NULL,
  `status` enum('masuk','pulang') NOT NULL,
  `telat_menit` int(11) DEFAULT 0,
  `approval_status` enum('Pending','Disetujui','Ditolak') DEFAULT 'Pending',
  `foto` varchar(255) DEFAULT NULL,
  `lat` varchar(50) DEFAULT NULL,
  `lng` varchar(50) DEFAULT NULL,
  `lokasi_text` varchar(200) DEFAULT NULL,
  `ip_client` varchar(100) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `tanggal` date NOT NULL,
  `is_telat` tinyint(1) NOT NULL DEFAULT 0,
  `devisi` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absensi_backup`
--

INSERT INTO `absensi_backup` (`id`, `user_id`, `waktu`, `tgl`, `shift_id`, `keterangan`, `todo`, `status`, `telat_menit`, `approval_status`, `foto`, `lat`, `lng`, `lokasi_text`, `ip_client`, `user_agent`, `tanggal`, `is_telat`, `devisi`, `created_at`) VALUES
(2, 4, '2025-08-26 21:33:03', '2025-08-26', NULL, NULL, NULL, 'masuk', 0, 'Disetujui', '20250826_213303_4_ibrahim_masuk_68adc59f18362.jpg', '-7.813610000000001', '110.367637', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26', 0, NULL, '2025-09-01 15:32:11'),
(3, 4, '2025-08-27 13:05:45', '2025-08-27', NULL, NULL, NULL, 'masuk', 0, 'Disetujui', '20250827_130545_4_ibrahim_masuk_68aea03963f9d.webp', '-7.813610000000001', '110.367637', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-27', 0, NULL, '2025-09-01 15:32:11'),
(4, 4, '2025-08-27 19:21:58', '2025-08-27', NULL, NULL, NULL, 'pulang', 0, 'Disetujui', 'uploads/out_4_20250827_192158.jpg', '-7.8136100', '110.3676370', 'Lat:-7.8136100, Lng:110.3676370', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-27', 0, NULL, '2025-09-01 15:32:11'),
(5, 4, '2025-08-28 04:10:00', '2025-08-28', NULL, NULL, NULL, 'masuk', 0, 'Pending', '20250828_041000_4_ibrahim_masuk_68af742866031.jpg', '', '', '', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28', 0, NULL, '2025-09-01 15:32:11'),
(6, 2, '2025-08-28 15:20:23', '2025-08-28', NULL, NULL, NULL, 'masuk', 0, 'Pending', NULL, '-7.8136100', '110.3676360', 'Lat:-7.8136100, Lng:110.3676360', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28', 0, NULL, '2025-09-01 15:32:11'),
(7, 2, '2025-08-28 16:10:13', '2025-08-28', NULL, NULL, NULL, 'pulang', 0, 'Disetujui', 'uploads/20250828_161013_2_pulang_b7f82b2d.jpg', '-7.8136100', '110.3676360', 'Lat:-7.8136100, Lng:110.3676360', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28', 0, NULL, '2025-09-01 15:32:11');

-- --------------------------------------------------------

--
-- Table structure for table `absensi_detail`
--

CREATE TABLE `absensi_detail` (
  `id` int(11) NOT NULL,
  `absensi_id` int(11) NOT NULL,
  `nama_tugas` varchar(100) DEFAULT NULL,
  `sub_tugas` varchar(100) DEFAULT NULL,
  `jumlah` int(11) DEFAULT 0,
  `sumber` enum('dropdown','manual') DEFAULT 'dropdown',
  `detail` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absensi_detail`
--

INSERT INTO `absensi_detail` (`id`, `absensi_id`, `nama_tugas`, `sub_tugas`, `jumlah`, `sumber`, `detail`, `created_at`) VALUES
(65, 114, 'desain website', NULL, 1, 'manual', 'asda', '2025-10-22 08:30:34'),
(66, 114, 'makan bakso', NULL, 1, 'manual', 'bakso urat', '2025-10-22 08:30:34');

-- --------------------------------------------------------

--
-- Table structure for table `absensi_todo`
--

CREATE TABLE `absensi_todo` (
  `id` int(11) NOT NULL,
  `absensi_id` int(11) NOT NULL,
  `sumber` enum('dropdown','manual') NOT NULL DEFAULT 'dropdown',
  `master_id` int(11) DEFAULT NULL,
  `sub_nama` varchar(100) DEFAULT NULL,
  `manual_judul` varchar(200) DEFAULT NULL,
  `manual_detail` text DEFAULT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 0,
  `is_done` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absensi_todo`
--

INSERT INTO `absensi_todo` (`id`, `absensi_id`, `sumber`, `master_id`, `sub_nama`, `manual_judul`, `manual_detail`, `jumlah`, `is_done`) VALUES
(67, 113, 'manual', NULL, NULL, 'makan bakso', 'bakso urat', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `absensi_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'info',
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int(11) NOT NULL,
  `nama_shift` varchar(50) NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_pulang` time NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`id`, `nama_shift`, `jam_masuk`, `jam_pulang`, `aktif`) VALUES
(1, 'Shift 1', '06:00:00', '13:30:00', 1),
(2, 'Shift 2', '08:00:00', '16:00:00', 1),
(3, 'Shift 3', '11:00:00', '18:00:00', 1),
(4, 'Shift 4', '12:00:00', '19:00:00', 1),
(5, 'Shift 5', '13:00:00', '20:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `shift_master`
--

CREATE TABLE `shift_master` (
  `id` int(11) NOT NULL,
  `nama_shift` varchar(50) NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_pulang` time DEFAULT NULL,
  `durasi_menit` int(11) NOT NULL DEFAULT 480,
  `toleransi_menit` int(11) NOT NULL DEFAULT 10,
  `aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shift_master`
--

INSERT INTO `shift_master` (`id`, `nama_shift`, `jam_masuk`, `jam_pulang`, `durasi_menit`, `toleransi_menit`, `aktif`) VALUES
(10, 'PAGI', '07:00:00', '00:00:13', 1020, 0, 1),
(11, 'SIANG', '15:30:00', '15:31:00', 1, 0, 1),
(12, 'testing', '15:23:00', '15:26:00', 3, 0, 1);

--
-- Triggers `shift_master`
--
DELIMITER $$
CREATE TRIGGER `trg_shift_master_set_durasi` BEFORE INSERT ON `shift_master` FOR EACH ROW BEGIN
  IF NEW.jam_pulang IS NOT NULL THEN
    SET NEW.durasi_menit = ROUND( ( ( (TIME_TO_SEC(NEW.jam_pulang) - TIME_TO_SEC(NEW.jam_masuk) + 86400) % 86400 ) / 60 ) );
    IF NEW.durasi_menit < 0 THEN SET NEW.durasi_menit = 0; END IF;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_shift_master_update_durasi` BEFORE UPDATE ON `shift_master` FOR EACH ROW BEGIN
  IF NEW.jam_pulang IS NOT NULL THEN
    SET NEW.durasi_menit = ROUND( ( ( (TIME_TO_SEC(NEW.jam_pulang) - TIME_TO_SEC(NEW.jam_masuk) + 86400) % 86400 ) / 60 ) );
    IF NEW.durasi_menit < 0 THEN SET NEW.durasi_menit = 0; END IF;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tugas_absensi`
--

CREATE TABLE `tugas_absensi` (
  `id` int(11) NOT NULL,
  `absensi_id` int(11) NOT NULL,
  `master_id` int(11) DEFAULT NULL,
  `sub_tugas` varchar(255) DEFAULT NULL,
  `manual_detail` text DEFAULT NULL,
  `jumlah` int(11) DEFAULT 0,
  `sumber` enum('dropdown','manual') DEFAULT 'dropdown',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tugas_harian`
--

CREATE TABLE `tugas_harian` (
  `id` int(11) NOT NULL,
  `absensi_id` int(11) NOT NULL,
  `nama_tugas` varchar(100) NOT NULL,
  `jenis_tugas` varchar(50) NOT NULL,
  `sumber` enum('dropdown','manual') NOT NULL DEFAULT 'dropdown',
  `sub_tugas` varchar(150) DEFAULT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tugas_master`
--

CREATE TABLE `tugas_master` (
  `id` int(11) NOT NULL,
  `nama_tugas` varchar(100) NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tugas_sub_master`
--

CREATE TABLE `tugas_sub_master` (
  `id` int(11) NOT NULL,
  `master_id` int(11) NOT NULL,
  `nama_sub` varchar(150) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `devisi` varchar(100) DEFAULT NULL,
  `nim` varchar(50) DEFAULT NULL,
  `jurusan` varchar(100) DEFAULT NULL,
  `asal_sekolah` varchar(150) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `no_hp_orangtua` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `nama`, `devisi`, `nim`, `jurusan`, `asal_sekolah`, `tanggal_lahir`, `no_hp`, `no_hp_orangtua`, `password`, `role`, `aktif`, `created_at`, `foto`) VALUES
(3, 'jaki', 'jaki admin ganteng', 'TIM SOSMED', '', '', '', NULL, NULL, NULL, '$2y$10$G89hEgOyNrIJeF4v.rrQVOM.hEN02sSLzi/DJRGkZlYxanZ5.F1Zm', 'admin', 1, '2025-09-30 12:11:48', NULL),
(18, 'tiara', 'TIARA', 'SMK TELKOM', '', '', 'SMK TELKOM', NULL, '', '', '$2y$10$.gxm1apPVdvabYhaqz5LcOKFDdg45t6WFIpi5vD2kG6Gk00UXSNIm', 'user', 1, '2025-10-13 04:17:16', NULL),
(19, 'azam', 'AZAM', 'SMK TAMSIS', '', '', 'SMK TAMSIS', NULL, '', '', '$2y$10$yrrQPKH3wuAUnyW6DaZ.SeLD1Zz5Xt3Ge1hB7zO9R9fRhMqaOzBrq', 'user', 1, '2025-10-13 04:22:42', NULL),
(20, 'gunawan', 'Gunawan', 'SMK TAMSIS', '', '', 'SMK TAMSIS', NULL, '', '', '$2y$10$s.vikSrbT8eNAzz43UYufO896wkaXpteFbh53vz7M/JVbthqeVSw2', 'user', 1, '2025-10-13 04:23:12', NULL),
(21, 'alex', 'Alex', 'SMK TAMSIS', '', '', 'SMK TAMSIS', NULL, '', '', '$2y$10$wFohI8sHZBH.Y1KouDt.5etTKvXYNsylicrgodxjIB394eEjInIDK', 'user', 1, '2025-10-13 04:23:35', NULL),
(22, 'raka', 'Raka', 'SMK TAMSIS', '', '', 'SMK TAMSIS', NULL, '', '', '$2y$10$e48Ca.o4TpZW0EnMVhsUz.0ohBzGvg5QWyhJcD0d3sk9OFh2BOqUK', 'user', 1, '2025-10-13 04:23:55', NULL),
(23, 'elvino', 'Elvino', 'SMK TAMSIS', '', '', 'SMK TAMSIS', NULL, '', '', '$2y$10$JMTGv68iURxAgsNo4A8i4eZipRjJ.92oNEeJorvd1EMuG92hFRr8K', 'user', 1, '2025-10-13 04:24:20', NULL),
(24, 'adam', 'Adam', 'SMK TAMSIS', '', '', 'SMK TAMSIS', NULL, '', '', '$2y$10$.lLTAWSGTMAystB65tfm0.CLrXa9/b1SZJjlQDXCGEl.79LzZFsVe', 'user', 1, '2025-10-13 04:24:37', NULL),
(25, 'ikky', 'Ikky', 'SMK TAMSIS', '', '', 'SMK TAMSIS', NULL, '', '', '$2y$10$24skOSdk3Dxhfz1qMWDvJuGVBojgTlwYYtGsw.SWESMa0KcPHjjJ.', 'user', 1, '2025-10-13 04:24:57', NULL),
(26, 'arya', 'Arya', 'SMK TAMSIS', '', '', 'SMK TAMSIS', NULL, '', '', '$2y$10$Jgpkx1WNz.FprQblfKprkefkIY/isoiYBsoJ9cedhiQEeiACsGr9u', 'user', 1, '2025-10-13 04:25:18', NULL),
(27, 'syafii', 'Syafii', 'SMK TAMSIS', '', '', 'SMK TAMSIS', NULL, '', '', '$2y$10$Ghg2xSBXQXwUY64T6AK7aefO7gD10RKLnN1iGielGY50ntJiay9LK', 'user', 1, '2025-10-13 04:25:40', NULL),
(28, 'alena', 'Alena', 'POLITEKNIK JEPARA', '', '', 'POLITEKNIK JEPARA', NULL, '', '', '$2y$10$3iHgu3.bb8r2qU5n7yYvWuLtp09S5oCBeDSv5kIOHMiGLx.5R9BLK', 'user', 1, '2025-10-13 04:25:56', NULL),
(29, 'adel', 'Adel', 'POLITEKNIK JEPARA', '', '', 'POLITEKNIK JEPARA', NULL, '', '', '$2y$10$3mOKflB.Z.tX/f78Wwrgp.nZPhV.xdI..AZNkYyTPIbGYXynGVsN2', 'user', 1, '2025-10-13 04:26:13', NULL),
(30, 'hilma', 'Hilma', 'POLITEKNIK JEPARA', '', '', 'POLITEKNIK JEPARA', NULL, '', '', '$2y$10$zbhtSTy/JIsUmq3RM8kH5eoN7lRryLa28whyWEFAoWKFW5NQlhYIC', 'user', 1, '2025-10-13 04:26:31', NULL),
(31, 'bagas', 'BAGAS', 'SMKN 5 YOGYAKARTA', '', 'ANIMASI', 'SMKN 5 YOGYAKARTA', NULL, '', '', '$2y$10$y7sfN3ZKwStX3lq0TDjvX.InwVVOGgi2mW5PmiMaTEBof2BPhM69.', 'user', 1, '2025-10-14 13:48:31', NULL),
(32, 'irfan', 'IRFAN', 'SMKN 5 YOGYAKARTA', '', 'ANIMASI', 'SMKN 5 YOGYAKARTA', NULL, '', '', '$2y$10$RhsCaOkfIFeq6QungWiIueO2W78dyOh6A4KFwgj3/lOCFS7FV0jBq', 'user', 1, '2025-10-14 13:49:20', NULL),
(33, 'renaldo', 'RENALDO', 'SMKN 5 YOGYAKARTA', '', 'ANIMASI', 'SMKN 5 YOGYAKARTA', NULL, '', '', '$2y$10$KRB03G9khJvCIEEgID8Ng.0yp4fh2aJ1p3M.UutoZ3MeE4yf3Soo2', 'user', 1, '2025-10-14 13:50:37', NULL),
(34, 'dian', 'DIAN', 'SMKN 5 YOGYAKARTA', '', 'ANIMASI', 'SMKN 5 YOGYAKARTA', NULL, '', '', '$2y$10$QYn4rU4BgihiL5FKjorrT.xxqk2ciE4/tNgUrsG.nUAtrpuRuXmGu', 'user', 1, '2025-10-14 13:51:14', NULL),
(35, 'abid', 'ABID', 'SMKN 5 YOGYAKARTA', '', 'ANIMASI', 'SMKN 5 YOGYAKARTA', NULL, '', '', '$2y$10$mcsAmWOON/WQp30v3lYsiectcauxt.YAwxYsTLAJtdGH2Hs7c9/gW', 'user', 1, '2025-10-14 13:51:53', NULL),
(36, 'noel', 'NOEL', 'SMKN 5 YOGYAKARTA', '', 'ANIMASI', 'SMKN 5 YOGYAKARTA', NULL, '', '', '$2y$10$igop0zQvuirQLtL9SngUfezuvTGNByquL/Bu9RX6dVYPOD3fjmiJm', 'user', 1, '2025-10-14 13:52:28', NULL),
(37, 'fikar', 'FIKAR', 'SMKN 5 YOGYAKARTA', '', 'ANIMASI', 'SMKN 5 YOGYAKARTA', NULL, '', '', '$2y$10$T4PZ3g0SGdQEasDXExwyaeVcBdML3WQBHU3sr627rFd5Wyu4tV6zS', 'user', 1, '2025-10-14 13:53:46', NULL),
(38, 'arif', 'ARIF', 'SMKN 5 YOGYAKARTA', '', 'ANIMASI', 'SMKN 5 YOGYAKARTA', NULL, '', '', '$2y$10$fss3G.0hqrbnMJQ9ehSXb.YwQVIebUh03cwjTjyXNaUaWmPYsf7.6', 'user', 1, '2025-10-14 13:54:27', NULL),
(39, 'nabil', 'NABIL', 'SMKN 5 YOGYAKARTA', '', 'ANIMASI', 'SMKN 5 YOGYAKARTA', NULL, '', '', '$2y$10$yjWVS68GRg8b2jNt7MvoOeIPitDaozv/gpwpCsLOoXBxc7IkZ7KVS', 'user', 1, '2025-10-14 13:55:15', NULL),
(40, 'arjuna', 'ARJUNA', 'SMKN 5 YOGYAKARTA', '', 'ANIMASI', 'SMKN 5 YOGYAKARTA', NULL, '', '', '$2y$10$v7a0sjqJPHDvra8dA8BMquo8HsYHRAEKUHbNWi.FD5kbc0Ih8YIXe', 'user', 1, '2025-10-14 13:55:45', NULL),
(41, 'lukman', 'LUKMAN', 'SMKN 5 YOGYAKARTA', '', 'ANIMASI', 'SMKN 5 YOGYAKARTA', NULL, '', '', '$2y$10$7h44U8WYIdjsyOhWTSaefuT.guwwwQSRUwDGugBLDXsGoQyAOgPxu', 'user', 1, '2025-10-14 13:56:17', NULL),
(42, 'nugroho', 'NUGROHO', 'SMKN 5 YOGYAKARTA', '', 'ANIMASI', 'SMKN 5 YOGYAKARTA', NULL, '', '', '$2y$10$ZFI8jGvOVpVG4lCyxVTgde8IDdqB9JF1cm7cOMmZf6LWEKSCgC4H6', 'user', 1, '2025-10-14 13:57:20', NULL),
(43, 'testing', 'Testing', 'PT mencari cinta sejati', '123', '123', '123', '1997-05-23', '0123456798', '0123456789', '$2y$10$rcnEun63uK8oNJydeev0Uu4jWWdQqcVDcHwYE/ZAmKgT9W3xH1PnW', 'user', 1, '2025-10-16 14:25:37', 'uploads/avatars/20251016160049_testing_123.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `user_jadwal`
--

CREATE TABLE `user_jadwal` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `status` enum('ON','OFF','TM') NOT NULL DEFAULT 'ON',
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_jadwal`
--

INSERT INTO `user_jadwal` (`id`, `user_id`, `tanggal`, `shift_id`, `status`, `jam_masuk`, `jam_pulang`) VALUES
(29, 3, '2025-09-08', NULL, 'OFF', NULL, NULL),
(30, 3, '2025-09-09', NULL, 'ON', NULL, NULL),
(31, 3, '2025-09-10', NULL, 'OFF', NULL, NULL),
(32, 3, '2025-09-11', NULL, 'OFF', NULL, NULL),
(33, 3, '2025-09-12', NULL, 'OFF', NULL, NULL),
(34, 3, '2025-09-13', NULL, 'OFF', NULL, NULL),
(35, 3, '2025-09-14', NULL, 'OFF', NULL, NULL),
(330, 3, '2025-09-15', NULL, 'OFF', NULL, NULL),
(331, 3, '2025-09-16', NULL, 'OFF', NULL, NULL),
(332, 3, '2025-09-17', NULL, 'OFF', NULL, NULL),
(333, 3, '2025-09-18', NULL, 'OFF', NULL, NULL),
(334, 3, '2025-09-19', NULL, 'OFF', NULL, NULL),
(335, 3, '2025-09-20', NULL, 'OFF', NULL, NULL),
(336, 3, '2025-09-21', NULL, 'OFF', NULL, NULL),
(8447, 29, '2025-10-13', 10, 'ON', NULL, NULL),
(8448, 29, '2025-10-14', NULL, 'OFF', NULL, NULL),
(8449, 29, '2025-10-15', 11, 'ON', NULL, NULL),
(8450, 29, '2025-10-16', 10, 'ON', NULL, NULL),
(8451, 29, '2025-10-17', 11, 'ON', NULL, NULL),
(8452, 29, '2025-10-18', 11, 'ON', NULL, NULL),
(8453, 29, '2025-10-19', 10, 'ON', NULL, NULL),
(8454, 28, '2025-10-13', 10, 'ON', NULL, NULL),
(8455, 28, '2025-10-14', NULL, 'OFF', NULL, NULL),
(8456, 28, '2025-10-15', 11, 'ON', NULL, NULL),
(8457, 28, '2025-10-16', 10, 'ON', NULL, NULL),
(8458, 28, '2025-10-17', 11, 'ON', NULL, NULL),
(8459, 28, '2025-10-18', 11, 'ON', NULL, NULL),
(8460, 28, '2025-10-19', 10, 'ON', NULL, NULL),
(8461, 30, '2025-10-13', 10, 'ON', NULL, NULL),
(8462, 30, '2025-10-14', NULL, 'OFF', NULL, NULL),
(8463, 30, '2025-10-15', 11, 'ON', NULL, NULL),
(8464, 30, '2025-10-16', 10, 'ON', NULL, NULL),
(8465, 30, '2025-10-17', 11, 'ON', NULL, NULL),
(8466, 30, '2025-10-18', 11, 'ON', NULL, NULL),
(8467, 30, '2025-10-19', 10, 'ON', NULL, NULL),
(8468, 24, '2025-10-13', 10, 'ON', NULL, NULL),
(8469, 24, '2025-10-14', NULL, 'OFF', NULL, NULL),
(8470, 24, '2025-10-15', 11, 'ON', NULL, NULL),
(8471, 24, '2025-10-16', 10, 'ON', NULL, NULL),
(8472, 24, '2025-10-17', 11, 'ON', NULL, NULL),
(8473, 24, '2025-10-18', 11, 'ON', NULL, NULL),
(8474, 24, '2025-10-19', 10, 'ON', NULL, NULL),
(8475, 21, '2025-10-13', 10, 'ON', NULL, NULL),
(8476, 21, '2025-10-14', NULL, 'OFF', NULL, NULL),
(8477, 21, '2025-10-15', 11, 'ON', NULL, NULL),
(8478, 21, '2025-10-16', 10, 'ON', NULL, NULL),
(8479, 21, '2025-10-17', 11, 'ON', NULL, NULL),
(8480, 21, '2025-10-18', 11, 'ON', NULL, NULL),
(8481, 21, '2025-10-19', 10, 'ON', NULL, NULL),
(8482, 26, '2025-10-13', 10, 'ON', NULL, NULL),
(8483, 26, '2025-10-14', NULL, 'OFF', NULL, NULL),
(8484, 26, '2025-10-15', 11, 'ON', NULL, NULL),
(8485, 26, '2025-10-16', 10, 'ON', NULL, NULL),
(8486, 26, '2025-10-17', 11, 'ON', NULL, NULL),
(8487, 26, '2025-10-18', 11, 'ON', NULL, NULL),
(8488, 26, '2025-10-19', 10, 'ON', NULL, NULL),
(8489, 19, '2025-10-13', 10, 'ON', NULL, NULL),
(8490, 19, '2025-10-14', NULL, 'OFF', NULL, NULL),
(8491, 19, '2025-10-15', 11, 'ON', NULL, NULL),
(8492, 19, '2025-10-16', 10, 'ON', NULL, NULL),
(8493, 19, '2025-10-17', 11, 'ON', NULL, NULL),
(8494, 19, '2025-10-18', 11, 'ON', NULL, NULL),
(8495, 19, '2025-10-19', 10, 'ON', NULL, NULL),
(8496, 23, '2025-10-13', 10, 'ON', NULL, NULL),
(8497, 23, '2025-10-14', NULL, 'OFF', NULL, NULL),
(8498, 23, '2025-10-15', 11, 'ON', NULL, NULL),
(8499, 23, '2025-10-16', 10, 'ON', NULL, NULL),
(8500, 23, '2025-10-17', 11, 'ON', NULL, NULL),
(8501, 23, '2025-10-18', 11, 'ON', NULL, NULL),
(8502, 23, '2025-10-19', 10, 'ON', NULL, NULL),
(8503, 20, '2025-10-13', 10, 'ON', NULL, NULL),
(8504, 20, '2025-10-14', NULL, 'OFF', NULL, NULL),
(8505, 20, '2025-10-15', 11, 'ON', NULL, NULL),
(8506, 20, '2025-10-16', 10, 'ON', NULL, NULL),
(8507, 20, '2025-10-17', 11, 'ON', NULL, NULL),
(8508, 20, '2025-10-18', 11, 'ON', NULL, NULL),
(8509, 20, '2025-10-19', 10, 'ON', NULL, NULL),
(8510, 25, '2025-10-13', 10, 'ON', NULL, NULL),
(8511, 25, '2025-10-14', NULL, 'OFF', NULL, NULL),
(8512, 25, '2025-10-15', 11, 'ON', NULL, NULL),
(8513, 25, '2025-10-16', 10, 'ON', NULL, NULL),
(8514, 25, '2025-10-17', 11, 'ON', NULL, NULL),
(8515, 25, '2025-10-18', 11, 'ON', NULL, NULL),
(8516, 25, '2025-10-19', 10, 'ON', NULL, NULL),
(8517, 22, '2025-10-13', 10, 'ON', NULL, NULL),
(8518, 22, '2025-10-14', NULL, 'OFF', NULL, NULL),
(8519, 22, '2025-10-15', 11, 'ON', NULL, NULL),
(8520, 22, '2025-10-16', 10, 'ON', NULL, NULL),
(8521, 22, '2025-10-17', 10, 'ON', NULL, NULL),
(8522, 22, '2025-10-18', 11, 'ON', NULL, NULL),
(8523, 22, '2025-10-19', 10, 'ON', NULL, NULL),
(8524, 27, '2025-10-13', 10, 'ON', NULL, NULL),
(8525, 27, '2025-10-14', NULL, 'OFF', NULL, NULL),
(8526, 27, '2025-10-15', 11, 'ON', NULL, NULL),
(8527, 27, '2025-10-16', 10, 'ON', NULL, NULL),
(8528, 27, '2025-10-17', 11, 'ON', NULL, NULL),
(8529, 27, '2025-10-18', 11, 'ON', NULL, NULL),
(8530, 27, '2025-10-19', 10, 'ON', NULL, NULL),
(8531, 18, '2025-10-13', 10, 'ON', NULL, NULL),
(8532, 18, '2025-10-14', 10, 'ON', NULL, NULL),
(8533, 18, '2025-10-15', 10, 'ON', NULL, NULL),
(8534, 18, '2025-10-16', 10, 'ON', NULL, NULL),
(8535, 18, '2025-10-17', 10, 'ON', NULL, NULL),
(8536, 18, '2025-10-18', 11, 'ON', NULL, NULL),
(8537, 18, '2025-10-19', 10, 'ON', NULL, NULL),
(8629, 35, '2025-10-13', 11, 'ON', NULL, NULL),
(8630, 35, '2025-10-14', 11, 'ON', NULL, NULL),
(8631, 35, '2025-10-15', NULL, 'OFF', NULL, NULL),
(8632, 35, '2025-10-16', 11, 'ON', NULL, NULL),
(8633, 35, '2025-10-17', 10, 'ON', NULL, NULL),
(8634, 35, '2025-10-18', NULL, 'OFF', NULL, NULL),
(8635, 35, '2025-10-19', NULL, 'OFF', NULL, NULL),
(8636, 38, '2025-10-13', 11, 'ON', NULL, NULL),
(8637, 38, '2025-10-14', 11, 'ON', NULL, NULL),
(8638, 38, '2025-10-15', NULL, 'OFF', NULL, NULL),
(8639, 38, '2025-10-16', 11, 'ON', NULL, NULL),
(8640, 38, '2025-10-17', 10, 'ON', NULL, NULL),
(8641, 38, '2025-10-18', 10, 'ON', NULL, NULL),
(8642, 38, '2025-10-19', 11, 'ON', NULL, NULL),
(8643, 40, '2025-10-13', 11, 'ON', NULL, NULL),
(8644, 40, '2025-10-14', 11, 'ON', NULL, NULL),
(8645, 40, '2025-10-15', NULL, 'OFF', NULL, NULL),
(8646, 40, '2025-10-16', 11, 'ON', NULL, NULL),
(8647, 40, '2025-10-17', 10, 'ON', NULL, NULL),
(8648, 40, '2025-10-18', 10, 'ON', NULL, NULL),
(8649, 40, '2025-10-19', 11, 'ON', NULL, NULL),
(8650, 31, '2025-10-13', 11, 'ON', NULL, NULL),
(8651, 31, '2025-10-14', 11, 'ON', NULL, NULL),
(8652, 31, '2025-10-15', NULL, 'OFF', NULL, NULL),
(8653, 31, '2025-10-16', 11, 'ON', NULL, NULL),
(8654, 31, '2025-10-17', 10, 'ON', NULL, NULL),
(8655, 31, '2025-10-18', 10, 'ON', NULL, NULL),
(8656, 31, '2025-10-19', 11, 'ON', NULL, NULL),
(8657, 34, '2025-10-13', 11, 'ON', NULL, NULL),
(8658, 34, '2025-10-14', 11, 'ON', NULL, NULL),
(8659, 34, '2025-10-15', NULL, 'OFF', NULL, NULL),
(8660, 34, '2025-10-16', 11, 'ON', NULL, NULL),
(8661, 34, '2025-10-17', 10, 'ON', NULL, NULL),
(8662, 34, '2025-10-18', 10, 'ON', NULL, NULL),
(8663, 34, '2025-10-19', 11, 'ON', NULL, NULL),
(8664, 37, '2025-10-13', 11, 'ON', NULL, NULL),
(8665, 37, '2025-10-14', 11, 'ON', NULL, NULL),
(8666, 37, '2025-10-15', NULL, 'OFF', NULL, NULL),
(8667, 37, '2025-10-16', 11, 'ON', NULL, NULL),
(8668, 37, '2025-10-17', 10, 'ON', NULL, NULL),
(8669, 37, '2025-10-18', 10, 'ON', NULL, NULL),
(8670, 37, '2025-10-19', 11, 'ON', NULL, NULL),
(8671, 32, '2025-10-13', 11, 'ON', NULL, NULL),
(8672, 32, '2025-10-14', 11, 'ON', NULL, NULL),
(8673, 32, '2025-10-15', NULL, 'OFF', NULL, NULL),
(8674, 32, '2025-10-16', 11, 'ON', NULL, NULL),
(8675, 32, '2025-10-17', 10, 'ON', NULL, NULL),
(8676, 32, '2025-10-18', 10, 'ON', NULL, NULL),
(8677, 32, '2025-10-19', 11, 'ON', NULL, NULL),
(8678, 41, '2025-10-13', 11, 'ON', NULL, NULL),
(8679, 41, '2025-10-14', 11, 'ON', NULL, NULL),
(8680, 41, '2025-10-15', NULL, 'OFF', NULL, NULL),
(8681, 41, '2025-10-16', 11, 'ON', NULL, NULL),
(8682, 41, '2025-10-17', 10, 'ON', NULL, NULL),
(8683, 41, '2025-10-18', 10, 'ON', NULL, NULL),
(8684, 41, '2025-10-19', 11, 'ON', NULL, NULL),
(8685, 39, '2025-10-13', 11, 'ON', NULL, NULL),
(8686, 39, '2025-10-14', 11, 'ON', NULL, NULL),
(8687, 39, '2025-10-15', NULL, 'OFF', NULL, NULL),
(8688, 39, '2025-10-16', 11, 'ON', NULL, NULL),
(8689, 39, '2025-10-17', 10, 'ON', NULL, NULL),
(8690, 39, '2025-10-18', 10, 'ON', NULL, NULL),
(8691, 39, '2025-10-19', 11, 'ON', NULL, NULL),
(8692, 36, '2025-10-13', 11, 'ON', NULL, NULL),
(8693, 36, '2025-10-14', 11, 'ON', NULL, NULL),
(8694, 36, '2025-10-15', NULL, 'OFF', NULL, NULL),
(8695, 36, '2025-10-16', 11, 'ON', NULL, NULL),
(8696, 36, '2025-10-17', 10, 'ON', NULL, NULL),
(8697, 36, '2025-10-18', 10, 'ON', NULL, NULL),
(8698, 36, '2025-10-19', 11, 'ON', NULL, NULL),
(8699, 42, '2025-10-13', 11, 'ON', NULL, NULL),
(8700, 42, '2025-10-14', 11, 'ON', NULL, NULL),
(8701, 42, '2025-10-15', NULL, 'OFF', NULL, NULL),
(8702, 42, '2025-10-16', 11, 'ON', NULL, NULL),
(8703, 42, '2025-10-17', 10, 'ON', NULL, NULL),
(8704, 42, '2025-10-18', 10, 'ON', NULL, NULL),
(8705, 42, '2025-10-19', 11, 'ON', NULL, NULL),
(8706, 33, '2025-10-13', 11, 'ON', NULL, NULL),
(8707, 33, '2025-10-14', 11, 'ON', NULL, NULL),
(8708, 33, '2025-10-15', NULL, 'OFF', NULL, NULL),
(8709, 33, '2025-10-16', 11, 'ON', NULL, NULL),
(8710, 33, '2025-10-17', 10, 'ON', NULL, NULL),
(8711, 33, '2025-10-18', 10, 'ON', NULL, NULL),
(8712, 33, '2025-10-19', 11, 'ON', NULL, NULL),
(8734, 43, '2025-10-13', NULL, 'OFF', NULL, NULL),
(8735, 43, '2025-10-14', NULL, 'OFF', NULL, NULL),
(8736, 43, '2025-10-15', NULL, 'OFF', NULL, NULL),
(8737, 43, '2025-10-16', 12, 'ON', NULL, NULL),
(8738, 43, '2025-10-17', NULL, 'OFF', NULL, NULL),
(8739, 43, '2025-10-18', NULL, 'OFF', NULL, NULL),
(8740, 43, '2025-10-19', NULL, 'OFF', NULL, NULL),
(9441, 29, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9442, 29, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9443, 29, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9444, 29, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9445, 29, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9446, 29, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9447, 29, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9448, 28, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9449, 28, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9450, 28, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9451, 28, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9452, 28, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9453, 28, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9454, 28, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9455, 30, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9456, 30, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9457, 30, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9458, 30, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9459, 30, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9460, 30, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9461, 30, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9462, 43, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9463, 43, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9464, 43, '2025-10-22', 11, 'ON', NULL, NULL),
(9465, 43, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9466, 43, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9467, 43, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9468, 43, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9469, 24, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9470, 24, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9471, 24, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9472, 24, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9473, 24, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9474, 24, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9475, 24, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9476, 21, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9477, 21, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9478, 21, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9479, 21, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9480, 21, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9481, 21, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9482, 21, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9483, 26, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9484, 26, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9485, 26, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9486, 26, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9487, 26, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9488, 26, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9489, 26, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9490, 19, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9491, 19, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9492, 19, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9493, 19, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9494, 19, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9495, 19, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9496, 19, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9497, 23, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9498, 23, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9499, 23, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9500, 23, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9501, 23, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9502, 23, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9503, 23, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9504, 20, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9505, 20, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9506, 20, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9507, 20, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9508, 20, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9509, 20, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9510, 20, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9511, 25, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9512, 25, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9513, 25, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9514, 25, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9515, 25, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9516, 25, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9517, 25, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9518, 22, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9519, 22, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9520, 22, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9521, 22, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9522, 22, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9523, 22, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9524, 22, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9525, 27, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9526, 27, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9527, 27, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9528, 27, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9529, 27, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9530, 27, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9531, 27, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9532, 18, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9533, 18, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9534, 18, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9535, 18, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9536, 18, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9537, 18, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9538, 18, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9539, 35, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9540, 35, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9541, 35, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9542, 35, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9543, 35, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9544, 35, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9545, 35, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9546, 38, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9547, 38, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9548, 38, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9549, 38, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9550, 38, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9551, 38, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9552, 38, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9553, 40, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9554, 40, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9555, 40, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9556, 40, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9557, 40, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9558, 40, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9559, 40, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9560, 31, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9561, 31, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9562, 31, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9563, 31, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9564, 31, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9565, 31, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9566, 31, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9567, 34, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9568, 34, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9569, 34, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9570, 34, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9571, 34, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9572, 34, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9573, 34, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9574, 37, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9575, 37, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9576, 37, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9577, 37, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9578, 37, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9579, 37, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9580, 37, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9581, 32, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9582, 32, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9583, 32, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9584, 32, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9585, 32, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9586, 32, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9587, 32, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9588, 41, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9589, 41, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9590, 41, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9591, 41, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9592, 41, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9593, 41, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9594, 41, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9595, 39, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9596, 39, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9597, 39, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9598, 39, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9599, 39, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9600, 39, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9601, 39, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9602, 36, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9603, 36, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9604, 36, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9605, 36, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9606, 36, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9607, 36, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9608, 36, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9609, 42, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9610, 42, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9611, 42, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9612, 42, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9613, 42, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9614, 42, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9615, 42, '2025-10-26', NULL, 'OFF', NULL, NULL),
(9616, 33, '2025-10-20', NULL, 'OFF', NULL, NULL),
(9617, 33, '2025-10-21', NULL, 'OFF', NULL, NULL),
(9618, 33, '2025-10-22', NULL, 'OFF', NULL, NULL),
(9619, 33, '2025-10-23', NULL, 'OFF', NULL, NULL),
(9620, 33, '2025-10-24', NULL, 'OFF', NULL, NULL),
(9621, 33, '2025-10-25', NULL, 'OFF', NULL, NULL),
(9622, 33, '2025-10-26', NULL, 'OFF', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_shift`
--

CREATE TABLE `user_shift` (
  `user_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_user_jadwal_hari_ini`
-- (See below for the actual view)
--
CREATE TABLE `v_user_jadwal_hari_ini` (
`user_id` int(11)
,`tanggal` date
,`shift_id` int(11)
,`jam_masuk` time
,`jam_pulang` time
,`toleransi_menit` int(11)
,`durasi_menit` int(11)
,`status` varchar(3)
);

-- --------------------------------------------------------

--
-- Structure for view `v_user_jadwal_hari_ini`
--
DROP TABLE IF EXISTS `v_user_jadwal_hari_ini`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_user_jadwal_hari_ini`  AS SELECT `u`.`id` AS `user_id`, coalesce(`uj`.`tanggal`,curdate()) AS `tanggal`, coalesce(`uj`.`shift_id`,`us`.`shift_id`) AS `shift_id`, coalesce(`uj`.`jam_masuk`,`sm`.`jam_masuk`) AS `jam_masuk`, coalesce(`uj`.`jam_pulang`,`sm`.`jam_pulang`) AS `jam_pulang`, `sm`.`toleransi_menit` AS `toleransi_menit`, `sm`.`durasi_menit` AS `durasi_menit`, coalesce(`uj`.`status`,'ON') AS `status` FROM (((`users` `u` left join `user_jadwal` `uj` on(`uj`.`user_id` = `u`.`id` and `uj`.`tanggal` = curdate())) left join `user_shift` `us` on(`us`.`user_id` = `u`.`id` and `us`.`aktif` = 1)) left join `shift_master` `sm` on(`sm`.`id` = coalesce(`uj`.`shift_id`,`us`.`shift_id`))) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_tanggal_status` (`user_id`,`tanggal`,`status`),
  ADD UNIQUE KEY `uq_user_tgl_status` (`user_id`,`tgl`,`status`),
  ADD KEY `idx_absensi_user_date` (`user_id`,`waktu`),
  ADD KEY `idx_absensi_user_tanggal` (`user_id`,`tanggal`),
  ADD KEY `idx_absensi_tanggal` (`tanggal`),
  ADD KEY `idx_user_status_tanggal` (`user_id`,`status`,`tanggal`),
  ADD KEY `idx_user_tanggal` (`user_id`,`tanggal`),
  ADD KEY `idx_tanggal_status` (`tanggal`,`status`),
  ADD KEY `idx_absensi_waktu` (`waktu`),
  ADD KEY `idx_absensi_user_status_waktu` (`user_id`,`status`,`waktu`);

--
-- Indexes for table `absensi_detail`
--
ALTER TABLE `absensi_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `absensi_id` (`absensi_id`),
  ADD KEY `idx_absensi_detail_absensi` (`absensi_id`);

--
-- Indexes for table `absensi_todo`
--
ALTER TABLE `absensi_todo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `absensi_id` (`absensi_id`),
  ADD KEY `master_id` (`master_id`),
  ADD KEY `idx_absensi_todo_absensi` (`absensi_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_absensi` (`absensi_id`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_nama_shift` (`nama_shift`);

--
-- Indexes for table `shift_master`
--
ALTER TABLE `shift_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tugas_absensi`
--
ALTER TABLE `tugas_absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `absensi_id` (`absensi_id`);

--
-- Indexes for table `tugas_harian`
--
ALTER TABLE `tugas_harian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tugas_harian_absensi` (`absensi_id`);

--
-- Indexes for table `tugas_master`
--
ALTER TABLE `tugas_master`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tugas_master_namatugas` (`nama_tugas`);

--
-- Indexes for table `tugas_sub_master`
--
ALTER TABLE `tugas_sub_master`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tsm_master` (`master_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_aktif` (`aktif`);

--
-- Indexes for table `user_jadwal`
--
ALTER TABLE `user_jadwal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_date` (`user_id`,`tanggal`),
  ADD UNIQUE KEY `ux_user_date` (`user_id`,`tanggal`),
  ADD KEY `idx_shift` (`shift_id`);

--
-- Indexes for table `user_shift`
--
ALTER TABLE `user_shift`
  ADD PRIMARY KEY (`user_id`,`shift_id`),
  ADD KEY `fk_user_shift_shift` (`shift_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `absensi_detail`
--
ALTER TABLE `absensi_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `absensi_todo`
--
ALTER TABLE `absensi_todo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `shift_master`
--
ALTER TABLE `shift_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tugas_absensi`
--
ALTER TABLE `tugas_absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tugas_harian`
--
ALTER TABLE `tugas_harian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tugas_master`
--
ALTER TABLE `tugas_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `tugas_sub_master`
--
ALTER TABLE `tugas_sub_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `user_jadwal`
--
ALTER TABLE `user_jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9805;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `absensi_detail`
--
ALTER TABLE `absensi_detail`
  ADD CONSTRAINT `absensi_detail_ibfk_1` FOREIGN KEY (`absensi_id`) REFERENCES `absensi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `absensi_todo`
--
ALTER TABLE `absensi_todo`
  ADD CONSTRAINT `absensi_todo_ibfk_1` FOREIGN KEY (`absensi_id`) REFERENCES `absensi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `absensi_todo_ibfk_2` FOREIGN KEY (`master_id`) REFERENCES `tugas_master` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_absensi` FOREIGN KEY (`absensi_id`) REFERENCES `absensi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tugas_absensi`
--
ALTER TABLE `tugas_absensi`
  ADD CONSTRAINT `tugas_absensi_ibfk_1` FOREIGN KEY (`absensi_id`) REFERENCES `absensi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tugas_harian`
--
ALTER TABLE `tugas_harian`
  ADD CONSTRAINT `fk_th_absensi` FOREIGN KEY (`absensi_id`) REFERENCES `absensi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tugas_sub_master`
--
ALTER TABLE `tugas_sub_master`
  ADD CONSTRAINT `fk_tsm_master` FOREIGN KEY (`master_id`) REFERENCES `tugas_master` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_jadwal`
--
ALTER TABLE `user_jadwal`
  ADD CONSTRAINT `fk_uj_shift` FOREIGN KEY (`shift_id`) REFERENCES `shift_master` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_uj_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_shift`
--
ALTER TABLE `user_shift`
  ADD CONSTRAINT `fk_user_shift_shift` FOREIGN KEY (`shift_id`) REFERENCES `shift_master` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_shift_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `ev_weekly_housekeeping` ON SCHEDULE EVERY 1 WEEK STARTS '2025-09-08 23:59:00' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
  -- contoh: optimasi index
  OPTIMIZE TABLE absensi;
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
