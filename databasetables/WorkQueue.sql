-- phpMyAdmin SQL Dump
-- version 3.3.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 17, 2012 at 09:45 AM
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
-- Table structure for table `WorkQueue`
--

CREATE TABLE IF NOT EXISTS `WorkQueue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `work` varchar(2000) NOT NULL,
  `work_hash` binary(20) NOT NULL,
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
  KEY(`type`),
  KEY(`company_id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Triggers `WorkQueue`
--
DROP TRIGGER IF EXISTS `workqueuetrigger`;
DELIMITER //
CREATE TRIGGER `workqueuetrigger` BEFORE INSERT ON `WorkQueue`
 FOR EACH ROW SET
    NEW.work_hash = UNHEX(SHA1(NEW.work))
//
DELIMITER ;
