-- phpMyAdmin SQL Dump
-- version 3.3.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 28, 2012 at 08:05 PM
-- Server version: 5.1.54
-- PHP Version: 5.3.5-1ubuntu7.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `Deals`
--

-- --------------------------------------------------------

--
-- Table structure for table `Images777`
--

CREATE TABLE IF NOT EXISTS `Images777` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `deal_id` int(10) unsigned NOT NULL,
  `image_url` varchar(2000) NOT NULL,
  `image_url_hash` binary(20) NOT NULL,
  `on_s3` boolean not null default false,
  PRIMARY KEY (`deal_id`,`image_url_hash`),
  UNIQUE KEY `id` (`id`),
  KEY `deal_id` (`deal_id`),
  KEY `image_url_hash` (`image_url_hash`),
  KEY `on_s3` (`on_s3`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=116589 ;

--
-- Triggers `Images777`
--
DROP TRIGGER IF EXISTS `imagestrigger`;
DELIMITER //
CREATE TRIGGER `imagestrigger` BEFORE INSERT ON `Images777`
 FOR EACH ROW SET
    NEW.image_url_hash = UNHEX(SHA1(NEW.image_url))
//
DELIMITER ;
