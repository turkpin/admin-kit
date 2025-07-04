-- AdminKit MySQL Initialization Script
-- Creates database structure and initial data

-- Set character set and collation
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Create AdminKit database if not exists
CREATE DATABASE IF NOT EXISTS `adminkit` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `adminkit`;

-- =====================================================
-- Users Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `email_verified_at` timestamp NULL DEFAULT NULL,
    `two_factor_secret` varchar(255) DEFAULT NULL,
    `two_factor_recovery_codes` text DEFAULT NULL,
    `last_login_at` timestamp NULL DEFAULT NULL,
    `login_attempts` int(11) DEFAULT 0,
    `locked_until` timestamp NULL DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email` (`email`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Roles Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `roles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL UNIQUE,
    `display_name` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `is_system` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Permissions Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL UNIQUE,
    `display_name` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `category` varchar(255) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_name` (`name`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- User Roles Junction Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `user_roles` (
    `user_id` int(11) NOT NULL,
    `role_id` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `role_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Role Permissions Junction Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id` int(11) NOT NULL,
    `permission_id` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Jobs Table (Queue System)
-- =====================================================
CREATE TABLE IF NOT EXISTS `jobs` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `queue` varchar(255) NOT NULL DEFAULT 'default',
    `payload` longtext NOT NULL,
    `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
    `reserved_at` int(10) unsigned DEFAULT NULL,
    `available_at` int(10) unsigned NOT NULL,
    `created_at` int(10) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Audit Log Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `event` varchar(255) NOT NULL,
    `auditable_type` varchar(255) NOT NULL,
    `auditable_id` bigint(20) unsigned NOT NULL,
    `old_values` json DEFAULT NULL,
    `new_values` json DEFAULT NULL,
    `url` text DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `tags` varchar(255) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_auditable` (`auditable_type`, `auditable_id`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Notifications Table
-- =====================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` char(36) NOT NULL,
    `type` varchar(255) NOT NULL,
    `notifiable_type` varchar(255) NOT NULL,
    `notifiable_id` bigint(20) unsigned NOT NULL,
    `data` json NOT NULL,
    `read_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`, `notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insert Initial Data
-- =====================================================

-- Insert default roles
INSERT IGNORE INTO `roles` (`name`, `display_name`, `description`, `is_system`) VALUES
('super_admin', 'Super Admin', 'Full system access with all permissions', 1),
('admin', 'Administrator', 'Administrative access with most permissions', 1),
('editor', 'Editor', 'Content management and editing permissions', 1),
('user', 'User', 'Basic user permissions', 1);

-- Insert default permissions
INSERT IGNORE INTO `permissions` (`name`, `display_name`, `description`, `category`) VALUES
-- User Management
('users.view', 'View Users', 'View user listings and details', 'User Management'),
('users.create', 'Create Users', 'Create new users', 'User Management'),
('users.edit', 'Edit Users', 'Edit existing users', 'User Management'),
('users.delete', 'Delete Users', 'Delete users', 'User Management'),

-- Role Management
('roles.view', 'View Roles', 'View role listings and details', 'Role Management'),
('roles.create', 'Create Roles', 'Create new roles', 'Role Management'),
('roles.edit', 'Edit Roles', 'Edit existing roles', 'Role Management'),
('roles.delete', 'Delete Roles', 'Delete roles', 'Role Management'),

-- System Administration
('system.settings', 'System Settings', 'Access system configuration', 'System'),
('system.logs', 'View Logs', 'Access system logs and audit trails', 'System'),
('system.maintenance', 'System Maintenance', 'Perform system maintenance tasks', 'System'),

-- Dashboard
('dashboard.view', 'View Dashboard', 'Access admin dashboard', 'Dashboard');

-- Assign all permissions to super_admin role
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.name = 'super_admin';

-- Assign basic permissions to admin role
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.name = 'admin' 
AND p.name IN ('users.view', 'users.create', 'users.edit', 'roles.view', 'dashboard.view');

-- Create default admin user (password: admin123)
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `is_active`, `email_verified_at`) VALUES
('Admin User', 'admin@adminkit.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW());

-- Assign super_admin role to default admin user
INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id, r.id 
FROM `users` u 
CROSS JOIN `roles` r 
WHERE u.email = 'admin@adminkit.local' 
AND r.name = 'super_admin';

-- Reset foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Show completion message
SELECT 'AdminKit database initialized successfully!' as message;
