CREATE DATABASE IF NOT EXISTS routing_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE routing_system;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS workflow_history;
DROP TABLE IF EXISTS workflow_instances;
DROP TABLE IF EXISTS workflow_transitions;
DROP TABLE IF EXISTS workflow_steps;
DROP TABLE IF EXISTS workflows;
DROP TABLE IF EXISTS panel_fields;
DROP TABLE IF EXISTS panels;
DROP TABLE IF EXISTS routes;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS service_requests;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    display_name VARCHAR(160) NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY(user_id,role_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY(role_id,permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE routes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    method VARCHAR(10) NOT NULL DEFAULT 'GET',
    path VARCHAR(190) NOT NULL,
    target_type ENUM('panel','workflow') NOT NULL,
    target_value VARCHAR(120) NOT NULL,
    permission VARCHAR(120) NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_route(method,path)
) ENGINE=InnoDB;

CREATE TABLE panels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    icon VARCHAR(30) NULL,
    source_table VARCHAR(120) NOT NULL,
    view_permission VARCHAR(120) NULL,
    create_permission VARCHAR(120) NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE panel_fields (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    panel_id BIGINT UNSIGNED NOT NULL,
    field_name VARCHAR(120) NOT NULL,
    label VARCHAR(160) NOT NULL,
    input_type ENUM('text','textarea','number','date','email','select') NOT NULL DEFAULT 'text',
    options_json JSON NULL,
    required TINYINT(1) NOT NULL DEFAULT 0,
    fillable TINYINT(1) NOT NULL DEFAULT 1,
    list_visible TINYINT(1) NOT NULL DEFAULT 1,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_panel_fields_panel FOREIGN KEY(panel_id) REFERENCES panels(id) ON DELETE CASCADE,
    UNIQUE KEY uq_panel_field(panel_id,field_name)
) ENGINE=InnoDB;

CREATE TABLE workflows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(140) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE workflow_steps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_workflow_steps_workflow FOREIGN KEY(workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    UNIQUE KEY uq_workflow_step(workflow_id,slug)
) ENGINE=InnoDB;

CREATE TABLE workflow_transitions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_id BIGINT UNSIGNED NOT NULL,
    from_step_id BIGINT UNSIGNED NOT NULL,
    to_step_id BIGINT UNSIGNED NOT NULL,
    action_label VARCHAR(120) NOT NULL,
    permission VARCHAR(120) NULL,
    closes_instance TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_transition_workflow FOREIGN KEY(workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    CONSTRAINT fk_transition_from FOREIGN KEY(from_step_id) REFERENCES workflow_steps(id) ON DELETE CASCADE,
    CONSTRAINT fk_transition_to FOREIGN KEY(to_step_id) REFERENCES workflow_steps(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE workflow_instances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_id BIGINT UNSIGNED NOT NULL,
    current_step_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    payload JSON NULL,
    status ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_instance_workflow FOREIGN KEY(workflow_id) REFERENCES workflows(id),
    CONSTRAINT fk_instance_step FOREIGN KEY(current_step_id) REFERENCES workflow_steps(id),
    CONSTRAINT fk_instance_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE workflow_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instance_id BIGINT UNSIGNED NOT NULL,
    from_step_id BIGINT UNSIGNED NULL,
    to_step_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(120) NOT NULL,
    comment TEXT NULL,
    acted_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_instance FOREIGN KEY(instance_id) REFERENCES workflow_instances(id) ON DELETE CASCADE,
    CONSTRAINT fk_history_from FOREIGN KEY(from_step_id) REFERENCES workflow_steps(id) ON DELETE SET NULL,
    CONSTRAINT fk_history_to FOREIGN KEY(to_step_id) REFERENCES workflow_steps(id),
    CONSTRAINT fk_history_user FOREIGN KEY(acted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(160) NOT NULL,
    entity_type VARCHAR(120) NULL,
    entity_id BIGINT NULL,
    metadata JSON NULL,
    ip_address VARCHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user(user_id),
    INDEX idx_audit_action(action),
    CONSTRAINT fk_audit_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE service_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(50) NOT NULL UNIQUE,
    requester_name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NULL,
    request_type VARCHAR(100) NOT NULL,
    details TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'New',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO roles(name,slug,description) VALUES
('Developer','developer','Trusted system developer with unconditional full access'),
('Administrator','admin','System administrator'),
('Approver','approver','Workflow reviewer and approver'),
('User','user','Standard authenticated user');

INSERT INTO permissions(name,slug) VALUES
('View Panels','panel.view'),
('Create Panel Records','panel.create'),
('View Workflows','workflow.view'),
('Start Workflows','workflow.start'),
('Act on Workflows','workflow.action'),
('Manage Users','users.manage'),
('Manage Roles','roles.manage'),
('Manage Routes','routes.manage');

INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p WHERE r.slug='admin';

INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.slug IN ('workflow.view','workflow.action') WHERE r.slug='approver';

INSERT INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id FROM roles r JOIN permissions p ON p.slug IN ('panel.view','panel.create','workflow.view','workflow.start') WHERE r.slug='user';

INSERT INTO panels(name,slug,description,icon,source_table,view_permission,create_permission,sort_order) VALUES
('Service Requests','service-requests','Dynamic service request records','📨','service_requests','panel.view','panel.create',10),
('System Routes','system-routes','Database driven URL aliases','⇢','routes','routes.manage','routes.manage',90),
('Users','users','Registered system users','👤','users','users.manage',NULL,91),
('Roles','roles','System roles','🔐','roles','roles.manage','roles.manage',92);

SET @request_panel=(SELECT id FROM panels WHERE slug='service-requests');
INSERT INTO panel_fields(panel_id,field_name,label,input_type,required,fillable,list_visible,sort_order) VALUES
(@request_panel,'reference_no','Reference No.','text',1,1,1,1),
(@request_panel,'requester_name','Requester Name','text',1,1,1,2),
(@request_panel,'email','Email','email',0,1,1,3),
(@request_panel,'request_type','Request Type','text',1,1,1,4),
(@request_panel,'details','Details','textarea',0,1,0,5),
(@request_panel,'status','Status','select',1,1,1,6),
(@request_panel,'created_at','Created','text',0,0,1,7);
UPDATE panel_fields SET options_json=JSON_OBJECT('New','New','Processing','Processing','Completed','Completed') WHERE panel_id=@request_panel AND field_name='status';

SET @routes_panel=(SELECT id FROM panels WHERE slug='system-routes');
INSERT INTO panel_fields(panel_id,field_name,label,input_type,required,fillable,list_visible,sort_order) VALUES
(@routes_panel,'method','Method','select',1,1,1,1),
(@routes_panel,'path','Path','text',1,1,1,2),
(@routes_panel,'target_type','Target Type','select',1,1,1,3),
(@routes_panel,'target_value','Target Slug','text',1,1,1,4),
(@routes_panel,'permission','Permission','text',0,1,1,5),
(@routes_panel,'enabled','Enabled','number',1,1,1,6);
UPDATE panel_fields SET options_json=JSON_OBJECT('GET','GET','POST','POST') WHERE panel_id=@routes_panel AND field_name='method';
UPDATE panel_fields SET options_json=JSON_OBJECT('panel','Panel','workflow','Workflow') WHERE panel_id=@routes_panel AND field_name='target_type';

SET @users_panel=(SELECT id FROM panels WHERE slug='users');
INSERT INTO panel_fields(panel_id,field_name,label,input_type,required,fillable,list_visible,sort_order) VALUES
(@users_panel,'id','ID','number',0,0,1,1),
(@users_panel,'username','Username','text',0,0,1,2),
(@users_panel,'email','Email','email',0,0,1,3),
(@users_panel,'display_name','Display Name','text',0,0,1,4),
(@users_panel,'is_active','Active','number',0,0,1,5),
(@users_panel,'created_at','Created','text',0,0,1,6);

SET @roles_panel=(SELECT id FROM panels WHERE slug='roles');
INSERT INTO panel_fields(panel_id,field_name,label,input_type,required,fillable,list_visible,sort_order) VALUES
(@roles_panel,'name','Role Name','text',1,1,1,1),
(@roles_panel,'slug','Slug','text',1,1,1,2),
(@roles_panel,'description','Description','text',0,1,1,3);

INSERT INTO routes(method,path,target_type,target_value,permission) VALUES
('GET','/requests','panel','service-requests','panel.view'),
('GET','/document-approval','workflow','document-approval','workflow.view');

INSERT INTO workflows(name,slug,description) VALUES
('Document Approval','document-approval','Sample configurable submission and approval workflow');

SET @workflow=(SELECT id FROM workflows WHERE slug='document-approval');
INSERT INTO workflow_steps(workflow_id,name,slug,sort_order) VALUES
(@workflow,'Submitted','submitted',10),
(@workflow,'For Review','review',20),
(@workflow,'Approved','approved',30),
(@workflow,'Rejected','rejected',40);

SET @submitted=(SELECT id FROM workflow_steps WHERE workflow_id=@workflow AND slug='submitted');
SET @review=(SELECT id FROM workflow_steps WHERE workflow_id=@workflow AND slug='review');
SET @approved=(SELECT id FROM workflow_steps WHERE workflow_id=@workflow AND slug='approved');
SET @rejected=(SELECT id FROM workflow_steps WHERE workflow_id=@workflow AND slug='rejected');

INSERT INTO workflow_transitions(workflow_id,from_step_id,to_step_id,action_label,permission,closes_instance) VALUES
(@workflow,@submitted,@review,'Submit for Review','workflow.start',0),
(@workflow,@review,@approved,'Approve','workflow.action',1),
(@workflow,@review,@rejected,'Reject','workflow.action',1);
