# Cronos Bakery Builder

A platform specializing in the customization, production, and sale of artisanal
cakes.

This repository contains the **Phase 1** foundation: an enterprise-grade,
Domain-Driven Design architecture for a Laravel 12 API and a Vue 3 SPA, fully
containerized for local development.

---

## Tech stack

| Layer        | Technology                                              |
| ------------ | ------------------------------------------------------- |
| Backend      | Laravel 12 (PHP 8.4), PSR-12, Sanctum                   |
| Frontend     | Vue 3 + TypeScript + Vite, Pinia, Vue Router, Axios     |
| Database     | PostgreSQL 16                                            |
| Cache/Queue  | Redis 7                                                  |
| Object store | MinIO (S3-compatible)                                    |
| Mail         | Mailpit                                                  |
| Web server   | Nginx + PHP-FPM                                          |

---

## Architecture overview

The backend follows **Domain-Driven Design** with decoupled, modular bounded
contexts. Each module is self-contained and wired into the framework through its
own service provider.

```
backend/app/
├── Domains/            # Shared domain primitives reused across modules
│   ├── Enums/          #   e.g. Currency
│   └── ValueObjects/   #   e.g. Money
├── Shared/             # Shared kernel — base abstractions for every module
│   ├── Application/DTO/             # DataTransferObject base
│   ├── Domain/Contracts/           # RepositoryInterface
│   ├── Domain/Events/              # DomainEvent base
│   ├── Infrastructure/Repositories/# AbstractEloquentRepository
│   └── Providers/                  # ModuleServiceProvider base
└── Modules/            # Feature modules (bounded contexts)
    ├── Authentication
    ├── CMS
    ├── Catalog
    ├── ProductBuilder
    ├── Orders
    ├── Payments
    ├── Calendar
    ├── Notifications
    └── Administration
```

### Anatomy of a module

Every module follows the same layered layout (the **Catalog** and
**Authentication** modules are fully implemented as reference; the rest are
scaffolded and ready for development):

```
Modules/<Module>/
├── Application/
│   ├── DTO/            # Immutable input objects (extend DataTransferObject)
│   └── Services/       # Use-case orchestration (Service Layer)
├── Domain/
│   ├── Models/         # Eloquent aggregate roots
│   ├── Repositories/   # Repository interfaces (persistence contracts)
│   ├── Events/         # Domain events
│   └── Policies/       # Authorization rules
├── Infrastructure/
│   ├── Repositories/   # Eloquent repository implementations
│   ├── Database/       # Migrations + factories
│   ├── Jobs/           # Queued jobs
│   └── Listeners/      # Event listeners (often queued)
├── Presentation/
│   └── Http/           # Controllers, Form Requests, API Resources, routes.php
└── Providers/          # <Module>ServiceProvider (routes, bindings, policies)
```

This realises the patterns requested for Phase 1: **Repository Pattern**,
**Service Layer**, **DTOs**, **Policies**, **Events**, **Queues** and **Jobs**.

The frontend mirrors the same modular decomposition:

```
frontend/src/
├── modules/<module>/   # Per-module components, pages, stores, services, types, routes
├── components/         # Shared UI components
├── layouts/            # DefaultLayout, AdminLayout, AuthLayout
├── pages/              # Top-level pages (Home, NotFound)
├── stores/             # Global Pinia stores (auth)
├── services/           # Shared services (http client)
└── router/             # Route composition
```

---

## Getting started

### With Docker (recommended)

```bash
# 1. Copy environment files
cp .env.example .env                 # docker-compose variables
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# 2. Build & start the stack
docker compose up -d --build

# 3. Generate app key, run migrations & seed
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed
```

### Service endpoints

| Service          | URL                              |
| ---------------- | -------------------------------- |
| API (Nginx)      | http://localhost:8080            |
| API health       | http://localhost:8080/api/status |
| Frontend (Vite)  | http://localhost:5173            |
| MinIO console    | http://localhost:9001            |
| Mailpit UI       | http://localhost:8025            |
| PostgreSQL       | localhost:5432                   |
| Redis            | localhost:6379                   |

The seeder creates a Super Admin (`superadmin@cronos.test` / `password`), one
administrator per role (e.g. `production@cronos.test`, `sales@cronos.test`, …)
and a handful of sample customers — all with the password `password`.

---

## Authentication (Phase 2)

Two fully independent authentication systems, both issuing Sanctum tokens:

### Customers (`/api/auth/*`)

- **Register** with first name, last name, email, phone and password.
- **Social login** via Google, Facebook and Apple (Laravel Socialite).
- **Email verification** (signed links) and **password recovery** (forgot/reset),
  both linking back to the SPA.
- **Profile management**: update details and change password (which revokes
  existing tokens).

### Administrators (`/api/admin/*`)

- **Independent `admin` guard** with its own model, token and password broker.
- **Granular permissions via Spatie Permission** on the `admin` guard, with six
  seeded roles: Super Admin, Administrador, Producción, Ventas, Marketing,
  Repartidor. Super Admin bypasses all checks via a `Gate::before` rule.
- Route protection: `auth:sanctum` + the `admin` middleware, then Spatie's
  `role:` / `permission:` middleware for fine-grained control.

| Method | Endpoint | Purpose |
| ------ | -------- | ------- |
| POST | `/api/auth/register` | Customer registration |
| POST | `/api/auth/login` | Customer login |
| POST | `/api/auth/password/forgot` · `/reset` | Password recovery |
| GET | `/api/auth/social/{provider}/redirect` · `/callback` | Social login |
| GET | `/api/auth/email/verify/{id}/{hash}` | Email verification (signed) |
| GET/PUT | `/api/auth/profile` · `/profile/password` | Profile management |
| POST | `/api/admin/login` · `/logout` | Admin auth (independent guard) |
| GET | `/api/admin/dashboard` | Role-gated example (`super-admin\|administrator`) |
| GET | `/api/admin/catalog/overview` | Permission-gated example (`manage products`) |

Configure social providers in `backend/.env` (`GOOGLE_*`, `FACEBOOK_*`,
`APPLE_*`); see `backend/.env.example`.

---

## Enterprise CMS (Phase 3)

A fully administrable, block-based CMS. Administrators create dynamic,
SEO-aware pages without touching code; the Vue frontend renders them from
stored configuration.

- **Dynamic pages** with title, slug, type (Inicio, Nosotros, Contacto, FAQ,
  Políticas, Blog, Landing), SEO meta (`meta_title` / `meta_description`),
  rich-text content and publication status (draft / published / archived).
- **Page builder** — pages are composed of ordered, configurable blocks:
  **Hero, Banner, Galería, Cards, Texto, Video, CTA, FAQ, Testimonios**. Each
  block carries a free-form `config` payload interpreted by its frontend
  renderer.
- **Reusable section library** — blocks can be saved once and referenced from
  many pages; per-page inline `config` overrides the reusable defaults.
- **Frontend rendering** — `GET /p/:slug` loads the published page and renders
  each block via a type → component registry (`BlockRenderer`), applying SEO
  metadata to the document head.

| Method | Endpoint | Purpose |
| ------ | -------- | ------- |
| GET | `/api/cms/pages` · `/cms/pages/{slug}` | Public published pages (frontend) |
| GET/POST/PUT/DELETE | `/api/admin/cms/pages` | Page CRUD (admin) |
| POST/PUT/DELETE | `/api/admin/cms/pages/{page}/blocks…` | Manage builder blocks |
| PUT | `/api/admin/cms/pages/{page}/blocks/reorder` | Reorder blocks |
| GET/POST/PUT/DELETE | `/api/admin/cms/sections` | Reusable section library |

Admin CMS endpoints require the `admin` guard plus the `manage cms` permission
(granted to Super Admin, Administrador and Marketing).

---

## Theme Builder (Phase 4)

Fully dynamic branding — administrators restyle the storefront without
redeploying. The Vue frontend reads the active configuration from the API and
applies it at runtime.

- **Branding**: logo, favicon, corporate palette (primary, secondary, accent,
  success, warning, danger) and Google Fonts (heading + body). Colours are
  injected as CSS custom properties; fonts are loaded on the fly; the favicon is
  swapped in the document head.
- **Footer**: structured visual editor (columns of links + copyright).
- **Dynamic menus**: location-bound (header/footer) with unlimited nesting
  (e.g. Pasteles → Floral, Moderno, Mini Cakes), rendered by a recursive
  `MenuTree` component.
- **Banners**: administrable per placement (home top/middle, sidebar, catalog),
  with optional scheduling (`starts_at` / `ends_at`); only "live" banners are
  served publicly.
- Multiple themes can coexist; exactly one is active at a time.

| Method | Endpoint | Purpose |
| ------ | -------- | ------- |
| GET | `/api/theme` | Active theme (frontend) |
| GET | `/api/menus/{location}` | Nested menu tree by location |
| GET | `/api/banners/{placement}` | Live banners for a placement |
| GET/POST/PUT | `/api/admin/themes` · `/themes/{id}/activate` | Theme CRUD + activation |
| … | `/api/admin/menus` · `/menus/{menu}/items` | Menus & nested items |
| … | `/api/admin/banners` | Banner management |

Admin Theme Builder endpoints require the `admin` guard plus the `manage theme`
permission (granted to Super Admin, Administrador and Marketing).

---

## Product Builder — central engine (Phase 5)

A fully dynamic configurator: any configurable product (Muse Blanc, Studio Cake,
Coquette Cake, Signature Cake…) is created from the admin panel, and the
frontend generates its configurator automatically — no per-product code.

- **Option types**: `select`, `radio`, `checkbox`, `color`, `image`, `text`,
  `textarea`. Choice types own a list of values; text/textarea capture free
  input (with optional `max_length`).
- **Dynamic pricing**: each option value applies a modifier to the running
  total — **add**, **subtract** or **set** (fija el precio base, e.g. la forma
  determina el precio). Pricing is computed authoritatively server-side.
- **Conditional dependencies**: rules show/hide options based on another
  option's value — e.g. *Si Forma = Domo, mostrar Perlas*. Operators: `equals`,
  `not_equals`, `in`. Hidden options contribute no price and are not validated;
  chained rules resolve to a fixed point.
- **Auto-generated UI**: the Vue configurator renders a field per option by
  type, evaluates visibility client-side (mirroring the server), and re-prices
  via the quote endpoint with a live breakdown.

| Method | Endpoint | Purpose |
| ------ | -------- | ------- |
| GET | `/api/product-builder/products` · `/products/{slug}` | List / full config (public) |
| POST | `/api/product-builder/products/{slug}/quote` | Validate + price selections |
| … | `/api/admin/product-builder/products` | Product CRUD (admin) |
| … | `/products/{product}/options[/{option}/values]` | Options & values |
| … | `/products/{product}/rules` | Conditional rules |

Admin Product Builder endpoints require the `admin` guard plus the
`manage products` permission (Super Admin, Administrador).

---

## Catalog & dynamic filters (Phase 6)

A dynamic, SEO-driven catalog with admin-configurable filters — no code per
filter.

- **Taxonomy**: hierarchical **categories** (Floral, Moderno, Mini, Signature),
  **collections**, **tags**, and admin-defined **attributes** (Tamaño, Sabor,
  Color…). Marking an attribute *filterable* surfaces it as a catalog facet
  automatically.
- **Dynamic filtering**: by category, collection, tag, price range and any
  number of attributes (AND across attributes, OR within each), plus full-text
  search and sorting — paginated and indexed for fast lookups.
- **Facets endpoint** returns the available filters (categories tree,
  collections, filterable attributes with values, price bounds) so the UI is
  generated from configuration.
- **SEO**: friendly URLs `/categoria/floral` and `/pastel/muse-blanc`,
  per-entity meta title/description applied to the document head, and
  **breadcrumb** trails built from the category hierarchy.
- **Responsive** filter sidebar, product grid, sorting and pagination on the
  Vue frontend.

| Method | Endpoint | Purpose |
| ------ | -------- | ------- |
| GET | `/api/catalog/browse` | Filtered, paginated products |
| GET | `/api/catalog/facets` | Configurable filter facets |
| GET | `/api/catalog/categories/{slug}` | Category landing + breadcrumbs + products |
| GET | `/api/catalog/detail/{slug}` | Product detail + breadcrumbs |
| … | `/api/admin/catalog/categories\|collections\|attributes` | Taxonomy CRUD |
| PUT | `/api/admin/catalog/products/{product}/taxonomy` | Classify a product |

Admin catalog endpoints require the `admin` guard plus the `manage products`
permission.

---

## Cart & checkout (Phase 7)

Persistent cart and **authenticated checkout — no guest purchases**.

- **Persistent cart** (one per customer, server-side): adding a configured cake
  re-validates and re-prices it through the Product Builder's
  `ConfiguratorService`, then stores a **full configuration snapshot** (selections,
  visibility and priced breakdown) plus the authoritative unit price.
- **Saved addresses** labelled Casa / Trabajo / Otra, with a single default per
  customer.
- **Fulfillment**: delivery to a saved address (snapshotted onto the order) or
  **pickup at a branch (sucursal) with date + time**.
- **Checkout** snapshots line items into an immutable order, generates a human
  order number and clears the cart — all inside a transaction.
- **Order history** and detailed summaries, scoped to the authenticated
  customer.

| Method | Endpoint | Purpose |
| ------ | -------- | ------- |
| GET | `/api/branches` | Pickup branches (public) |
| GET/POST/PUT/DELETE | `/api/cart` · `/cart/items[/{item}]` | Persistent cart |
| GET/POST/PUT/DELETE | `/api/addresses` | Saved addresses |
| POST | `/api/checkout` | Place an order (delivery or pickup) |
| GET | `/api/orders` · `/orders/{order}` | Order history + detail |

Every cart, address, checkout and order endpoint requires an authenticated
customer (`auth:sanctum`).

---

## Smart scheduling calendar (Phase 8)

An advanced delivery/pickup scheduling engine. Each product has independent
production rules, and the engine computes valid dates automatically.

- **Production rules per product**: lead time in hours (24h / 48h / 72h / 7d),
  with a global default rule as fallback.
- **Admin-controlled availability**: weekly schedule (open days + daily
  capacity), bookable time slots (each with its own capacity), **holidays**
  (festivos, one-off or recurring) and **blackouts** (bloqueos, full-day or a
  single slot).
- **Engine** computes the **minimum available date/slot** and the set of
  available days, honouring: production lead time, open days, holidays,
  blackouts and day/slot capacity (consumed by bookings), over a search window.
- The Vue `AvailabilityPicker` consumes the engine and is wired into checkout
  pickup — customers can only pick valid dates/slots.

| Method | Endpoint | Purpose |
| ------ | -------- | ------- |
| GET | `/api/calendar/availability?product={slug}` | Minimum date + available days/slots |
| GET/PUT | `/api/admin/calendar/schedule` | Weekly schedule + capacity |
| … | `/api/admin/calendar/slots` | Time-slot CRUD |
| POST/DELETE | `/api/admin/calendar/holidays` · `/blackouts` | Holidays & blackouts |
| PUT | `/api/admin/calendar/production-rules` | Per-product / default lead time |

Admin calendar endpoints require the `admin` guard plus the `manage calendar`
permission (Super Admin, Administrador, Producción).

---

## Multi-gateway payments (Phase 9)

A decoupled payment architecture using the **Strategy pattern**, supporting
**MercadoPago, Stripe and OpenPay**.

- **Strategy per gateway** behind a common `PaymentGateway` contract, resolved
  by a `PaymentGatewayManager` — the application layer never depends on a
  concrete provider.
- **Sandbox / Production** modes and credentials are configured **per gateway
  from administration** (encrypted at rest), switchable without a deploy.
- **Mandatory webhooks**: each provider posts to
  `/api/payments/webhooks/{gateway}`; the signature is verified (Stripe's
  `t=…,v1=…` HMAC scheme; HMAC-of-body for MercadoPago/OpenPay) before the
  status is applied.
- **Reconciliation**: webhook events drive the authoritative payment status and
  update the related order (paid → confirmed).
- **Retries**: a queued `RetryPaymentStatusJob` (with exponential backoff)
  re-checks non-final payments.
- **Full traceability**: every initiation, webhook, status change, retry and
  reconciliation is recorded in `payment_events`.

| Method | Endpoint | Purpose |
| ------ | -------- | ------- |
| GET | `/api/payments/gateways` | Active gateways for checkout |
| POST | `/api/payments/initiate` | Start a payment for an order |
| GET | `/api/payments/{payment}` | Payment status + event trail |
| POST | `/api/payments/webhooks/{gateway}` | Provider webhook (signature-verified) |
| GET/PUT | `/api/admin/payments/gateways[/{gateway}]` | Configure gateways + mode |
| GET/POST | `/api/admin/payments` · `/{payment}/retry` | Traceability + reconcile retry |

Admin payment endpoints require the `admin` guard plus the `manage payments`
permission. Production SDK calls plug into each strategy's `createCharge()`.

---

## Automation engine (Phase 10)

An event-driven notification engine with admin-configurable templates and
automatic reminders, processed asynchronously via **Laravel Queues + Scheduler**.

- **Configurable email templates** per event (subject, body with `{{ variable }}`
  placeholders, documented variables), toggled active/inactive by the admin.
- **Events**: `order.placed`, `payment.approved`, `production.started`,
  `order.ready`, `order.reminder`. Owning modules raise a single decoupled
  `AutomationTriggered` event; a listener resolves the active template, renders
  it and queues delivery (`SendNotificationJob`).
- **Automatic reminders**: configurable offset rules (24h / 12h / 2h before
  pickup). A scheduled command (`notifications:dispatch-reminders`, hourly) fires
  due reminders, idempotent per order/offset.
- **Full traceability + idempotency**: every dispatch is recorded in
  `notification_logs` with a dedupe key.
- Integrated end-to-end: checkout → `order.placed`; payment reconciliation →
  `payment.approved`; admin order-status transitions → `production.started` /
  `order.ready`.

| Method | Endpoint | Purpose |
| ------ | -------- | ------- |
| GET/POST/PUT/DELETE | `/api/admin/notifications/templates` | Email templates |
| GET/POST/PUT/DELETE | `/api/admin/notifications/reminders` | Reminder offset rules |
| GET | `/api/admin/notifications/logs` | Delivery traceability |
| PUT | `/api/admin/orders/{order}/status` | Transition status (fires automations) |

Admin notification endpoints require the `admin` guard plus the
`manage notifications` permission. The scheduler runs
`notifications:dispatch-reminders` hourly.

---

## Enterprise admin panel (Phase 11)

A complete administration panel with an analytical dashboard, user & role
management and advanced auditing.

- **Analytical dashboard**: aggregated **sales** (revenue, paid payments, average
  order value), **orders** (totals + by status), **production** (in-production,
  ready, upcoming pickups), **conversion** (cart→order, order→paid) and
  **customers** (total, new, with orders).
- **Audit trail — every action logged**: a global middleware automatically
  records every *mutating* admin request (who, method, path, status, IP, payload
  with sensitive fields redacted) to `audit_logs`, viewable in the panel.
- **User management**: search and view customers.
- **Access control**: list roles + their permissions, create administrators and
  assign roles (Super Admin / Administrador only).
- The Vue admin SPA provides the panel shell (sidebar nav, dashboard cards,
  audit table, users and roles views); the remaining sections (CMS, Theme,
  Products, Orders, Calendar, Payments, Automations) are driven by the admin
  APIs built in earlier phases.

| Method | Endpoint | Purpose |
| ------ | -------- | ------- |
| GET | `/api/admin/dashboard` | Aggregated metrics (`view dashboard`) |
| GET | `/api/admin/audit-logs` | Audit trail (`view audit`) |
| GET | `/api/admin/users[/{user}]` | Customer management (`manage users`) |
| GET/POST | `/api/admin/roles` · `/admins` | Roles + admin management (Super/Admin role) |
| PUT | `/api/admin/admins/{admin}/roles` | Assign roles |

---

## Local development (without Docker)

```bash
# Backend
cd backend
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve

# Frontend
cd frontend
npm install
npm run dev
```

---

## Quality

```bash
# Backend — tests + PSR-12 style
cd backend
php artisan test
./vendor/bin/pint --test

# Frontend — type-check + build
cd frontend
npm run build
```
