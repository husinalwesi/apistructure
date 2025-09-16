-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 05, 2024 at 08:17 AM
-- Server version: 5.7.23-23
-- PHP Version: 8.1.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `yxlplomy_game`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `username` text COLLATE utf8_unicode_ci NOT NULL,
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `created_date` int(10) NOT NULL,
  `is_deleted` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `userID`, `username`, `comment`, `created_date`, `is_deleted`) VALUES
(7, 0, 'EnglishUser', 'This is a comment in English', 1730735485, 0),
(8, 0, 'ArabicUser', 'هذا تعليق باللغة العربية', 1730735485, 0),
(9, 0, 'HindiUser', 'यह हिंदी में एक टिप्पणी है', 1730735485, 0),
(10, 0, 'FarsiUser', 'این یک نظر به زبان فارسی است', 1730735485, 0),
(11, 0, 'FrenchUser', 'Ceci est un commentaire en français', 1730735485, 0),
(12, 0, 'PortugueseUser', 'Este é um comentário em português', 1730735485, 0),
(13, 0, 'ChineseUser', '这是中文评论', 1730735485, 0),
(14, 0, 'SpanishUser', 'Este es un comentario en español', 1730735485, 0),
(15, 0, 'RussianUser', 'Это комментарий на русском языке', 1730735485, 0),
(16, 0, 'HebrewUser', 'זהו תגובה בעברית', 1730735485, 0),
(25, 0, 'YOUR NAME', 'ابداع ابداع ابداع', 1730810061, 0),
(26, 48, '', 'comment here', 1730818139, 0),
(27, 48, '48', 'test khaled', 1730819654, 0);

-- --------------------------------------------------------

--
-- Stand-in structure for view `comments_view`
-- (See below for the actual view)
--
CREATE TABLE `comments_view` (
`id` int(11)
,`userID` int(11)
,`username` text
,`comment` text
,`created_date` int(10)
,`is_deleted` int(1)
,`linked_username` text
,`email` text
);

-- --------------------------------------------------------

--
-- Table structure for table `rank`
--

CREATE TABLE `rank` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `created_date` int(11) NOT NULL,
  `score` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `rank`
--

INSERT INTO `rank` (`id`, `userid`, `rank`, `created_date`, `score`) VALUES
(85, 47, 0, 1730815468, 3),
(96, 48, 0, 1730817212, 3);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` text COLLATE utf8_unicode_ci NOT NULL,
  `email` text COLLATE utf8_unicode_ci NOT NULL,
  `password` text COLLATE utf8_unicode_ci NOT NULL,
  `created_date` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `email`, `password`, `created_date`) VALUES
(46, 'rami', 'rami@gmail.com', '123456', 1730811685),
(47, 'hu', 'he', '1', 1730813612),
(48, 're', 'ru', '1', 1730814462);

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_top_scores`
-- (See below for the actual view)
--
CREATE TABLE `user_top_scores` (
`rank_id` int(11)
,`score` int(11)
,`created_date` int(11)
,`userid` int(11)
,`username` text
,`email` text
);

-- --------------------------------------------------------

--
-- Structure for view `comments_view`
--
DROP TABLE IF EXISTS `comments_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`cpses_yxwrphtki5`@`localhost` SQL SECURITY DEFINER VIEW `comments_view`  AS SELECT `c`.`id` AS `id`, `c`.`userID` AS `userID`, `c`.`username` AS `username`, `c`.`comment` AS `comment`, `c`.`created_date` AS `created_date`, `c`.`is_deleted` AS `is_deleted`, `u`.`username` AS `linked_username`, `u`.`email` AS `email` FROM (`comments` `c` left join `user` `u` on((`u`.`id` = `c`.`userID`))) ORDER BY `c`.`created_date` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `user_top_scores`
--
DROP TABLE IF EXISTS `user_top_scores`;

CREATE ALGORITHM=UNDEFINED DEFINER=`cpses_yxwrphtki5`@`localhost` SQL SECURITY DEFINER VIEW `user_top_scores`  AS SELECT `r`.`id` AS `rank_id`, max(`r`.`score`) AS `score`, `r`.`created_date` AS `created_date`, `r`.`userid` AS `userid`, `u`.`username` AS `username`, `u`.`email` AS `email` FROM (`rank` `r` join `user` `u` on((`u`.`id` = `r`.`userid`))) GROUP BY `r`.`userid` ORDER BY `score` DESC, `r`.`created_date` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rank`
--
ALTER TABLE `rank`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `rank`
--
ALTER TABLE `rank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
