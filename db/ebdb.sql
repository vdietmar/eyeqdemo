-- phpMyAdmin SQL Dump
-- version 4.1.12
-- http://www.phpmyadmin.net
--
-- Host: aa14j73b29w26b4.cmcf3ksppdbh.eu-west-1.rds.amazonaws.com
-- Erstellungszeit: 11. Apr 2014 um 18:31
-- Server Version: 5.5.27-log
-- PHP-Version: 5.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `ebdb`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `eyeq_airings`
--

CREATE TABLE IF NOT EXISTS `eyeq_airings` (
  `airingid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `programid` int(10) unsigned NOT NULL,
  `channelid` int(10) unsigned NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `video` varchar(200) DEFAULT NULL,
  `audio` varchar(200) DEFAULT NULL,
  `agerating` varchar(200) DEFAULT NULL,
  `viewing` varchar(200) DEFAULT NULL,
  `caption` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`airingid`),
  KEY `start` (`start`,`end`),
  KEY `programid` (`programid`,`channelid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=478024 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `eyeq_channels`
--

CREATE TABLE IF NOT EXISTS `eyeq_channels` (
  `channelid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gnid` varchar(100) NOT NULL,
  `region` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `nameshort` varchar(10) DEFAULT NULL,
  `callsign` varchar(2000) DEFAULT NULL COMMENT 'Can be multiple, separated by comma',
  `cleannames` varchar(2000) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL COMMENT 'can be multiple, separated by comma',
  `lang` varchar(100) DEFAULT NULL,
  `triplet` text COMMENT 'Can be multiple, format dvbs|c|t://onid.tsid.sid, separated by comma',
  `listingsavailable` tinyint(1) NOT NULL DEFAULT '1',
  `lastupdate` timestamp NULL DEFAULT NULL,
  `updatetries` tinyint(3) unsigned DEFAULT NULL,
  `updateinfo` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`channelid`),
  KEY `listingsavailable` (`listingsavailable`),
  KEY `region` (`region`),
  FULLTEXT KEY `triplet` (`triplet`),
  FULLTEXT KEY `cleannames` (`cleannames`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=677760 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `eyeq_channel_triplet`
--

CREATE TABLE IF NOT EXISTS `eyeq_channel_triplet` (
  `channelid` int(10) unsigned NOT NULL,
  `triplet` varchar(30) NOT NULL,
  PRIMARY KEY (`channelid`,`triplet`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Pairs are not unique here';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `eyeq_config`
--

CREATE TABLE IF NOT EXISTS `eyeq_config` (
  `key` varchar(20) NOT NULL,
  `value` varchar(2000) DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `eyeq_config_bak`
--

CREATE TABLE IF NOT EXISTS `eyeq_config_bak` (
  `key` varchar(20) NOT NULL,
  `value` varchar(2000) DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `eyeq_lineups`
--

CREATE TABLE IF NOT EXISTS `eyeq_lineups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `init` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `eyeq_lineup_channel`
--

CREATE TABLE IF NOT EXISTS `eyeq_lineup_channel` (
  `lineupid` int(10) unsigned NOT NULL,
  `channelid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`lineupid`,`channelid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `eyeq_programs`
--

CREATE TABLE IF NOT EXISTS `eyeq_programs` (
  `programid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gnid` varchar(100) NOT NULL,
  `title` varchar(200) NOT NULL,
  `subtitle` varchar(200) DEFAULT NULL,
  `orgtitle` varchar(200) DEFAULT NULL,
  `synopsis` varchar(2000) DEFAULT NULL,
  `listing` varchar(200) DEFAULT NULL,
  `date` varchar(10) DEFAULT NULL,
  `origin` varchar(200) DEFAULT NULL,
  `type` varchar(200) NOT NULL,
  `rank` int(10) unsigned DEFAULT NULL,
  `groupref` int(10) unsigned NOT NULL,
  `imgurl` varchar(2000) DEFAULT NULL,
  `episodenumber` varchar(10) DEFAULT NULL,
  `episodecount` smallint(5) unsigned DEFAULT NULL,
  `season` smallint(5) unsigned DEFAULT NULL,
  `avwork` varchar(100) DEFAULT NULL,
  `avseries` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`programid`),
  KEY `gnid` (`gnid`,`rank`,`groupref`),
  FULLTEXT KEY `title` (`title`,`subtitle`,`orgtitle`,`synopsis`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=150059 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `eyeq_program_category`
--

CREATE TABLE IF NOT EXISTS `eyeq_program_category` (
  `programid` int(10) unsigned NOT NULL,
  `categoryl1id` int(10) unsigned NOT NULL,
  `categoryl2id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`programid`,`categoryl1id`,`categoryl2id`),
  KEY `categoryid` (`categoryl1id`,`categoryl2id`,`programid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `eyeq_program_origin`
--

CREATE TABLE IF NOT EXISTS `eyeq_program_origin` (
  `programid` int(10) unsigned NOT NULL,
  `originid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`programid`,`originid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `eyeq_program_type`
--

CREATE TABLE IF NOT EXISTS `eyeq_program_type` (
  `programid` int(10) unsigned NOT NULL,
  `typeid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`programid`,`typeid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
