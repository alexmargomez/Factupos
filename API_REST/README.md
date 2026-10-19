# Factupos API REST — Backend

API RESTful desarrollada en **Laravel 11 (PHP 8.2)** con autenticación por tokens mediante **Laravel Sanctum**, base de datos **MariaDB 11.4** y generación de facturas en PDF/HTML con **SnappyPDF (`wkhtmltopdf`)**.

---

## Características Principales

- **Autenticación segura con Sanctum**: Inicio y cierre de sesión con tokens Bearer (`/api/login`, `/api/logout`).
- **Facturación POS Transaccional**:
  - Registro de ventas (`Sales`), detalles de repuestos (`Sale_details`), mano de obra (`Services`) y numeración consecutiva automática de facturas (`Invoices` con formato `FACT-000001`).
  - Descuento automático de existencias en `Inventory` y trazabilidad de entradas/salidas en `Inventory_movements` dentro de transacciones `DB::transaction`.
  - Acumulación y cierre diario de producción por mecánico/técnico (`Workers`).
- **Agendamiento de Citas (`Schedules`)**:
  - Gestión de citas pendientes y completadas con listado de servicios JSON, cliente, vehículo y mecánico asignado.
- **Generación de Facturas Imprimibles (`/api/invoices/pdf/{id}`)**:
  - Generación nativa de PDF con `wkhtmltopdf` y fallback automático a vista HTML imprimible con logo incrustado en Base64.

---

## Requisitos del Sistema

- **Con Docker (Recomendado)**:
  - Docker Engine `>= 24.0` y Docker Compose `v2`
- **Instalación Local (Sin Docker)**:
  - PHP `>= 8.2` (extensiones: `pdo_mysql`, `mbstring`, `xml`, `bcmath`, `gd`, `zip`)
  - Composer `>= 2.x`
  - MariaDB `>= 10.6` / `11.4`
  - `wkhtmltopdf` instalado en `/usr/bin/wkhtmltopdf`

---

## Despliegue Rápido con Docker Compose (Recomendado)

Desde la carpeta raíz del proyecto (`factuposAL/`), donde se encuentra el archivo `docker-compose.yml`:

```bash
# 1. (Opcional) Copiar variables de entorno en la raíz
cp .env.example .env

# 2. Construir y levantar MariaDB + Backend + Frontend
docker compose up -d --build
```

El contenedor `factupos-backend` realizará automáticamente:
1. Instalación de dependencias de Composer si no existen.
2. Creación del archivo `.env` a partir de `.env.example` y generación de `APP_KEY`.
3. Espera activa a que el servicio **MariaDB (`db`)** esté saludable (`service_healthy`).
4. Ejecución automática de migraciones (`php artisan migrate --force`) y seeders (`php artisan db:seed --force`).
5. Exposición de la API en `http://localhost:7000/api` (según `BACKEND_PORT`).

---

## Configuración de Variables de Entorno (`.env`)

El archivo `.env.example` viene preconfigurado para **MariaDB**:

```env
APP_NAME="Factupos"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=America/Bogota
BACKEND_PORT=7000
APP_URL=http://localhost:${BACKEND_PORT}

DB_CONNECTION=mariadb
DB_HOST=factupos-mariadb
DB_PORT=3306
DB_DATABASE=factupos
DB_USERNAME=factupos_user
DB_PASSWORD=factupos_pass

WKHTML_PDF_BINARY=/usr/bin/wkhtmltopdf
WKHTML_IMG_BINARY=/usr/bin/wkhtmltoimage
```

> **Nota para ejecución sin Docker**: Si ejecutas MariaDB directamente en tu máquina local, cambia `DB_HOST=127.0.0.1` y ajusta `DB_USERNAME` / `DB_PASSWORD` según tu servidor local.

---

## Usuarios y Datos Iniciales (Seeder)

Al ejecutarse `php artisan db:seed`, se crean automáticamente los siguientes accesos y catálogos iniciales:

| Usuario | Contraseña | Rol |
| :--- | :--- | :--- |
| `admin` | `admin123` | `Admin` |
| `Admin` | `123456` | `Admin` |

Además, se inicializan mecánicos base (`Mecánico General 1`, `Mecánico General 2`, `Técnico Eléctrico`) y categorías de inventario (`Repuestos`, `Lubricantes`, `Eléctricos`).

---

## Endpoints Principales de la API (`/api`)

Todas las rutas (excepto `/api/login`) requieren el encabezado `Authorization: Bearer <token>`:

| Recurso | Ruta Base | Operaciones Soportadas |
| :--- | :--- | :--- |
| **Autenticación** | `/api/login`, `/api/logout` | `POST` |
| **Clientes** | `/api/customers` | `GET /`, `POST /`, `GET /{id}`, `PUT /{id}`, `DELETE /{id}` |
| **Vehículos** | `/api/vehicles` | `GET /`, `POST /`, `GET /{id}`, `PUT /{id}`, `DELETE /{id}` |
| **Productos** | `/api/products` | `GET /`, `POST /`, `GET /{id}`, `PUT /{id}`, `DELETE /{id}` |
| **Inventario** | `/api/inventory` | `GET /`, `POST /`, `GET /{id}`, `PUT /{id}` |
| **Movimientos** | `/api/inventory-movements` | `GET /`, `POST /`, `GET /{id}`, `PUT /{id}`, `DELETE /{id}` |
| **Ventas (POS)** | `/api/sales` | `GET /`, `POST /`, `GET /{id}`, `PUT /{id}`, `DELETE /{id}` |
| **Detalles Venta** | `/api/sale-details` | `GET /`, `POST /`, `GET /{id}`, `PUT /{id}`, `DELETE /{id}` |
| **Servicios** | `/api/services` | `POST /`, `GET /{saleId}`, `GET /customer/{id}`, `GET /vehicle/{id}` |
| **Facturas** | `/api/invoices` | `GET /`, `POST /`, `GET /{saleId}`, `GET /pdf/{id}`, `DELETE /{id}` |
| **Agendamientos** | `/api/schedules` | `GET /`, `POST /`, `GET /{id}`, `PUT /{id}`, `DELETE /{id}` |
| **Mecánicos** | `/api/workers` | `GET /`, `POST /`, `GET /{id}`, `PUT /{id}`, `DELETE /{id}` |
