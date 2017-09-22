SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for subscription
-- ----------------------------
DROP TABLE IF EXISTS `subscription`;
CREATE TABLE `subscription` (
  `subscription_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `merchant_subscription_id` varchar(128) NOT NULL,
  `user_id` mediumint(8) unsigned NOT NULL,
  `subscription_start_date` int(10) unsigned NOT NULL,
  `subscription_current_billing_start` int(10) unsigned NOT NULL,
  `subscription_next_payment` int(11) NOT NULL,
  `subscription_initial_amount` decimal(6,2) unsigned NOT NULL,
  `subscription_amount` decimal(6,2) NOT NULL,
  `subscription_status` enum('active','cancelled','pending','suspended','terminated') NOT NULL DEFAULT 'terminated',
  `subscription_length` mediumint(5) DEFAULT '1' COMMENT 'subscription length in months',
  `subscription_plan` varchar(100) DEFAULT NULL,
  `subscription_failed_payments` mediumint(5) DEFAULT '0',
  PRIMARY KEY (`subscription_id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for subscription_payment
-- ----------------------------
DROP TABLE IF EXISTS `subscription_payment`;
CREATE TABLE `subscription_payment` (
  `payment_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` int(10) unsigned NOT NULL,
  `merchant_transaction_id` varchar(128) NOT NULL,
  `user_id` mediumint(8) unsigned NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `payment_time` (`payment_time`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `subscription_id` (`subscription_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL DEFAULT '',
  `password` varchar(64) NOT NULL DEFAULT '',
  `first_name` varchar(30) NOT NULL DEFAULT '',
  `last_name` varchar(30) NOT NULL,
  `added_at` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(10) unsigned NOT NULL DEFAULT '0',
  `facebook_id` varchar(50) DEFAULT NULL,
  `auth_token` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
