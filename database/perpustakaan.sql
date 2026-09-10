-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 03, 2026 at 03:19 AM
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
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `nomor_anggota` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `no_hp` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anggota`
--

INSERT INTO `anggota` (`id_anggota`, `nomor_anggota`, `nama`, `email`, `password`, `no_hp`, `alamat`, `foto`, `reset_token`, `reset_expiry`, `status`, `created_at`) VALUES
(1, 'A0001', 'Budi Ramsey', 'budi@example.com', '$2y$10$0xjRw9o1aU5Uy/laFXhFsuerQIF/9optwGQSUVLfpjEtw6pPB1u2i', '081234567890', 'Jl. Melati No. 10', 'profil_6a8ee472d6845.jpg', NULL, NULL, 'aktif', '2026-08-25 08:41:25'),
(2, 'A0002', 'Ali Alhafidz', 'alialhafidz28@gmail.com', '$2y$10$C.Wn92VPKmGtpCAySw5QaOrmFuhJGZ4tYzOKfkYW0t0HwYMHpi.g2', '083820227687', 'Kp. Cijawal, Desa Cibedug, Kec. Rongga', NULL, NULL, NULL, 'aktif', '2026-08-29 14:48:34');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `aksi` enum('tambah','edit','hapus') COLLATE utf8mb4_general_ci NOT NULL,
  `target_tabel` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `target_id` int NOT NULL,
  `detail` text COLLATE utf8mb4_general_ci,
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
(90, 1, 'edit', 'buku', 43, '{\"judul\":\"Harry Potter and the Prisoner of Azkaban\"}', '2026-09-03 03:09:07');

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id_buku` int NOT NULL,
  `kode_buku` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `isbn` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `judul` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `penulis` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `penerbit` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tahun_terbit` year DEFAULT NULL,
  `id_kategori` int DEFAULT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `cover` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `stok` int NOT NULL DEFAULT '0',
  `tersedia` int NOT NULL DEFAULT '0',
  `is_arsip` tinyint(1) NOT NULL DEFAULT '0',
  `arsip_at` datetime DEFAULT NULL,
  `lokasi_rak` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id_buku`, `kode_buku`, `isbn`, `judul`, `penulis`, `penerbit`, `tahun_terbit`, `id_kategori`, `deskripsi`, `cover`, `stok`, `tersedia`, `is_arsip`, `arsip_at`, `lokasi_rak`, `created_at`) VALUES
(6, 'BK-0006', '9786239726201', 'Janji', 'Tere Liye', 'Sabak Grip Nusantara', 2021, 1, 'Kita semua adalah pengembara di dunia ini.\r\nAda yang kaya, pun ada yang miskin. Ada yang terkenal, ternama, berkuasa, juga ada\r\nyang bukan siapa-siapa.\r\nAda yang seolah bisa membeli apapun, melakukan apapun yang dia mau, hebat sekali.\r\nAda yang bahkan bingung besok harus makan apa.\r\nTapi sesungguhnya di manakah kebahagiaan itu hinggap?\r\nDi manakah hakikat kehidupan itu tersembunyi?\r\nApakah seperti yang kita lihat dari luar saja?\r\nInilah kisah tentang janji.\r\nKita semua adalah pengembara di dunia ini.\r\nDari hari ke hari. Dari satu tempat ke tempat lain.\r\nDari satu kejadian ke kejadian lain. Terus mengembara.\r\nDan kita pasti akan menggenapkan janji yang satu ini: mati.', 'cover_6a8d64fa7cfa5.jpg', 6, 6, 0, NULL, 'RakA1', '2026-08-25 09:48:42'),
(7, 'BK-0007', '9780061120084', 'To Kill a Mockingbird', 'Harper Lee', 'Harper Perennial Modern Classics', 1960, 7, 'One of the best-loved stories of all time, To Kill a Mockingbird has been translated into more than forty languages, sold more than thirty million copies worldwide, served as the basis of an enormously popular motion picture, and was voted one of the best novels of the twentieth century by librarians across the country. A gripping, heart wrenching, and wholly remarkable tale of coming-of-age in a South poisoned by viruletn prejudice, it views a world of great beauty and savage inequities through the eyes of a youn girl, as her father--a crusading local lawyer--risks everything to defend a black man unjustly accused of a terrible crime.\r\n(front flap)', 'cover_6a8d7264a0180.jpg', 2, 2, 0, NULL, 'Rak A4', '2026-08-25 10:45:56'),
(8, 'BK-0008', '9780451524935', 'Nineteen Eighty-Four', 'George Orwell', 'Signet Classics', 1949, 4, '1984 has come and gone, but George Orwell\'s prophetic, nightmarish vision in 1949 of the world we were becoming is timelier than ever. 1984 is still the great modern classic of \"negative utopia\" a startlingly original and haunting novel that creates an imaginary world that is convincing, from the first sentence to the last four words. No one can deny the novel\'s hold on the imaginations of whole generations, or the power of its admonitions a power that seems to grow, not lessen, with the passage of time.\r\n--back cover', 'cover_6a8d7269e53a9.jpg', 3, 3, 0, NULL, 'Rak A4', '2026-08-25 10:46:01'),
(9, 'BK-0009', '9780743273565', 'The Great Gatsby', 'F. Scott Fitzgerald', 'Scribner', 1920, 4, 'THE GREAT GATSBY, F. Scott Fitzgerald’s third book, stands as the supreme achievement of his career. This exemplary novel of the Jazz Age has been acclaimed by generations of readers. The story of the fabulously wealthy Jay Gatsby and his love for the beautiful Daisy Buchanan, of lavish parties on Long Island at a time when The New York Times noted “gin was the national drink and sex the national obsession,” is an exquisitely crafted tale of America in the 1920s.\r\n\r\nThe Great Gatsby is one of the great classics of twentieth-century literature.\r\n--back cover', 'cover_6a8d726f9cd13.jpg', 8, 8, 0, NULL, 'Rak A4', '2026-08-25 10:46:07'),
(10, 'BK-0010', '9780307474278', 'The Da Vinci Code', 'Dan Brown', 'Anchor', 2003, 4, 'The Da Vinci Code is a 2003 mystery thriller novel by Dan Brown. It is Brown\'s second novel to include the character Robert Langdon: the first was his 2000 novel Angels & Demons. The Da Vinci Code follows \"symbologist\" Robert Langdon and cryptologist Sophie Neveu after a murder in the Louvre Museum in Paris causes them to become involved in a battle between the Priory of Sion and Opus Dei over the possibility of Jesus Christ and Mary Magdalene having had a child together.\r\n\r\n\r\n----------\r\nSee also:\r\n[The Da Vinci Code [1/2]](https://openlibrary.org/works/OL24164822W)\r\n[The Da Vinci Code [2/2]](https://openlibrary.org/works/OL24210437W)\r\n\r\n		\r\nContained in:\r\n[Angels & Demons / The Da Vinci Code](https://openlibrary.org/works/OL15290520W)', 'cover_6a8d7275c7c84.jpg', 6, 6, 0, NULL, 'Rak A4', '2026-08-25 10:46:13'),
(11, 'BK-0011', '9780140283334', 'Lord of the Flies', 'William Golding', 'Penguin (Non-Classics)', 1954, 1, 'Lord of the Flies is a 1954 novel by Nobel Prize–winning British author William Golding. The book focuses on a group of British boys stranded on an uninhabited island and their disastrous attempt to govern themselves. Themes include the tension between groupthink and individuality, between rational and emotional reactions, and between morality and immorality.\r\n\r\nThe novel has been generally well received. It was named in the Modern Library 100 Best Novels, reaching number 41 on the editor\'s list, and 25 on the reader\'s list. In 2003 it was listed at number 70 on the BBC\'s The Big Read poll, and in 2005 Time magazine named it as one of the 100 best English-language novels from 1923 to 2005. Time also included the novel in its list of the 100 Best Young-Adult Books of All Time. Popular reading in schools, especially in the English-speaking world, a 2016 UK poll saw Lord of the Flies ranked third in the nation\'s favourite books from school.\r\n\r\n(From https://en.wikipedia.org/wiki/Lord_of_the_Flies)', 'cover_6a8d727c6ea25.jpg', 7, 7, 0, NULL, 'Rak A2', '2026-08-25 10:46:20'),
(12, 'BK-0012', '9780385490818', 'The Handmaid\'s Tale', 'Margaret Atwood', 'Anchor Books', 1985, 4, 'Offred is a Handmaid in the Republic of Gilead. She may leave the home of the Commander and his wife once a day to walk to food markets whose signs are now pictures instead of words because women are no longer allowed to read. She must lie on her back once a month and pray that the Commander makes her pregnant, because in an age of declining births, Offred and the other Handmaids are valued only if their ovaries are viable. Offred can remember the years before, when she lived and made love with her husband, Luke; when she played with and protected her daughter; when she had a job, money of her own, and access to knowledge. But all of that is gone now...\r\n--back cover', 'cover_6a8d7282318b9.jpg', 9, 9, 0, NULL, 'Rak A2', '2026-08-25 10:46:26'),
(13, 'BK-0013', '9780062315007', 'O Alquimista', 'Paulo Coelho', 'HarperCollins Publishers', 1988, 1, 'The Alchemist details the journey of a young Andalusian shepherd boy named Santiago. Santiago, believing a recurring dream to be prophetic, decides to travel to the pyramids of Egypt to find treasure. On the way, he encounters love, danger, opportunity and disaster. One of the significant characters that he meets is an old king named Melchizedek who tells him that \"When you want something, all the universe conspires in helping you to achieve it.\" This is the core philosophy and motif of the book.', 'cover_6a8d728686333.jpg', 6, 6, 0, NULL, 'Rak A2', '2026-08-25 10:46:30'),
(14, 'BK-0014', '9780439023481', 'The Hunger Games', 'Suzanne Collins', 'Scholastic Press', 2008, 6, 'In the ruins of a place once known as North America lies the nation of Panem, a shining Capitol surrounded by twelve outlying districts. The Capitol is harsh and cruel and keeps the districts in line by forcing them all to send one boy and one girl between the ages of twelve and eighteen to participate in the annual Hunger Games, a fight to the death on live TV.\r\n\r\nSixteen-year-old Katniss Everdeen, who lives alone with her mother and younger sister, regards it as a death sentence when she steps forward to take her sister\'s place in the Games. But Katniss has been close to dead before—and survival, for her, is second nature. Without really meaning to, she becomes a contender. But if she is to win, she will have to start making choices that weight survival against humanity and life against love.', 'cover_6a8d728c1d796.jpg', 5, 5, 0, NULL, 'Rak A2', '2026-08-25 10:46:36'),
(15, 'BK-0015', '9780553380163', 'A Brief History of Time', 'Stephen Hawking', 'andom House Publishing Group', 1988, 4, 'Stephen Hawking\'s ‘A Brief History of Time* has become an international publishing phenomenon. Translated into thirty languages, it has sold over ten million copies worldwide and lives on as a science book that continues to captivate and inspire new readers each year. When it was first published in 1988 the ideas discussed in it were at the cutting edge of what was then known about the universe. In the intervening twenty years there have been extraordinary advances in the technology of observing both the micro- and macro-cosmic world. Indeed, during that time cosmology and the theoretical sciences have entered a new golden age . Professor Hawking is one of the major scientists and thinkers to have contributed to this renaissance.', 'cover_6a8d7292cdd6c.jpg', 3, 2, 0, NULL, 'Rak A2', '2026-08-25 10:46:42'),
(16, 'BK-0016', '9786020301129', 'Bumi', 'Tere Liye', 'PT Gramedia Pustaka Utama', 2014, 1, 'Namaku Raib, usiaku 15 tahun, kelas sepuluh. Aku anak perempuan seperti kalian, adik-adik kalian, tetangga kalian. Aku punya dua kucing, namanya si Putih dan si Hitam. Mama dan papaku menyenangkan. Guru-guru di sekolahku seru. Teman-temanku baik dan kompak.\r\n\r\nAku sama seperti remaja kebanyakan, kecuali satu hal. Sesuatu yang kusimpan sendiri sejak kecil. Sesuatu yang menakjubkan.\r\n\r\nNamaku, Raib. Dan aku bisa menghilang.', 'cover_6a8d73dcbe216.jpg', 6, 5, 0, NULL, 'Rak A3', '2026-08-25 10:52:12'),
(17, 'BK-0017', '9786020822341', 'Tentang Kamu', 'Tere Liye', 'Pt. Putra Bangsa', 2016, 1, 'Terima kasih untuk kesempatan mengenalmu, itu adalah salah satu anugerah terbesar hidupku. Cinta memang tidak perlu ditemukan, cintalah yang akan menemukan kita. Terima kasih. Nasihat lama itu benar sekali, aku tidak akan menangis karena sesuatu telah berakhir, tapi aku akan tersenyum karena sesuatu itu pernah terjadi. Masa lalu. Rasa sakit. Masa depan. Mimpi-mimpi. Semua akan berlalu, seperti sungai yang mengalir. Maka biarlah hidupku mengalir seperti sungai kehidupan.\r\n\r\n***\r\n\r\nAtas dasar pekerjaan, Zaman Zulkarnaen harus menelusuri hidup seorang kliennya, perempuan pemegang paspor Inggris yang barusan meninggal dan mewariskan harta yang jumlahnya bisa menyaingi kekayaan Ratu Inggris. Tiga negara, lima kota, beribu luka. Hingga akhirnya Zaman mengerti, bahwa ini bukan sekadar perkara mengerti jalan hidup seorang klien, melainkan pengejawantahan prinsip kuat di tengah cobaan yang terus mendera.\r\n\r\nTentang Kamu adalah novel terbaru Tere Liye. Sebuah karya yang tak hanya akan membawa pembacanya menyelami sebuah petualangan yang seru dan sarat emosi, tapi juga memberikan nilai positif sehingga membuat hidup serasa lebih patut disyukuri.', 'cover_6a8d741f7b544.jpg', 6, 6, 0, NULL, 'Rak A3', '2026-08-25 10:53:19'),
(18, 'BK-0018', '9786020332116', 'Matahari', 'Tere Liye', 'Gramedia Pustaka Utama', 2016, 1, 'Namanya Ali, 15 tahun, kelas X. Jika saja orangtuanya mengizinkan, seharusnya dia sudah duduk di tingkat akhir ilmu fisika program doktor di universitas ternama. Ali tidak menyukai sekolahnya, guru-gurunya, teman-teman sekelasnya. Semua membosankan baginya.\r\n\r\nTapi sejak dia mengetahui ada yang aneh pada diriku dan Seli, teman sekelasnya, hidupnya yang membosankan berubah seru. Aku bisa menghilang, dan Seli bisa mengeluarkan petir.Ali sendiri punya rahasia kecil. Dia bisa berubah menjadi beruang raksasa. Kami bertiga kemudian bertualang ke tempat-tempat menakjubkan. Namanya Ali. Dia tahu sejak dulu dunia ini tidak sesederhana yang dilihat orang. Dan di atas segalanya, dia akhirnya tahu persahabatan adalah hal yang paling utama.', 'cover_6a8d74251faf9.jpg', 6, 6, 0, NULL, NULL, '2026-08-25 10:53:25'),
(19, 'BK-0019', '9786020324784', 'Hujan', 'Tere Liye', 'Gramedia Pustaka Utama', 2018, 1, 'Tentang persahabatan\r\nTentang cinta\r\nTentang perpisahan\r\nTentang melupakan\r\nTentang hujan', 'cover_6a8d742ac56f2.jpg', 5, 5, 0, NULL, 'Rak A3', '2026-08-25 10:53:30'),
(20, 'BK-0020', '9786238829682', 'HELLO', 'Tere Liye', 'Sabak Grip Nusantara', 2023, 1, 'Hello.\r\n\r\nApakah kamu di sana?\r\n\r\nAku tahu kamu di sana.\r\n\r\nAku tahu kamu mendengarkan suaraku.\r\n\r\nHello.\r\n\r\nAku tahu kita belum bisa bicara. Tapi aku tidak bisa menahan diriku untuk meneleponmu. Aku hanya hendak bilang, aku tidak akan menyerah. Aku akan selalu menyayangimu.', 'cover_6a8d7431005c6.jpg', 3, 3, 0, NULL, 'Rak A3', '2026-08-25 10:53:37'),
(21, 'BK-0021', '9786020652283', 'Lumpu', 'Tere Liye', 'Gramedia Pustaka Utama', 2021, 1, 'Yes! Akhirnya, Raib, Seli, dan Ali kembali bertualang. Kalian sudah kangen dengan trio ini? Misi mereka adalah menyelamatkan Miss Selena, guru matematika mereka. Tapi, apakah semua berjalan mudah? Siapa yang bersedia membantu mereka? Kali ini, si genius Ali memutuskan meminta bantuan dari sosok yang tidak terduga, karena musuh dari musuh adalah teman. Apakah Raib bisa melupakan masa lalu itu dengan memaafkan Miss Selena? Bagaimana dengan Tazk? Apakah Raib bisa bertemu lagi dengan ayahnya, atau itu masih menjadi misteri? Bagaimana dengan jejak ekspedisi Klan Aldebaran 40.000 tahun lalu? Benda apa saja yang ditinggalkan oleh perjalanan besar tersebut? Pertarungan panjang telah menunggu mereka. Dan lawan mereka adalah Lumpu, petarung yang memiliki teknik unik, yaitu melumpuhkan kekuatan lawan. Itu teknik yang amat menakutkan, karena Lumpu bisa menghabisi teknik bertarung. Jangan-jangan… Siapa di antara Raib, Seli, dan Ali yang akan kehilangan kekuatan di dunia paralel? Buku ini adalah buku ke-11 dari serial BUMI.', 'cover_6a8d743714efe.jpg', 2, 2, 0, NULL, 'Rak A3', '2026-08-25 10:53:43'),
(22, 'BK-0022', '9786020623399', 'Komet Minor', 'Tere Liye', 'PT GRAMEDIA PUSTAKA UTAMA (GPU)', 2019, NULL, 'Buku ini adalah versi unedited version, naskah original sebelum diedit dan direvisi oleh redaksi Penerbit. Jika kalian hendak memiliki fisik cetaknya, baru terbit Maret 2019.\r\n\r\nPertarungan melawan Si Tanpa Mahkota akan berakhir di sini. Siapapun yang menang, semua berakhir di sini, di klan Komet Minor, tempat aliansi Para Pemburu pernah dibentuk, dan pusaka hebat pernah diciptakan.\r\n\r\nDalam saga terakhir melawan Si Tanpa Mahkota, aku, Seli dan Ali menemukan teman seperjalanan yang hebat, yang bersama-sama melewati berbagai rintangan. Memahami banyak hal, berlatih teknik baru, dan bertarung bersama-sama. Inilah kisah kami. Tentang persahabatan sejati. Tentang pengorbanan. Tentang ambisi. Tentang memaafkan. Namaku Raib, dan aku bisa menghilang.', 'cover_6a8d743cb2bdd.jpg', 4, 4, 0, NULL, 'Rak A3', '2026-08-25 10:53:48'),
(23, 'BK-0023', '9786025734052', 'Pergi', 'Tere Liye', 'Republika', 2018, 1, 'Sebuah kisah tentang menemukan tujuan, ke mana hendak pergi, melalui kenangan demi kenangan masa lalu, pertarungan hidup-mati, untuk memutuskan ke mana langkah kaki akan dibawa.', 'cover_6a8d74427c485.jpg', 5, 5, 1, '2026-08-29 21:19:28', 'Rak A3', '2026-08-25 10:53:54'),
(24, 'BK-0024', '9786239554521', 'Pulang-Pergi', 'Tere Liye', 'Sabak Grip Nusantara', 2021, 1, '\"Ada jodoh yang ditemukan lewat tatapan pertama.\r\nAda persahabatan yang diawali lewat sapa hangat.\r\nBagaimana jika takdir bersama ternyata,\r\ndiawali dengan pertarungan mematikan?\r\nLantas semua cerita berkelindan dengan,\r\npengejaran demi pengejaran mencari jawaban?\r\nPulang-Pergi.\"', 'cover_6a8d744a4f4ec.jpg', 9, 9, 0, NULL, 'Rak A3', '2026-08-25 10:54:02'),
(25, 'BK-0025', '9786239726232', 'Rasa', 'Tere Liye', 'Sabak Grip Nusantara', 2022, 1, 'Apakah memaafkan itu mudah diberikan?\r\nApakah melupakan itu ringan dilakukan?\r\nSayangnya, itu sering kali lebih enteng diucapkan,\r\ntapi di hati terdalam tetap begitulah.\r\n\r\nBagaimana caranya kita memeluk erat semua\r\nrasa marah, benci, sakit hati, ketika itu bahkan\r\nbaru mulai dibicarakan saja sudah menyakitkan?\r\nBagaimana berdamai dengan situasi tersebut?\r\n\r\nInilah novel tentang ’rasa’.\r\n\r\nBerbagai rasa berkumpul di novel ini.\r\n\r\nJika kalian tertawa, menangis, atau merenung\r\npanjang saat membaca buku ini, ingatlah selalu:\r\napapun yang terjadi atas kehidupan,\r\ntidak semuanya berjalan sesuai keinginan kita.\r\nTapi kita selalu bisa menerimanya.', 'cover_6a8d744fbd8db.jpg', 5, 5, 0, NULL, 'Rak A3', '2026-08-25 10:54:07'),
(33, 'BK-0033', '9780747558194', 'Harry Potter and the Philosopher\'s Stone', 'J. K. Rowling', 'Bloomsbury Publishing', 1997, 7, 'Harry Potter thinks he is an ordinary boy - until he is rescued by a beetle-eyed giant of a man, enrolls at Hogwarts School of Witchcraft and Wizardry, learns to play Quidditch and does battle in a deadly duel. The Reason: HARRY POTTER IS A WIZARD!\r\n(back cover)', 'cover_6a8d75d050113.jpg', 2, 1, 0, NULL, 'Rak A1', '2026-08-25 11:00:32'),
(36, 'BK-0036', '9780545139700', 'Harry Potter and the Deathly Hallows', 'J. K. Rowling', 'Arthur A. Levine Books', 2007, 7, 'Harry Potter is leaving Privet Drive for the last time. But as he climbs into the sidecar of Hagrid’s motorbike and they take to the skies, he knows Lord Voldemort and the Death Eaters will not be far behind.\r\n\r\nThe protective charm that has kept him safe until now is broken. But the Dark Lord is breathing fear into everything he loves. And he knows he can’t keep hiding.\r\n\r\nTo stop Voldemort, Harry knows he must find the remaining Horcruxes and destroy them.\r\n\r\nHe will have to face his enemy in one final battle.\r\n\r\n([source](https://www.jkrowling.com/book/harry-potter-deathly-hallows/))\r\n\r\n---\r\n\r\nSee also:\r\n\r\n- [Harry Potter and the Deathly Hallows: 2/2](https://openlibrary.org/works/OL17922343W/Harry_Potter_and_the_Deathly_Hallows_Chapters_20-36)', 'cover_6a8d75dd17b2f.jpg', 8, 8, 0, NULL, 'Rak A1', '2026-08-25 11:00:45'),
(37, 'BK-0037', '9780545227704', 'Herr der Diebe', 'Cornelia Funke', 'Scholastic', 2000, 1, 'Escaping the aunt who wants to adopt only one of them, two orphaned brothers run away from Hamburg to Venice, finding shelter with a gang of street children and their leader, the thirteen-year-old \"Thief Lord,\" while also eluding the detective hired to return them to Germany.', 'cover_6a8d75e470e59.jpg', 6, 6, 0, NULL, 'Rak A2', '2026-08-25 11:00:52'),
(38, 'BK-0038', '9780439784542', 'Harry Potter and the Half-Blood Prince', 'J. K. Rowling', 'Arthur A. Levine Books', 2005, 7, 'Harry Potter and the Half-Blood Prince brings us Harry Potter\'s sixth year at Hogwarts School of Witchcraft and Wizardry as Lord Voldemort becomes ever more powerful with his followers increasing day by day in this continuing battle between good and evil. Harry searches for the full and complex story of the boy who became Lord Voldemort, and thereby finds what may be his only vulnerability.', 'cover_6a8d75e9a4099.jpg', 7, 7, 0, NULL, 'Rak A1', '2026-08-25 11:00:57'),
(40, 'BK-0040', '9780439358071', 'Harry Potter and the Order of the Phoenix', 'J. K. Rowling', 'Scholastic Inc.', 2003, 7, 'There is a door at the end of a silent corridor. And it’s haunting Harry Pottter’s dreams. Why else would he be waking in the middle of the night, screaming in terror?\r\n\r\nHarry has a lot on his mind for this, his fifth year at Hogwarts: a Defense Against the Dark Arts teacher with a personality like poisoned honey; a big surprise on the Gryffindor Quidditch team; and the looming terror of the Ordinary Wizarding Level exams. But all these things pale next to the growing threat of He-Who-Must-Not-Be-Named---a threat that neither the magical government nor the authorities at Hogwarts can stop.\r\n\r\nAs the grasp of darkness tightens, Harry must discover the true depth and strength of his friends, the importance of boundless loyalty, and the shocking price of unbearable sacrifice.\r\n\r\nHis fate depends on them all.\r\n(back cover)', 'cover_6a8d75f36a222.jpg', 4, 4, 0, NULL, 'Rak A1', '2026-08-25 11:01:07'),
(41, 'BK-0041', '9780439139595', 'Harry Potter and the Goblet of Fire', 'J. K. Rowling', 'Arthur A. Levine Books', 2000, 7, 'Fourteen-year-old Harry Potter joins the Weasleys at the Quidditch World Cup, then enters his fourth year at Hogwarts Academy where he is mysteriously entered in an unusual contest that challenges his wizarding skills, friendships and character, amid signs that an old enemy is growing stronger.', 'cover_6a8d75f899459.jpg', 6, 6, 0, NULL, 'Rak A1', '2026-08-25 11:01:12'),
(42, 'BK-0042', '9780439064866', 'Harry Potter and the Chamber of Secrets', 'J. K. Rowling', 'Arthur A. Levine Books', 1998, 7, 'When the Chamber of Secrets is opened again at the Hogswart School for Witchcraft and Wizardry, second-year student Harry Potter finds himself in danger from a dark power that has once more been released on the school.', 'cover_6a8d75fc4667b.jpg', 3, 3, 0, NULL, 'Rak A1', '2026-08-25 11:01:16'),
(43, 'BK-0043', '9780439655484', 'Harry Potter and the Prisoner of Azkaban', 'J. K. Rowling', 'Scholastic', 1999, 7, 'Harry Potter and the Prisoner of Azkaban menceritakan petualangan Harry Potter pada tahun ketiganya di Sekolah Sihir Hogwarts. Sebelum kembali ke sekolah, Harry mengetahui bahwa seorang tahanan berbahaya bernama Sirius Black berhasil melarikan diri dari penjara Azkaban. Banyak orang percaya bahwa Sirius Black sedang mencari Harry dan ingin mencelakainya.\r\n\r\nSelama berada di Hogwarts, Harry bersama sahabatnya, Ron Weasley dan Hermione Granger, menghadapi berbagai kejadian misterius. Mereka juga harus berhadapan dengan Dementor, makhluk menyeramkan yang menjaga Hogwarts. Harry mulai mengetahui lebih banyak tentang masa lalu orang tuanya dan hubungan mereka dengan Sirius Black.\r\n\r\nSeiring berjalannya waktu, Harry menemukan kenyataan yang mengejutkan bahwa tidak semua orang adalah seperti yang terlihat. Rahasia tentang Sirius Black akhirnya terungkap dan mengubah pemahaman Harry tentang masa lalu keluarganya. Cerita ini mengangkat tema persahabatan, keberanian, kesetiaan, dan pentingnya mengetahui kebenaran.', 'cover_6a8d7602e8e6a.jpg', 6, 6, 0, NULL, 'Rak A1', '2026-08-25 11:01:22'),
(44, 'BK-0044', '9780545162074', 'Harry Potter (series) 1-7', 'J. K. Rowling', 'Arthur A. Levine Books', 1999, 7, 'The Harry Potter books throw you into an amazing fantasy world of witches and wizards, spells, magical creatures, He-Who-Must-Not-Be-Named, and a school of witchcraft and wizardry called Hogwarts. In this school Harry Potter takes many magical classes, plays Quidditch (the sport where you are on a broomstick scoring goals through hoops, and trying to find the magical snitch.), and defeats Voldemort. When Voldemort comes back at him again and again, Harry realizes that Voldemort is invincible, and there is only one way to kill him. With his lessons with Professor Dumbledore, he discovers Voldemort\'s past, who he was before he killed all those people, why he killed all those people (including Harry Potter\'s parents), and how he is still alive, but is almost a creature, with snake eyes, and slits for nostrils. This best-selling series is astonishing and breathtaking and will change your life forever, just by reading it.', 'cover_6a8d7609282d5.jpg', 5, 5, 0, NULL, 'Rak A1', '2026-08-25 11:01:29'),
(46, 'BK-0045', '9781402736889', 'The War of the Worlds', 'H. G. Wells', 'Sterling', 2007, 1, '150 p. : ill. ; 20 cm.NC760L Lexile', 'cover_6a8e4e51ad214.jpg', 6, 6, 1, '2026-08-29 21:19:23', 'Rak A4', '2026-08-26 02:24:17'),
(47, 'BK-0046', '0395366917', 'Bold Journey', 'Charles H. Bohner', 'Houghton Mifflin', 1985, 1, 'Private Hugh McNeal relates his experiences accompanying Captains Lewis and Clark on their 1804-1806 expedition in search of a northwest passage to the Pacific Ocean.', 'cover_6a919d30b6014.jpg', 9, 9, 1, '2026-08-29 21:19:20', 'Rak A1', '2026-08-28 14:37:36');

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

--
-- Dumping data for table `favorit`
--

INSERT INTO `favorit` (`id_favorit`, `id_anggota`, `id_buku`, `created_at`) VALUES
(2, 1, 43, '2026-08-29 14:58:01');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
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
  `judul` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `pesan` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `tipe` enum('info','success','warning','danger') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'info',
  `link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kunci_unik` varchar(180) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `status` enum('dipinjam','dikembalikan') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'dipinjam',
  `denda` int NOT NULL DEFAULT '0',
  `diproses_oleh` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peminjaman`
--

INSERT INTO `peminjaman` (`id_peminjaman`, `id_anggota`, `id_buku`, `tanggal_pinjam`, `tanggal_jatuh_tempo`, `tanggal_kembali`, `status`, `denda`, `diproses_oleh`, `created_at`) VALUES
(1, 1, 43, '2026-08-26', '2026-09-02', '2026-09-03', 'dikembalikan', 1000, 1, '2026-08-26 02:26:32'),
(2, 1, 15, '2026-08-26', '2026-09-02', '2026-09-03', 'dikembalikan', 1000, 1, '2026-08-26 13:32:40'),
(3, 1, 16, '2026-08-26', '2026-09-02', NULL, 'dipinjam', 0, 1, '2026-08-26 13:32:40'),
(6, 1, 15, '2026-09-03', '2026-09-10', NULL, 'dipinjam', 0, 1, '2026-09-03 01:38:01'),
(7, 1, 33, '2026-09-03', '2026-09-10', NULL, 'dipinjam', 0, 1, '2026-09-03 01:49:23');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_buku`
--

CREATE TABLE `pengajuan_buku` (
  `id` int NOT NULL,
  `anggota_id` int NOT NULL,
  `judul` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `penulis` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `isbn` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alasan` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('menunggu','disetujui','ditolak') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'menunggu',
  `catatan_admin` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `status` enum('menunggu','disetujui','ditolak','dibatalkan') NOT NULL DEFAULT 'menunggu',
  `catatan_anggota` varchar(500) DEFAULT NULL,
  `catatan_admin` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `diproses_at` datetime DEFAULT NULL,
  `diproses_oleh` int DEFAULT NULL,
  `aktif_unik` varchar(40) GENERATED ALWAYS AS ((case when (`status` = _utf8mb4'menunggu') then concat(`id_anggota`,_utf8mb4':',`id_buku`) else NULL end)) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pengajuan_peminjaman`
--

INSERT INTO `pengajuan_peminjaman` (`id_pengajuan`, `id_anggota`, `id_buku`, `status`, `catatan_anggota`, `catatan_admin`, `created_at`, `updated_at`, `diproses_at`, `diproses_oleh`) VALUES
(15, 1, 33, 'disetujui', NULL, NULL, '2026-09-03 01:38:37', '2026-09-03 01:49:23', '2026-09-03 08:49:23', 1);

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
  `catatan_anggota` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan_admin` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('menunggu','disetujui','ditolak') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'menunggu',
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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id_buku` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `favorit`
--
ALTER TABLE `favorit`
  MODIFY `id_favorit` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id_peminjaman` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pengajuan_buku`
--
ALTER TABLE `pengajuan_buku`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  MODIFY `id_pengajuan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
