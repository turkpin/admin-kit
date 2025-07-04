-- AdminKit RBAC Seed Data
-- Insert default roles, permissions and admin user

-- Insert default roles
INSERT IGNORE INTO admin_roles (name, description, created_at) VALUES
('super_admin', 'Super Administrator - Full system access', NOW()),
('admin', 'Administrator - Manage users and content', NOW()),
('editor', 'Editor - Manage content only', NOW()),
('viewer', 'Viewer - Read-only access', NOW());

-- Insert default permissions
INSERT IGNORE INTO admin_permissions (name, description, resource, action, created_at) VALUES
-- Dashboard permissions
('dashboard.index', 'View dashboard', 'dashboard', 'index', NOW()),

-- User management permissions
('user.index', 'List users', 'user', 'index', NOW()),
('user.show', 'View user details', 'user', 'show', NOW()),
('user.new', 'Show new user form', 'user', 'new', NOW()),
('user.create', 'Create new user', 'user', 'create', NOW()),
('user.edit', 'Show edit user form', 'user', 'edit', NOW()),
('user.update', 'Update user', 'user', 'update', NOW()),
('user.delete', 'Delete user', 'user', 'delete', NOW()),
('user.*', 'All user operations', 'user', '*', NOW()),

-- Role management permissions
('role.index', 'List roles', 'role', 'index', NOW()),
('role.show', 'View role details', 'role', 'show', NOW()),
('role.new', 'Show new role form', 'role', 'new', NOW()),
('role.create', 'Create new role', 'role', 'create', NOW()),
('role.edit', 'Show edit role form', 'role', 'edit', NOW()),
('role.update', 'Update role', 'role', 'update', NOW()),
('role.delete', 'Delete role', 'role', 'delete', NOW()),
('role.*', 'All role operations', 'role', '*', NOW()),

-- Permission management permissions
('permission.index', 'List permissions', 'permission', 'index', NOW()),
('permission.show', 'View permission details', 'permission', 'show', NOW()),
('permission.new', 'Show new permission form', 'permission', 'new', NOW()),
('permission.create', 'Create new permission', 'permission', 'create', NOW()),
('permission.edit', 'Show edit permission form', 'permission', 'edit', NOW()),
('permission.update', 'Update permission', 'permission', 'update', NOW()),
('permission.delete', 'Delete permission', 'permission', 'delete', NOW()),
('permission.*', 'All permission operations', 'permission', '*', NOW()),

-- Content management permissions
('content.index', 'List content', 'content', 'index', NOW()),
('content.show', 'View content details', 'content', 'show', NOW()),
('content.new', 'Show new content form', 'content', 'new', NOW()),
('content.create', 'Create new content', 'content', 'create', NOW()),
('content.edit', 'Show edit content form', 'content', 'edit', NOW()),
('content.update', 'Update content', 'content', 'update', NOW()),
('content.delete', 'Delete content', 'content', 'delete', NOW()),
('content.*', 'All content operations', 'content', '*', NOW()),

-- Media management permissions
('media.index', 'List media files', 'media', 'index', NOW()),
('media.show', 'View media details', 'media', 'show', NOW()),
('media.upload', 'Upload media files', 'media', 'upload', NOW()),
('media.delete', 'Delete media files', 'media', 'delete', NOW()),
('media.*', 'All media operations', 'media', '*', NOW()),

-- Settings permissions
('settings.index', 'View settings', 'settings', 'index', NOW()),
('settings.update', 'Update settings', 'settings', 'update', NOW()),
('settings.*', 'All settings operations', 'settings', '*', NOW()),

-- Report permissions
('report.index', 'View reports', 'report', 'index', NOW()),
('report.export', 'Export reports', 'report', 'export', NOW()),
('report.*', 'All report operations', 'report', '*', NOW()),

-- Import/Export permissions
('import.index', 'View import page', 'import', 'index', NOW()),
('import.upload', 'Upload import files', 'import', 'upload', NOW()),
('export.index', 'View export page', 'export', 'index', NOW()),
('export.download', 'Download export files', 'export', 'download', NOW());

-- Assign permissions to roles
-- Super Admin gets all permissions (handled in code with hasRole('super_admin'))

-- Admin role permissions
INSERT IGNORE INTO admin_role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM admin_roles r, admin_permissions p
WHERE r.name = 'admin' AND p.name IN (
    'dashboard.index',
    'user.*', 'role.*', 'permission.*',
    'content.*', 'media.*',
    'settings.*', 'report.*',
    'import.index', 'import.upload',
    'export.index', 'export.download'
);

-- Editor role permissions  
INSERT IGNORE INTO admin_role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM admin_roles r, admin_permissions p
WHERE r.name = 'editor' AND p.name IN (
    'dashboard.index',
    'content.*', 'media.*',
    'user.index', 'user.show',
    'export.index', 'export.download'
);

-- Viewer role permissions
INSERT IGNORE INTO admin_role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM admin_roles r, admin_permissions p
WHERE r.name = 'viewer' AND p.name IN (
    'dashboard.index',
    'content.index', 'content.show',
    'media.index', 'media.show',
    'user.index', 'user.show',
    'report.index'
);

-- Create default super admin user
-- Password: admin123 (hashed)
INSERT IGNORE INTO admin_users (name, email, password, is_active, created_at) VALUES
('Super Admin', 'admin@adminkit.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 1, NOW());

-- Assign super_admin role to the default user
INSERT IGNORE INTO admin_user_roles (user_id, role_id, created_at)
SELECT u.id, r.id, NOW()
FROM admin_users u, admin_roles r
WHERE u.email = 'admin@adminkit.com' AND r.name = 'super_admin';

-- Create sample admin user
-- Password: admin123 (hashed)
INSERT IGNORE INTO admin_users (name, email, password, is_active, created_at) VALUES
('Admin User', 'admin@example.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 1, NOW());

-- Assign admin role to the sample user
INSERT IGNORE INTO admin_user_roles (user_id, role_id, created_at)
SELECT u.id, r.id, NOW()
FROM admin_users u, admin_roles r
WHERE u.email = 'admin@example.com' AND r.name = 'admin';

-- Create sample editor user
-- Password: editor123 (hashed)
INSERT IGNORE INTO admin_users (name, email, password, is_active, created_at) VALUES
('Editor User', 'editor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW());

-- Assign editor role to the sample user
INSERT IGNORE INTO admin_user_roles (user_id, role_id, created_at)
SELECT u.id, r.id, NOW()
FROM admin_users u, admin_roles r
WHERE u.email = 'editor@example.com' AND r.name = 'editor';
