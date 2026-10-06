# Dynamic Routing + Workflow MVC System

PHP + MySQL MVC starter application with database-driven routing, dynamic panels, configurable workflows, RBAC, and audit logs.

## Architecture

```text
routing/
├── app/
│   ├── Controllers/        # HTTP request handlers
│   ├── Models/             # Database queries and persistence
│   ├── Views/              # Presentation/templates only
│   ├── Core/               # Router, DB connection, auth, RBAC, CSRF, view renderer
│   └── Services/           # Business logic for panels, workflows, auditing
├── config/
│   └── config.php
├── database/
│   └── schema.sql
├── public/
│   ├── index.php           # Front Controller
│   ├── .htaccess
│   └── assets/
├── routes/
│   └── web.php
├── scripts/
│   └── create_developer.php
└── bootstrap.php
```

The old `src/Core` structure has been retired. Application classes now live under `app/` to keep the MVC boundaries clear.

## Features

- Front-controller routing through `public/index.php`
- Static routes in `routes/web.php`
- Dynamic database routes stored in `routes`
- Dynamic database-driven panels and form fields
- Dynamic workflows, steps, transitions, history, and permissions
- User roles and granular permissions
- `developer` role has trusted full-access bypass
- Session login using `password_hash()` and `password_verify()`
- CSRF protection for POST requests
- Audit logs
- Responsive admin interface

## Install using XAMPP

1. Clone into:

```text
C:\xampp\htdocs\routing
```

2. Import:

```text
database/schema.sql
```

3. Check MySQL settings in `config/config.php`.

4. Create your first developer account:

```bash
php scripts/create_developer.php developer developer@example.com YourStrongPassword
```

5. Open:

```text
http://localhost/routing/public/
```

## MVC responsibilities

**Controllers** receive requests, validate access, call Models/Services, and select Views. They should not contain SQL.

**Models** contain database queries and persistence logic.

**Views** render HTML only and receive prepared data from Controllers.

**Services** contain reusable business rules such as workflow transitions and dynamic-panel operations.

**Core** contains framework infrastructure such as routing, authentication, authorization, CSRF, database connection, and rendering.

## Roles

Starter roles:

- `developer` — unrestricted trusted access through the RBAC bypass
- `admin` — all seeded permissions
- `approver` — workflow review/action access
- `user` — standard panel and workflow submission access

## Dynamic routes

Database route examples seeded by `schema.sql`:

```text
/requests            -> panel: service-requests
/document-approval   -> workflow: document-approval
```

New aliases can be added through the routes table without modifying `routes/web.php`.

## Production hardening

Before public deployment, configure HTTPS, secure session cookie flags, environment-based secrets, rate limiting, login throttling, password reset/email verification, database backups, centralized exception logging, and stricter validation for every dynamic field type.
