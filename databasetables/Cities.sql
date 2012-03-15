-- phpMyAdmin SQL Dump
-- version 3.3.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 15, 2012 at 08:58 PM
-- Server version: 5.1.54
-- PHP Version: 5.3.5-1ubuntu7.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `WorkQueue`
--

-- --------------------------------------------------------

--
-- Table structure for table `Cities`
--

CREATE TABLE IF NOT EXISTS `Cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `latitude` float NOT NULL,
  `longitude` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=36 ;

--
-- Dumping data for table `Cities`
--

INSERT INTO `Cities` (`id`, `name`, `latitude`, `longitude`) VALUES
(1, 'Unknown', 0, 0),
(2, 'National', 0, 0),
(3, 'Seattle', 47.3928, -122.607),
(4, 'Portland', 45.5235, -122.676),
(5, 'San Francisco', 37.775, -122.418),
(6, 'San Jose', 37.3394, -121.894),
(7, 'San Diego', 32.7153, -117.156),
(8, 'Silicon Valley', 37.4378, -122.178),
(9, 'Los Angeles', 34.0522, -118.243),
(10, 'Tacoma', 47.2531, -122.443),
(11, 'New York', 40.7146, -74.0066),
(12, 'Chicago', 41.79, -87.75),
(13, 'Boston', 42.38, -71.03),
(14, 'Atlanta', 33.78, -84.52),
(15, 'Orlando', 28.55, -81.33),
(16, 'Houston', 29.65, -95.28),
(17, 'Washington D.C.', 38.86, -77.03),
(18, 'Miami', 25.78, -80.32),
(19, 'Dallas', 32.86, -96.85),
(20, 'Denver', 39.7263, -104.965),
(21, 'Las Vegas', 36.0789, -115.155),
(22, 'Austin', 30.32, -97.77),
(23, 'Philadelphia', 39.87, -75.23),
(24, 'Cleveland', 41.52, -81.68),
(25, 'Minneapolis', 44.89, -93.22),
(26, 'Phoenix', 33.4342, -112.051),
(27, 'Orange County', 33.7315, -117.862),
(28, 'Baltimore', 39.28, -76.61),
(29, 'Kansas City', 39.12, -94.6),
(30, 'Detroit', 42.4, -83.02),
(31, 'St Louis', 38.76, -90.37),
(32, 'Pittsburgh', 40.36, -79.94),
(33, 'San Antonio', 29.54, -98.47),
(34, 'New Orleans', 29.98, -90.25),
(35, 'Honolulu', 21.35, -157.93);
