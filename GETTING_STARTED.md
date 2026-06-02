# Guía de ejecución desde cero — Cronos Bakery Builder

Esta guía te lleva desde un clon limpio del repositorio hasta la plataforma
corriendo (backend Laravel 12 + frontend Vue 3 + servicios). Hay **dos caminos**:
con **Docker** (recomendado) o **local** (sin Docker).

---

## 1. Requisitos previos

| Camino | Necesitas |
| ------ | --------- |
| Docker (recomendado) | Docker 24+ y Docker Compose v2 |
| Local | PHP 8.3+ con extensiones `pdo_pgsql`, `redis`, `gd`, `intl`, `bcmath`, `zip`; Composer 2; Node 20+ y npm; PostgreSQL 16; Redis 7 |

Comprueba versiones:

```bash
docker --version && docker compose version   # camino Docker
php -v && composer --version && node -v       # camino local
```

---

## 2. Clonar el repositorio

```bash
git clone <repo-url> cronos-bakery-builder
cd cronos-bakery-builder
```

Estructura de alto nivel:

```
cronos-bakery-builder/
├── backend/            # API Laravel 12 (DDD: app/Domains, app/Shared, app/Modules)
├── frontend/           # SPA Vue 3 + TypeScript (Vite)
├── docker/             # Dockerfiles y configs (nginx, php)
├── docker-compose.yml  # Nginx, PHP-FPM, Horizon, Scheduler, Postgres, Redis, MinIO, Mailpit
├── scripts/deploy.sh   # despliegue a producción
├── README.md           # arquitectura y features por fase
└── GETTING_STARTED.md  # este archivo
```

---

## 3. Camino A — Docker (recomendado)

### 3.1 Copiar variables de entorno

```bash
cp .env.example .env                 # variables de docker-compose (puertos, credenciales)
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
```

### 3.2 Levantar el stack

```bash
docker compose up -d --build
```

Esto inicia: **nginx**, **php-fpm**, **horizon** (colas Redis), **scheduler**,
**frontend** (Vite), **postgres**, **redis**, **minio** (+ creación de bucket),
**mailpit**.

### 3.3 Inicializar la aplicación

```bash
# Generar APP_KEY
docker compose exec php php artisan key:generate

# Migrar y poblar datos de ejemplo (admins, catálogo, CMS, tema, calendario, etc.)
docker compose exec php php artisan migrate --seed
```

### 3.4 Listo — endpoints

| Servicio | URL |
| -------- | --- |
| API (Nginx) | http://localhost:8080 |
| Health check | http://localhost:8080/api/health |
| Status API | http://localhost:8080/api/status |
| Frontend (Vite) | http://localhost:5173 |
| Horizon (colas) | http://localhost:8080/horizon |
| MinIO console | http://localhost:9001 |
| Mailpit (correos) | http://localhost:8025 |
| PostgreSQL | localhost:5432 |
| Redis | localhost:6379 |

---

## 4. Camino B — Local (sin Docker)

Necesitas PostgreSQL y Redis corriendo localmente. Crea una base de datos
`cronos` y ajusta `backend/.env` (`DB_HOST=127.0.0.1`, `REDIS_HOST=127.0.0.1`,
`MAIL_HOST=127.0.0.1`, etc.).

### 4.1 Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Servir la API (http://localhost:8000)
php artisan serve

# En otra terminal: procesar colas y el scheduler
php artisan queue:work        # o: php artisan horizon
php artisan schedule:work
```

> Para usar SQLite en local rápido: `DB_CONNECTION=sqlite`,
> `touch database/database.sqlite`, y `CACHE_STORE`/`QUEUE_CONNECTION`/`SESSION_DRIVER`
> con drivers que no requieran Redis.

### 4.2 Frontend

```bash
cd frontend
npm install
cp .env.example .env   # ajusta VITE_API_URL si tu API no está en :8080
npm run dev            # http://localhost:5173
```

---

## 5. Credenciales sembradas (seed)

| Rol | Email | Password |
| --- | ----- | -------- |
| Super Admin | `superadmin@cronos.test` | `password` |
| Administrador | `administrator@cronos.test` | `password` |
| Producción | `production@cronos.test` | `password` |
| Ventas | `sales@cronos.test` | `password` |
| Marketing | `marketing@cronos.test` | `password` |
| Repartidor | `courier@cronos.test` | `password` |
| Clientes | 5 generados aleatoriamente | `password` |

- **Panel admin (SPA)**: http://localhost:5173/admin/login
- **Login admin (API)**: `POST /api/admin/login`
- **Tienda (clientes)**: http://localhost:5173

---

## 6. Verificar la instalación

```bash
cd backend

# Suite de pruebas (usa SQLite en memoria, no toca tu base de datos)
php artisan test          # 174 tests verdes

# Estilo de código (PSR-12)
./vendor/bin/pint --test

# Listar todas las rutas de la API
php artisan route:list
```

Frontend:

```bash
cd frontend
npm run build             # type-check + build de producción
```

---

## 7. Recorrido funcional rápido

1. **Tienda**: abre http://localhost:5173 → navega el **Catálogo**
   (`/catalog`), filtra por categoría/atributos, abre un producto
   (`/pastel/{slug}`).
2. **Product Builder**: ve a **Arma tu pastel** (`/builder`), elige un producto,
   configura opciones (el precio se calcula en vivo y las reglas muestran/ocultan
   opciones), **Agregar al carrito** (te pide iniciar sesión).
3. **Registro/Login** de cliente, **carrito** (`/carrito`), **checkout**
   (`/checkout`) eligiendo recolección (con el calendario inteligente) o entrega,
   y **pago** (sandbox) en `/orders/{id}/pay`.
4. **Panel admin** (`/admin/login`): dashboard analítico, usuarios, roles,
   auditoría y seguridad (2FA). El resto de la gestión (CMS, tema, productos,
   pagos, automatizaciones) está disponible vía las APIs `/api/admin/*`.
5. **Correos**: revisa Mailpit (http://localhost:8025) para ver las
   notificaciones automáticas (compra realizada, pago aprobado, etc.).

---

## 8. Comandos útiles

```bash
# Recalcular cachés (producción)
php artisan config:cache && php artisan route:cache && php artisan event:cache

# Reset total de la base de datos + re-seed
php artisan migrate:fresh --seed

# Disparar manualmente los recordatorios del calendario
php artisan notifications:dispatch-reminders

# Backups (Spatie) — requieren disco configurado (S3 por defecto)
php artisan backup:run

# Despliegue a producción (desde la raíz del repo)
./scripts/deploy.sh
```

---

## 9. Solución de problemas

| Síntoma | Causa probable / solución |
| ------- | ------------------------- |
| `419`/`CSRF` en peticiones del SPA | Revisa `SANCTUM_STATEFUL_DOMAINS` y `FRONTEND_URL` en `backend/.env`. |
| Colas no procesan | Asegura `QUEUE_CONNECTION=redis` y que **Horizon** (o `queue:work`) esté corriendo. |
| Correos no llegan | En local apuntan a **Mailpit** (`MAIL_HOST=mailpit`/`127.0.0.1`, puerto `1025`). |
| `connection refused` a Postgres/Redis (local) | Ajusta `DB_HOST`/`REDIS_HOST` a `127.0.0.1` y verifica que los servicios corran. |
| Falla la subida de archivos a S3 | Verifica credenciales `AWS_*` y que el bucket exista (MinIO lo crea al iniciar). |
| Permisos de Horizon en producción | Define el gate en `app/Providers/HorizonServiceProvider.php`. |

---

¡Listo! Con esto tienes la plataforma completa corriendo desde cero. Consulta
el [`README.md`](README.md) para el detalle de la arquitectura y de cada una de
las 12 fases.
