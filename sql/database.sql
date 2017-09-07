-- phpMyAdmin SQL Dump
-- version 4.7.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Set 07, 2017 alle 08:59
-- Versione del server: 10.1.23-MariaDB-9+deb9u1
-- Versione PHP: 7.0.22-1~dotdeb+8.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `codymaze`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `certificates_list`
--

CREATE TABLE `certificates_list` (
  `certificate_id` char(36) NOT NULL,
  `telegram_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struttura della tabella `moves`
--

CREATE TABLE `moves` (
  `telegram_id` int(11) NOT NULL,
  `reached_on` datetime DEFAULT NULL,
  `cell` char(3) CHARACTER SET ascii NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struttura della tabella `user_status`
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
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `certificates_list`
--
ALTER TABLE `certificates_list`
  ADD PRIMARY KEY (`certificate_id`);

--
-- Indici per le tabelle `moves`
--
ALTER TABLE `moves`
  ADD KEY `reached_on` (`reached_on`);

--
-- Indici per le tabelle `user_status`
--
ALTER TABLE `user_status`
  ADD PRIMARY KEY (`telegram_id`);
COMMIT;
