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
