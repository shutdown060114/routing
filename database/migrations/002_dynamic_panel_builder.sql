USE routing_system;

ALTER TABLE panels
    ADD COLUMN access_mode ENUM('all','restricted') NOT NULL DEFAULT 'all' AFTER sort_order,
    ADD COLUMN workflow_slug VARCHAR(120) NULL AFTER access_mode;

CREATE TABLE panel_user_access (
    panel_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    can_view TINYINT(1) NOT NULL DEFAULT 1,
    can_create TINYINT(1) NOT NULL DEFAULT 0,
    can_edit TINYINT(1) NOT NULL DEFAULT 0,
    can_delete TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY(panel_id,user_id),
    CONSTRAINT fk_panel_user_access_panel FOREIGN KEY(panel_id) REFERENCES panels(id) ON DELETE CASCADE,
    CONSTRAINT fk_panel_user_access_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE panel_role_access (
    panel_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    can_view TINYINT(1) NOT NULL DEFAULT 1,
    can_create TINYINT(1) NOT NULL DEFAULT 0,
    can_edit TINYINT(1) NOT NULL DEFAULT 0,
    can_delete TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY(panel_id,role_id),
    CONSTRAINT fk_panel_role_access_panel FOREIGN KEY(panel_id) REFERENCES panels(id) ON DELETE CASCADE,
    CONSTRAINT fk_panel_role_access_role FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO permissions(name,slug,description)
VALUES ('Manage Dynamic Panels','panels.manage','Create and configure dynamic panels, database tables and workflows');

INSERT IGNORE INTO role_permissions(role_id,permission_id)
SELECT r.id,p.id
FROM roles r
JOIN permissions p ON p.slug='panels.manage'
WHERE r.slug='admin';
