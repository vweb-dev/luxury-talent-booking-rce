-- Luxury Talent Booking - Red Carpet Edition Seed Data
-- Initial data for roles, demo company, and sample users

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- --------------------------------------------------------
-- Insert default roles
-- --------------------------------------------------------

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `permissions`, `is_active`) VALUES
(1, 'super_admin', 'Super Administrator', 'System-wide administrator with full access', '["*"]', 1),
(2, 'tenant_admin', 'Tenant Administrator', 'Company administrator with full company access', '["company.*", "users.*", "talent.*", "bookings.*", "settings.*"]', 1),
(3, 'talent', 'Talent', 'Talent user with profile and booking management', '["profile.*", "media.*", "bookings.view", "bookings.respond"]', 1),
(4, 'client', 'Client', 'Client user with booking and discovery capabilities', '["discover.*", "bookings.*", "shortlist.*", "broadcasts.*"]', 1);

-- --------------------------------------------------------
-- Insert demo company
-- --------------------------------------------------------

INSERT INTO `companies` (`id`, `name`, `slug`, `domain`, `plan_type`, `status`, `settings`, `contact_email`, `contact_phone`, `address`) VALUES
(1, 'RCE Demo Company', 'rce-demo', 'demo.rce-talent.com', 'elite', 'active', 
'{"commission_rate": 15, "auto_approval": false, "geo_radius_default": 50, "payment_methods": ["stripe", "paypal"], "features": {"broadcasts": true, "advanced_filters": true, "analytics": true}}',
'admin@demo.rce-talent.com', '+1-555-0123', '123 Demo Street, Demo City, DC 12345');

-- --------------------------------------------------------
-- Insert demo users
-- --------------------------------------------------------

-- Super Admin (password: admin123)
INSERT INTO `users` (`id`, `name`, `email`, `username`, `password_hash`, `role_id`, `company_id`, `is_active`, `email_verified_at`) VALUES
(1, 'System Administrator', 'admin@rce-system.com', 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NULL, 1, NOW());

-- Tenant Admin (password: demo123)
INSERT INTO `users` (`id`, `name`, `email`, `username`, `password_hash`, `role_id`, `company_id`, `is_active`, `email_verified_at`) VALUES
(2, 'Demo Company Admin', 'admin@demo.rce-talent.com', 'demoadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, 1, NOW());

-- Demo Talents (password: talent123)
INSERT INTO `users` (`id`, `name`, `email`, `username`, `password_hash`, `role_id`, `company_id`, `is_active`, `email_verified_at`) VALUES
(3, 'Sarah Chen', 'sarah.chen@demo.com', 'sarahchen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1, 1, NOW()),
(4, 'Marcus Johnson', 'marcus.johnson@demo.com', 'marcusj', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1, 1, NOW()),
(5, 'Emma Rodriguez', 'emma.rodriguez@demo.com', 'emmar', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1, 1, NOW()),
(6, 'David Kim', 'david.kim@demo.com', 'davidkim', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1, 1, NOW());

-- Demo Clients (password: client123)
INSERT INTO `users` (`id`, `name`, `email`, `username`, `password_hash`, `role_id`, `company_id`, `is_active`, `email_verified_at`) VALUES
(7, 'Elite Productions', 'casting@eliteproductions.com', 'eliteprod', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1, 1, NOW()),
(8, 'Fashion Forward Agency', 'bookings@fashionforward.com', 'fashionfw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4, 1, 1, NOW());

-- --------------------------------------------------------
-- Insert demo talent profiles
-- --------------------------------------------------------

INSERT INTO `talent_profiles` (`id`, `user_id`, `stage_name`, `category`, `bio`, `location`, `latitude`, `longitude`, `height`, `hair_color`, `eye_color`, `age_range`, `years_experience`, `languages`, `skills`, `privacy_level`, `is_verified`, `rating`, `cup_size`, `measurements`) VALUES
(1, 3, 'Sarah Chen', 'model', 'International fashion model with runway and commercial experience. Specializes in high-end fashion, beauty, and lifestyle campaigns.', 'New York, NY', 40.7128, -74.0060, '5\'9"', 'Black', 'Brown', '25-30', 8, '["English", "Mandarin", "French"]', '["Runway", "Commercial", "Beauty", "Lifestyle", "Editorial"]', 'public', 1, 4.85, 'B', '{"bust": "34", "waist": "24", "hips": "36", "dress": "4", "shoe": "8.5"}'),

(2, 4, 'Marcus Johnson', 'dancer', 'Professional dancer with Broadway and commercial experience. Trained in contemporary, jazz, hip-hop, and ballroom styles.', 'Los Angeles, CA', 34.0522, -118.2437, '6\'0"', 'Black', 'Brown', '28-35', 12, '["English", "Spanish"]', '["Contemporary", "Jazz", "Hip-Hop", "Ballroom", "Choreography", "Teaching"]', 'public', 1, 4.92, NULL, '{"chest": "42", "waist": "32", "inseam": "34", "shoe": "11"}'),

(3, 5, 'Emma Rodriguez', 'actor', 'Versatile actor with film, television, and theater experience. Trained at prestigious acting conservatory with range from drama to comedy.', 'Chicago, IL', 41.8781, -87.6298, '5\'6"', 'Brown', 'Green', '26-32', 6, '["English", "Spanish", "Italian"]', '["Drama", "Comedy", "Voice Acting", "Stage Combat", "Accents", "Improvisation"]', 'partial', 1, 4.78, 'C', '{"bust": "36", "waist": "26", "hips": "38", "dress": "6", "shoe": "7.5"}'),

(4, 6, 'David Kim', 'musician', 'Multi-instrumentalist and vocalist specializing in pop, R&B, and electronic music. Producer and songwriter with studio experience.', 'Miami, FL', 25.7617, -80.1918, '5\'10"', 'Black', 'Brown', '24-29', 10, '["English", "Korean"]', '["Vocals", "Piano", "Guitar", "Production", "Songwriting", "Electronic Music"]', 'public', 1, 4.67, NULL, '{"chest": "40", "waist": "30", "inseam": "32", "shoe": "10"}');

-- --------------------------------------------------------
-- Insert demo talent media
-- --------------------------------------------------------

INSERT INTO `talent_media` (`id`, `talent_id`, `title`, `description`, `media_type`, `media_url`, `width`, `height`, `aspect_ratio`, `is_primary`, `is_active`) VALUES
(1, 1, 'Fashion Portfolio - Editorial', 'High-end editorial fashion shoot for luxury magazine', 'image', '/uploads/demo/sarah-editorial-1.jpg', 1080, 1920, 0.563, 1, 1),
(2, 1, 'Runway Walk - Fashion Week', 'Professional runway walk from New York Fashion Week', 'video', '/uploads/demo/sarah-runway-1.mp4', 1080, 1920, 0.563, 0, 1),
(3, 2, 'Contemporary Dance Performance', 'Solo contemporary piece showcasing technical skill and artistry', 'video', '/uploads/demo/marcus-contemporary-1.mp4', 1080, 1920, 0.563, 1, 1),
(4, 2, 'Commercial Dance Reel', 'High-energy commercial dance compilation', 'video', '/uploads/demo/marcus-commercial-1.mp4', 1080, 1920, 0.563, 0, 1),
(5, 3, 'Dramatic Monologue', 'Powerful dramatic performance showcasing range', 'video', '/uploads/demo/emma-drama-1.mp4', 1080, 1920, 0.563, 1, 1),
(6, 3, 'Comedy Scene', 'Comedic timing and character work demonstration', 'video', '/uploads/demo/emma-comedy-1.mp4', 1080, 1920, 0.563, 0, 1),
(7, 4, 'Original Song Performance', 'Live performance of original composition', 'video', '/uploads/demo/david-original-1.mp4', 1080, 1920, 0.563, 1, 1),
(8, 4, 'Studio Session', 'Behind-the-scenes studio recording session', 'video', '/uploads/demo/david-studio-1.mp4', 1080, 1920, 0.563, 0, 1);

-- --------------------------------------------------------
-- Insert media approvals (all approved for demo)
-- --------------------------------------------------------

INSERT INTO `media_approvals` (`media_id`, `status`, `reviewed_by`, `review_notes`, `reviewed_at`) VALUES
(1, 'approved', 2, 'Excellent quality, meets all requirements', NOW() - INTERVAL 1 DAY),
(2, 'approved', 2, 'Professional runway footage, great quality', NOW() - INTERVAL 1 DAY),
(3, 'approved', 2, 'Beautiful contemporary piece, technically sound', NOW() - INTERVAL 1 DAY),
(4, 'approved', 2, 'High-energy commercial work, perfect for client needs', NOW() - INTERVAL 1 DAY),
(5, 'approved', 2, 'Powerful dramatic performance, shows great range', NOW() - INTERVAL 1 DAY),
(6, 'approved', 2, 'Excellent comedic timing, very engaging', NOW() - INTERVAL 1 DAY),
(7, 'approved', 2, 'Original composition is impressive, great vocal quality', NOW() - INTERVAL 1 DAY),
(8, 'approved', 2, 'Studio work shows professionalism and skill', NOW() - INTERVAL 1 DAY);

-- --------------------------------------------------------
-- Insert demo client shortlists
-- --------------------------------------------------------

INSERT INTO `client_shortlist` (`client_id`, `talent_id`, `notes`, `is_active`) VALUES
(7, 1, 'Perfect for upcoming luxury fashion campaign', 1),
(7, 3, 'Great dramatic range, considering for film project', 1),
(8, 1, 'Interested for beauty campaign collaboration', 1),
(8, 2, 'Excellent for commercial dance project', 1);

-- --------------------------------------------------------
-- Insert demo event broadcast
-- --------------------------------------------------------

INSERT INTO `event_broadcasts` (`id`, `client_id`, `title`, `description`, `event_date`, `location`, `latitude`, `longitude`, `radius_km`, `target_filters`, `rate_offered`, `rate_type`, `max_talents`, `status`, `expires_at`) VALUES
(1, 7, 'Luxury Fashion Shoot - Downtown NYC', 'High-end fashion shoot for luxury brand campaign. Looking for experienced models with editorial background.', NOW() + INTERVAL 7 DAY, 'New York, NY', 40.7128, -74.0060, 25.00, '{"category": ["model"], "experience_min": 5, "height_min": "5\'7\"", "skills": ["Editorial", "Runway"]}', 500.00, 'daily', 3, 'active', NOW() + INTERVAL 3 DAY);

-- --------------------------------------------------------
-- Insert demo event targets
-- --------------------------------------------------------

INSERT INTO `event_targets` (`broadcast_id`, `talent_id`, `distance_km`, `match_score`) VALUES
(1, 1, 0.5, 95.50);

-- --------------------------------------------------------
-- Insert demo event response
-- --------------------------------------------------------

INSERT INTO `event_responses` (`broadcast_id`, `talent_id`, `response`, `message`, `rate_counter`, `auto_booking`) VALUES
(1, 1, 'accept', 'I would love to be part of this luxury campaign. My editorial experience aligns perfectly with your requirements.', 500.00, 1);

-- --------------------------------------------------------
-- Insert demo booking
-- --------------------------------------------------------

INSERT INTO `bookings` (`id`, `client_id`, `talent_id`, `event_broadcast_id`, `title`, `description`, `booking_type`, `status`, `start_date`, `end_date`, `location`, `rate_amount`, `rate_type`, `total_amount`, `commission_rate`, `commission_amount`) VALUES
(1, 7, 1, 1, 'Luxury Fashion Shoot - Downtown NYC', 'High-end fashion shoot for luxury brand campaign featuring Sarah Chen', 'broadcast_response', 'confirmed', NOW() + INTERVAL 7 DAY, NOW() + INTERVAL 7 DAY + INTERVAL 8 HOUR, 'New York, NY', 500.00, 'daily', 500.00, 15.00, 75.00);

-- --------------------------------------------------------
-- Insert demo settings
-- --------------------------------------------------------

INSERT INTO `settings` (`company_id`, `category`, `key_name`, `value`, `is_public`) VALUES
(1, 'general', 'company_name', '"RCE Demo Company"', 1),
(1, 'general', 'timezone', '"America/New_York"', 0),
(1, 'booking', 'default_commission_rate', '15.0', 0),
(1, 'booking', 'auto_approval_threshold', '1000.0', 0),
(1, 'media', 'max_file_size_mb', '100', 0),
(1, 'media', 'require_approval', 'true', 0),
(1, 'broadcast', 'default_radius_km', '50.0', 0),
(1, 'broadcast', 'max_targets_per_broadcast', '100', 0),
(NULL, 'system', 'maintenance_mode', 'false', 1),
(NULL, 'system', 'registration_enabled', 'true', 1);

-- --------------------------------------------------------
-- Insert demo audit log entries
-- --------------------------------------------------------

INSERT INTO `audit_logs` (`user_id`, `company_id`, `action`, `entity_type`, `entity_id`, `new_values`, `ip_address`, `created_at`) VALUES
(2, 1, 'user_created', 'users', 3, '{"name": "Sarah Chen", "email": "sarah.chen@demo.com", "role": "talent"}', '127.0.0.1', NOW() - INTERVAL 2 DAY),
(2, 1, 'media_approved', 'talent_media', 1, '{"status": "approved", "reviewed_by": 2}', '127.0.0.1', NOW() - INTERVAL 1 DAY),
(7, 1, 'broadcast_created', 'event_broadcasts', 1, '{"title": "Luxury Fashion Shoot - Downtown NYC", "status": "active"}', '127.0.0.1', NOW() - INTERVAL 6 HOUR),
(3, 1, 'broadcast_response', 'event_responses', 1, '{"response": "accept", "broadcast_id": 1}', '127.0.0.1', NOW() - INTERVAL 4 HOUR),
(7, 1, 'booking_created', 'bookings', 1, '{"title": "Luxury Fashion Shoot - Downtown NYC", "status": "confirmed"}', '127.0.0.1', NOW() - INTERVAL 2 HOUR);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- --------------------------------------------------------
-- Demo Login Credentials Summary
-- --------------------------------------------------------

/*
DEMO LOGIN CREDENTIALS:

Super Admin Portal (/saportal/login):
- Username: superadmin
- Password: admin123
- Security Token: rce-admin-2024

Tenant Admin:
- Username: demoadmin
- Password: demo123

Talents:
- Username: sarahchen, Password: talent123
- Username: marcusj, Password: talent123
- Username: emmar, Password: talent123
- Username: davidkim, Password: talent123

Clients:
- Username: eliteprod, Password: client123
- Username: fashionfw, Password: client123

Note: All passwords are hashed using PHP's password_hash() function.
The actual password for all demo accounts is the same as shown above.
*/
