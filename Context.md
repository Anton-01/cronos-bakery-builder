# Cronos Bakery Builder — Context.md

Fecha de última actualización: 2026-06-21

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
| `ProductOptionsManager.vue` | Vinculación de opciones de producto con leyendas |
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
| `useProductOptions.ts` | Links de opciones, leyendas, toggle de valores |
| `useProductPreview.ts` | Estado del modal de preview del producto |

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
| Layout | `Sidebar`, `Menubar`, `PanelMenu`, `Card`, `Panel`, `Divider` |
| Data | `DataTable`, `Column`, `Paginator`, `Tag` |
| Form | `InputText`, `Textarea`, `Select` (ex-Dropdown), `InputNumber`, `Checkbox`, `RadioButton`, `ToggleSwitch`, `Editor` |
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
