-- MySQL dump 10.13  Distrib 5.7.22, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: laravel-shop
-- ------------------------------------------------------
-- Server version	5.7.22-0ubuntu18.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `admin_menu`
--

LOCK TABLES `admin_menu` WRITE;
/*!40000 ALTER TABLE `admin_menu` DISABLE KEYS */;
INSERT INTO `admin_menu` VALUES (1,0,1,'首页','fa-bar-chart','/',NULL,'2018-10-02 14:46:40'),(2,0,9,'系统管理','fa-tasks',NULL,NULL,'2018-10-04 20:31:45'),(3,2,10,'管理员','fa-users','auth/users',NULL,'2018-10-04 20:31:45'),(4,2,11,'角色','fa-user','auth/roles',NULL,'2018-10-04 20:31:45'),(5,2,12,'权限','fa-ban','auth/permissions',NULL,'2018-10-04 20:31:45'),(6,2,13,'菜单','fa-bars','auth/menu',NULL,'2018-10-04 20:31:45'),(7,2,14,'操作日志','fa-history','auth/logs',NULL,'2018-10-04 20:31:45'),(8,0,2,'用户管理','fa-users','/users','2018-10-02 15:10:14','2018-10-02 15:10:44'),(9,0,4,'商品管理','fa-cubes','/products','2018-10-02 15:59:44','2018-10-04 15:41:27'),(10,0,7,'订单管理','fa-rmb','/orders','2018-10-03 20:04:42','2018-10-04 20:31:45'),(11,0,8,'优惠券管理','fa-tags','/coupon_codes','2018-10-03 23:59:06','2018-10-04 20:31:45'),(12,0,3,'类目管理','fa-bars','/categories','2018-10-04 15:38:00','2018-10-04 15:41:27'),(13,9,6,'众筹商品','fa-flag-checkered','/crowdfunding_products','2018-10-04 20:30:42','2018-10-04 20:31:45'),(14,9,5,'普通商品','fa-cubes','/products','2018-10-04 20:31:28','2018-10-04 20:31:45');
/*!40000 ALTER TABLE `admin_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_permissions`
--

LOCK TABLES `admin_permissions` WRITE;
/*!40000 ALTER TABLE `admin_permissions` DISABLE KEYS */;
INSERT INTO `admin_permissions` VALUES (1,'All permission','*','','*',NULL,NULL),(2,'Dashboard','dashboard','GET','/',NULL,NULL),(3,'Login','auth.login','','/auth/login\r\n/auth/logout',NULL,NULL),(4,'User setting','auth.setting','GET,PUT','/auth/setting',NULL,NULL),(5,'Auth management','auth.management','','/auth/roles\r\n/auth/permissions\r\n/auth/menu\r\n/auth/logs',NULL,NULL),(6,'用户管理','users','','/users*','2018-10-02 15:15:06','2018-10-02 15:15:06'),(7,'商品管理','products','','/products*','2018-10-04 11:54:40','2018-10-04 11:54:40'),(8,'订单管理','orders','','/orders*','2018-10-04 11:55:14','2018-10-04 11:55:14'),(9,'优惠券管理','coupon_codes','','/coupon_codes*','2018-10-04 11:56:02','2018-10-04 11:56:02');
/*!40000 ALTER TABLE `admin_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_role_menu`
--

LOCK TABLES `admin_role_menu` WRITE;
/*!40000 ALTER TABLE `admin_role_menu` DISABLE KEYS */;
INSERT INTO `admin_role_menu` VALUES (1,2,NULL,NULL);
/*!40000 ALTER TABLE `admin_role_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_role_permissions`
--

LOCK TABLES `admin_role_permissions` WRITE;
/*!40000 ALTER TABLE `admin_role_permissions` DISABLE KEYS */;
INSERT INTO `admin_role_permissions` VALUES (1,1,NULL,NULL),(2,2,NULL,NULL),(2,3,NULL,NULL),(2,4,NULL,NULL),(2,6,NULL,NULL),(2,7,NULL,NULL),(2,8,NULL,NULL),(2,9,NULL,NULL);
/*!40000 ALTER TABLE `admin_role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_role_users`
--

LOCK TABLES `admin_role_users` WRITE;
/*!40000 ALTER TABLE `admin_role_users` DISABLE KEYS */;
INSERT INTO `admin_role_users` VALUES (1,1,NULL,NULL),(2,2,NULL,NULL);
/*!40000 ALTER TABLE `admin_role_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_roles`
--

LOCK TABLES `admin_roles` WRITE;
/*!40000 ALTER TABLE `admin_roles` DISABLE KEYS */;
INSERT INTO `admin_roles` VALUES (1,'Administrator','administrator','2018-10-02 14:31:52','2018-10-02 14:31:52'),(2,'运营','operator','2018-10-02 15:19:01','2018-10-02 15:19:01');
/*!40000 ALTER TABLE `admin_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_user_permissions`
--

LOCK TABLES `admin_user_permissions` WRITE;
/*!40000 ALTER TABLE `admin_user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin','$2y$10$K5u/06gjp7sZWaIKaf/ajOkvzFsPUhSw2rvXwVRlmNvNGAZkl.9Mu','Administrator',NULL,'6QErpM0aN6lazJTK6T8xm1bnUg42vcuOYAYTVBpV5OiW4pew8BSrxaruh9Vb','2018-10-02 14:31:52','2018-10-02 14:31:52'),(2,'operator','$2y$10$p19rt37.C.IaG/Yg/SkW4eQzNDm35elOKJwuthNa7aRvGva2HmGm2','运营',NULL,'EGlYq3Al4kjIukAVAFAWLZghgYHpEr2egHjH7TXku2oEUr3iN0eXzO0GYW9d','2018-10-02 15:21:30','2018-10-02 15:21:30');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-10-04 12:58:03
