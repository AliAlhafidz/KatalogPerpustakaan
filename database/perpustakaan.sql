-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 24, 2026 at 01:43 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `perpustakaan`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`, `nama`, `foto`, `created_at`) VALUES
(1, 'admin', '$2y$10$Jl4Li0i4s./c80HCAjeTiemee5FUEUwopxAD1QLQYxs/D/rBo.RuG', 'Administrator Perpustakaan', 'profil_6a8e3b15a8d15.jpg', '2026-08-25 08:41:25');

-- --------------------------------------------------------

--
-- Table structure for table `anggota`
--

CREATE TABLE `anggota` (
  `id_anggota` int NOT NULL,
  `nomor_anggota` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `no_hp` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `status` enum('aktif','nonaktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anggota`
--

INSERT INTO `anggota` (`id_anggota`, `nomor_anggota`, `nama`, `email`, `password`, `no_hp`, `alamat`, `foto`, `reset_token`, `reset_expiry`, `status`, `created_at`) VALUES
(1, 'A0001', 'Budi Ramsey', 'budi@example.com', '$2y$10$0xjRw9o1aU5Uy/laFXhFsuerQIF/9optwGQSUVLfpjEtw6pPB1u2i', '081234567890', 'Jl. Melati No. 10', 'profil_6ab47e32423a9.png', NULL, NULL, 'aktif', '2026-08-25 08:41:25'),
(2, 'A0002', 'Ali Alhafidz', 'alialhafidz28@gmail.com', '$2y$10$C.Wn92VPKmGtpCAySw5QaOrmFuhJGZ4tYzOKfkYW0t0HwYMHpi.g2', '083820227687', 'Kp. Cijawal, Desa Cibedug, Kec. Rongga', NULL, NULL, NULL, 'aktif', '2026-08-29 14:48:34');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `aksi` enum('tambah','edit','hapus') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `target_tabel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `target_id` int NOT NULL,
  `detail` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `aksi`, `target_tabel`, `target_id`, `detail`, `created_at`) VALUES
(1, 1, 'edit', 'buku', 1, '{\"pengajuan\":\"Janji\",\"aksi\":\"disetujui\",\"catatan\":\"\"}', '2026-08-25 08:42:05'),
(2, 1, 'tambah', 'buku', 6, '{\"judul\":\"Janji\",\"kode\":\"BK-0006\",\"isbn\":\"9786239726201\"}', '2026-08-25 09:48:42'),
(3, 1, 'tambah', 'buku', 7, '{\"judul\":\"To Kill a Mockingbird\",\"kode\":\"BK-0007\",\"isbn\":\"9780061120084\"}', '2026-08-25 10:45:56'),
(4, 1, 'tambah', 'buku', 8, '{\"judul\":\"Nineteen Eighty-Four\",\"kode\":\"BK-0008\",\"isbn\":\"9780451524935\"}', '2026-08-25 10:46:01'),
(5, 1, 'tambah', 'buku', 9, '{\"judul\":\"The Great Gatsby\",\"kode\":\"BK-0009\",\"isbn\":\"9780743273565\"}', '2026-08-25 10:46:07'),
(6, 1, 'tambah', 'buku', 10, '{\"judul\":\"The Da Vinci Code\",\"kode\":\"BK-0010\",\"isbn\":\"9780307474278\"}', '2026-08-25 10:46:13'),
(7, 1, 'tambah', 'buku', 11, '{\"judul\":\"Lord of the Flies\",\"kode\":\"BK-0011\",\"isbn\":\"9780140283334\"}', '2026-08-25 10:46:20'),
(8, 1, 'tambah', 'buku', 12, '{\"judul\":\"The Handmaid\'s Tale\",\"kode\":\"BK-0012\",\"isbn\":\"9780385490818\"}', '2026-08-25 10:46:26'),
(9, 1, 'tambah', 'buku', 13, '{\"judul\":\"O Alquimista\",\"kode\":\"BK-0013\",\"isbn\":\"9780062315007\"}', '2026-08-25 10:46:30'),
(10, 1, 'tambah', 'buku', 14, '{\"judul\":\"The Hunger Games\",\"kode\":\"BK-0014\",\"isbn\":\"9780439023481\"}', '2026-08-25 10:46:36'),
(11, 1, 'tambah', 'buku', 15, '{\"judul\":\"A Brief History of Time\",\"kode\":\"BK-0015\",\"isbn\":\"9780553380163\"}', '2026-08-25 10:46:42'),
(12, 1, 'hapus', 'buku', 5, '{\"judul\":\"Filosofi Teras\"}', '2026-08-25 10:47:26'),
(13, 1, 'hapus', 'buku', 4, '{\"judul\":\"Effective Java\"}', '2026-08-25 10:47:31'),
(14, 1, 'hapus', 'buku', 3, '{\"judul\":\"Sapiens: Riwayat Singkat Umat Manusia\"}', '2026-08-25 10:47:34'),
(15, 1, 'hapus', 'buku', 2, '{\"judul\":\"Bumi Manusia\"}', '2026-08-25 10:47:38'),
(16, 1, 'hapus', 'buku', 1, '{\"judul\":\"Laskar Pelangi\"}', '2026-08-25 10:47:42'),
(17, 1, 'tambah', 'buku', 16, '{\"judul\":\"Bumi\",\"kode\":\"BK-0016\",\"isbn\":\"9786020301129\"}', '2026-08-25 10:52:12'),
(18, 1, 'tambah', 'buku', 17, '{\"judul\":\"Tentang Kamu\",\"kode\":\"BK-0017\",\"isbn\":\"9786020822341\"}', '2026-08-25 10:53:19'),
(19, 1, 'tambah', 'buku', 18, '{\"judul\":\"Matahari\",\"kode\":\"BK-0018\",\"isbn\":\"9786020332116\"}', '2026-08-25 10:53:25'),
(20, 1, 'tambah', 'buku', 19, '{\"judul\":\"Hujan\",\"kode\":\"BK-0019\",\"isbn\":\"9786020324784\"}', '2026-08-25 10:53:30'),
(21, 1, 'tambah', 'buku', 20, '{\"judul\":\"HELLO\",\"kode\":\"BK-0020\",\"isbn\":\"9786238829682\"}', '2026-08-25 10:53:37'),
(22, 1, 'tambah', 'buku', 21, '{\"judul\":\"Lumpu\",\"kode\":\"BK-0021\",\"isbn\":\"9786020652283\"}', '2026-08-25 10:53:43'),
(23, 1, 'tambah', 'buku', 22, '{\"judul\":\"Komet Minor\",\"kode\":\"BK-0022\",\"isbn\":\"9786020623399\"}', '2026-08-25 10:53:48'),
(24, 1, 'tambah', 'buku', 23, '{\"judul\":\"Pergi\",\"kode\":\"BK-0023\",\"isbn\":\"9786025734052\"}', '2026-08-25 10:53:54'),
(25, 1, 'tambah', 'buku', 24, '{\"judul\":\"Pulang-Pergi\",\"kode\":\"BK-0024\",\"isbn\":\"9786239554521\"}', '2026-08-25 10:54:02'),
(26, 1, 'tambah', 'buku', 25, '{\"judul\":\"Rasa\",\"kode\":\"BK-0025\",\"isbn\":\"9786239726232\"}', '2026-08-25 10:54:07'),
(27, 1, 'tambah', 'buku', 26, '{\"judul\":\"Harry Potter and the Philosopher\'s Stone\",\"kode\":\"BK-0026\",\"isbn\":\"9780747532699\"}', '2026-08-25 11:00:01'),
(28, 1, 'tambah', 'buku', 27, '{\"judul\":\"Harry Potter and the Philosopher\'s Stone\",\"kode\":\"BK-0027\",\"isbn\":\"9780590353427\"}', '2026-08-25 11:00:05'),
(29, 1, 'tambah', 'buku', 28, '{\"judul\":\"Harry Potter and the Philosopher\'s Stone\",\"kode\":\"BK-0028\",\"isbn\":\"9781408845646\"}', '2026-08-25 11:00:09'),
(30, 1, 'tambah', 'buku', 29, '{\"judul\":\"Harry Potter and the Philosopher\'s Stone\",\"kode\":\"BK-0029\",\"isbn\":\"9780613206334\"}', '2026-08-25 11:00:13'),
(31, 1, 'tambah', 'buku', 30, '{\"judul\":\"Harry Potter (series) 1-7\",\"kode\":\"BK-0030\",\"isbn\":\"9780747544388\"}', '2026-08-25 11:00:19'),
(32, 1, 'tambah', 'buku', 31, '{\"judul\":\"Harry Potter and the Goblet of Fire\",\"kode\":\"BK-0031\",\"isbn\":\"9780747546245\"}', '2026-08-25 11:00:25'),
(33, 1, 'tambah', 'buku', 32, '{\"judul\":\"Harry Potter and the Order of the Phoenix\",\"kode\":\"BK-0032\",\"isbn\":\"9780747551003\"}', '2026-08-25 11:00:29'),
(34, 1, 'tambah', 'buku', 33, '{\"judul\":\"Harry Potter and the Philosopher\'s Stone\",\"kode\":\"BK-0033\",\"isbn\":\"9780747558194\"}', '2026-08-25 11:00:32'),
(35, 1, 'tambah', 'buku', 34, '{\"judul\":\"Harry Potter and the Half-Blood Prince\",\"kode\":\"BK-0034\",\"isbn\":\"9780747581086\"}', '2026-08-25 11:00:37'),
(36, 1, 'tambah', 'buku', 35, '{\"judul\":\"Harry Potter and the Philosopher\'s Stone\",\"kode\":\"BK-0035\",\"isbn\":\"9780545582889\"}', '2026-08-25 11:00:40'),
(37, 1, 'tambah', 'buku', 36, '{\"judul\":\"Harry Potter and the Deathly Hallows\",\"kode\":\"BK-0036\",\"isbn\":\"9780545139700\"}', '2026-08-25 11:00:45'),
(38, 1, 'tambah', 'buku', 37, '{\"judul\":\"Herr der Diebe\",\"kode\":\"BK-0037\",\"isbn\":\"9780545227704\"}', '2026-08-25 11:00:52'),
(39, 1, 'tambah', 'buku', 38, '{\"judul\":\"Harry Potter and the Half-Blood Prince\",\"kode\":\"BK-0038\",\"isbn\":\"9780439784542\"}', '2026-08-25 11:00:57'),
(40, 1, 'tambah', 'buku', 39, '{\"judul\":\"Harry Potter and the Half-Blood Prince\",\"kode\":\"BK-0039\",\"isbn\":\"9780439785969\"}', '2026-08-25 11:01:03'),
(41, 1, 'tambah', 'buku', 40, '{\"judul\":\"Harry Potter and the Order of the Phoenix\",\"kode\":\"BK-0040\",\"isbn\":\"9780439358071\"}', '2026-08-25 11:01:07'),
(42, 1, 'tambah', 'buku', 41, '{\"judul\":\"Harry Potter and the Goblet of Fire\",\"kode\":\"BK-0041\",\"isbn\":\"9780439139595\"}', '2026-08-25 11:01:12'),
(43, 1, 'tambah', 'buku', 42, '{\"judul\":\"Harry Potter and the Chamber of Secrets\",\"kode\":\"BK-0042\",\"isbn\":\"9780439064866\"}', '2026-08-25 11:01:16'),
(44, 1, 'tambah', 'buku', 43, '{\"judul\":\"Harry Potter and the Prisoner of Azkaban\",\"kode\":\"BK-0043\",\"isbn\":\"9780439655484\"}', '2026-08-25 11:01:22'),
(45, 1, 'tambah', 'buku', 44, '{\"judul\":\"Harry Potter (series) 1-7\",\"kode\":\"BK-0044\",\"isbn\":\"9780545162074\"}', '2026-08-25 11:01:29'),
(46, 1, 'tambah', 'buku', 45, '{\"judul\":\"Harry Potter and the Philosopher\'s Stone\",\"kode\":\"BK-0045\",\"isbn\":\"9781408855652\"}', '2026-08-25 11:01:34'),
(47, 1, 'hapus', 'buku', 39, '{\"judul\":\"Harry Potter and the Half-Blood Prince\"}', '2026-08-25 11:01:56'),
(48, 1, 'hapus', 'buku', 34, '{\"judul\":\"Harry Potter and the Half-Blood Prince\"}', '2026-08-25 11:02:10'),
(49, 1, 'hapus', 'buku', 35, '{\"judul\":\"Harry Potter and the Philosopher\'s Stone\"}', '2026-08-25 11:02:18'),
(50, 1, 'hapus', 'buku', 45, '{\"judul\":\"Harry Potter and the Philosopher\'s Stone\"}', '2026-08-25 11:02:25'),
(51, 1, 'hapus', 'buku', 27, '{\"judul\":\"Harry Potter and the Philosopher\'s Stone\"}', '2026-08-25 11:02:36'),
(52, 1, 'hapus', 'buku', 28, '{\"judul\":\"Harry Potter and the Philosopher\'s Stone\"}', '2026-08-25 11:02:44'),
(53, 1, 'hapus', 'buku', 29, '{\"judul\":\"Harry Potter and the Philosopher\'s Stone\"}', '2026-08-25 11:02:51'),
(54, 1, 'hapus', 'buku', 26, '{\"judul\":\"Harry Potter and the Philosopher\'s Stone\"}', '2026-08-25 11:02:56'),
(55, 1, 'hapus', 'buku', 31, '{\"judul\":\"Harry Potter and the Goblet of Fire\"}', '2026-08-25 11:03:06'),
(56, 1, 'hapus', 'buku', 32, '{\"judul\":\"Harry Potter and the Order of the Phoenix\"}', '2026-08-25 11:03:16'),
(57, 1, 'tambah', 'kategori', 7, '{\"nama\":\"Fantasi\"}', '2026-08-25 12:06:44'),
(58, 1, 'edit', 'buku', 44, '{\"stok\":{\"dari\":1,\"ke\":5},\"id_kategori\":{\"dari\":null,\"ke\":7}}', '2026-08-25 12:06:59'),
(59, 1, 'edit', 'buku', 43, '{\"stok\":{\"dari\":1,\"ke\":6},\"id_kategori\":{\"dari\":null,\"ke\":7}}', '2026-08-25 12:07:11'),
(60, 1, 'edit', 'buku', 42, '{\"stok\":{\"dari\":1,\"ke\":3},\"id_kategori\":{\"dari\":null,\"ke\":7}}', '2026-08-25 12:07:28'),
(61, 1, 'edit', 'buku', 41, '{\"stok\":{\"dari\":1,\"ke\":6},\"id_kategori\":{\"dari\":null,\"ke\":7}}', '2026-08-25 12:07:45'),
(62, 1, 'edit', 'buku', 40, '{\"stok\":{\"dari\":1,\"ke\":4},\"id_kategori\":{\"dari\":null,\"ke\":7}}', '2026-08-25 12:08:01'),
(63, 1, 'edit', 'buku', 38, '{\"stok\":{\"dari\":1,\"ke\":7},\"id_kategori\":{\"dari\":null,\"ke\":7}}', '2026-08-25 12:08:14'),
(64, 1, 'hapus', 'buku', 30, '{\"judul\":\"Harry Potter (series) 1-7\"}', '2026-08-25 12:08:26'),
(65, 1, 'edit', 'buku', 37, '{\"stok\":{\"dari\":1,\"ke\":6},\"id_kategori\":{\"dari\":null,\"ke\":1}}', '2026-08-25 12:08:43'),
(66, 1, 'edit', 'buku', 36, '{\"stok\":{\"dari\":1,\"ke\":8},\"id_kategori\":{\"dari\":null,\"ke\":7}}', '2026-08-25 12:09:01'),
(67, 1, 'edit', 'buku', 33, '{\"stok\":{\"dari\":1,\"ke\":2},\"id_kategori\":{\"dari\":null,\"ke\":7}}', '2026-08-25 12:09:21'),
(68, 1, 'edit', 'buku', 22, '{\"stok\":{\"dari\":1,\"ke\":4},\"id_kategori\":{\"dari\":null,\"ke\":0}}', '2026-08-25 12:09:56'),
(69, 1, 'edit', 'buku', 25, '{\"stok\":{\"dari\":1,\"ke\":5},\"id_kategori\":{\"dari\":null,\"ke\":1}}', '2026-08-25 12:10:11'),
(70, 1, 'edit', 'buku', 24, '{\"stok\":{\"dari\":1,\"ke\":9},\"id_kategori\":{\"dari\":null,\"ke\":1}}', '2026-08-25 12:10:26'),
(71, 1, 'edit', 'buku', 23, '{\"stok\":{\"dari\":1,\"ke\":5},\"id_kategori\":{\"dari\":null,\"ke\":1}}', '2026-08-25 12:10:38'),
(72, 1, 'edit', 'buku', 21, '{\"stok\":{\"dari\":1,\"ke\":2},\"id_kategori\":{\"dari\":null,\"ke\":1}}', '2026-08-25 12:10:53'),
(73, 1, 'edit', 'buku', 20, '{\"stok\":{\"dari\":1,\"ke\":3},\"id_kategori\":{\"dari\":null,\"ke\":1}}', '2026-08-25 12:11:15'),
(74, 1, 'edit', 'buku', 19, '{\"stok\":{\"dari\":1,\"ke\":5},\"id_kategori\":{\"dari\":null,\"ke\":1}}', '2026-08-25 12:12:05'),
(75, 1, 'edit', 'buku', 18, '{\"stok\":{\"dari\":1,\"ke\":6},\"id_kategori\":{\"dari\":null,\"ke\":1}}', '2026-08-25 12:12:28'),
(76, 1, 'edit', 'buku', 17, '{\"stok\":{\"dari\":1,\"ke\":6},\"id_kategori\":{\"dari\":null,\"ke\":1}}', '2026-08-25 12:12:54'),
(77, 1, 'edit', 'buku', 16, '{\"stok\":{\"dari\":1,\"ke\":6},\"id_kategori\":{\"dari\":null,\"ke\":1}}', '2026-08-25 12:13:18'),
(78, 1, 'edit', 'buku', 15, '{\"stok\":{\"dari\":1,\"ke\":3},\"id_kategori\":{\"dari\":null,\"ke\":4}}', '2026-08-25 12:13:49'),
(79, 1, 'edit', 'buku', 14, '{\"stok\":{\"dari\":1,\"ke\":5},\"id_kategori\":{\"dari\":null,\"ke\":6}}', '2026-08-25 12:14:07'),
(80, 1, 'edit', 'buku', 13, '{\"stok\":{\"dari\":1,\"ke\":6},\"id_kategori\":{\"dari\":null,\"ke\":1}}', '2026-08-25 12:14:26'),
(81, 1, 'edit', 'buku', 12, '{\"stok\":{\"dari\":1,\"ke\":9},\"id_kategori\":{\"dari\":null,\"ke\":4}}', '2026-08-25 12:14:42'),
(82, 1, 'edit', 'buku', 11, '{\"stok\":{\"dari\":1,\"ke\":7},\"id_kategori\":{\"dari\":null,\"ke\":1}}', '2026-08-25 12:14:56'),
(83, 1, 'edit', 'buku', 10, '{\"stok\":{\"dari\":1,\"ke\":6},\"id_kategori\":{\"dari\":null,\"ke\":4}}', '2026-08-25 12:15:17'),
(84, 1, 'edit', 'buku', 9, '{\"stok\":{\"dari\":1,\"ke\":8},\"id_kategori\":{\"dari\":null,\"ke\":4}}', '2026-08-25 12:15:34'),
(85, 1, 'edit', 'buku', 8, '{\"stok\":{\"dari\":1,\"ke\":3},\"id_kategori\":{\"dari\":null,\"ke\":4}}', '2026-08-25 12:16:02'),
(86, 1, 'edit', 'buku', 7, '{\"stok\":{\"dari\":1,\"ke\":5},\"id_kategori\":{\"dari\":null,\"ke\":7}}', '2026-08-25 12:16:19'),
(87, 1, 'tambah', 'buku', 46, '{\"judul\":\"The War of the Worlds\",\"kode\":\"BK-0045\",\"isbn\":\"9781402736889\"}', '2026-08-26 02:24:17'),
(88, 1, 'tambah', 'buku', 47, '{\"judul\":\"Bold Journey\",\"kode\":\"BK-0046\",\"isbn\":\"0395366917\"}', '2026-08-28 14:37:36'),
(89, 1, 'edit', 'pengajuan_peminjaman', 15, '{\"aksi\":\"setujui\",\"buku\":\"Harry Potter and the Philosopher\'s Stone\",\"id_peminjaman\":7,\"catatan\":\"\"}', '2026-09-03 01:49:23'),
(90, 1, 'edit', 'buku', 43, '{\"judul\":\"Harry Potter and the Prisoner of Azkaban\"}', '2026-09-03 03:09:07'),
(91, 1, 'tambah', 'buku', 1, '{\"judul\":\"Janji\",\"kode\":\"BK-0001\",\"isbn\":\"9786239726201\"}', '2026-09-24 01:07:31'),
(92, 1, 'edit', 'buku', 1, '{\"stok\":{\"dari\":1,\"ke\":5}}', '2026-09-24 01:07:48'),
(93, 1, 'tambah', 'buku', 2, '{\"judul\":\"Foundation and Empire\",\"kode\":\"BK-0002\",\"isbn\":\"9782070415717\"}', '2026-09-24 01:08:32'),
(94, 1, 'tambah', 'buku', 3, '{\"judul\":\"Four Past Midnight\",\"kode\":\"BK-0003\",\"isbn\":\"9789021012261\"}', '2026-09-24 01:09:30'),
(95, 1, 'tambah', 'buku', 4, '{\"judul\":\"The Great Gatsby\",\"kode\":\"BK-0004\",\"isbn\":\"9780684717609\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:14:25'),
(96, 1, 'tambah', 'buku', 5, '{\"judul\":\"The Hobbit\",\"kode\":\"BK-0005\",\"isbn\":\"9780008376055\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:14:31'),
(97, 1, 'tambah', 'buku', 6, '{\"judul\":\"Animal Farm\",\"kode\":\"BK-0006\",\"isbn\":\"9780451525369\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:14:37'),
(98, 1, 'tambah', 'buku', 7, '{\"judul\":\"Nineteen Eighty-Four\",\"kode\":\"BK-0007\",\"isbn\":\"9780141391700\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:14:43'),
(99, 1, 'tambah', 'buku', 8, '{\"judul\":\"The Catcher in the Rye\",\"kode\":\"BK-0008\",\"isbn\":\"9780553239768\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:14:56'),
(100, 1, 'tambah', 'buku', 9, '{\"judul\":\"To Kill a Mockingbird\",\"kode\":\"BK-0009\",\"isbn\":\"9780061743528\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:15:05'),
(101, 1, 'tambah', 'buku', 10, '{\"judul\":\"Le petit prince\",\"kode\":\"BK-0010\",\"isbn\":\"9780152048044\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:15:15'),
(102, 1, 'tambah', 'buku', 11, '{\"judul\":\"Het Achterhuis\",\"kode\":\"BK-0011\",\"isbn\":\"9780141032009\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:15:22'),
(103, 1, 'tambah', 'buku', 12, '{\"judul\":\"Fahrenheit 451\",\"kode\":\"BK-0012\",\"isbn\":\"9780552092388\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:15:36'),
(104, 1, 'tambah', 'buku', 13, '{\"judul\":\"The Kite Runner\",\"kode\":\"BK-0013\",\"isbn\":\"9781594480003\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:15:41'),
(105, 1, 'tambah', 'buku', 14, '{\"judul\":\"The Book Thief\",\"kode\":\"BK-0014\",\"isbn\":\"9780385754729\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:15:48'),
(106, 1, 'tambah', 'buku', 15, '{\"judul\":\"The picture of Dorian Gray and other writings\",\"kode\":\"BK-0015\",\"isbn\":\"9781452896304\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:15:55'),
(107, 1, 'tambah', 'buku', 16, '{\"judul\":\"The Great Gatsby\",\"kode\":\"BK-0016\",\"isbn\":\"9780743273565\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:16:02'),
(108, 1, 'tambah', 'buku', 17, '{\"judul\":\"The Hobbit\",\"kode\":\"BK-0017\",\"isbn\":\"9780345534835\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:16:08'),
(109, 1, 'tambah', 'buku', 18, '{\"judul\":\"Animal Farm\",\"kode\":\"BK-0018\",\"isbn\":\"9781299886322\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:16:15'),
(110, 1, 'tambah', 'buku', 19, '{\"judul\":\"The Catcher in the Rye\",\"kode\":\"BK-0019\",\"isbn\":\"9780553117226\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:16:20'),
(111, 1, 'tambah', 'buku', 20, '{\"judul\":\"To Kill a Mockingbird\",\"kode\":\"BK-0020\",\"isbn\":\"9780061980268\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:16:26'),
(112, 1, 'tambah', 'buku', 21, '{\"judul\":\"The Diary of a Young Girl- Anne Frank\",\"kode\":\"BK-0021\",\"isbn\":\"9780141971667\",\"via\":\"import_batch\",\"providers\":[\"Open Library\"]}', '2026-09-24 01:16:33'),
(113, 1, 'edit', 'buku', 21, '{\"stok\":{\"dari\":1,\"ke\":5}}', '2026-09-24 01:17:53'),
(114, 1, 'edit', 'buku', 20, '{\"stok\":{\"dari\":1,\"ke\":4}}', '2026-09-24 01:18:00'),
(115, 1, 'edit', 'buku', 19, '{\"stok\":{\"dari\":1,\"ke\":0}}', '2026-09-24 01:18:08'),
(116, 1, 'edit', 'buku', 19, '{\"stok\":{\"dari\":0,\"ke\":4}}', '2026-09-24 01:18:14'),
(117, 1, 'edit', 'buku', 18, '{\"stok\":{\"dari\":1,\"ke\":7}}', '2026-09-24 01:18:24'),
(118, 1, 'hapus', 'buku', 9, '{\"judul\":\"To Kill a Mockingbird\"}', '2026-09-24 01:20:01'),
(119, 1, 'hapus', 'buku', 18, '{\"judul\":\"Animal Farm\"}', '2026-09-24 01:20:19'),
(120, 1, 'hapus', 'buku', 6, '{\"judul\":\"Animal Farm\"}', '2026-09-24 01:20:35');

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id_buku` int NOT NULL,
  `kode_buku` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `isbn` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `judul` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `penulis` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `penerbit` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tahun_terbit` year DEFAULT NULL,
  `id_kategori` int DEFAULT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `stok` int NOT NULL DEFAULT '0',
  `tersedia` int NOT NULL DEFAULT '0',
  `is_arsip` tinyint(1) NOT NULL DEFAULT '0',
  `arsip_at` datetime DEFAULT NULL,
  `lokasi_rak` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id_buku`, `kode_buku`, `isbn`, `judul`, `penulis`, `penerbit`, `tahun_terbit`, `id_kategori`, `deskripsi`, `cover`, `stok`, `tersedia`, `is_arsip`, `arsip_at`, `lokasi_rak`, `created_at`) VALUES
(1, 'BK-0001', '9786239726201', 'Janji', 'Tere Liye', 'Sabak Grip Nusantara', 2021, 1, 'Kita semua adalah pengembara di dunia ini.\r\nAda yang kaya, pun ada yang miskin. Ada yang terkenal, ternama, berkuasa, juga ada\r\nyang bukan siapa-siapa.\r\nAda yang seolah bisa membeli apapun, melakukan apapun yang dia mau, hebat sekali.\r\nAda yang bahkan bingung besok harus makan apa.\r\nTapi sesungguhnya di manakah kebahagiaan itu hinggap?\r\nDi manakah hakikat kehidupan itu tersembunyi?\r\nApakah seperti yang kita lihat dari luar saja?\r\nInilah kisah tentang janji.\r\nKita semua adalah pengembara di dunia ini.\r\nDari hari ke hari. Dari satu tempat ke tempat lain.\r\nDari satu kejadian ke kejadian lain. Terus mengembara.\r\nDan kita pasti akan menggenapkan janji yang satu ini: mati.', 'cover_6ab477d30fe60.jpg', 5, 5, 0, NULL, 'Rak A1', '2026-09-24 01:07:31'),
(2, 'BK-0002', '9782070415717', 'Foundation and Empire', 'Isaac Asimov', 'Denoel', 1945, 1, 'Led by its founding father, the great psychohistorian Hari Seldon, and taking advantage of its superior science and technology, the Foundation has survived the greed and barbarism of its neighboring warrior-planets. Yet now it must face the Empire still the mightiest force in the Galaxy even in its death throes. When an ambitious general determined to restore the Empire\'s glory turns the vast Imperial fleet toward the Foundation, the only hope for the small planet of scholars and scientists lies in the prophecies of Hari Seldon.', 'cover_6ab478106c24a.jpg', 5, 5, 0, NULL, 'Rak A1', '2026-09-24 01:08:32'),
(3, 'BK-0003', '9789021012261', 'Four Past Midnight', 'Stephen King', 'Poema Pocket', 1990, 1, 'Four Past Midnight is a collection of novellas written by Stephen King in 1988 and 1989 and published in August 1990. It is his second book of this type, the first one being Different Seasons. The collection won the Bram Stoker Award in 1990 for Best Collection and was nominated for a Locus Award in 1991.\r\n\r\nOne Past Midnight: \"[The Langoliers][1]\" takes a red-eye flight from L.A. to Boston into a most unfriendly sky. Only eleven passengers survive, but landing in an eerily empty world makes them wish they hadn\'t. Something\'s waiting for them, you see....\r\n\r\nTwo Past Midnight: \"[Secret Window, Secret Garden][2]\" enters the suddenly strange life of writer Mort Rainey, recently divorced, depressed, and alone on the shore of Tashmore Lake. Alone, that is, until a figure named John Shooter arrives, pointing an accusing finger.\r\n\r\nThree Past Midnight: \"[The Library Policeman][3]\" is set in Junction City, Iowa, an unlikely place for evil to be hiding. But for small businessman Sam Peebles, who thinks he may be losing his mind, another enemy is hiding there as well--the truth. If he can find it in time, he might stand a chance.\r\n\r\nFour Past Midnight: The flat surface of a Polaroid photograph becomes for fifteen-year-old Kevin Delevan an invitation to the supernatural. Old Pop Merrill, Castle Rock\'s sharpest trader, wants to crash the party for profit, but \"[The Sun Dog][4],\" a creature that shouldn\'t exist at all, is a very dangerous investment.\r\n\r\n[1]: https://openlibrary.org/works/OL149138W/The_Langoliers\r\n[2]: https://openlibrary.org/works/OL14917476W/Secret_Window_Secret_Garden\r\n[3]: https://openlibrary.org/works/OL14917299W/The_Library_Policeman\r\n[4]: https://openlibrary.org/works/OL14916850W/The_Sun_Dog', 'cover_6ab4784ac29ce.jpg', 4, 4, 0, NULL, 'Rak A1', '2026-09-24 01:09:30'),
(4, 'BK-0004', '9780684717609', 'The Great Gatsby', 'F. Scott Fitzgerald', 'Charles Scribner\'s Sons', 1920, 1, 'In the nearly fifty years since its publication, THE GREAT GATSBY has rightly taken its place as one of Fitzgerald\'s masterpieces, and indeed as one of the masterpieces of American fiction.\n--back cover', 'cover_6ab479719cef5.jpg', 1, 1, 0, NULL, NULL, '2026-09-24 01:14:25'),
(5, 'BK-0005', '9780008376055', 'The Hobbit', 'J.R.R. Tolkien', 'HarperCollins Publishers', 1937, 6, 'The Hobbit is a tale of high adventure, undertaken by a company of dwarves in search of dragon-guarded gold. A reluctant partner in this perilous quest is Bilbo Baggins, a comfort-loving unambitious hobbit, who surprises even himself by his resourcefulness and skill as a burglar.\n\nEncounters with trolls, goblins, dwarves, elves, and giant spiders, conversations with the dragon, Smaug, and a rather unwilling presence at the Battle of Five Armies are just some of the adventures that befall Bilbo.\n\nBilbo Baggins has taken his place among the ranks of the immortals of children’s fiction. Written by Professor Tolkien for his children, The Hobbit met with instant critical acclaim when published.', 'cover_6ab479770419a.jpg', 1, 1, 0, NULL, NULL, '2026-09-24 01:14:31'),
(7, 'BK-0007', '9780141391700', 'Nineteen Eighty-Four', 'George Orwell, George Orwell, Georges Orwell', 'Penguin Books, Limited', 1949, 1, 'Nineteen Eighty-Four: A Novel, often referred to as 1984, is a dystopian social science fiction novel by the English novelist George Orwell (the pen name of Eric Arthur Blair). It was published on 8 June 1949 by Secker & Warburg as Orwell\'s ninth and final book completed in his lifetime. Thematically, Nineteen Eighty-Four centres on the consequences of totalitarianism, mass surveillance, and repressive regimentation of persons and behaviours within society. Orwell, himself a democratic socialist, modelled the authoritarian government in the novel after Stalinist Russia. More broadly, the novel examines the role of truth and facts within politics and the ways in which they are manipulated.\n\n----------\nAlso contained in:\n[Novels (Animal Farm / Burmese Days / Clergyman\'s Daughter / Coming Up for Air / Keep the Aspidistra Flying / Nineteen Eighty-Four)](https://openlibrary.org/works/OL1168045W)\n[Novels (Animal Farm / Nineteen Eighty-Four)](https://openlibrary.org/works/OL1167981W)\n[Orwell\'s Nineteen Eighty-Four: Text, Sources, Criticism](https://openlibrary.org/works/OL1168095W)', 'cover_6ab47983e25e0.jpg', 1, 1, 0, NULL, NULL, '2026-09-24 01:14:43'),
(8, 'BK-0008', '9780553239768', 'The Catcher in the Rye', 'J. D. Salinger, Shao jia yun', 'Bantam Books', 1945, 1, 'Story of Holden Caufield with his idiosyncrasies, penetrating insight, confusion, sensitivity and negativism. Holden, knowing he is to be expelled from school, decides to leave early. He spends three days in New York City and tells the story of what he did and suffered there.', 'cover_6ab47990ebfda.jpg', 1, 1, 0, NULL, NULL, '2026-09-24 01:14:56'),
(10, 'BK-0010', '9780152048044', 'Le petit prince', 'Antoine de Saint-Exupéry', 'Harcourt Children\'s Books', 1943, 6, '*Le Petit Prince* est une œuvre de langue française, la plus connue d\'Antoine de Saint-Exupéry. Publié en 1943 à New York simultanément à sa traduction anglaise, c\'est une œuvre poétique et philosophique sous l\'apparence d\'un conte pour enfants.\n\nTraduit en quatre cent cinquante-sept langues et dialectes, *Le Petit Prince* est le deuxième ouvrage le plus traduit au monde après la Bible.\n\nLe langage, simple et dépouillé, parce qu\'il est destiné à être compris par des enfants, est en réalité pour le narrateur le véhicule privilégié d\'une conception symbolique de la vie. Chaque chapitre relate une rencontre du petit prince qui laisse celui-ci perplexe, par rapport aux comportements absurdes des « grandes personnes ». Ces différentes rencontres peuvent être lues comme une allégorie.\n\nLes aquarelles font partie du texte et participent à cette pureté du langage : dépouillement et profondeur sont les qualités maîtresses de l\'œuvre.\n\nOn peut y lire une invitation de l\'auteur à retrouver l\'enfant en soi, car « toutes les grandes personnes ont d\'abord été des enfants. (Mais peu d\'entre elles s\'en souviennent.) ». L\'ouvrage est dédié à Léon Werth, mais « quand il était petit garçon ».\n\n(Wikipedia)', 'cover_6ab479a3ab61b.jpg', 1, 1, 0, NULL, NULL, '2026-09-24 01:15:15'),
(11, 'BK-0011', '9780141032009', 'Het Achterhuis', 'Anne Frank', 'Penguin Books, Limited (UK)', 1944, 4, 'Het Achterhuis is de titel van het dagboek van Anne Frank (1929-1945) voor het eerst uitgegeven op 25 juni 1947. Het is genoemd naar het onderduikpand Het Achterhuis op de Prinsengracht en is het verhaal van een ondergedoken jong Joods meisje ten tijde van de Tweede Wereldoorlog. Het is wereldwijd een van de meest gelezen boeken. Sinds 2009 staat Annes dagboek op de Werelderfgoedlijst voor documenten van UNESCO.\n\n----------\nAlso contained in:\n[Works of Anne Frank](https://openlibrary.org/works/OL2931445W)', 'cover_6ab479aa4c4a8.jpg', 1, 1, 0, NULL, NULL, '2026-09-24 01:15:22'),
(12, 'BK-0012', '9780552092388', 'Fahrenheit 451', 'Ray Bradbury', 'Corgi', 1953, 1, 'Fahrenheit 451 is a 1953 dystopian novel by American writer Ray Bradbury. Often regarded as one of his best works, the novel presents a future American society where books are outlawed and \"firemen\" burn any that are found. The book\'s tagline explains the title as \"\'the temperature at which book paper catches fire, and burns\": the autoignition temperature of paper. The lead character, Guy Montag, is a fireman who becomes disillusioned with his role of censoring literature and destroying knowledge, eventually quitting his job and committing himself to the preservation of literary and cultural writings.\n\nThe novel has been the subject of interpretations focusing on the historical role of book burning in suppressing dissenting ideas for change. In a 1956 radio interview, Bradbury said that he wrote Fahrenheit 451 because of his concerns at the time (during the McCarthy era) about the threat of book burning in the United States. In later years, he described the book as a commentary on how mass media reduces interest in reading literature.\n\nIn 1954, Fahrenheit 451 won the American Academy of Arts and Letters Award in Literature and the Commonwealth Club of California Gold Medal. It later won the Prometheus \"Hall of Fame\" Award in 1984 and a \"Retro\" Hugo Award, one of a limited number of Best Novel Retro Hugos ever given, in 2004. Bradbury was honored with a Spoken Word Grammy nomination for his 1976 audiobook version.\n\n---\n\nAlso contained in:\n\n- [451° по Фаренгейту: Рассказы](https://openlibrary.org/works/OL17811384W/Fahrenheit_451_stories)\n- [451° по Фаренгейту: повести и рассказы](https://openlibrary.org/works/OL27741633W)\n- [Works](https://openlibrary.org/works/OL28185143W)', 'cover_6ab479b8483af.jpg', 1, 1, 0, NULL, NULL, '2026-09-24 01:15:36'),
(13, 'BK-0013', '9781594480003', 'The Kite Runner', 'Khaled Hosseini', 'Riverhead Books', 2003, 1, 'The unforgettable, heartbreaking story of the unlikely friendship between a wealthy boy and the son of his father’s servant, The Kite Runner is a beautifully crafted novel set in a country that is in the process of being destroyed. It is about the power of reading, the price of betrayal, and the possibility of redemption; and an exploration of the power of fathers over sons—their love, their sacrifices, their lies.\n\nA sweeping story of family, love, and friendship told against the devastating backdrop of the history of Afghanistan over the last thirty years, The Kite Runner is an unusual and powerful novel that has become a beloved, one-of-a-kind classic.\n([source][1])\n\n[1]: https://khaledhosseini.com/books/the-kite-runner/', 'cover_6ab479bdb8c23.jpg', 1, 1, 0, NULL, NULL, '2026-09-24 01:15:41'),
(14, 'BK-0014', '9780385754729', 'The Book Thief', 'Markus Zusak', 'Alfred A. Knopf', 1998, 1, 'It is 1939 Nazi Germany. The country is holding its breath.\nDeath has never been busier, and will become busier still.\n\nBy her brother\'s graveside, Liesel Meminger\'s life is changed when she picks up a single object, partially hidden in the snow. It is The Grave Digger\'s Handbook, left there by accident, and it is her first act of book thievery. So begins a love affair with books and words, as Liesel, with the help of her accordion-playing foster father, learns to read. Soon she is stealing books from Nazi book-burnings, the mayor\'s wife\'s library, wherever there are books to be found.\n\nBut these are dangerous times. When Liesel\'s foster family hides a Jew in their basement, Liesel\'s world is both opened up and closed down.\n\nIn superbly crafted writing that burns with intensity, award-winning author Markus Zusak has given us one of the most enduring stories of our time.\n\n--back cover', 'cover_6ab479c49957d.jpg', 1, 1, 0, NULL, NULL, '2026-09-24 01:15:48'),
(15, 'BK-0015', '9781452896304', 'The picture of Dorian Gray and other writings', 'Oscar Wilde', 'SoHoBooks', 2014, 2, 'This is a story of love, murder, thwarted revenge, and finally, justice. As Dorian Gray remains young and handsome, his pact with the devil allows his portrait to reflect the degradation of his many sins.', 'cover_6ab479cb7164d.jpg', 1, 1, 0, NULL, NULL, '2026-09-24 01:15:55'),
(16, 'BK-0016', '9780743273565', 'The Great Gatsby', 'F. Scott Fitzgerald', 'Scribner', 1920, 1, 'THE GREAT GATSBY, F. Scott Fitzgerald’s third book, stands as the supreme achievement of his career. This exemplary novel of the Jazz Age has been acclaimed by generations of readers. The story of the fabulously wealthy Jay Gatsby and his love for the beautiful Daisy Buchanan, of lavish parties on Long Island at a time when The New York Times noted “gin was the national drink and sex the national obsession,” is an exquisitely crafted tale of America in the 1920s.\n\nThe Great Gatsby is one of the great classics of twentieth-century literature.\n--back cover', 'cover_6ab479d251d78.jpg', 1, 1, 0, NULL, NULL, '2026-09-24 01:16:02'),
(17, 'BK-0017', '9780345534835', 'The Hobbit', 'J.R.R. Tolkien', 'Del Rey', 1937, 6, 'The Hobbit is a tale of high adventure, undertaken by a company of dwarves in search of dragon-guarded gold. A reluctant partner in this perilous quest is Bilbo Baggins, a comfort-loving unambitious hobbit, who surprises even himself by his resourcefulness and skill as a burglar.\n\nEncounters with trolls, goblins, dwarves, elves, and giant spiders, conversations with the dragon, Smaug, and a rather unwilling presence at the Battle of Five Armies are just some of the adventures that befall Bilbo.\n\nBilbo Baggins has taken his place among the ranks of the immortals of children’s fiction. Written by Professor Tolkien for his children, The Hobbit met with instant critical acclaim when published.', 'cover_6ab479d8848d8.jpg', 1, 1, 0, NULL, NULL, '2026-09-24 01:16:08'),
(19, 'BK-0019', '9780553117226', 'The Catcher in the Rye', 'J. D. Salinger, Shao jia yun', 'Bantam Books', 1945, 1, 'Story of Holden Caufield with his idiosyncrasies, penetrating insight, confusion, sensitivity and negativism. Holden, knowing he is to be expelled from school, decides to leave early. He spends three days in New York City and tells the story of what he did and suffered there.', 'cover_6ab479e4d84d0.jpg', 4, 4, 0, NULL, 'Rak A1', '2026-09-24 01:16:20'),
(20, 'BK-0020', '9780061980268', 'To Kill a Mockingbird', 'Harper Lee', 'HarperCollins', 1960, 1, 'One of the best-loved stories of all time, To Kill a Mockingbird has been translated into more than 40 languages, sold more than 30 million copies worldwide, served as the basis for an enormously popular motion picture, and voted one of the best novels of the 20th century by librarians across the United States. A gripping, heart-wrenching, and wholly remarkable tale of coming-of-age in a South poisoned by virulent prejudice, it views a world of great beauty and savage inequities through the eyes of a young girl, as her father -- a crusading local lawyer -- risks everything to defend a black man unjustly accused of a terrible crime.\r\n\r\nLawyer Atticus Finch defends Tom Robinson -- a black man charged with the rape of a white girl. Writing through the young eyes of Finch\'s children Scout and Jem, Harper Lee explores with rich humor and unswerving honesty the irrationality of adult attitudes toward race and class in small-town Alabama during the mid-1930s Depression years. The conscience of a town steeped in prejudice, violence, and hypocrisy is pricked by the stamina and quiet heroism of one man\'s struggle for justice. But the weight of history will only tolerate so much.\r\n\r\n----------\r\nAlso contained in:\r\n\r\n- [Best Sellers from Reader\'s Digest Condensed Books](https://openlibrary.org/works/OL16035425W)', 'cover_6ab479eaa2c0d.jpg', 4, 4, 0, NULL, 'Rak A1', '2026-09-24 01:16:26'),
(21, 'BK-0021', '9780141971667', 'The Diary of a Young Girl- Anne Frank', 'Anne Frank', 'Penguin Books, Limited', 2001, 4, 'The Diary of a Young Girl, also known as The Diary of Anne Frank, is a book of the writings from the Dutch-language diary kept by Anne Frank while she was in hiding for two years with her family during the Nazi occupation of the Netherlands. The family was apprehended in 1944, and Anne Frank died of typhus in the Bergen-Belsen concentration camp in 1945. Anne\'s diaries were retrieved by Miep Gies and Bep Voskuijl. Miep gave them to Anne\'s father, Otto Frank, the family\'s only survivor, just after the Second World War was over.', 'cover_6ab479f142c5c.jpg', 5, 5, 0, NULL, 'Rak A1', '2026-09-24 01:16:33');

-- --------------------------------------------------------

--
-- Table structure for table `favorit`
--

CREATE TABLE `favorit` (
  `id_favorit` int NOT NULL,
  `id_anggota` int NOT NULL,
  `id_buku` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `keterangan`) VALUES
(1, 'Fiksi', 'Novel, cerpen, dan karya fiksi lainnya'),
(2, 'Non-Fiksi', 'Buku pengetahuan umum'),
(3, 'Sains & Teknologi', 'Buku sains, komputer, dan teknologi'),
(4, 'Sejarah', 'Buku sejarah dan biografi'),
(5, 'Agama', 'Buku keagamaan'),
(6, 'Anak & Remaja', 'Buku bacaan anak dan remaja'),
(7, 'Fantasi', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int NOT NULL,
  `id_anggota` int NOT NULL,
  `judul` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pesan` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipe` enum('info','success','warning','danger') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'info',
  `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kunci_unik` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dibaca` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id_notifikasi`, `id_anggota`, `judul`, `pesan`, `tipe`, `link`, `kunci_unik`, `dibaca`, `created_at`) VALUES
(1, 1, 'Pengajuan disetujui', 'Pengajuan buku \"Janji\" disetujui. Admin akan menindaklanjuti.', 'success', '/anggota/pengajuan.php', 'pengajuan:1:disetujui', 1, '2026-08-25 08:42:05'),
(2, 1, 'Peminjaman berhasil dicatat', 'Buku \"Harry Potter and the Prisoner of Azkaban\" berhasil dipinjam. Jatuh tempo: 02 September 2026.', 'success', '/anggota/peminjaman.php', 'pinjam:43:2026-08-26', 1, '2026-08-26 02:26:32'),
(3, 1, 'Peminjaman berhasil dicatat', '2 buku berhasil dipinjam. Jatuh tempo: 02 September 2026.', 'success', '/anggota/peminjaman.php', 'pinjam:multi:2026-08-26', 0, '2026-08-26 13:32:40'),
(4, 1, 'Jatuh tempo semakin dekat', 'Buku \"Harry Potter and the Prisoner of Azkaban\" jatuh tempo 3 hari lagi (02 September 2026).', 'warning', '/anggota/peminjaman.php', 'jatuh_tempo:1:2026-09-02', 0, '2026-08-30 00:19:00'),
(5, 1, 'Jatuh tempo semakin dekat', 'Buku \"A Brief History of Time\" jatuh tempo 3 hari lagi (02 September 2026).', 'warning', '/anggota/peminjaman.php', 'jatuh_tempo:2:2026-09-02', 0, '2026-08-30 00:19:00'),
(6, 1, 'Jatuh tempo semakin dekat', 'Buku \"Bumi\" jatuh tempo 3 hari lagi (02 September 2026).', 'warning', '/anggota/peminjaman.php', 'jatuh_tempo:3:2026-09-02', 0, '2026-08-30 00:19:00'),
(7, 1, 'Peminjaman sudah melewati jatuh tempo', 'Buku \"Harry Potter and the Prisoner of Azkaban\" sudah terlambat 1 hari. Segera kembalikan buku untuk mencegah denda bertambah.', 'danger', '/anggota/peminjaman.php', 'telat:1', 0, '2026-09-03 01:13:58'),
(8, 1, 'Peminjaman sudah melewati jatuh tempo', 'Buku \"A Brief History of Time\" sudah terlambat 1 hari. Segera kembalikan buku untuk mencegah denda bertambah.', 'danger', '/anggota/peminjaman.php', 'telat:2', 0, '2026-09-03 01:13:58'),
(9, 1, 'Peminjaman sudah melewati jatuh tempo', 'Buku \"Bumi\" sudah terlambat 1 hari. Segera kembalikan buku untuk mencegah denda bertambah.', 'danger', '/anggota/peminjaman.php', 'telat:3', 0, '2026-09-03 01:13:58'),
(18, 1, 'Peminjaman selesai', 'Buku \"Harry Potter and the Prisoner of Azkaban\" telah dicatat sebagai dikembalikan. Denda tercatat: Rp 1.000.', 'warning', '/anggota/riwayat.php', 'kembali:1', 0, '2026-09-03 01:37:36'),
(19, 1, 'Peminjaman selesai', 'Buku \"A Brief History of Time\" telah dicatat sebagai dikembalikan. Denda tercatat: Rp 1.000.', 'warning', '/anggota/riwayat.php', 'kembali:2', 0, '2026-09-03 01:37:40'),
(20, 1, 'Peminjaman berhasil dicatat', 'Buku \"A Brief History of Time\" berhasil dipinjam. Jatuh tempo: 10 September 2026.', 'success', '/anggota/peminjaman.php', 'pinjam:batch:1:2026-09-03:15:1788399481', 0, '2026-09-03 01:38:01'),
(24, 1, 'Pengajuan peminjaman disetujui 🎉', 'Pengajuan peminjaman buku \"Harry Potter and the Philosopher\'s Stone\" telah disetujui. Silakan ambil buku di perpustakaan. Jatuh tempo: 10 September 2026.', 'success', '/anggota/peminjaman.php', 'pengajuan_pinjam:15:disetujui', 0, '2026-09-03 01:49:23');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id_peminjaman` int NOT NULL,
  `id_anggota` int NOT NULL,
  `id_buku` int NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_jatuh_tempo` date NOT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `status` enum('dipinjam','dikembalikan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'dipinjam',
  `denda` int NOT NULL DEFAULT '0',
  `diproses_oleh` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_buku`
--

CREATE TABLE `pengajuan_buku` (
  `id` int NOT NULL,
  `anggota_id` int NOT NULL,
  `judul` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `penulis` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `isbn` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alasan` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('menunggu','disetujui','ditolak') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'menunggu',
  `catatan_admin` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengajuan_buku`
--

INSERT INTO `pengajuan_buku` (`id`, `anggota_id`, `judul`, `penulis`, `isbn`, `alasan`, `status`, `catatan_admin`, `created_at`, `updated_at`) VALUES
(1, 1, 'Janji', 'Tere liye', '9786239726201', 'menurut saya buku ini penting karena menginspirasi khalayak banyak untuk menjadi orang yang sabar dan tidak sembrono', 'disetujui', NULL, '2026-08-25 08:41:38', '2026-08-25 08:42:05');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_peminjaman`
--

CREATE TABLE `pengajuan_peminjaman` (
  `id_pengajuan` int NOT NULL,
  `id_anggota` int NOT NULL,
  `id_buku` int NOT NULL,
  `status` enum('menunggu','disetujui','ditolak','dibatalkan') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'menunggu',
  `catatan_anggota` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan_admin` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `diproses_at` datetime DEFAULT NULL,
  `diproses_oleh` int DEFAULT NULL,
  `aktif_unik` varchar(40) COLLATE utf8mb4_general_ci GENERATED ALWAYS AS ((case when (`status` = _utf8mb4'menunggu') then concat(`id_anggota`,_utf8mb4':',`id_buku`) else NULL end)) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `perpanjangan_peminjaman`
--

CREATE TABLE `perpanjangan_peminjaman` (
  `id_perpanjangan` int NOT NULL,
  `id_peminjaman` int NOT NULL,
  `id_anggota` int NOT NULL,
  `tanggal_pengajuan` date NOT NULL,
  `hari_diminta` tinyint UNSIGNED NOT NULL,
  `tanggal_jatuh_tempo_lama` date NOT NULL,
  `tanggal_jatuh_tempo_baru` date DEFAULT NULL,
  `catatan_anggota` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan_admin` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('menunggu','disetujui','ditolak') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'menunggu',
  `diproses_oleh` int DEFAULT NULL,
  `diproses_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `anggota`
--
ALTER TABLE `anggota`
  ADD PRIMARY KEY (`id_anggota`),
  ADD UNIQUE KEY `nomor_anggota` (`nomor_anggota`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_anggota_reset_token` (`reset_token`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_target` (`target_tabel`,`target_id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_created` (`created_at`);

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id_buku`),
  ADD UNIQUE KEY `kode_buku` (`kode_buku`),
  ADD UNIQUE KEY `uniq_buku_isbn` (`isbn`),
  ADD KEY `id_kategori` (`id_kategori`),
  ADD KEY `idx_buku_arsip` (`is_arsip`);

--
-- Indexes for table `favorit`
--
ALTER TABLE `favorit`
  ADD PRIMARY KEY (`id_favorit`),
  ADD UNIQUE KEY `unik_favorit` (`id_anggota`,`id_buku`),
  ADD KEY `id_buku` (`id_buku`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`),
  ADD UNIQUE KEY `uniq_kategori_nama` (`nama_kategori`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD UNIQUE KEY `unik_notifikasi_anggota` (`id_anggota`,`kunci_unik`),
  ADD KEY `idx_notifikasi_anggota_baca` (`id_anggota`,`dibaca`,`created_at`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id_peminjaman`),
  ADD KEY `id_anggota` (`id_anggota`),
  ADD KEY `id_buku` (`id_buku`),
  ADD KEY `diproses_oleh` (`diproses_oleh`),
  ADD KEY `idx_peminjaman_status_jatuh_tempo` (`status`,`tanggal_jatuh_tempo`);

--
-- Indexes for table `pengajuan_buku`
--
ALTER TABLE `pengajuan_buku`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pengajuan_anggota` (`anggota_id`),
  ADD KEY `idx_pengajuan_status` (`status`),
  ADD KEY `idx_pengajuan_created` (`created_at`);

--
-- Indexes for table `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  ADD PRIMARY KEY (`id_pengajuan`),
  ADD UNIQUE KEY `uniq_pengajuan_aktif` (`aktif_unik`),
  ADD KEY `idx_pengajuan_status` (`status`,`created_at`),
  ADD KEY `idx_pengajuan_anggota` (`id_anggota`,`created_at`),
  ADD KEY `idx_pengajuan_buku` (`id_buku`),
  ADD KEY `idx_pengajuan_created` (`created_at`),
  ADD KEY `idx_pengajuan_diproses_oleh` (`diproses_oleh`),
  ADD KEY `idx_pengajuan_anggota_buku` (`id_anggota`,`id_buku`);

--
-- Indexes for table `perpanjangan_peminjaman`
--
ALTER TABLE `perpanjangan_peminjaman`
  ADD PRIMARY KEY (`id_perpanjangan`),
  ADD KEY `id_peminjaman` (`id_peminjaman`),
  ADD KEY `diproses_oleh` (`diproses_oleh`),
  ADD KEY `idx_perpanjangan_status` (`status`,`created_at`),
  ADD KEY `idx_perpanjangan_anggota` (`id_anggota`,`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `anggota`
--
ALTER TABLE `anggota`
  MODIFY `id_anggota` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id_buku` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `favorit`
--
ALTER TABLE `favorit`
  MODIFY `id_favorit` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id_peminjaman` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengajuan_buku`
--
ALTER TABLE `pengajuan_buku`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  MODIFY `id_pengajuan` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `perpanjangan_peminjaman`
--
ALTER TABLE `perpanjangan_peminjaman`
  MODIFY `id_perpanjangan` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL;

--
-- Constraints for table `buku`
--
ALTER TABLE `buku`
  ADD CONSTRAINT `buku_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE SET NULL;

--
-- Constraints for table `favorit`
--
ALTER TABLE `favorit`
  ADD CONSTRAINT `favorit_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorit_ibfk_2` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON DELETE CASCADE;

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON DELETE CASCADE;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`),
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`),
  ADD CONSTRAINT `peminjaman_ibfk_3` FOREIGN KEY (`diproses_oleh`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL;

--
-- Constraints for table `pengajuan_buku`
--
ALTER TABLE `pengajuan_buku`
  ADD CONSTRAINT `pengajuan_buku_ibfk_1` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id_anggota`) ON DELETE CASCADE;

--
-- Constraints for table `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  ADD CONSTRAINT `pengajuan_peminjaman_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengajuan_peminjaman_ibfk_2` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengajuan_peminjaman_ibfk_3` FOREIGN KEY (`diproses_oleh`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL;

--
-- Constraints for table `perpanjangan_peminjaman`
--
ALTER TABLE `perpanjangan_peminjaman`
  ADD CONSTRAINT `perpanjangan_peminjaman_ibfk_1` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE CASCADE,
  ADD CONSTRAINT `perpanjangan_peminjaman_ibfk_2` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON DELETE CASCADE,
  ADD CONSTRAINT `perpanjangan_peminjaman_ibfk_3` FOREIGN KEY (`diproses_oleh`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
