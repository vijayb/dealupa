-- phpMyAdmin SQL Dump
-- version 3.3.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 28, 2012 at 08:06 PM
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
-- Table structure for table `YelpInfo`
--

CREATE TABLE IF NOT EXISTS `YelpInfo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` int(10) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `url` varchar(2000) NOT NULL,
  `last_updated` datetime NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `raw_address` varchar(1000) DEFAULT NULL,
  `street` varchar(100) DEFAULT NULL,
  `street2` varchar(100) DEFAULT NULL,
  `suburb` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zipcode` varchar(20) DEFAULT NULL,
  `country` varchar(30) DEFAULT NULL,
  `latitude` float DEFAULT NULL,
  `longitude` float DEFAULT NULL,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `merchant_id` (`merchant_id`),
  KEY `last_updated` (`last_updated`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=41235 ;
