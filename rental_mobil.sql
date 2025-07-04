-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 03, 2025 at 11:04 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rental_mobil`
--

-- --------------------------------------------------------

--
-- Table structure for table `history_transactions`
--

CREATE TABLE `history_transactions` (
  `id` int(11) NOT NULL,
  `nama_peminjam` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `alamat` varchar(255) NOT NULL,
  `merk` varchar(100) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `tanggal_aktual_kembali` date DEFAULT NULL,
  `status_peminjaman` enum('dipinjam','kembali','batal') NOT NULL DEFAULT 'dipinjam',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `history_transactions`
--

INSERT INTO `history_transactions` (`id`, `nama_peminjam`, `no_hp`, `alamat`, `merk`, `nama`, `tanggal_pinjam`, `tanggal_kembali`, `tanggal_aktual_kembali`, `status_peminjaman`, `created_at`) VALUES
(3, 'M. Ghazi Alfarizi', '08123930492039', 'Jl Muteran Residence', 'Suzuki', 'Baleno', '2025-06-25', '2025-06-30', '2025-06-27', 'kembali', '2025-06-26 22:20:52'),
(4, 'alfa', '08132567890', 'Jalan Sesama', 'Mitsubishi', 'Pajero', '2025-07-01', '2025-07-03', NULL, 'batal', '2025-06-26 23:12:59'),
(5, 'Bp Sumarmo', '0290239402394', 'Jalan Jauh', 'Mitsubishi', 'Pajero', '2025-06-28', '2025-06-30', '2025-07-02', 'kembali', '2025-06-27 15:01:56'),
(6, 'Fafa', '09809809707', 'Jl. Maju mundur', 'Honda', 'Brio', '2025-06-24', '2025-06-30', '2025-07-01', 'kembali', '2025-06-27 15:33:43'),
(7, 'Bp. Muhajir', '09829484932', 'Jl. Kantil Sari', 'Suzuki', 'Baleno', '2025-06-17', '2025-06-21', '2025-07-03', 'kembali', '2025-06-27 15:39:37'),
(8, 'Ayu Riana', '081555666777', 'Jalan Pemuda 148', 'Mitsubishi', 'Pajero', '2025-07-07', '2025-07-09', NULL, 'dipinjam', '2025-07-03 03:23:21'),
(9, 'Syifa Hadju', '085900700123', 'Jalan Melati Wangi No 45 Semarang', 'Honda', 'Brio', '2025-07-01', '2025-07-02', '2025-07-02', 'kembali', '2025-07-03 03:26:49'),
(10, 'Rizky Nazar', '089700654123', 'Jalan Wonosari No 12 Semarang', 'Suzuki', 'Baleno', '2025-07-12', '2025-07-13', NULL, 'dipinjam', '2025-07-03 03:32:14'),
(11, 'Sri Utami', '081555456789', 'Jalan Karangayu no  80 Semarang', 'Mitsubishi', 'Strada', '2025-07-02', '2025-07-03', NULL, 'dipinjam', '2025-07-03 03:33:22'),
(12, 'Alif', '08966332255', 'Jalan Mawar No 10 Semarang', 'Toyota', 'Raize', '2025-07-03', '2025-07-03', '2025-07-03', 'kembali', '2025-07-03 08:24:10');

-- --------------------------------------------------------

--
-- Table structure for table `merks`
--

CREATE TABLE `merks` (
  `id` int(11) NOT NULL,
  `merk` varchar(100) NOT NULL,
  `nama` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `merks`
--

INSERT INTO `merks` (`id`, `merk`, `nama`) VALUES
(1, 'Toyota', 'Avanza'),
(2, 'Toyota', 'Kijang Inova'),
(3, 'Toyota', 'Raize'),
(4, 'Honda', 'Mobilio'),
(5, 'Honda', 'Brio'),
(6, 'Honda', 'HRV'),
(7, 'Suzuki', 'Ertiga'),
(8, 'Suzuki', 'Baleno'),
(9, 'Mitsubishi', 'Pajero'),
(10, 'Mitsubishi', 'Strada');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `merk` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `merk`, `name`, `price`, `description`, `created_at`) VALUES
(5, 'Honda', 'Brio', 300000.00, 'Warna Hitam', '2025-06-20 15:00:23'),
(6, 'Honda', 'Brio', 300000.00, 'Warna Putih', '2025-06-20 15:00:41'),
(7, 'Honda', 'Brio', 300000.00, 'Warna Merah', '2025-06-20 15:00:56'),
(13, 'Mitsubishi', 'Pajero', 600000.00, 'Warna Hitam', '2025-06-20 15:04:38'),
(15, 'Mitsubishi', 'Strada', 600000.00, 'Warna Merah', '2025-06-20 15:07:01'),
(16, 'Mitsubishi', 'Pajero', 600000.00, 'Warna Putih', '2025-06-20 15:07:21'),
(17, 'Suzuki', 'Baleno', 300000.00, 'Warna Biru', '2025-06-20 15:52:59'),
(18, 'Toyota', 'Avanza', 350000.00, 'Warna Hitam', '2025-07-03 06:30:06'),
(19, 'Toyota', 'Avanza', 350000.00, 'Warna Silver', '2025-07-03 06:30:25'),
(20, 'Toyota', 'Kijang Inova', 600000.00, 'Warna Hitam', '2025-07-03 06:30:38'),
(21, 'Toyota', 'Kijang Inova', 600000.00, 'Warna Putih', '2025-07-03 06:31:04'),
(22, 'Toyota', 'Raize', 300000.00, 'Warna merah', '2025-07-03 08:23:25');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `nama_peminjam` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `alamat` varchar(255) NOT NULL,
  `merk` varchar(100) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `nama_peminjam`, `no_hp`, `alamat`, `merk`, `nama`, `tanggal_pinjam`, `tanggal_kembali`, `created_at`) VALUES
(10, 'Ayu Riana', '081555666777', 'Jalan Pemuda 148', 'Mitsubishi', 'Pajero', '2025-07-07', '2025-07-09', '2025-07-03 03:23:21'),
(12, 'Rizky Nazar', '089700654123', 'Jalan Wonosari No 12 Semarang', 'Suzuki', 'Baleno', '2025-07-12', '2025-07-13', '2025-07-03 03:32:14'),
(13, 'Sri Utami', '081555456789', 'Jalan Karangayu no  80 Semarang', 'Mitsubishi', 'Strada', '2025-07-02', '2025-07-03', '2025-07-03 03:33:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `level` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`, `level`) VALUES
(1, 'faishal', '$2y$10$9OPOSq/T1Tj2v1hWJ6hhU.dUxoT6FOAWxMDCNiM/pO8DCxPgCG9oe', '2025-06-20 14:15:28', 'admin'),
(2, 'verina', '$2y$10$KKoJcF5PocGm7WvpEzXuuOHfUjuA/qe6rwN7gfA/bEm2FWlDPjmRS', '2025-06-20 14:21:45', 'admin'),
(3, 'yunita', '$2y$10$7dv37YUteDo0AtlKCU19d.j5ML3wtllM5SOM.WX7LtTfj9Lm9/yki', '2025-07-03 02:05:35', 'admin'),
(4, 'indri', '$2y$10$ycQ2EYFchrzhX6HceadhnOjFEGNKemP.lk5YDmWvClSljxo8e6Wzy', '2025-07-03 02:21:44', ''),
(5, 'agung', '$2y$10$EKrHN3Wh59Gjp4jGMSwM/uLroLqnM0ybFbd54HZInpYs2xQu3Yk5G', '2025-07-03 02:22:57', 'staf'),
(6, 'likha', '$2y$10$aW3lo9xWUlDVAchtlHLpYOL1ovHv68b0e1eMr7sjR0K05fbW3FsyG', '2025-07-03 03:04:05', 'staf'),
(7, 'lia', '$2y$10$TzCdDRMaPlTZQSpO1ZTCi.l9Wn3PTooP/o6HrugakSngnoL6T1lpe', '2025-07-03 08:24:50', 'staf');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `history_transactions`
--
ALTER TABLE `history_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `merks`
--
ALTER TABLE `merks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `history_transactions`
--
ALTER TABLE `history_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `merks`
--
ALTER TABLE `merks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
