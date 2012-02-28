-- phpMyAdmin SQL Dump
-- version 3.3.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 28, 2012 at 08:09 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `Companies`
--

CREATE TABLE IF NOT EXISTS `Companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `url` varchar(40) NOT NULL,
  `address` varchar(200) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `hub_crawl_frequency` int(11) NOT NULL DEFAULT '0',
  `page_crawl_frequency` int(11) NOT NULL DEFAULT '0',
  `use_cookie` tinyint(1) NOT NULL DEFAULT '0',
  `use_phantom` tinyint(1) NOT NULL DEFAULT '0',
  `use_password` tinyint(1) NOT NULL DEFAULT '0',
  `crawl_ajax` tinyint(1) NOT NULL DEFAULT '0',
  `output_server` varchar(100) NOT NULL,
  `output_database` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=40 ;

-- --------------------------------------------------------

--
-- Table structure for table `HubCities`
--

CREATE TABLE IF NOT EXISTS `HubCities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hub_url` varchar(255) NOT NULL,
  `city_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1059 ;

-- --------------------------------------------------------

--
-- Table structure for table `Hubs`
--

CREATE TABLE IF NOT EXISTS `Hubs` (
  `url` varchar(255) NOT NULL,
  `company_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `post_form` text,
  PRIMARY KEY (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `Workers`
--

CREATE TABLE IF NOT EXISTS `Workers` (
  `ip` varchar(30) NOT NULL,
  `pid` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `spawned` datetime NOT NULL,
  `heartbeat` datetime NOT NULL,
  `status` int(11) NOT NULL,
  `latest_work_id` int(11) DEFAULT NULL,
  `force_shutdown` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ip`,`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `WorkQueue`
--

CREATE TABLE IF NOT EXISTS `WorkQueue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work` varchar(2000) NOT NULL,
  `work_hash` varchar(50) NOT NULL,
  `type` int(11) NOT NULL,
  `company_id` int(11) NOT NULL DEFAULT '-1',
  `frequency` int(11) NOT NULL DEFAULT '0',
  `output_server` varchar(100) NOT NULL,
  `output_database` varchar(100) NOT NULL,
  `worker_ip` varchar(30) DEFAULT NULL,
  `worker_pid` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `started` datetime DEFAULT NULL,
  `completed` datetime DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `status_message` text,
  PRIMARY KEY (`work_hash`,`type`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=42754557 ;

--
-- Triggers `WorkQueue`
--
DROP TRIGGER IF EXISTS `workqueuetrigger`;
DELIMITER //
CREATE TRIGGER `workqueuetrigger` BEFORE INSERT ON `WorkQueue`
 FOR EACH ROW SET
    NEW.work_hash = SHA1(NEW.work)
//
DELIMITER ;
