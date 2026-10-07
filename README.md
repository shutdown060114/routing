# Dynamic Routing + Workflow MVC System

PHP + MySQL MVC starter application with database-driven routing, dynamic panels, configurable workflows, RBAC, per-panel access, and audit logs.

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
│   ├── schema.sql
│   └── migrations/
│       └── 002_dynamic_panel_builder.sql
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

## Dynamic Panel Builder

Developer/Admin users can open:

```text
/developer/panels/create
```

From one screen they can define:

- panel name and slug
- custom fields and field types
- required fields
- select options
- specific users allowed to access the panel
- specific roles allowed to access the panel
- all-user or restricted access mode
- optional workflow steps

When the panel is saved, the system automatically generates:

1. A physical MySQL table named `dyn_<panel_slug>`.
2. `panels` metadata.
3. `panel_fields` metadata.
4. User/role access rows.
5. A dynamic route under `/module/<slug>`.
6. An optional workflow definition, steps, transitions and route.
7. A workflow instance automatically whenever a new record is added to a workflow-enabled panel.

The `developer` role bypasses all panel and workflow access restrictions.

## Features

- Front-controller routing through `public/index.php`
- Static routes in `routes/web.php`
- Dynamic database routes stored in `routes`
- Dynamic panel/form builder
- Automatic database-table generation for new panels
- Per-panel user and role access
- Dynamic workflows, steps, transitions, history, and permissions
- Auto-start workflow per newly created panel record
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

2. Import the base database:

```text
database/schema.sql
```

3. Import the Panel Builder migration:

```text
database/migrations/002_dynamic_panel_builder.sql
```

4. Check MySQL settings in `config/config.php`.

5. Create your first developer account:

```bash
php scripts/create_developer.php developer developer@example.com YourStrongPassword
```

6. Open:

```text
http://localhost/routing/public/
```

## MVC responsibilities

**Controllers** receive requests, validate access, call Models/Services, and select Views. They should not contain SQL.

**Models** contain database queries and persistence logic.

**Views** render HTML only and receive prepared data from Controllers.

**Services** contain reusable business rules such as workflow transitions and panel generation.

**Core** contains framework infrastructure such as routing, authentication, authorization, CSRF, database connection, and rendering.

## Roles

Starter roles:

- `developer` — unrestricted trusted access through the RBAC bypass
- `admin` — administrative permissions including panel builder access after migration
- `approver` — workflow review/action access
- `user` — standard panel and workflow submission access

## Dynamic routes

Database route examples:

```text
/requests                 -> panel: service-requests
/module/travel-orders     -> generated panel
/module/travel-orders/workflow -> generated workflow
```

New aliases can be added without modifying `routes/web.php`.

## Production hardening

Before public deployment, configure HTTPS, secure session cookie flags, environment-based secrets, rate limiting, login throttling, password reset/email verification, database backups, centralized exception logging, and stricter validation for every dynamic field type.
