# Cronos Bakery Builder — Context.md

Fecha de última actualización: 2026-07-04

---

## 1. Resumen del Proyecto

**Cronos Bakery Builder** es un SPA en Vue 3 + TypeScript que actúa como plataforma e-commerce especializada para pastelerías. Permite a clientes explorar catálogos de productos personalizables (product builder), gestionar pedidos y hacer pagos, mientras los administradores operan el negocio desde un panel completo.

---

## 2. Stack Tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| Framework frontend | Vue 3 (Composition API, `<script setup>`) | ^3.5.34 |
| UI Library (admin) | **PrimeVue** + PrimeIcons | ^4.x |
| Router | vue-router | ^4.6.4 |
| Estado global | Pinia | ^3.0.4 |
| HTTP client | Axios | ^1.16.1 |
| Build tool | Vite | ^8.0.12 |
| Lenguaje | TypeScript (strict) | ~6.0.2 |

> **Nota:** El panel administrativo fue migrado completamente a PrimeVue. Se eliminaron `vue-sonner` y los paquetes `@tiptap/*`. El editor de texto enriquecido del producto ahora usa el componente `<Editor>` nativo de PrimeVue (Quill).

---

## 3. Arquitectura de Directorios

```
frontend/
├── src/
│   ├── assets/                  # Imágenes, fuentes estáticas
│   ├── components/              # Componentes compartidos globales
│   │   └── (sin ConfirmDialog ni DataTable custom — reemplazados por PrimeVue)
│   ├── composables/             # Lógica reutilizable (Composition API)
│   │   ├── useToast.ts          # Wrappea useToast() de PrimeVue
│   │   ├── useConfirm.ts        # Wrappea ConfirmationService de PrimeVue
│   │   ├── useFormValidation.ts
│   │   ├── useOptimistic.ts
│   │   ├── useSudo.ts
│   │   ├── useEcho.ts
│   │   └── useValidationErrors.ts
│   ├── layouts/
│   │   ├── AdminLayout.vue      # Shell del panel — usa PrimeVue Sidebar + Menubar
│   │   ├── DefaultLayout.vue    # Layout público del cliente
│   │   ├── AuthLayout.vue       # Login / registro de clientes
│   │   └── BlankLayout.vue      # Sin chrome (login admin, 404)
│   ├── modules/                 # Arquitectura modular por dominio
│   │   ├── administration/      # ★ Panel administrativo completo
│   │   │   ├── pages/           # 18 páginas (routes level)
│   │   │   ├── components/      # Componentes específicos del admin
│   │   │   ├── composables/     # Lógica del admin (useProductForm, etc.)
│   │   │   ├── services/
│   │   │   │   ├── adminPanelService.ts   # 100+ llamadas API CRUD
│   │   │   │   └── adminAuthService.ts
│   │   │   ├── stores/
│   │   │   │   ├── adminAuth.ts           # Sesión del admin (token independiente)
│   │   │   │   └── userManagement.ts
│   │   │   ├── types/index.ts             # Todos los tipos del dominio admin
│   │   │   └── routes.ts                  # 22 rutas bajo /admin/*
│   │   ├── authentication/      # Auth del cliente (login, registro)
│   │   ├── catalog/             # Catálogo público (productos, categorías)
│   │   ├── product-builder/     # Builder de productos personalizables
│   │   ├── orders/              # Pedidos del cliente
│   │   ├── payments/            # Pagos y gateways
│   │   ├── calendar/            # Calendario de entregas
│   │   ├── cms/                 # CMS y temas del sitio
│   │   └── notifications/       # Centro de notificaciones
│   ├── pages/
│   │   ├── HomePage.vue
│   │   └── NotFoundPage.vue
│   ├── router/
│   │   └── index.ts             # Composición de rutas + guards de auth
│   ├── services/
│   │   └── http.ts              # Instancia Axios con interceptores de auth
│   ├── stores/
│   │   ├── theme.ts             # Carga el tema activo y aplica CSS vars
│   │   └── auth.ts              # Auth del cliente (store Pinia)
│   └── styles/
│       └── admin.css            # Variables CSS del admin (tokens de color)
├── package.json
└── vite.config.ts
```

---

## 4. Autenticación (Doble Sistema)

El sistema tiene **dos sesiones completamente independientes**:

| Sesión | Token localStorage | Guard de ruta | Store |
|---|---|---|---|
| Cliente | `auth_token` | `meta.requiresAuth` | `stores/auth.ts` |
| Admin | `admin_token` | `meta.requiresAdmin` | `modules/administration/stores/adminAuth.ts` |

El guard vive en `router/index.ts` (`router.beforeEach`). Si no hay token, redirige a `auth.login` o `admin.login` respectivamente.

---

## 5. Panel Administrativo — Módulo `administration`

### 5.1 Rutas del admin (`/admin/*`)

| Ruta | Nombre | Página |
|---|---|---|
| `/admin/login` | `admin.login` | `AdminLoginPage.vue` (layout: blank) |
| `/admin` | `admin.dashboard` | `AdminDashboardPage.vue` |
| `/admin/orders` | `admin.orders` | `AdminOrdersPage.vue` |
| `/admin/calendar` | `admin.calendar` | `AdminCalendarPage.vue` |
| `/admin/products` | `admin.products` | `AdminProductsPage.vue` |
| `/admin/productos/new` | `admin.products.create` | `AdminProductFormPage.vue` |
| `/admin/productos/:id` | `admin.products.edit` | `AdminProductFormPage.vue` |
| `/admin/options` | `admin.options` | `AdminOptionsPage.vue` |
| `/admin/categories` | `admin.categories` | `AdminCategoriesPage.vue` |
| `/admin/cms` | `admin.cms` | `AdminCmsPage.vue` |
| `/admin/menus` | `admin.menus` | `AdminMenusPage.vue` |
| `/admin/theme` | `admin.theme` | `AdminThemePage.vue` |
| `/admin/payments` | `admin.payments` | `AdminPaymentsPage.vue` |
| `/admin/emails` | `admin.emails` | `AdminEmailsPage.vue` |
| `/admin/notifications` | `admin.notifications` | `AdminNotificationsPage.vue` |
| `/admin/users` | `admin.users` | `UsersPage.vue` |
| `/admin/roles` | `admin.roles` | `RolesPage.vue` |
| `/admin/audit` | `admin.audit` | `AuditLogPage.vue` |
| `/admin/security` | `admin.security` | `SecurityPage.vue` |
| `/admin/profile` | `admin.profile` | `AdminProfilePage.vue` |
| `/admin/tasks` | `admin.tasks` | `AdminTasksPage.vue` |

Todas las rutas (excepto login) tienen `meta: { layout: 'admin', requiresAdmin: true }`.

### 5.2 Navegación del Sidebar

El `AdminLayout.vue` define las secciones del menú lateral:

| Sección | Items |
|---|---|
| Principal | Dashboard, Pedidos, Calendario |
| Catalogo | Productos, Opciones, Categorias |
| Contenido | CMS, Menus, Theme Builder |
| Finanzas | Pagos |
| Comunicaciones | Correos, Notificaciones |
| Administracion | Usuarios, Roles, Auditoria, Seguridad (2FA) |

### 5.3 Componentes del Admin

| Componente | Propósito |
|---|---|
| `ProductGeneralForm.vue` | Nombre, slug, descripción con editor rico |
| `ProductMediaGallery.vue` | Upload de imagen principal y galería drag-drop |
| `ProductPricing.vue` | Gestión de precios base |
| `ProductOptionsManager.vue` | Vinculación de opciones con leyendas y exclusión de valores por producto |
| `UserTable.vue` | Tabla de usuarios con acciones |
| `UserFormModal.vue` | Modal de creación/edición de usuario |
| `SuspendUserModal.vue` | Modal de suspensión de usuario |
| `ImpersonationBanner.vue` | Banner cuando se impersona un usuario |

### 5.4 Composables del Admin

| Composable | Propósito |
|---|---|
| `useProductForm.ts` | Estado del formulario de producto, validación, submit |
| `useRichTextEditor.ts` | Setup del editor de texto enriquecido (PrimeVue Editor) |
| `useMediaGallery.ts` | Upload de imágenes, drag-drop para thumbnail y galería |
| `useProductOptions.ts` | Links de opciones, leyendas, exclusión de valores por producto |
| `useProductPreview.ts` | Genera token temporal y abre la vista previa del storefront en pestaña nueva |

### 5.5 Tipos principales (`adminPanelService.ts`)

```typescript
DashboardMetrics     // Métricas de ventas, pedidos, producción, conversión
AdminProduct         // Producto con base_price, is_active, options_count
AdminProductDetail   // Extiende AdminProduct con gallery y options[]
PbOption             // Opción de producto (select, radio, checkbox, color, etc.)
PbOptionValue        // Valor de opción con price_modifier
OptionTemplate       // Plantilla de opción reutilizable (global)
ProductOptionLink    // Vínculo producto ↔ template con legend y valores habilitados
AdminOrder           // Pedido con items, totals, fulfillment
AdminUser            // Usuario con roles, suspension_info
RoleDefinition       // { name: string; permissions: string[] }
AuditLog             // Registro de actividad HTTP del admin
PaymentGateway       // Gateway de pago con settings
CmsSection/Page/Block // CMS jerárquico
CmsMenu/MenuItem     // Menús de navegación del sitio
Theme                // Tema activo con settings (colores, fuentes)
CalendarSchedule     // Horario semanal de atención
DeliverySlot         // Slot de entrega con max_orders
```

---

## 6. Setup de PrimeVue (Panel Admin)

### 6.1 Registro en `main.ts`

```typescript
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import 'primeicons/primeicons.css'

app.use(PrimeVue, { theme: { preset: Aura } })
app.use(ConfirmationService)
app.use(ToastService)
```

### 6.2 Componentes PrimeVue utilizados en el Admin

| Categoría | Componentes |
|---|---|
| Layout | `Sidebar`, `Menubar`, `PanelMenu`, `Card`, `Panel`, `Divider`, `Tabs` (TabList/Tab/TabPanels/TabPanel), `Accordion` (AccordionPanel/Header/Content) |
| Data | `DataTable`, `TreeTable`, `Column`, `Paginator`, `Tag` |
| Form | `InputText`, `Textarea`, `Select` (ex-Dropdown), `InputNumber`, `Checkbox`, `RadioButton`, `ToggleSwitch`, `Editor`, `Password`, `DatePicker` |
| Button | `Button` |
| Overlay | `Dialog`, `ConfirmDialog`, `Toast`, `OverlayPanel` |
| Media | `Image`, `FileUpload` |
| Navigation | `Breadcrumb`, `Menu`, `Avatar` |
| Feedback | `ProgressSpinner`, `Message`, `InlineMessage` |
| Misc | `Chip`, `Badge` |

### 6.3 Toasts y Confirmaciones

**Toast** — se llama desde cualquier componente mediante `useToast()` wrapper:
```typescript
// src/composables/useToast.ts
import { useToast as usePrimeToast } from 'primevue/usetoast'
```
El `<Toast />` global está registrado en `AdminLayout.vue`.

**ConfirmDialog** — se llama mediante `useConfirm()` wrapper:
```typescript
// src/composables/useConfirm.ts
import { useConfirm as usePrimeConfirm } from 'primevue/useconfirm'
```
El `<ConfirmDialog />` global está en `AdminLayout.vue`.

---

## 7. HTTP Service (`src/services/http.ts`)

Instancia Axios con:
- `baseURL` desde `import.meta.env.VITE_API_URL`
- Interceptor de request: inyecta `Authorization: Bearer {admin_token}` o `auth_token` según el token disponible
- Interceptor de response: maneja 401 → logout automático

Función principal:
```typescript
export function request<T>(config: AxiosRequestConfig): Promise<T>
```

---

## 8. Stores Pinia

### `stores/auth.ts` (cliente)
- Estado: `user`, `token`
- Getters: `isAuthenticated`, `isVerified`
- Acciones: `login()`, `register()`, `logout()`

### `stores/theme.ts` (público)
- Carga el tema activo via API
- Aplica colores como CSS custom properties (`:root`)
- Carga Google Fonts dinámicamente
- Expone `headerMenu`, `footerContent`

### `modules/administration/stores/adminAuth.ts`
- Estado: `admin: Admin | null`, `token: string | null`
- Getters: `isAuthenticated`, `can(permission)`, `hasRole(role)`
- Acciones: `login()`, `fetchCurrentAdmin()`, `logout()`

### `modules/administration/stores/userManagement.ts`
- CRUD de usuarios desde el panel admin

---

## 9. Variables CSS del Admin (`styles/admin.css`)

El archivo define tokens de diseño como CSS custom properties:

```css
--admin-primary: #5d87ff
--admin-success: #13deb9
--admin-warning: #ffae1f
--admin-error:   #fa896b
--admin-info:    #539bff
--admin-text:    #2a3547
--admin-border:  #e5eaef
--admin-bg:      #f5f7fa
--admin-font:    'Plus Jakarta Sans', sans-serif
--admin-radius:  12px
```

Estos tokens siguen siendo usados en las páginas para mantener consistencia visual con el tema del admin, aunque los componentes de UI son nativos de PrimeVue.

---

## 10. Convenciones y Patrones

### Naming
- Páginas: `AdminXxxPage.vue` (nivel de ruta)
- Componentes feature-specific: bajo `modules/*/components/`
- Composables: `useXxx.ts`
- Stores: `xxxStore` (camelCase)

### Formularios
- Todos usan `<script setup>` con `ref()` para el estado
- Validación vía composable `useFormValidation.ts`
- Submit con manejo de errores y toast de confirmación

### API
- Todas las llamadas pasan por `adminPanelService`
- Respuestas envueltas en `{ data: T }` → se desenvuelven en el service
- Paginación: `{ data: T[]; meta: { current_page, last_page, total } }`

### Layouts
- Cada ruta declara su layout en `meta.layout`
- `App.vue` renderiza el layout correspondiente dinámicamente

---

## 11. Flujo de Desarrollo

1. **Instalar dependencias:** `npm install` en `frontend/`
2. **Dev server:** `npm run dev` → `http://localhost:5173`
3. **Variables de entorno:** crear `frontend/.env.local` con:
   ```
   VITE_API_URL=http://localhost:8000/api
   ```
4. **Build:** `npm run build` (incluye `vue-tsc` para type-check)

---

## 12. Decisiones Técnicas Relevantes

| Decisión | Razón |
|---|---|
| PrimeVue como UI library del admin | Componentes ricos out-of-the-box (DataTable con sort/filter/paginación, Dialog, ConfirmDialog, Toast, Editor). Elimina la necesidad de construir y mantener componentes custom. |
| TipTap → PrimeVue `<Editor>` | Reducción de dependencias; PrimeVue Editor (Quill) cubre los casos de uso del admin (bold, italic, underline, listas). |
| vue-sonner → PrimeVue `<Toast>` | Unificación bajo una sola librería de UI. |
| Módulos por dominio | Cada feature (admin, catalog, orders, etc.) es auto-contenido con sus propias rutas, tipos, servicios y stores. Facilita el crecimiento del proyecto. |
| Doble sistema de auth | El admin opera con un token completamente separado del cliente, permitiendo sesiones simultáneas y sin interferencia. |
| CSS custom properties para theming | El tema del admin se puede personalizar centralmente desde `admin.css` sin tocar componentes individuales. PrimeVue se integra via su sistema de temas (Aura preset). |

---

## 13. Persistencia: IDs Autoincrementales Universales (regla innegociable)

**Decisión (2026-07):** todas las llaves primarias del sistema usan **BIGINT autoincremental nativo de PostgreSQL 16** (`$table->id()` → identity/bigserial). Queda **prohibido** generar UUIDs como PK desde la aplicación (ni `HasUuids` en modelos, ni `Str::uuid()` para llaves). Los UUIDs solo se admiten en valores no-clave (ej. nombres de archivo en MinIO).

- **Módulos ya migrados a identity:** `brands`, CMS completo (`cms_pages`, `cms_page_blocks`, `cms_sections`, `themes`, `menus`/`menu_items`, `banners`, `storage_providers`/`media_assets`, `content_versions`, `content_workflows`), `audit_logs` (Administration), **todo el módulo Catalog** (`catalog_products`, `catalog_categories`, `catalog_collections`, `catalog_attributes`/`_values`, `catalog_tags` y sus 4 pivotes) y **todo el módulo Payments** (`payment_gateways`, `transactions`, `transaction_events`, `gateway_webhook_events` — ver §21).
- **Módulos pendientes (aún UUID):** ProductBuilder (`pb_*`), Orders, Calendar, Notifications. Sus PKs no cruzan FKs con los módulos ya convertidos (`cart_items.product_id` y `order_items.product_id` referencian `pb_products`, no el catálogo). Cuando un módulo identity necesita referenciar uno UUID, la FK vive en una **columna no-clave** del tipo correspondiente (ej. `transactions.order_id` es `uuid` → `orders`), lo cual no viola la regla: la prohibición aplica a las PKs.
- **Cómo se aplicó:** las migraciones se editaron **en sitio** (el proyecto está en desarrollo, sin datos productivos) → requiere `php artisan migrate:fresh --seed`. Las columnas JSON de los módulos convertidos pasaron a **JSONB**.
- **Frontend:** las interfaces TS de los módulos convertidos usan `id: number` (cms/types, catalog/types, adminPanelService). Los módulos aún en UUID conservan `id: string`.

## 14. Versionado de Contenido y Workflow Editorial (CMS)

- **Snapshot polimórfico**, no tabla por entidad: `content_versions` (morphs `versionable`, `version_number` incremental por entidad, `payload_before`/`payload_after` JSONB, `status_before`/`status_after`, `change_summary`, `author_id` → **admins**). Cada transición de workflow y cada rollback insertan una versión automáticamente (`PublishContentAction` / `RollbackContentAction`).
- **Máquina de estados:** `ContentStatus` (draft → pending_review → published/scheduled → archived) define las transiciones válidas; `PageStatus` (cast de `cms_pages.status`) incluye los 5 estados para aceptar todo lo que el workflow persiste. Transición ilegal ⇒ HTTP 422.
- **Endpoints** (bajo `admin/cms`, permiso `manage cms`): `POST pages/{id}/submit-review | approve | reject | schedule`, `GET pages/{id}/versions`, `POST pages/{id}/rollback` (`version_id` validado contra la misma página), `GET pages/{id}/workflows`. "Guardar borrador" NO pasa por el workflow: es el update normal de página/bloques y nunca cambia el estado de publicación.
- Los actores del workflow son **Admins** (guard sanctum del panel); las FKs `author_id`/`requested_by`/`approved_by`/`last_editor_id` apuntan a `admins`.
- El middleware `RequirePasswordRevalidation` (sudo por sesión) existe pero **no está enrutado** — el flujo sudo del frontend (`useSudo`) queda pendiente de wiring en backend.

## 15. Auditoría Automática de Modelos (Audit Trail)

- **Dos tablas con propósitos distintos:** `audit_logs` (Administration) registra **requests HTTP** del panel (method, path, status, payload redactado); la nueva **`model_audit_logs`** registra **diffs de estado por modelo**: `brand_id` (multitenant, nullable), `user_id` (→ `admins`, null para acciones de sistema), `event` (created/updated/deleted/restored), morphs `auditable`, `old_values`/`new_values` JSONB, `ip_address`. Se nombró `model_audit_logs` porque `audit_logs` ya existía con otro esquema.
- **Patrón:** trait opt-in `App\Shared\Domain\Concerns\Auditable` → registra `AuditObserver` (captura contexto en el request: diff `getOriginal()` vs `getChanges()`, admin autenticado, IP, brand) → despacha `RecordModelAuditJob` **encolado y `afterCommit()`** (la escritura nunca añade latencia ni fallos al request). Atributos en `$hidden` o en `auditExclude()` jamás se auditan.
- **Modelos auditados hoy:** Page, PageBlock, Section, Theme, Menu, MenuItem, Banner (CMS) y Product, Category, Collection (Catalog). Para auditar otro modelo basta `use Auditable;`.

## 16. Dependencia obligatoria: Quill (PrimeVue `<Editor>`)

El componente `<Editor>` de PrimeVue **no incluye Quill**; lo resuelve en runtime (`import "quill"`). Sin el paquete instalado, Vite lanza un **500** en `vite:import-analysis` (`Failed to resolve import "quill"`) y rompe la navegación de Vue Router hacia cualquier ruta que monte el editor (`AdminProductFormPage.vue`, `AdminPageBuilderPage.vue`).

- **Instalación (regla innegociable mientras se use `<Editor>`):** `npm install quill` en `frontend/`.
- **Tipos TypeScript:** **NO** instalar `@types/quill`. Quill **2.x** (v2.0.3) ya distribuye sus propias declaraciones (`node_modules/quill/quill.d.ts`); el paquete `@types/quill` es un stub deprecado de la era v1 y **entra en conflicto** con los tipos nativos.
- **Configuración en `vite.config.ts`:** se añadió `optimizeDeps.include: ['quill']` para forzar el pre-bundling y evitar que el pre-bundler falle al resolver el import dinámico del editor. Tras cambiar esto conviene reiniciar el dev server (o borrar `node_modules/.vite`) para invalidar la caché de deps.

## 17. Estándar de UI: Acciones en tablas (iconos + tooltips)

Convención para la columna de "Acciones" de todos los `DataTable` del admin:

- **Botones solo-icono** (PrimeIcons), **sin texto** (`label`). Íconos canónicos: `pi-pencil` (editar datos), `pi-palette` (abrir Constructor/Builder), `pi-eye` / `pi-eye-slash` (publicar / despublicar), `pi-trash` (eliminar).
- Cada botón lleva la directiva **`v-tooltip`** (`v-tooltip.top="'…'"`) con texto descriptivo, más `aria-label` equivalente por accesibilidad.
- Estilo uniforme: `size="small"`, `text`, `rounded`. Variantes de `severity` semánticas: `secondary`/`info` para navegación/edición, `warn` para publicar/despublicar, `danger` para eliminar.
- **Registro de la directiva `Tooltip`:** se registra **globalmente** en `main.ts` con `app.directive('tooltip', Tooltip)` (import de `primevue/tooltip`), habilitando `v-tooltip` en toda la app sin re-declararla por componente. Alternativa local por componente: `import Tooltip from 'primevue/tooltip'` + `const vTooltip = Tooltip` en `<script setup>`.
- Implementación de referencia: columna "Acciones" de `AdminCmsPage.vue`.

## 18. Exclusión de Valores en Opciones de Producto (Product Builder)

**Estrategia de BD (2026-07):** el pivote `pb_product_option_links` (producto ↔ plantilla de opción global) guarda las exclusiones en la columna **`excluded_value_ids` (JSONB, nullable)** — un array de IDs de `pb_option_template_values` que ese producto **oculta**. Se eligió **semántica de exclusión** (antes existía `enabled_value_ids`, lista de inclusión, ya eliminada por migración con conversión de datos):

- `null` o `[]` ⇒ el producto **hereda todos** los valores de la plantilla, **incluidos los que se agreguen a la plantilla en el futuro** (ventaja clave frente a la lista de inclusión, que congelaba el set).
- No se usó tabla relacional intermedia adicional: la cardinalidad es baja (decenas de valores por opción), el array JSONB vive junto al vínculo que califica y nunca se consulta por valor individual desde SQL. *(Nota: el módulo ProductBuilder sigue en UUIDs — pendiente de la conversión a IDs autoincrementales de la regla §13; los IDs dentro del array son UUIDs de valores de plantilla.)*
- **Validación (Form Requests dedicados):** `StoreProductOptionLinkRequest` / `UpdateProductOptionLinkRequest` exigen que cada ID excluido **pertenezca a la plantilla vinculada** (`Rule::exists(...)->where('template_id', …)`) ⇒ 422 si no.
- **Contrato del API (`ProductOptionLinkResource`):** devuelve **ambas vistas**: `excluded_value_ids` + `template.values` completo (para que el admin pinte los toggles) y `values` = **valores efectivos ya filtrados** vía `ProductOptionLink::effectiveValues()` (lo que consume el storefront). Helpers de dominio: `isValueExcluded()`, `effectiveValues()`.
- **Frontend:** en `ProductOptionsManager.vue` cada opción vinculada es desplegable y cada valor tiene un **`ToggleSwitch`** con `v-tooltip` (encendido = heredado, apagado = excluido, con `Tag` "Excluido"). El guardado es inmediato por valor (PUT del link con el array recalculado; array vacío se normaliza a `null`).

## 19. Vista Previa Real del Producto (Tokenized Storefront View)

**Flujo de seguridad (2026-07):** la vista previa "Ver como usuario" abandonó la modal del admin y abre una **pestaña nueva con el layout público real** (`/builder/preview/:token`, ruta `builder.preview`, misma `ConfiguratorPage.vue` del storefront). Autorización por **token opaco temporal**, no por sesión:

- **Minteo (admin-only):** `POST /api/admin/product-builder/products/{id}/preview-token` → `PreviewTokenService::mint()` genera `Str::random(64)` y lo guarda en **Cache con TTL de 30 min** (`product_preview:{token}` → product id). Se eligió token opaco en cache sobre `URL::temporarySignedRoute` porque la URL que se comparte es una ruta del **SPA** (no del API) y el token debe poder viajar como parámetro de ruta del frontend y usarse en **varios** endpoints (show + quote); la caducidad y revocación quedan del lado del servidor.
- **Consumo (público, sin sesión):** `GET /api/product-builder/preview/{token}` — el token es la **única credencial**; devuelve la configuración completa aunque el producto esté en **borrador** (403 si expiró/es inválido). `POST products/{slug}/quote` acepta `preview_token` opcional: si resuelve al mismo producto, permite cotizar borradores.
- **Frontend:** `useProductPreview.openPreview()` abre `window.open('', '_blank')` **antes** del `await` (evita el popup-blocker), pide el token y redirige la pestaña a `builder.preview`. En modo preview la página muestra un banner de advertencia y el carrito queda deshabilitado.

## 20. Estándar absoluto de UI para jerarquías y menús: `TreeTable` + iconos con tooltips

- Toda vista del admin que represente **estructuras jerárquicas** (menús de navegación padre/hijo, árboles de categorías, etc.) usa **`TreeTable` de PrimeVue** (o `DataTable` con `rowGroupMode` cuando la agrupación es plana) — nunca listas `<ul>` anidadas a mano ni tarjetas apiladas.
- Las **acciones por fila** siguen la regla de §17 sin excepción: botones **solo-icono** (`pi-pencil` editar, `pi-trash` eliminar, `pi-plus` agregar hijo/submenú) con `size="small"`, `text`, `rounded`, `severity` semántico, texto explicativo **solo** vía `v-tooltip` + `aria-label`.
- Implementación de referencia: `AdminMenusPage.vue` — nodos raíz = menús (con `Tag` de ubicación), nivel 1 = enlaces, nivel 2 = subenlaces; CRUD de enlaces contra `POST/PUT/DELETE /admin/menus/{menu}/items/…` (expuesto en `adminPanelService` como `createMenuItem` / `updateMenuItem` / `deleteMenuItem`).

## 21. Módulo de Pasarelas de Pago (Payments) — Arquitectura y Seguridad

Refactor completo del módulo Payments (2026-07): multi-tenant por `brand_id`, PKs identity (§13) y cuatro tablas: `payment_gateways` (instancia configurada por marca: `driver_name`, `name`, `environment` sandbox/production, `is_active`, `credentials` JSONB, soft deletes), `transactions` (histórico con `provider_transaction_id`, `raw_response` JSONB, `idempotency_key` única por gateway), `transaction_events` (audit trail por transacción) y `gateway_webhook_events` (ledger de idempotencia).

### 21.1 Patrón Strategy para pasarelas (estándar absoluto)

- **Contrato:** `PaymentGatewayInterface` (`initialize`, `processPayment`, `handleWebhook`, `refund`) en `Payments/Domain/Contracts`. Las estrategias (`StripeGateway`, `MercadoPagoGateway`, `PayPalGateway`, `OpenPayGateway`) extienden `AbstractGateway`, que aporta HTTP resiliente (`callProvider()`: timeout/connect-timeout/retries desde `config/payments.php`, mapeo tipado a `GatewayTimeoutException` 504, `GatewayRateLimitException` 429 + Retry-After, `GatewayException` 502 — cada excepción trae su propio `render()`).
- **Resolución dinámica:** `PaymentGatewayManager::forGateway($gateway)` resuelve la clase desde el mapa `config('payments.drivers')` usando `driver_name` y la inicializa con credenciales+entorno. **Prohibido** el `if/else`/`match` por proveedor en controladores o servicios: agregar una pasarela = 1 entrada en config + 1 clase. `GET /admin/payments/drivers` expone labels y definición de campos de credenciales para que el frontend pinte formularios dinámicos sin hardcodear proveedores.
- Los drivers en sandbox **simulan** el cargo/refund (sin red); en producción llaman al API real vía `callProvider()` (fakeable con `Http::fake`).

### 21.2 Encriptación "At rest" de credenciales (política innegociable)

- Campo `credentials` JSONB con cast custom **`EncryptedCredentials`**: encripta **cada valor individualmente** (`Crypt` AES-256 con `APP_KEY`) dejando las claves en claro — el documento JSONB sigue siendo inspeccionable ({"secret_key": "eyJpdiI6…"}) pero ningún secreto es legible sin la llave de la app. Transparente para el consumidor del modelo.
- **El API jamás devuelve secretos en claro:** `PaymentGatewayResource` expone solo hints enmascarados (`••••••••1234`); el modelo además lleva `credentials` en `$hidden` (defensa en profundidad ante serializaciones ingenuas). Updates con **semántica de merge**: el frontend envía solo los campos re-escritos; valor no-vacío sobreescribe, `null` elimina la clave, clave omitida se conserva.
- El middleware de auditoría (`LogAdminActivity`) ya redacta `credentials`/`secret_key`/`webhook_secret` en `audit_logs`.

### 21.3 Estándar de webhooks: firma criptográfica + idempotencia obligatorias

- **Endpoint genérico** `POST /api/payments/webhooks/{driver}/{gateway_id}` (público): cada instancia configurada tiene su propia URL, así siempre aplica el secreto correcto de esa marca. El body crudo se pasa byte a byte para que el HMAC cuadre.
- **Autenticidad primero:** `AbstractGateway::handleWebhook()` es un template method que **verifica la firma antes de parsear**; firma inválida ⇒ `InvalidWebhookSignatureException` (400) **sin ningún write** en BD.
- **Idempotencia a nivel BD:** todo evento se inserta en `gateway_webhook_events` bajo el unique `(payment_gateway_id, provider_event_id)`; un duplicado (incluso concurrente) dispara `UniqueConstraintViolationException` y responde `{handled:false, status:"duplicate"}` sin reprocesar. Si el proveedor no manda event id, se usa `sha256(body)` como fallback. Además `transactions.idempotency_key` es única por gateway (una re-iniciación nunca duplica un cargo).
- **Nota transversal:** el callback `$exceptions->respond()` de `bootstrap/app.php` ahora respeta respuestas JSON ya renderizadas con status < 500 (excepciones con `render()` propio, 401/403/404 del framework) y solo sanitiza 5xx; `phpunit.xml` define `APP_KEY` (necesario para los casts encriptados en tests).

### 21.4 Frontend (AdminPaymentsPage + Pinia)

- Store `usePaymentGatewayStore` (`modules/administration/stores/paymentGateways.ts`): drivers, gateways, transacciones paginadas y filtros; todas las llamadas vía `adminPanelService` (tipos estrictos `PaymentGateway`, `Transaction`, `GatewayDriver`, enums de estado/entorno).
- UI con `Tabs` (Transacciones / Pasarelas). Pasarelas: `Accordion` por instancia, formularios de credenciales **generados desde `GET /drivers`** (`Password` con `toggleMask` para secretos, placeholder = hint enmascarado), `ToggleSwitch` para activación y entorno (cambio a Producción pide confirmación). Transacciones: `DataTable` lazy paginado con filtros por estado (`Select`), pasarela y rango de fechas (`DatePicker` range); `Tag` con severidad semántica; acciones solo-icono con `v-tooltip` (§17): `pi-eye` detalle+auditoría, `pi-refresh` reintento de conciliación (pending/processing), `pi-undo` reembolso (solo paid, con confirmación).
