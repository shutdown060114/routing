# Dynamic Routing, Panel & Workflow System

PHP + MySQL starter system with database-driven routing, dynamic panels, workflows, RBAC, and audit logs.

## Main features

- Clean URL router with route parameters
- Database-driven route aliases
- Dynamic panels and form fields stored in MySQL
- Dynamic workflow definitions, steps, and transitions
- User roles and permissions
- `developer` role bypasses all permission checks
- Session authentication with `password_hash()` / `password_verify()`
- CSRF protection for POST actions
- Audit logs
- Responsive admin dashboard

## Requirements

- PHP 8.1+
- MySQL 8 / MariaDB 10.4+
- Apache with `mod_rewrite`
- PDO MySQL extension

## Install with XAMPP

1. Clone this repository into `C:\xampp\htdocs\routing`.
2. Create a MySQL database named `routing_system`.
3. Import `database/schema.sql` in phpMyAdmin.
4. Edit `config/config.php` if your MySQL username/password is different.
5. Run from the project folder:

```bash
php scripts/create_developer.php developer developer@example.com YourStrongPassword
```

6. Point Apache to the `public` folder, or open:

```text
http://localhost/routing/public/
```

## Default architecture

```text
app/Controllers/     Controllers
config/              Application/database config
database/            SQL schema and starter data
public/              Web root
routes/              Static application routes
scripts/             CLI setup tools
src/Core/            Router, auth, RBAC, workflow engine, etc.
views/               PHP views
```

## Developer role

The `developer` role has full system access in `Rbac::can()` even if a permission was not explicitly assigned. Use this role only for trusted system developers/administrators.

## Dynamic routing

Rows in the `routes` table can create URL aliases without editing PHP source. A dynamic route can target a panel or workflow definition.

Example:

```text
/service-requests -> panel: service-requests
/document-approval -> workflow: document-approval
```

## Security note

This repository is a solid starter, but before internet-facing production deployment you should add HTTPS, secure cookie settings, rate limiting, password reset/email verification, stricter validation, centralized exception logging, and deployment-specific secret management.
