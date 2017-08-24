-- phpMyAdmin SQL Dump
-- version 4.7.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 24, 2017 at 08:26 AM
-- Server version: 10.1.23-MariaDB-9+deb9u1
-- PHP Version: 7.0.22-1~dotdeb+8.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `codymaze`
--

-- --------------------------------------------------------

--
-- Table structure for table `moves`
--

CREATE TABLE `moves` (
  `telegram_id` int(11) NOT NULL,
  `reached_on` datetime DEFAULT NULL,
  `cell` char(3) CHARACTER SET ascii NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user_status`
--

CREATE TABLE `user_status` (
  `telegram_id` int(11) NOT NULL,
  `completed` bit(1) NOT NULL DEFAULT b'0',
  `completed_on` datetime DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `certificate_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `moves`
--
ALTER TABLE `moves`
  ADD KEY `reached_on` (`reached_on`);

--
-- Indexes for table `user_status`
--
ALTER TABLE `user_status`
  ADD PRIMARY KEY (`telegram_id`);
COMMIT;
