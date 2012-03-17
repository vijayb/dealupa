-- phpMyAdmin SQL Dump
-- version 3.3.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 06, 2012 at 09:47 PM
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
-- Table structure for table `CategoryInfo`
--

CREATE TABLE IF NOT EXISTS `CategoryInfo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

--
-- Dumping data for table `CategoryInfo`
--

INSERT INTO `CategoryInfo` (`name`, `description`) VALUES
('Casual Dining', NULL),
('Quick Bites', NULL),
('Sweet Tooth', NULL),
('Foodie', NULL),
('Groceries and Spices', NULL),
('Good for Kids', NULL),
('Moms and Babies', NULL),
('Always Learning', NULL),
('Fashionista', NULL),
('Well Groomed', NULL),
('Forever Beautiful', NULL),
('Pampered', NULL),
('Body Art', NULL),
('Trim and Terrific', NULL),
('Healthy Living', NULL),
('Alternative Healing', NULL),
('Seeing Clearly', NULL),
('Medical and Dental', NULL),
('Nightlife', NULL),
('Active Fun', NULL),
('Shake Your Booty', NULL),
('Will Call', NULL),
('Cultural Pursuits', NULL),
('Great Outdoors', NULL),
('Sporting Life', NULL),
('Adrenaline', NULL),
('Romantic at Heart', NULL),
('Once in a Lifetime', NULL),
('The Finer Things', NULL),
('Automotive', NULL),
('Home and garden', NULL),
('Handy man', NULL),
('Professional Services', NULL),
('Gadgets and Gear', NULL),
('Bookish', NULL),
('Photographic', NULL),
('Crafty', NULL),
('Pet Lover', NULL),
('Gift Ideas', NULL),
('Giving Back', NULL),
('Around the World', NULL),
('Road Trip', NULL),
('Just for Girls', NULL),
('Just for Guys', NULL);
