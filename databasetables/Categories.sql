-- phpMyAdmin SQL Dump
-- version 3.3.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 28, 2012 at 08:04 PM
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
-- Table structure for table `Categories`
--

CREATE TABLE IF NOT EXISTS `Categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `deal_id` int(10) unsigned NOT NULL,
  `category_id` int(11) NOT NULL,
  `rank` int(11) NOT NULL DEFAULT '0',
  `classifier_id` int(11) NOT NULL DEFAULT '1',
  `time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`deal_id`,`category_id`),
  UNIQUE KEY `id` (`id`),
  KEY `deal_id` (`deal_id`),
  KEY `classifier_id` (`classifier_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
