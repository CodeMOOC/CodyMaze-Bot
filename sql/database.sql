-- phpMyAdmin SQL Dump
-- version 4.7.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 18, 2017 at 09:50 AM
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
-- Table structure for table `certificates_list`
--

CREATE TABLE `certificates_list` (
  `certificate_id` char(36) NOT NULL,
  `telegram_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `log_id` int(10) UNSIGNED NOT NULL,
  `severity` tinyint(3) NOT NULL,
  `tag` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` datetime NOT NULL,
  `telegram_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `moves`
--

CREATE TABLE `moves` (
  `telegram_id` int(11) NOT NULL,
  `reached_on` datetime DEFAULT NULL,
  `cell` char(3) CHARACTER SET ascii NOT NULL,
  `last_callback_id` int(16) NOT NULL DEFAULT '0'
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
  `certificate_id` char(36) DEFAULT NULL,
  `certificate_sent` bit(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `certificates_list`
--
ALTER TABLE `certificates_list`
  ADD PRIMARY KEY (`certificate_id`);

--
-- Indexes for table `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `telegram_id` (`telegram_id`);

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

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `log`
--
ALTER TABLE `log`
  MODIFY `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;COMMIT;
