-- phpMyAdmin SQL Dump
-- version 3.1.2deb1ubuntu0.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 18, 2011 at 04:21 PM
-- Server version: 5.0.75
-- PHP Version: 5.2.6-3ubuntu4.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `houston`
--

-- --------------------------------------------------------

--
-- Table structure for table `houston_queue`
--

CREATE TABLE IF NOT EXISTS `houston_queue` (
  `qid` int(10) unsigned NOT NULL auto_increment,
  `type` varchar(255) NOT NULL default '',
  `operation` varchar(255) NOT NULL default '',
  `controller` varchar(255) NOT NULL default '',
  `local_id` int(10) unsigned NOT NULL default '0',
  `local_blocker_id` int(10) unsigned default NULL,
  `local_blocker_type` varchar(255) default NULL,
  `timestamp` int(10) unsigned NOT NULL default '0',
  `process_count` int(10) unsigned default '0',
  `status_flag` tinyint(3) unsigned default '0',
  `data` longtext,
  PRIMARY KEY  (`qid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Table structure for table `houston_variables`
--

CREATE TABLE IF NOT EXISTS `houston_variables` (
  `name` varchar(128) NOT NULL default '',
  `value` longtext NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
