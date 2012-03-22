-- phpMyAdmin SQL Dump
-- version 3.3.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 20, 2012 at 02:04 AM
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=46 ;

--
-- Dumping data for table `CategoryInfo`
--

INSERT INTO `CategoryInfo` (`id`, `name`, `description`) VALUES
(1, 'Casual Dining', 'Sit-down dining experience with waiter service'),
(2, 'Quick Bites', 'Uncomplicated dining experiences that won''t waste your time or budget'),
(3, 'Sweet Tooth', 'Sweet treats like cupcakes, cookies, ice cream, and froyo'),
(4, 'Foodie', 'High-end culinary experiences and epicurean products'),
(5, 'Groceries and Spices', 'Produce and ingredients you''d find in your supermarket'),
(6, 'Good for Kids', 'Stuff that the kids will enjoy'),
(7, 'Moms and Babies', 'Everything you need as a new mother'),
(8, 'Always Learning', 'Classes and lessons, from language courses to flight school'),
(9, 'Threads', 'Clothes, shoes, fashion accessories for those who dress to thrill'),
(10, 'Well Groomed', 'Upkeep essentials, such as electric toothbrushes, laser hair removal, or hair care'),
(11, 'Forever Beautiful', 'Experiences that can help make a more beautiful you'),
(12, 'Pampered', 'Treat yourself with experiences like Swedish massages, spa treatments, and mani-pedis'),
(13, 'Body Art', 'For those whose body is their canvas'),
(14, 'Trim and Terrific', 'Lost weight and feel terrific with body wraps, lipo treatments, weight loss consultations'),
(15, 'Healthy Living', 'For those who care for their well-being with exercise, yoga, and balanced nutrition'),
(16, 'Alternative Healing', 'To help you feel better, with acupuncture, chiropractic treatments, and hypnotherapy'),
(17, 'Seeing Clearly', 'See better with offers for eyeglasses, contacts, and LASIK'),
(18, 'Medical and Dental', 'Services from physicians and dentists'),
(19, 'Nightlife', 'Hit the town with things like comedy shows, pub crawls, and mixers'),
(20, 'Fun Activities', 'Great activities that will get you up off the couch'),
(21, 'Dancing Feet', 'For anyone who loves to dance'),
(22, 'Will Call', 'Live music, theater, and other performance events'),
(23, 'Cultural Pursuits', 'Experiences that expand cultural awareness, such as museums, tours, and literature'),
(24, 'The Outdoors', 'Outdoor activities from cycling to sailing'),
(25, 'Sporting Life', 'For those who have team spirit and enjoy athletic competition'),
(26, 'Adrenaline', 'Thrilling and heart-pounding experiences'),
(27, 'Date Night', 'Great date experiences and other fun two-person activities'),
(28, 'Once in a Lifetime', 'Extraordinary "bucket list" opportunities, such as skydiving and helicopter tours'),
(29, 'The Finer Things', 'Sophisticated opportunities, such as upscale dining experiences and yacht excursions'),
(30, 'Automotive', 'Deals to keep your car looking good and running well'),
(31, 'Home and Garden', 'Products and services to spruce up your home, from furniture and kitchenware to garden supplies'),
(32, 'Handyman', 'Help with home repairs an maintenance to  renovation projects'),
(33, 'Squeaky Clean', 'Services to keep your home spic-and-span from carpet and window cleaning to maid service'),
(34, 'General Services', 'All the help you need from tax preparation to bike repair'),
(35, 'Gadgets and Gear', 'High-tech products, from smart phones to MP3 players'),
(36, 'Bookish', 'Books, magazines, and newspaper for those who love the written word'),
(37, 'Photographic', 'Photography services and workshops for those who prefer looking at life through a lens'),
(38, 'Crafty', 'Creative projects and pursuits, from pottery and glassblowing classes to arts and craft supplies'),
(39, 'Pet Lover', 'Pet amenities and accessories, from vet services to gourmet pet food'),
(40, 'Gift Ideas', 'Great gifts for every occasion'),
(41, 'Giving Back', 'Philanthropic causes'),
(42, 'Around the World', 'Vacation packages and great escapes'),
(43, 'Road Trip', 'Quick getaways, such as day trips or nearby overnights'),
(44, 'Good for Girls', 'Deals that girls might like'),
(45, 'Good for Guys', 'Deals that guys might like');
