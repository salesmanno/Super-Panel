-- MySQL Export from PostgreSQL
-- Generated: 2025-10-13 19:31:58

SET FOREIGN_KEY_CHECKS = 0;

-- Tenant Companies
INSERT INTO tenant_companies (id, name, subdomain, is_active, created_at) VALUES (1, 'Salesman Test AS', 'salesmantest', 1, '2025-10-13 10:40:35.386741');

-- Tenant Staff
INSERT INTO tenant_staff (id, first_name, last_name, email, phone, created_at) VALUES (1, 'Atle', 'Bergsøy', 'atle@bergsoy.no', '+47 123 45 678', '2025-10-13 10:40:44.390772');

-- Tenant Admins
INSERT INTO tenant_admins (id, username, password_hash, company_id, tenant_staff_id, is_active, last_login, created_at) VALUES (1, 'Atle', '$2y$12$vM.V8MLJ.snN3rPi0XxJOOjxiJMqfXxOCVRDqlnnoksi5oStwzElO', 1, 1, 1, '2025-10-13 19:29:32.537551', '2025-10-13 10:40:55.46778');

-- Modules
INSERT INTO modules (module_id, name, description, is_active, created_at) VALUES (1, 'sales', 'Product management and sales tracking', 1, '2025-08-14 09:50:13.656366');
INSERT INTO modules (module_id, name, description, is_active, created_at) VALUES (2, 'webmail', 'Email configuration and sending', 1, '2025-08-14 09:50:13.656366');
INSERT INTO modules (module_id, name, description, is_active, created_at) VALUES (3, 'reservations', 'Resource booking system', 1, '2025-08-14 09:50:13.656366');
INSERT INTO modules (module_id, name, description, is_active, created_at) VALUES (4, 'economy', 'Financial transaction tracking', 1, '2025-08-14 09:50:13.656366');
INSERT INTO modules (module_id, name, description, is_active, created_at) VALUES (5, 'membership', 'Member management system', 1, '2025-08-14 09:50:13.656366');
INSERT INTO modules (module_id, name, description, is_active, created_at) VALUES (6, 'rental', 'Equipment and item rental', 1, '2025-08-14 09:50:13.656366');
INSERT INTO modules (module_id, name, description, is_active, created_at) VALUES (7, 'events', 'Event management with registration', 1, '2025-08-14 09:50:13.656366');
INSERT INTO modules (module_id, name, description, is_active, created_at) VALUES (8, 'marketplace', 'Item listing and trading platform', 1, '2025-08-14 09:50:13.656366');

-- Tenant Admin Modules
INSERT INTO tenant_admin_modules (id, tenant_admin_id, module_id, granted_by) VALUES (1, 1, 1, NULL);
INSERT INTO tenant_admin_modules (id, tenant_admin_id, module_id, granted_by) VALUES (2, 1, 2, NULL);
INSERT INTO tenant_admin_modules (id, tenant_admin_id, module_id, granted_by) VALUES (3, 1, 3, NULL);
INSERT INTO tenant_admin_modules (id, tenant_admin_id, module_id, granted_by) VALUES (4, 1, 4, NULL);
INSERT INTO tenant_admin_modules (id, tenant_admin_id, module_id, granted_by) VALUES (5, 1, 5, NULL);
INSERT INTO tenant_admin_modules (id, tenant_admin_id, module_id, granted_by) VALUES (6, 1, 6, NULL);
INSERT INTO tenant_admin_modules (id, tenant_admin_id, module_id, granted_by) VALUES (7, 1, 7, NULL);
INSERT INTO tenant_admin_modules (id, tenant_admin_id, module_id, granted_by) VALUES (8, 1, 8, NULL);

-- Tenant Company Modules
INSERT INTO tenant_company_modules (id, company_id, module_id, is_active) VALUES (1, 1, 1, 1);
INSERT INTO tenant_company_modules (id, company_id, module_id, is_active) VALUES (2, 1, 2, 1);
INSERT INTO tenant_company_modules (id, company_id, module_id, is_active) VALUES (3, 1, 3, 1);
INSERT INTO tenant_company_modules (id, company_id, module_id, is_active) VALUES (4, 1, 4, 1);
INSERT INTO tenant_company_modules (id, company_id, module_id, is_active) VALUES (5, 1, 5, 1);
INSERT INTO tenant_company_modules (id, company_id, module_id, is_active) VALUES (6, 1, 6, 1);
INSERT INTO tenant_company_modules (id, company_id, module_id, is_active) VALUES (7, 1, 7, 1);
INSERT INTO tenant_company_modules (id, company_id, module_id, is_active) VALUES (8, 1, 8, 1);

SET FOREIGN_KEY_CHECKS = 1;
