-- Twixo Payment Gateway schema
-- https://twixo.sweez.xyz

CREATE DATABASE IF NOT EXISTS `twixo` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `twixo`;

-- SMS payments ingested by the Twixo app via read_sms.php
CREATE TABLE IF NOT EXISTS `payment_sms` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` varchar(64) NOT NULL,
  `track_id` varchar(64) DEFAULT NULL,
  `sender_number` varchar(20) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `method` varchar(32) NOT NULL,
  `fee` decimal(12,2) DEFAULT 0.00,
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '0=unused, 1=used',
  `raw_sms` text,
  `last_balance` decimal(14,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_transaction_id` (`transaction_id`),
  KEY `idx_status` (`status`),
  KEY `idx_method` (`method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Successful verifications logged by callback.php
CREATE TABLE IF NOT EXISTS `payment_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(64) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(32) NOT NULL,
  `transaction_id` varchar(64) NOT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `gateway` varchar(32) NOT NULL DEFAULT 'TWIXO',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_log_trx` (`transaction_id`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
