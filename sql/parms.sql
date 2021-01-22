# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 192.168.1.221 (MySQL 5.5.60-0+deb8u1)
# Database: temp
# Generation Time: 2021-01-12 07:15:55 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table alarms
# ------------------------------------------------------------

DROP TABLE IF EXISTS `alarms`;

CREATE TABLE `alarms` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `serial` varchar(8) DEFAULT '',
  `alarmStatus` int(1) DEFAULT '0',
  `alarmDate` varchar(12) DEFAULT '0',
  `lostStatus` int(1) DEFAULT '0',
  `lostDate` varchar(12) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `alarms` WRITE;
/*!40000 ALTER TABLE `alarms` DISABLE KEYS */;

INSERT INTO `alarms` (`id`, `serial`, `alarmStatus`, `alarmDate`, `lostStatus`, `lostDate`)
VALUES
	(1,'18967632',0,'',0,''),
	(2,'20960014',0,'',0,''),
	(3,'20960015',0,'',0,''),
	(4,'20960047',0,'',0,''),
	(5,'20960050',0,'',0,'');

/*!40000 ALTER TABLE `alarms` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table emails
# ------------------------------------------------------------

DROP TABLE IF EXISTS `emails`;

CREATE TABLE `emails` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `emails` WRITE;
/*!40000 ALTER TABLE `emails` DISABLE KEYS */;

INSERT INTO `emails` (`id`, `name`, `email`)
VALUES
	(1,'User 1','u1@mail.ee'),
	(2,'User 2','u2@mail.ee'),
	(3,'User 3','u3@mail.ee'),
	(4,'User 4','u4@mail.ee'),
	(5,'User 5','u5@mail.ee'),
	(101,'User 1A','u1a@mail.ee'),
	(102,'User 2A','u2a@mail.ee');

/*!40000 ALTER TABLE `emails` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table emails_to_sensors
# ------------------------------------------------------------

DROP TABLE IF EXISTS `emails_to_sensors`;

CREATE TABLE `emails_to_sensors` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `sensors_id` int(5) DEFAULT NULL,
  `emails_id` int(5) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `emails_to_sensors` WRITE;
/*!40000 ALTER TABLE `emails_to_sensors` DISABLE KEYS */;

INSERT INTO `emails_to_sensors` (`id`, `sensors_id`, `emails_id`)
VALUES
	(1,1,1),
	(2,2,2),
	(3,3,3),
	(4,4,4),
	(5,5,5),
	(11,99999,101),
	(12,99999,102);

/*!40000 ALTER TABLE `emails_to_sensors` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table parms
# ------------------------------------------------------------

DROP TABLE IF EXISTS `parms`;

CREATE TABLE `parms` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(45) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `parms` WRITE;
/*!40000 ALTER TABLE `parms` DISABLE KEYS */;

INSERT INTO `parms` (`id`, `label`, `value`)
VALUES
	(1,'name','TempSens'),
	(2,'release','v0.3.5'),
	(3,'date','24.01.2021'),
	(11,'stat_mintemp','15'),
	(12,'stat_maxtemp','25'),
	(13,'alert_mintemp','15'),
	(14,'alert_maxtemp','25'),
	(15,'watchdog_hrs','3'),
        (16,'avg_cnt','4');

/*!40000 ALTER TABLE `parms` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table queue
# ------------------------------------------------------------

DROP TABLE IF EXISTS `queue`;

CREATE TABLE `queue` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `addr` varchar(45) DEFAULT '',
  `name` varchar(45) DEFAULT '0',
  `subj` varchar(200) DEFAULT '0',
  `body` text,
  `status` int(1) DEFAULT '0',
  `duedate` varchar(12) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table sensors
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sensors`;

CREATE TABLE `sensors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) DEFAULT '',
  `serial` varchar(8) DEFAULT '',
  `ip` varchar(15) DEFAULT '',
  `desc` varchar(200) DEFAULT '',
  `portable` int(1) DEFAULT '0',
  `active` int(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `sensors` WRITE;
/*!40000 ALTER TABLE `sensors` DISABLE KEYS */;

INSERT INTO `sensors` (`id`, `name`, `serial`, `ip`, `desc`, `portable`, `active`)
VALUES
	(1,'EE01-Pharma','18967632','10.37.2.15','Salve 2c 3. korruse ravimiladu',0,1),
	(2,'EE02-WHPharma','20960014','10.37.2.16','Salve 2c 1. korruse lao esisein',0,1),
	(3,'EE03-WHBack','20960015','10.37.2.17','Salve 2c 1. korruse lao tagasein',0,1),
	(4,'EE04-WHLabeling','20960047','10.37.2.18','Salve 2c 1. korruse kleepsuruum',0,1),
	(5,'EE05-Pulsaar','20960050','10.37.2.19','Salve 2c Bepulsaar',0,1),
	(6,'EE06-Tartu',NULL,'10.37.2.20',NULL,0,0),
	(7,'EE07-Portable','19260003','10.37.2.14','Ajutiselt Bepulsaaris',1,1);

/*!40000 ALTER TABLE `sensors` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
