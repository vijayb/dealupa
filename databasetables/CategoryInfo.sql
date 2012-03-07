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
('Food and Drink', NULL),
('Activities and Events', NULL),
('Spa and Beauty', NULL),
('Kids and Parents', NULL),
('Shopping and Services', NULL),
('Classes and Learning', NULL),
('Fitness and Health', NULL),
('Dental and Medical', NULL),
('Vacations and Hotels', NULL),
('Casual Dining', NULL),
('Quick Bites', NULL),
('Foodie', NULL),
('Groceries and Spices', NULL),
('Sweet Tooth', NULL),
('Good for Kids', NULL),
('Moms and Babies', NULL),
('Fashionista', NULL),
('Gadgets and Gear', NULL),
('Automotive', NULL),
('Bookish', NULL),
('Well Groomed', NULL),
('Forever Beautiful', NULL),
('Pampered', NULL),
('The Finer Things', NULL),
('Medical and Dental', NULL),
('Seeing Clearly', NULL),
('Alternative Healing', NULL),
('Healthy Living', NULL),
('Trim and Terrific', NULL),
('Body Art', NULL),
('Professional Services', NULL),
('Handy man', NULL),
('Home and garden', NULL),
('Crafty', NULL),
('Pet Lover', NULL),
('Photographic', NULL),
('Nightlife', NULL),
('Romantic at Heart', NULL),
('Adrenaline', NULL),
('Once in a Lifetime', NULL),
('Will Call', NULL),
('Cultural Pursuits', NULL),
('Great Outdoors', NULL),
('Active Fun', NULL),
('Sporting Life', NULL),
('Shake Your Booty', NULL),
('Always Learning', NULL),
('Gift Ideas', NULL),
('Giving Back', NULL),
('Around the World', NULL),
('Road Trip', NULL),
('Just for Girls', NULL),
('Just for Guys', NULL);
