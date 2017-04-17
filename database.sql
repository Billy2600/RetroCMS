-- phpMyAdmin SQL Dump
-- version 2.11.11.3
-- http://www.phpmyadmin.net
--
-- Host: 68.178.136.207
-- Generation Time: Jun 10, 2016 at 10:08 PM
-- Server version: 5.0.96
-- PHP Version: 5.1.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `retrooftheweek64`
--

-- --------------------------------------------------------

--
-- Table structure for table `ret_account_types`
--

CREATE TABLE `ret_account_types` (
  `tid` int(11) NOT NULL auto_increment,
  `title` varchar(45) NOT NULL,
  `admin` int(1) default NULL,
  `editor` int(1) default NULL,
  `banned` int(1) default NULL,
  PRIMARY KEY  (`tid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `ret_categories`
--

CREATE TABLE `ret_categories` (
  `cid` int(11) NOT NULL auto_increment,
  `name` varchar(200) NOT NULL,
  PRIMARY KEY  (`cid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `ret_comments`
--

CREATE TABLE `ret_comments` (
  `cid` int(255) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `text` text,
  `poster_id` int(255) default NULL,
  `date` datetime NOT NULL,
  `post_id` int(255) NOT NULL,
  `reply` int(255) default NULL,
  `ip_address` varchar(20) default NULL,
  `msg_reply` int(1) NOT NULL default '0',
  PRIMARY KEY  (`cid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=912 ;

-- --------------------------------------------------------

--
-- Table structure for table `ret_messages`
--

CREATE TABLE `ret_messages` (
  `mid` int(255) NOT NULL auto_increment,
  `title` varchar(255) default NULL,
  `text` text NOT NULL,
  `date` datetime default NULL,
  `to` int(255) NOT NULL,
  `from` int(255) NOT NULL,
  `read` int(1) NOT NULL default '0',
  `reply` int(255) NOT NULL default '0',
  PRIMARY KEY  (`mid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=725 ;

-- --------------------------------------------------------

--
-- Table structure for table `ret_posts`
--

CREATE TABLE `ret_posts` (
  `pid` int(255) NOT NULL auto_increment,
  `title` varchar(100) default NULL,
  `text` longtext,
  `poster_id` int(255) NOT NULL,
  `date` datetime NOT NULL,
  `tags` text,
  `img` text,
  `thumb` text,
  `email_author` int(1) NOT NULL default '1',
  `hidden` tinyint(1) NOT NULL default '0',
  `views` int(255) NOT NULL default '0',
  `rating` int(255) NOT NULL default '0',
  `name` varchar(20) NOT NULL default 'User not found',
  `email` varchar(255) default NULL,
  PRIMARY KEY  (`pid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=170 ;

-- --------------------------------------------------------

--
-- Table structure for table `ret_sessions`
--

CREATE TABLE `ret_sessions` (
  `sid` int(255) NOT NULL auto_increment,
  `userid` int(255) NOT NULL,
  `ip_address` varchar(39) NOT NULL,
  `date` datetime NOT NULL,
  KEY `sid` (`sid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=72 ;

-- --------------------------------------------------------

--
-- Table structure for table `ret_unvalidated_posts`
--

CREATE TABLE `ret_unvalidated_posts` (
  `pid` int(255) NOT NULL auto_increment,
  `title` varchar(100) NOT NULL,
  `text` longtext NOT NULL,
  `img` text NOT NULL,
  `thumb` text NOT NULL,
  `user` int(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `tags` text NOT NULL,
  PRIMARY KEY  (`pid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=17 ;

-- --------------------------------------------------------

--
-- Table structure for table `ret_users`
--

CREATE TABLE `ret_users` (
  `uid` int(255) NOT NULL auto_increment,
  `username` varchar(20) NOT NULL,
  `password` varchar(100) NOT NULL,
  `fname` varchar(20) default NULL,
  `lname` varchar(20) default NULL,
  `aboutme` text,
  `account_type` int(1) NOT NULL default '3',
  `join_date` date NOT NULL,
  `birthday` date default NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(20) default NULL,
  `avatar` varchar(255) default NULL,
  `gender` int(1) default NULL,
  `country` text,
  `skype` varchar(255) default NULL,
  `msn` varchar(255) default NULL,
  `yahoo` varchar(255) default NULL,
  `aim` varchar(255) default NULL,
  `steam` varchar(255) default NULL,
  PRIMARY KEY  (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=485 ;

-- --------------------------------------------------------

--
-- Table structure for table `ret_votes`
--

CREATE TABLE `ret_votes` (
  `vid` int(255) NOT NULL auto_increment,
  `user_id` int(255) NOT NULL,
  `post_id` varchar(255) NOT NULL,
  `value` tinyint(1) NOT NULL default '1',
  `date` datetime NOT NULL,
  `type` int(1) NOT NULL default '0',
  PRIMARY KEY  (`vid`),
  UNIQUE KEY `user_id` (`user_id`,`post_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=161 ;