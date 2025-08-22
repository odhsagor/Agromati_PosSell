-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 14, 2025 at 07:33 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4



SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";



/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;



--
-- Database: `agromatiDB`
--



----------------------------------------------------------



--
-- Table structure for table `carts`
--



CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `retailer_id` int(11) NOT NULL,
  `status` enum('active','ordered') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `retailer_id`, `status`, `created_at`) VALUES
(1, 1, 'ordered', '2025-08-13 11:12:03'),
(2, 1, 'ordered', '2025-08-13 13:14:36'),
(3, 2, 'ordered', '2025-08-13 18:13:15'),
(4, 2, 'ordered', '2025-08-14 15:23:36');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `quantity`, `unit_price`, `created_at`) VALUES
(1, 1, 1, 170, 55.00, '2025-08-13 11:12:03'),
(2, 2, 1, 50, 55.00, '2025-08-13 13:14:36'),
(3, 3, 1, 100, 55.00, '2025-08-13 18:13:15'),
(4, 3, 2, 100, 80.00, '2025-08-13 18:13:29'),
(5, 4, 3, 5, 60.00, '2025-08-14 15:23:36');

-- --------------------------------------------------------

--
-- Table structure for table `harvests`
--

CREATE TABLE `harvests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `harvest_date` date NOT NULL,
  `quantity` varchar(50) NOT NULL,
  `harvest_type` varchar(50) NOT NULL,
  `production_cost` varchar(50) NOT NULL,
  `land_acreage` varchar(50) NOT NULL,
  `seed_requirement` varchar(50) NOT NULL,
  `harvest_time` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `harvests`
--

INSERT INTO `harvests` (`id`, `user_id`, `harvest_date`, `quantity`, `harvest_type`, `production_cost`, `land_acreage`, `seed_requirement`, `harvest_time`, `created_at`) VALUES
(2, 1, '2025-08-13', '1000', 'organic', '100', '5', '10', 'Morning', '2025-08-12 12:11:09'),
(3, 2, '2025-08-08', '100', '1000', '6000', '1900', '100000', 'Morning', '2025-08-13 18:09:51');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `retailer_id` int(11) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','confirmed','shipped','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `retailer_id`, `total_amount`, `status`, `created_at`) VALUES
(1, 1, 9350.00, 'shipped', '2025-08-13 11:15:22'),
(2, 1, 2750.00, 'shipped', '2025-08-13 13:50:49'),
(3, 2, 13500.00, 'shipped', '2025-08-13 18:13:34'),
(4, 2, 300.00, 'shipped', '2025-08-14 15:23:46');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','shipped','cancelled') NOT NULL DEFAULT 'pending',
  `stock_added` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `farmer_id`, `quantity`, `unit_price`, `created_at`, `status`, `stock_added`) VALUES
(1, 1, 1, 1, 170, 55.00, '2025-08-13 11:15:22', 'shipped', 1),
(2, 2, 1, 1, 50, 55.00, '2025-08-13 13:50:49', 'shipped', 0),
(3, 3, 1, 1, 100, 55.00, '2025-08-13 18:13:34', 'shipped', 1),
(4, 3, 2, 2, 100, 80.00, '2025-08-13 18:13:34', 'shipped', 1),
(5, 4, 3, 2, 5, 60.00, '2025-08-14 15:23:46', 'shipped', 0);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `unit_of_measure` varchar(20) NOT NULL,
  `seasonality` varchar(50) DEFAULT NULL,
  `nutrition` text DEFAULT NULL,
  `per_unit_price` decimal(10,2) NOT NULL,
  `total_units` int(11) NOT NULL,
  `total_price` decimal(10,2) GENERATED ALWAYS AS (`per_unit_price` * `total_units`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `user_id`, `name`, `type`, `unit_of_measure`, `seasonality`, `nutrition`, `per_unit_price`, `total_units`, `created_at`) VALUES
(1, 1, 'Rice', 'cereal', '100', 'Winter', 'Vitamin A', 55.00, 1231, '2025-08-13 03:28:51'),
(2, 2, 'Rice', 'ceral', '130', 'Summer', 'Vitamin A', 80.00, 1100, '2025-08-13 18:10:58'),
(3, 2, 'Banana', 'fruit', '100', 'ALL', 'Vitamin A', 60.00, 95, '2025-08-14 15:22:36');

-- --------------------------------------------------------

--
-- Table structure for table `retailers`
--

CREATE TABLE `retailers` (
  `id` int(11) NOT NULL,
  `retailer_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `retailers`
--

INSERT INTO `retailers` (`id`, `retailer_id`, `name`, `email`, `phone`, `password`, `created_at`) VALUES
(1, 'RET001', 'daad', 'daad@gmail.com', '0132193713', '$2y$10$8NLjSpb59.kA9yNe/xdrDe8FYfLI8MM7vb112Ii01t9kxY/CEJ8aq', '2025-08-12 18:49:49'),
(2, 'RET002', 'Romtahena', 'romtahena@gmail.com', '01821009211', '$2y$10$1s6DCDwYEwJMbH1x9/0FqusOZV//rF/YX5eQqHCGrZi5KS/IB.yzK', '2025-08-13 18:11:58');

-- --------------------------------------------------------

--
-- Table structure for table `retailer_inventory`
--

CREATE TABLE `retailer_inventory` (
  `id` int(11) NOT NULL,
  `retailer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty_available` int(11) NOT NULL DEFAULT 0,
  `default_retail_price` decimal(10,2) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `retailer_inventory`
--

INSERT INTO `retailer_inventory` (`id`, `retailer_id`, `product_id`, `qty_available`, `default_retail_price`, `updated_at`) VALUES
(1, 1, 1, 165, 55.00, '2025-08-13 15:07:04'),
(2, 2, 1, 66, 55.00, '2025-08-14 15:25:19'),
(3, 2, 2, 88, 80.00, '2025-08-14 15:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `retailer_sales`
--

CREATE TABLE `retailer_sales` (
  `id` int(11) NOT NULL,
  `retailer_id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(30) DEFAULT NULL,
  `total_items` int(11) NOT NULL DEFAULT 0,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `retailer_sales`
--

INSERT INTO `retailer_sales` (`id`, `retailer_id`, `customer_name`, `customer_phone`, `total_items`, `total_amount`, `created_at`) VALUES
(1, 1, '', '', 3, 165.00, '2025-08-13 14:54:41'),
(2, 1, '', '', 1, 55.00, '2025-08-13 15:06:18'),
(3, 1, '', '', 1, 55.00, '2025-08-13 15:07:04'),
(4, 2, '', '', 5, 350.00, '2025-08-13 18:18:39'),
(5, 2, '', '', 10, 675.00, '2025-08-13 18:19:10'),
(6, 2, '', '', 20, 1100.00, '2025-08-13 19:33:16'),
(7, 2, 'jj', '7778', 8, 515.00, '2025-08-14 15:24:52'),
(8, 2, '', '', 3, 190.00, '2025-08-14 15:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `retailer_sale_items`
--

CREATE TABLE `retailer_sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `retailer_sale_items`
--

INSERT INTO `retailer_sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 3, 55.00),
(2, 2, 1, 1, 55.00),
(3, 3, 1, 1, 55.00),
(4, 4, 1, 2, 55.00),
(5, 4, 2, 3, 80.00),
(6, 5, 1, 5, 55.00),
(7, 5, 2, 5, 80.00),
(8, 6, 1, 20, 55.00),
(9, 7, 1, 5, 55.00),
(10, 7, 2, 3, 80.00),
(11, 8, 1, 2, 55.00),
(12, 8, 2, 1, 80.00);

-- --------------------------------------------------------

--
-- Table structure for table `retailer_stock_moves`
--

CREATE TABLE `retailer_stock_moves` (
  `id` int(11) NOT NULL,
  `retailer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `direction` enum('in','out') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `source_type` enum('order_item','sale') NOT NULL,
  `source_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `retailer_stock_moves`
--

INSERT INTO `retailer_stock_moves` (`id`, `retailer_id`, `product_id`, `direction`, `quantity`, `unit_price`, `source_type`, `source_id`, `created_at`) VALUES
(1, 1, 1, 'in', 170, 55.00, 'order_item', 1, '2025-08-13 14:54:11'),
(2, 1, 1, 'out', 3, 55.00, 'sale', 1, '2025-08-13 14:54:41'),
(3, 1, 1, 'out', 1, 55.00, 'sale', 2, '2025-08-13 15:06:18'),
(4, 1, 1, 'out', 1, 55.00, 'sale', 3, '2025-08-13 15:07:04'),
(5, 2, 1, 'in', 100, 55.00, 'order_item', 3, '2025-08-13 18:17:52'),
(6, 2, 2, 'in', 100, 80.00, 'order_item', 4, '2025-08-13 18:17:52'),
(7, 2, 1, 'out', 2, 55.00, 'sale', 4, '2025-08-13 18:18:39'),
(8, 2, 2, 'out', 3, 80.00, 'sale', 4, '2025-08-13 18:18:39'),
(9, 2, 1, 'out', 5, 55.00, 'sale', 5, '2025-08-13 18:19:10'),
(10, 2, 2, 'out', 5, 80.00, 'sale', 5, '2025-08-13 18:19:10'),
(11, 2, 1, 'out', 20, 55.00, 'sale', 6, '2025-08-13 19:33:16'),
(12, 2, 1, 'out', 5, 55.00, 'sale', 7, '2025-08-14 15:24:52'),
(13, 2, 2, 'out', 3, 80.00, 'sale', 7, '2025-08-14 15:24:52'),
(14, 2, 1, 'out', 2, 55.00, 'sale', 8, '2025-08-14 15:25:19'),
(15, 2, 2, 'out', 1, 80.00, 'sale', 8, '2025-08-14 15:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `created_at`) VALUES
(1, 'Nazrul Islam', 'nazrul@gmail.com', '01228310880', '$2y$10$Rq9CAP8YU5/OAmYr1lYmU.6e6ExY/TXq0GDCzLrPa0GQz8zF66oyq', '2025-08-12 09:08:08'),
(2, 'Zahid Kabir', 'zahidkabir@gmail.com', '014014808311', '$2y$10$V3IxOzXp20d.8sb6VLPLEOLCVF8YHlis3JxFDdoP17BgGOUsabmAS', '2025-08-13 18:09:12');

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL,
  `contact_info` varchar(100) DEFAULT NULL,
  `warehouse_type` varchar(50) DEFAULT NULL,
  `last_inspection_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `user_id`, `name`, `location`, `capacity`, `contact_info`, `warehouse_type`, `last_inspection_date`, `created_at`) VALUES
(1, 1, 'MY HOME', 'IUB', 1500, '0199288881', 'Dry Storage', '2025-08-13', '2025-08-12 13:16:26'),
(2, 1, 'mo', 'dhaka', 1700, '9317391313', 'Refrigerated', '2025-08-13', '2025-08-12 13:33:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `retailer_id` (`retailer_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `harvests`
--
ALTER TABLE `harvests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `retailer_id` (`retailer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `farmer_id` (`farmer_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `retailers`
--
ALTER TABLE `retailers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `retailer_id` (`retailer_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `retailer_id_2` (`retailer_id`),
  ADD KEY `email_2` (`email`);

--
-- Indexes for table `retailer_inventory`
--
ALTER TABLE `retailer_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_retailer_product` (`retailer_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `retailer_sales`
--
ALTER TABLE `retailer_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `retailer_id` (`retailer_id`);

--
-- Indexes for table `retailer_sale_items`
--
ALTER TABLE `retailer_sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `retailer_stock_moves`
--
ALTER TABLE `retailer_stock_moves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `retailer_id` (`retailer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `harvests`
--
ALTER TABLE `harvests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `retailers`
--
ALTER TABLE `retailers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `retailer_inventory`
--
ALTER TABLE `retailer_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `retailer_sales`
--
ALTER TABLE `retailer_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `retailer_sale_items`
--
ALTER TABLE `retailer_sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `retailer_stock_moves`
--
ALTER TABLE `retailer_stock_moves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`retailer_id`) REFERENCES `retailers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `harvests`
--
ALTER TABLE `harvests`
  ADD CONSTRAINT `harvests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`retailer_id`) REFERENCES `retailers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `retailer_inventory`
--
ALTER TABLE `retailer_inventory`
  ADD CONSTRAINT `retailer_inventory_ibfk_1` FOREIGN KEY (`retailer_id`) REFERENCES `retailers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `retailer_inventory_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `retailer_sales`
--
ALTER TABLE `retailer_sales`
  ADD CONSTRAINT `retailer_sales_ibfk_1` FOREIGN KEY (`retailer_id`) REFERENCES `retailers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `retailer_sale_items`
--
ALTER TABLE `retailer_sale_items`
  ADD CONSTRAINT `retailer_sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `retailer_sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `retailer_sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `retailer_stock_moves`
--
ALTER TABLE `retailer_stock_moves`
  ADD CONSTRAINT `retailer_stock_moves_ibfk_1` FOREIGN KEY (`retailer_id`) REFERENCES `retailers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `retailer_stock_moves_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD CONSTRAINT `warehouses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
