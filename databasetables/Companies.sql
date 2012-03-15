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
  `cj_feed` tinyint(1) NOT NULL DEFAULT '0',
  `output_server` varchar(100) NOT NULL,
  `output_database` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=44 ;

--
-- Dumping data for table `Companies`
--

INSERT INTO `Companies` (`id`, `name`, `url`, `address`, `phone`, `hub_crawl_frequency`, `page_crawl_frequency`, `use_cookie`, `use_phantom`, `use_password`, `crawl_ajax`, `cj_feed`, `output_server`, `output_database`) VALUES
(1, 'Groupon', 'http://www.groupon.com/', 'Groupon Inc.  600 W Chicago Ave.  Suite 620  Chicago, IL 60654', '1 (877) 788-7858', 1800, 3600, 0, 1, 0, 0, 0, '50.57.43.108', 'Deals'),
(2, 'Living Social', 'http://www.livingsocial.com', '1445 New York Ave NW Suite 200 Washington, DC, 20005', '888.808.6676', 1800, 3600, 1, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(3, 'BuyWithMe', 'http://www.buywithme.com/', '345 Hudson Street, 13th floor New York, NY 10014', NULL, 1800, 18000, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(4, 'Tippr', 'http://tippr.com/', '517 Aloha St. Seattle, WA 98109', '866-347-0752', 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(5, 'Travel Zoo', 'http://www.travelzoo.com/', '800 W El Camino Real, Suite 180 Mountain View, CA 94040', NULL, 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(6, 'Angie''s List', 'http://www.angieslist.com/', '1030 E. Washington Street Indianapolis, IN 46202', '1-888-888-5478', 1800, 18000, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(7, 'Gilt City', 'http://www.giltcity.com', '1 Madison Avenue, 5th Floor New York, NY 10010 USA', '(877) 280-0541', 1800, 18000, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(8, 'Yollar', 'http://yollar.com/', NULL, '1-800-965-5278', 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(9, 'Zozi', 'http://www.zozi.com', 'Jackson Square 540 Washington Street San Francisco, CA 94111', '888-969-4123', 1800, 18000, 1, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(10, 'Bloomspot', 'http://www.bloomspot.com/', '345 Ritch Street San Francisco, CA, 94107 USA', '650-691-5110', 1800, 18000, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(11, 'ScoutMob', 'http://www.scoutmob.com/', NULL, NULL, 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(12, 'Amazon Local', 'http://local.amazon.com/', 'P.O. Box 81226  Seattle, WA 98108-1226', '206-266-7180', 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(13, 'KGB Deals', 'http://www.kgbdeals.com/', NULL, NULL, 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(14, 'LifeBooker', 'http://lifebooker.com', NULL, '(800) 401-9258 ext.1', 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(15, 'DealOn', 'http://www.dealon.com/', '92 East Main St., Suite 405 Somerville, NJ 08876', NULL, 1800, 18000, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(16, 'EverSave', 'http://www.eversave.com/', NULL, '877.383.1154', 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(17, 'Living Social Escapes', 'http://www.livingsocial.com/escapes', '1445 New York Ave NW Suite 200 Washington, DC, 20005', '888.808.6676', 180, 3600, 1, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(18, 'Google Offers', 'https://www.google.com/offers/', '1600 Amphitheater parkway, Mountain View, CA 94043', NULL, 1800, 3600, 0, 0, 0, 1, 0, '50.57.43.108', 'Deals'),
(19, 'Get My Perks', 'http://www.getmyperks.com/', NULL, '1-877-898-4114', 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(20, 'Voice Daily Deals', 'http://www.voicedailydeals.com/', '1621 Milam Ste. 100, Houston, TX 77002', '713-280-2400', 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(21, 'Munch on Me', 'http://munchonme.com/', NULL, NULL, 1800, 3600, 1, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(22, 'Doodle Deals', 'http://doodledeals.com', '594 Broadway, Suite 201 New York, NY 10012', '1-800-963-4753 ', 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(23, 'Juice in the City', 'http://www.juiceinthecity.com/', '177 Bovet Road Suite 520 San Mateo, CA 94402', NULL, 1800, 18000, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(24, 'Schwaggle', 'http://schwaggle.active.com/', NULL, NULL, 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(25, 'Home Run', 'http://homerun.com', NULL, NULL, 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(26, 'Bargain Bee', 'http://bargainbee.com/', '2310 East Burton St. Sulphur, LA 70663', '(800) 756-3510', 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(27, 'SignPost', 'http://signpost.com', NULL, NULL, 36000, 36000, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(28, 'Crowd Seats', 'http://www.crowdseats.com/', NULL, NULL, 36000, 36000, 1, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(29, 'Landmark Great Deals', 'http://landmarksgreatdeals.com', NULL, NULL, 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(30, 'DealFind', 'http://dealfind.com', '250 Ferrand Drive Suite 1503 Toronto, Ontario M3C 3G8 Canada', '18883206368', 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(31, 'Restaurant.com', 'http://restaurant.com', '1500 West Shure Drive, Suite 200  Arlington Heights, Illinois 60004 ', '(847) 506-9680', 0, 0, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(32, 'Pinchit', 'http://pinchit.com', '111 Pine St Suite 1605 San Francisco, CA 94111', '1-800-207-2160', 3600, 18000, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(33, 'GoldStar Events', 'http://www.goldstar.com/', 'PO Box 277 Altadena CA 91003-0277', NULL, 1800, 18000, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(34, 'OnSale', 'http://www.onsale.com/', 'OnSale, LLC 111 N. Canal Street, Suite 1551 Chicago,  IL 60606', '1-888-760-0300', 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(35, 'LivingSocial Adventures', 'http://www.livingsocial.com/adventures', '1445 New York Ave NW Suite 200 Washington, DC 20005', '888.808.6676', 1800, 3600, 1, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(36, 'Entertainment.com', 'http://deals.entertainment.com/', NULL, '(866) 499 0660', 1800, 18000, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(37, 'Thrillist', 'http://www.thrillist.com/', NULL, NULL, 1800, 18000, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(38, 'Savored', 'http://savored.com/', NULL, NULL, 18000, 18000, 0, 0, 1, 0, 0, '50.57.43.108', 'Deals'),
(39, 'MSN Offers', 'https://msnoffers.com/', NULL, NULL, 1800, 18000, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(40, 'CBS Local Offers', 'http://offers.cbslocal.com/', NULL, '1-800-252-4361', 1800, 18000, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(41, 'CrowdSavings', 'http://www.crowdsavings.com/', '5405 Cypress Center Drive, Suite 110 Tampa, Florida 33609', '800-285-3499', 1800, 3600, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(42, 'PlumDistrict', 'http://www.plumdistrict.com/', NULL, NULL, 36000, 36000, 0, 0, 0, 0, 0, '50.57.43.108', 'Deals'),
(43, 'Mamapedia', 'http://deals.mamapedia.com/', NULL, NULL, 18000, 18000, 0, 0, 0, 0, 1, '50.57.43.108', 'Deals');
