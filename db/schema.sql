-- Luxury Talent Booking - Red Carpet Edition Database Schema
-- MySQL 8.0+ compatible schema with proper indexes and constraints

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table structure for table `roles`
-- --------------------------------------------------------

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text,
  `permissions` json,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_roles_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `companies`
-- --------------------------------------------------------

CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `domain` varchar(255),
  `plan_type` enum('basic','elite') DEFAULT 'basic',
  `status` enum('active','inactive','pending','suspended') DEFAULT 'pending',
  `settings` json,
  `billing_info` json,
  `contact_email` varchar(255),
  `contact_phone` varchar(50),
  `address` text,
  `logo_url` varchar(500),
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `domain` (`domain`),
  KEY `idx_companies_status` (`status`),
  KEY `idx_companies_plan` (`plan_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(100),
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `company_id` int(11),
  `avatar_url` varchar(500),
  `phone` varchar(50),
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified_at` timestamp NULL,
  `last_login` timestamp NULL,
  `login_count` int(11) DEFAULT 0,
  `preferences` json,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_company` (`company_id`),
  KEY `idx_users_active` (`is_active`),
  KEY `idx_users_email_verified` (`email_verified_at`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `talent_profiles`
-- --------------------------------------------------------

CREATE TABLE `talent_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `stage_name` varchar(255),
  `category` enum('model','actor','dancer','musician','voice_artist','other') NOT NULL,
  `bio` text,
  `location` varchar(255),
  `latitude` decimal(10,8),
  `longitude` decimal(11,8),
  `height` varchar(20),
  `weight` varchar(20),
  `hair_color` varchar(50),
  `eye_color` varchar(50),
  `age_range` varchar(20),
  `years_experience` int(11) DEFAULT 0,
  `languages` json,
  `skills` json,
  `availability` json,
  `rates` json,
  `privacy_level` enum('public','partial','private') DEFAULT 'partial',
  `is_verified` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_bookings` int(11) DEFAULT 0,
  -- Elite tier fields
  `tattoos` text,
  `piercings` text,
  `enhancements` text,
  `botox_history` text,
  `nail_size` varchar(20),
  `cup_size` varchar(10),
  `measurements` json,
  `special_skills` json,
  `wardrobe_sizes` json,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_talent_category` (`category`),
  KEY `idx_talent_location` (`location`),
  KEY `idx_talent_geo` (`latitude`,`longitude`),
  KEY `idx_talent_privacy` (`privacy_level`),
  KEY `idx_talent_verified` (`is_verified`),
  KEY `idx_talent_featured` (`is_featured`),
  KEY `idx_talent_experience` (`years_experience`),
  KEY `idx_talent_hair_eye` (`hair_color`,`eye_color`),
  CONSTRAINT `fk_talent_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `talent_media`
-- --------------------------------------------------------

CREATE TABLE `talent_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `talent_id` int(11) NOT NULL,
  `title` varchar(255),
  `description` text,
  `media_type` enum('image','video') NOT NULL,
  `media_url` varchar(500) NOT NULL,
  `thumbnail_url` varchar(500),
  `normalized_url` varchar(500),
  `file_size` bigint(20),
  `width` int(11),
  `height` int(11),
  `duration` decimal(8,2),
  `aspect_ratio` decimal(5,3),
  `is_primary` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `metadata` json,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_talent` (`talent_id`),
  KEY `idx_media_type` (`media_type`),
  KEY `idx_media_active` (`is_active`),
  KEY `idx_media_primary` (`is_primary`),
  KEY `idx_media_aspect` (`aspect_ratio`),
  CONSTRAINT `fk_media_talent` FOREIGN KEY (`talent_id`) REFERENCES `talent_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `media_approvals`
-- --------------------------------------------------------

CREATE TABLE `media_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `media_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11),
  `review_notes` text,
  `reviewed_at` timestamp NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_id` (`media_id`),
  KEY `idx_approvals_status` (`status`),
  KEY `idx_approvals_reviewer` (`reviewed_by`),
  CONSTRAINT `fk_approval_media` FOREIGN KEY (`media_id`) REFERENCES `talent_media` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_approval_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `status_posts`
-- --------------------------------------------------------

CREATE TABLE `status_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `content` text,
  `media_url` varchar(500),
  `media_type` enum('image','video'),
  `expires_at` timestamp NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_user` (`user_id`),
  KEY `idx_status_expires` (`expires_at`),
  KEY `idx_status_active` (`is_active`),
  CONSTRAINT `fk_status_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `bookings`
-- --------------------------------------------------------

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `talent_id` int(11) NOT NULL,
  `event_broadcast_id` int(11),
  `title` varchar(255) NOT NULL,
  `description` text,
  `booking_type` enum('direct','broadcast_response','inquiry') DEFAULT 'direct',
  `status` enum('pending','confirmed','in_progress','completed','cancelled','disputed') DEFAULT 'pending',
  `start_date` datetime NOT NULL,
  `end_date` datetime,
  `location` varchar(255),
  `latitude` decimal(10,8),
  `longitude` decimal(11,8),
  `rate_amount` decimal(10,2),
  `rate_type` enum('hourly','daily','project','commission') DEFAULT 'hourly',
  `total_amount` decimal(10,2),
  `commission_rate` decimal(5,2) DEFAULT 0.00,
  `commission_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','partial','paid','refunded') DEFAULT 'pending',
  `requirements` json,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bookings_client` (`client_id`),
  KEY `idx_bookings_talent` (`talent_id`),
  KEY `idx_bookings_status` (`status`),
  KEY `idx_bookings_dates` (`start_date`,`end_date`),
  KEY `idx_bookings_location` (`latitude`,`longitude`),
  KEY `idx_bookings_broadcast` (`event_broadcast_id`),
  CONSTRAINT `fk_booking_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_talent` FOREIGN KEY (`talent_id`) REFERENCES `talent_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payments`
-- --------------------------------------------------------

CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `payer_id` int(11) NOT NULL,
  `payee_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `payment_method` varchar(50),
  `transaction_id` varchar(255),
  `processor` varchar(50),
  `status` enum('pending','processing','completed','failed','refunded') DEFAULT 'pending',
  `processor_response` json,
  `fees` decimal(10,2) DEFAULT 0.00,
  `net_amount` decimal(10,2),
  `processed_at` timestamp NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_booking` (`booking_id`),
  KEY `idx_payments_payer` (`payer_id`),
  KEY `idx_payments_payee` (`payee_id`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_transaction` (`transaction_id`),
  CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_payer` FOREIGN KEY (`payer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_payee` FOREIGN KEY (`payee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `event_broadcasts`
-- --------------------------------------------------------

CREATE TABLE `event_broadcasts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `event_date` datetime NOT NULL,
  `location` varchar(255),
  `latitude` decimal(10,8),
  `longitude` decimal(11,8),
  `radius_km` decimal(8,2) DEFAULT 50.00,
  `target_filters` json,
  `requirements` json,
  `rate_offered` decimal(10,2),
  `rate_type` enum('hourly','daily','project') DEFAULT 'hourly',
  `max_talents` int(11) DEFAULT 1,
  `status` enum('draft','active','paused','closed','expired') DEFAULT 'draft',
  `expires_at` timestamp NULL,
  `response_deadline` timestamp NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_broadcasts_client` (`client_id`),
  KEY `idx_broadcasts_status` (`status`),
  KEY `idx_broadcasts_location` (`latitude`,`longitude`),
  KEY `idx_broadcasts_date` (`event_date`),
  KEY `idx_broadcasts_expires` (`expires_at`),
  CONSTRAINT `fk_broadcast_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `event_targets`
-- --------------------------------------------------------

CREATE TABLE `event_targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `broadcast_id` int(11) NOT NULL,
  `talent_id` int(11) NOT NULL,
  `distance_km` decimal(8,2),
  `match_score` decimal(5,2) DEFAULT 0.00,
  `sent_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `broadcast_talent` (`broadcast_id`,`talent_id`),
  KEY `idx_targets_broadcast` (`broadcast_id`),
  KEY `idx_targets_talent` (`talent_id`),
  KEY `idx_targets_distance` (`distance_km`),
  CONSTRAINT `fk_target_broadcast` FOREIGN KEY (`broadcast_id`) REFERENCES `event_broadcasts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_target_talent` FOREIGN KEY (`talent_id`) REFERENCES `talent_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `event_responses`
-- --------------------------------------------------------

CREATE TABLE `event_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `broadcast_id` int(11) NOT NULL,
  `talent_id` int(11) NOT NULL,
  `response` enum('accept','decline','maybe') NOT NULL,
  `message` text,
  `rate_counter` decimal(10,2),
  `availability_notes` text,
  `auto_booking` tinyint(1) DEFAULT 0,
  `responded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `broadcast_talent_response` (`broadcast_id`,`talent_id`),
  KEY `idx_responses_broadcast` (`broadcast_id`),
  KEY `idx_responses_talent` (`talent_id`),
  KEY `idx_responses_type` (`response`),
  CONSTRAINT `fk_response_broadcast` FOREIGN KEY (`broadcast_id`) REFERENCES `event_broadcasts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_response_talent` FOREIGN KEY (`talent_id`) REFERENCES `talent_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `client_shortlist`
-- --------------------------------------------------------

CREATE TABLE `client_shortlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `talent_id` int(11) NOT NULL,
  `notes` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_talent` (`client_id`,`talent_id`),
  KEY `idx_shortlist_client` (`client_id`),
  KEY `idx_shortlist_talent` (`talent_id`),
  KEY `idx_shortlist_active` (`is_active`),
  CONSTRAINT `fk_shortlist_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_shortlist_talent` FOREIGN KEY (`talent_id`) REFERENCES `talent_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `settings`
-- --------------------------------------------------------

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11),
  `category` varchar(100) NOT NULL,
  `key_name` varchar(255) NOT NULL,
  `value` json,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_category_key` (`company_id`,`category`,`key_name`),
  KEY `idx_settings_company` (`company_id`),
  KEY `idx_settings_category` (`category`),
  CONSTRAINT `fk_settings_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `audit_logs`
-- --------------------------------------------------------

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11),
  `company_id` int(11),
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100),
  `entity_id` int(11),
  `old_values` json,
  `new_values` json,
  `ip_address` varchar(45),
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_company` (`company_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_created` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_audit_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Add foreign key for event_broadcasts in bookings table
-- --------------------------------------------------------

ALTER TABLE `bookings` ADD CONSTRAINT `fk_booking_broadcast` FOREIGN KEY (`event_broadcast_id`) REFERENCES `event_broadcasts` (`id`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
