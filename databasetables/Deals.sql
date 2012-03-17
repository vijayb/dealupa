-- phpMyAdmin SQL Dump
-- version 3.3.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 28, 2012 at 08:02 PM
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
-- Table structure for table `Deals`
--

CREATE TABLE IF NOT EXISTS `Deals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(2000) NOT NULL,
  `url_hash` binary(20) NOT NULL,
  `affiliate_url` varchar(2000) DEFAULT NULL,
  `discovered` datetime NOT NULL,
  `last_updated` datetime NOT NULL,
  `num_updates` int(11) NOT NULL DEFAULT '1',
  `dup` tinyint(1) NOT NULL DEFAULT '0',
  `dup_id` int(10) unsigned DEFAULT NULL,
  `company_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `price` float DEFAULT NULL,
  `value` float DEFAULT NULL,
  `num_purchased` int(11) DEFAULT NULL,
  `fb_likes` int(11) DEFAULT NULL,
  `fb_shares` int(11) DEFAULT NULL,
  `text` text,
  `fine_print` text,
  `expired` tinyint(1) NOT NULL DEFAULT '0',
  `upcoming` tinyint(1) NOT NULL DEFAULT '0',
  `deadline` datetime DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `yelp_rating` float DEFAULT NULL,
  `yelp_url` varchar(255) DEFAULT NULL,
  `yelp_categories` varchar(255) DEFAULT NULL,
  `yelp_review_count` int(11) DEFAULT NULL,
  `yelp_excerpt1` text,
  `yelp_review_url1` varchar(255) DEFAULT NULL,
  `yelp_user1` varchar(50) DEFAULT NULL,
  `yelp_rating1` float DEFAULT NULL,
  `yelp_user_url1` varchar(255) DEFAULT NULL,
  `yelp_user_image_url1` varchar(255) DEFAULT NULL,
  `yelp_excerpt2` text,
  `yelp_review_url2` varchar(255) DEFAULT NULL,
  `yelp_user2` varchar(50) DEFAULT NULL,
  `yelp_rating2` float DEFAULT NULL,
  `yelp_user_url2` varchar(255) DEFAULT NULL,
  `yelp_user_image_url2` varchar(255) DEFAULT NULL,
  `yelp_excerpt3` text,
  `yelp_review_url3` varchar(255) DEFAULT NULL,
  `yelp_user3` varchar(50) DEFAULT NULL,
  `yelp_rating3` float DEFAULT NULL,
  `yelp_user_url3` varchar(255) DEFAULT NULL,
  `yelp_user_image_url3` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`url_hash`),
  UNIQUE KEY `id` (`id`),
  KEY `last_updated` (`last_updated`),
  KEY `deadline` (`deadline`),
  KEY `expired` (`expired`),
  KEY `upcoming` (`upcoming`),
  KEY `discovered` (`discovered`),
  KEY `dup` (`dup`),
  KEY `dup_id` (`dup_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Triggers `Deals`
--
DROP TRIGGER IF EXISTS `dealstrigger`;
DELIMITER //
CREATE TRIGGER `dealstrigger` BEFORE INSERT ON `Deals`
 FOR EACH ROW SET
    NEW.url_hash = UNHEX(SHA1(NEW.url))
//
DELIMITER ;
DROP TRIGGER IF EXISTS `dealstrigger2`;
DELIMITER //
CREATE TRIGGER `dealstrigger2` BEFORE UPDATE ON `Deals`
 FOR EACH ROW SET
    NEW.num_updates=NEW.num_updates+1
//
DELIMITER ;
