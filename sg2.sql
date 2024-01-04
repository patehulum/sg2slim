-- MariaDB dump 10.19  Distrib 10.11.4-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: sg2
-- ------------------------------------------------------
-- Server version	10.11.4-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `t_cargo`
--

DROP TABLE IF EXISTS `t_cargo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_cargo` (
  `cargo_id` int(11) NOT NULL AUTO_INCREMENT,
  `cis2_id` varchar(100) DEFAULT NULL,
  `payment_id` int(11) NOT NULL,
  `buid` varchar(5) NOT NULL,
  `trandate` datetime NOT NULL,
  `btb_date` datetime DEFAULT NULL,
  `tranout` datetime NOT NULL,
  `trannbr` varchar(50) DEFAULT NULL,
  `airline_id` int(11) NOT NULL,
  `flight` varchar(10) NOT NULL DEFAULT '',
  `flight_date` datetime NOT NULL,
  `airline_id_out` int(11) NOT NULL,
  `flight_out` varchar(10) DEFAULT NULL,
  `flight_out_date` date DEFAULT NULL,
  `awb` varchar(15) NOT NULL,
  `goodgroup` varchar(100) NOT NULL,
  `goodsub` varchar(100) DEFAULT NULL,
  `good` varchar(200) NOT NULL,
  `ori` char(3) NOT NULL,
  `dst` char(3) NOT NULL,
  `qty` int(11) NOT NULL,
  `total_qty` int(11) NOT NULL,
  `gw` float NOT NULL DEFAULT 0,
  `vol` float NOT NULL DEFAULT 0,
  `chw` float NOT NULL DEFAULT 0,
  `dom_int` tinyint(4) NOT NULL COMMENT '1=Domestik,2=Internasional',
  `trantipe` tinyint(4) NOT NULL COMMENT '1=Outgoing,2=Incoming,3=Transit,4=RA',
  `shp_id` varchar(50) DEFAULT NULL,
  `shp_name` varchar(100) DEFAULT NULL,
  `shp_addr` varchar(700) DEFAULT NULL,
  `shp_loc` varchar(100) DEFAULT NULL,
  `shp_country` varchar(20) DEFAULT NULL,
  `shp_telp` varchar(20) DEFAULT NULL,
  `cne_id` varchar(50) DEFAULT NULL,
  `cne_name` varchar(100) DEFAULT NULL,
  `cne_addr` varchar(700) DEFAULT NULL,
  `cne_loc` varchar(100) DEFAULT NULL,
  `cne_country` varchar(20) DEFAULT NULL,
  `cne_telp` varchar(20) DEFAULT NULL,
  `cne_email` varchar(100) DEFAULT NULL,
  `agt_id` varchar(50) DEFAULT NULL,
  `agt_name` varchar(100) DEFAULT NULL,
  `agt_addr` varchar(700) DEFAULT NULL,
  `agt_loc` varchar(100) DEFAULT NULL,
  `agt_country` varchar(20) DEFAULT NULL,
  `agt_telp` varchar(20) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `sender` varchar(100) DEFAULT NULL,
  `receiver` varchar(100) DEFAULT NULL,
  `kemasan` varchar(200) DEFAULT NULL,
  `info` varchar(200) DEFAULT NULL,
  `instruct` varchar(200) DEFAULT NULL,
  `anomali` varchar(200) DEFAULT NULL,
  `locked` tinyint(4) NOT NULL DEFAULT 1,
  `csd` varchar(30) DEFAULT NULL,
  `bast` varchar(30) DEFAULT NULL,
  `released` datetime DEFAULT NULL,
  `master` tinyint(4) NOT NULL,
  `house` tinyint(4) NOT NULL,
  `master_awb` int(11) NOT NULL COMMENT 'Contain cargo_id',
  `checklist_id` int(11) NOT NULL,
  `withdrawn` tinyint(4) NOT NULL,
  `create_by` varchar(100) DEFAULT NULL,
  `create_date` datetime DEFAULT NULL,
  `modified_by` varchar(100) NOT NULL,
  `modified_date` datetime DEFAULT NULL,
  `bpp_id` int(11) NOT NULL,
  `ra_asal` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`cargo_id`),
  UNIQUE KEY `UNIQUE` (`trannbr`),
  KEY `IDX_t_cargo_master` (`master`,`master_awb`),
  KEY `IDX_t_cargo_csd` (`csd`,`trantipe`,`locked`),
  KEY `IDX_t_cargo2` (`buid`,`flight`,`flight_date`,`trantipe`,`master_awb`,`locked`),
  KEY `IDX_t_cargo_bast` (`buid`,`trantipe`,`csd`,`bast`,`locked`,`trandate`),
  KEY `IDX_t_cargo_buid_awb_locked` (`buid`,`trandate`,`awb`,`locked`),
  KEY `IDX_t_cargo` (`buid`,`master_awb`,`locked`),
  KEY `IDX_t_cargo_bppView` (`buid`,`customer_id`,`dom_int`,`trantipe`,`locked`,`master`),
  KEY `IDX_t_cargo_bppView2` (`buid`,`locked`,`trantipe`,`dom_int`,`customer_id`),
  KEY `IDX_t_cargo_awb` (`buid`,`awb`),
  KEY `IDX_t_cargo_<list>` (`buid`,`trantipe`,`master`),
  KEY `IDX_t_cargo_awb2` (`awb`)
) ENGINE=InnoDB AUTO_INCREMENT=267 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci AVG_ROW_LENGTH=558;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_cargo_item`
--

DROP TABLE IF EXISTS `t_cargo_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_cargo_item` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `cargo_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `kg` float NOT NULL,
  `d` float NOT NULL,
  `w` float NOT NULL,
  `h` float NOT NULL,
  `cm3` float NOT NULL,
  `vw` float NOT NULL,
  `manualScale` tinyint(4) NOT NULL DEFAULT 0,
  `authorScale` varchar(255) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `t_cargo_item_cargo_id_IDX` (`cargo_id`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci AVG_ROW_LENGTH=58;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `t_cargo_charges`
--

DROP TABLE IF EXISTS `t_cargo_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `t_cargo_charges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cargo_id` int(11) NOT NULL,
  `charge_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `value` decimal(15,2) NOT NULL,
  `vat` decimal(15,2) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UK_t_cargo_charges` (`cargo_id`,`charge_id`)
) ENGINE=InnoDB AUTO_INCREMENT=204 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci AVG_ROW_LENGTH=61;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-01-02  9:36:59
