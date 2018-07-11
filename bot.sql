/*
Navicat MySQL Data Transfer

Source Server         : osu!
Source Server Version : 50553
Source Host           : 120.52.176.13:3306
Source Database       : osu

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2018-04-24 21:33:15
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for osu_pay
-- ----------------------------
DROP TABLE IF EXISTS `osu_pay`;
CREATE TABLE `osu_pay` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `type` varchar(10) DEFAULT NULL,
  `qq` int(11) unsigned DEFAULT NULL,
  `money` float(7,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qq` (`qq`)
) ENGINE=InnoDB AUTO_INCREMENT=318 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for osu_store
-- ----------------------------
DROP TABLE IF EXISTS `osu_store`;
CREATE TABLE `osu_store` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(15) DEFAULT NULL,
  `callname` varchar(30) DEFAULT NULL,
  `money` float(7,2) unsigned DEFAULT NULL,
  `disposable` bit(1) DEFAULT b'1',
  `sql` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for osu_store_bill
-- ----------------------------
DROP TABLE IF EXISTS `osu_store_bill`;
CREATE TABLE `osu_store_bill` (
  `qq` int(11) unsigned DEFAULT NULL,
  `store_id` tinyint(3) unsigned DEFAULT NULL,
  `pay_id` mediumint(8) unsigned DEFAULT NULL,
  KEY `qq` (`qq`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
