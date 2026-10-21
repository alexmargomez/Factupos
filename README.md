# 🧾 FactuPOS — Sistema Integral POS, Facturación, Inventario y Gestión de Taller

**FactuPOS** es una plataforma web moderna, rápida y autogestionable diseñada para la administración integral de puntos de venta (**POS**), facturación comercial en PDF/HTML, control de inventario con trazabilidad de movimientos y agendamiento de servicios de taller (mecánica, repuestos y mano de obra).

Construido bajo una arquitectura **Monorepo Multicontenedor** (`Frontend` + `Backend API REST` + `Base de Datos MariaDB`), permite operar tanto en servidores VPS administrados con **ALpanel** como en entornos locales mediante un único archivo `.env` y `docker compose`.

![React 19](https://img.shields.io/badge/React-19.1-61DAFB?logo=react&logoColor=black)
![Vite 6](https://img.shields.io/badge/Vite-6.3-646CFF?logo=vite&logoColor=white)
![Tailwind CSS v4](https://img.shields.io/badge/Tailwind_CSS-v4.1-06B6D4?logo=tailwindcss&logoColor=white)
![React Router v7](https://img.shields.io/badge/React_Router-v7.5-CA4245?logo=reactrouter&logoColor=white)
![Laravel 11](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel&logoColor=white)
![PHP 8.2](https://img.shields.io/badge/PHP-8.2_CLI-777BB4?logo=php&logoColor=white)
![Laravel Sanctum](https://img.shields.io/badge/Auth-Laravel_Sanctum_(Bearer)-FF2D20?logo=laravel&logoColor=white)
![MariaDB 11.4](https://img.shields.io/badge/MariaDB-11.4_LTS-003545?logo=mariadb&logoColor=white)
![Docker Compose](https://img.shields.io/badge/Docker-Multi--Container-2496ED?logo=docker&logoColor=white)

---

## ✨ ¿Qué es FactuPOS y qué resuelve?

**FactuPOS** centraliza en una sola interfaz todo el flujo operativo, comercial y financiero de un negocio de comercialización de repuestos y prestación de servicios técnicos/taller:

- 🛒 **Punto de Venta (POS) Ágil, Cotizaciones y Cartera:** Facturación combinada de repuestos y mano de obra, generación de **Cotizaciones Imprimibles / WhatsApp** sin descontar inventario, lectura de **Código de Barras / SKU**, **Pagos Mixtos** y control de **Créditos / Abonos (Cuentas por Cobrar)**.
- 📦 **Inventario Valorizado y Proveedores:** Control en tiempo real de existencias con **Costo de Compra vs. Precio de Venta**, cálculo de **Margen de Ganancia (%)**, utilidad proyectada en bodega, directorio de **Proveedores** y alertas personalizadas por `min_stock`.
- 🛠️ **Tablero Kanban de Taller y Recepción Técnica:** Órdenes de trabajo con estados visuales en tiempo real (`Recibido` $\rightarrow$ `En Diagnóstico` $\rightarrow$ `Esperando Repuesto` $\rightarrow$ `En Reparación` $\rightarrow$ `Listo para Entrega`), registro de kilometraje, observaciones de ingreso y carga automática en caja al facturar.
- 🔔 **Alertas de Mantenimiento, SOAT y Notificaciones WhatsApp:** Control de próximo cambio de aceite por kilometraje, vencimiento de **SOAT** y **Tecnomecánica**, y envío directo de facturas, cotizaciones, estados de taller y recordatorios por **WhatsApp (`wa.me`) en 1 clic**.
- 👨‍🔧 **Comisiones de Mecánicos, Arqueo de Caja y Exportación Excel (CSV):** Porcentaje de comisión configurable por técnico (`% Técnico` vs. `% Taller`), **Historial Auditable de Liquidaciones Pagadas**, registro de **Gastos Menores / Salidas de Caja**, utilidad neta real y exportación contable a **Excel (CSV)** en 1 clic.

---

## 💻 Tecnologías Usadas

El sistema está dividido en tres capas desacopladas y orquestadas con Docker:

### 1. Frontend (`Frontend/`)
- **[React 19](https://react.dev/) (`^19.1.0`)** — Biblioteca reactiva de interfaz de usuario con componentes funcionales y Hooks.
- **[Vite 6](https://vite.dev/) (`^6.3.2`)** — Servidor de desarrollo y empaquetador con proxy interno automático (`/api` $\rightarrow$ `backend`) y soporte de dominios externos (`allowedHosts: true`).
- **[Tailwind CSS v4](https://tailwindcss.com/) (`^4.1.4` + `@tailwindcss/vite`)** — Motor de estilos utilitarios de última generación para diseño responsivo.
- **[React Router DOM v7](https://reactrouter.com/) (`^7.5.0`)** — Enrutamiento SPA con protección de rutas privadas (`PrivateRoute`).
- **[React Icons](https://react-icons.github.io/react-icons/) (`^5.5.0`)** — Iconografía vectorial para módulos operativos y tableros.

### 2. Backend API REST (`API_REST/`)
- **[Laravel 11](https://laravel.com/) (`^11.9`) sobre [PHP 8.2](https://www.php.net/)** — Framework backend para la API RESTful, validaciones, transacciones ACID (`DB::transaction`) y ORM Eloquent.
- **[Laravel Sanctum](https://laravel.com/docs/11.x/sanctum) (`^4.0`)** — Autenticación stateless basada en tokens Bearer (`Authorization: Bearer <token>`).
- **[Laravel Snappy PDF](https://github.com/barryvdh/laravel-snappy) (`barryvdh/laravel-snappy` + `wkhtmltopdf`)** — Motor de renderizado de facturas comerciales en PDF y plantillas Blade HTML listas para impresión térmica o carta.
- **Middleware `trustProxies`** — Configurado para operar de forma transparente detrás de proxies inversos SSL (Caddy / Nginx en ALpanel).

### 3. Base de Datos e Infraestructura (`docker-compose.yml`)
- **[MariaDB 11.4 LTS](https://mariadb.org/)** — Motor relacional con volumen persistente (`mariadb_data`), `healthcheck` de auto-sincronización de credenciales y compatibilidad nativa con el gestor **Usuarios App** y **Base de Datos** de **ALpanel**.
- **[Docker & Docker Compose v2](https://docs.docker.com/compose/)** — Orquestación de los 3 contenedores (`factupos-mariadb`, `factupos-backend`, `factupos-frontend`) en red bridge aislada (`factupos-network`).

---

## 🛠️ Cómo Instalarlo y Desplegarlo

### Prerrequisitos
- [Git](https://git-scm.com/)
- [Docker](https://docs.docker.com/get-docker/) y [Docker Compose v2](https://docs.docker.com/compose/install/)

### 1. Clonar el Repositorio
```bash
git clone https://github.com/alexmargomez/Factupos.git
cd Factupos
```

### 2. Configurar Variables de Entorno (`.env`)
Toda la configuración del proyecto (puertos, credenciales de MariaDB, URLs y conexión entre contenedores) se controla desde un **único archivo `.env` en la raíz**:

```bash
cp .env.example .env
```

Contenido base del archivo `.env`:
```ini
# ==========================================
# Variables de Entorno - Docker Compose (Raíz)
# ==========================================

# Puertos (Solo cambias el puerto aquí y se aplica en todo el stack)
BACKEND_PORT=7000
FRONTEND_PORT=5173
DB_EXTERNAL_PORT=3309

# Base de Datos (MariaDB)
DB_CONNECTION=mariadb
DB_HOST=factupos-mariadb
DB_PORT=3306
DB_DATABASE=factupos
DB_USERNAME=factupos_user
DB_PASSWORD=factupos_pass
DB_ROOT_PASSWORD=factupos_pass

# Backend (Laravel API)
APP_NAME="Factupos"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:${BACKEND_PORT}
PHP_CLI_SERVER_WORKERS=4

# Frontend (React + Vite)
VITE_API_URL=/api
```

### 3. Construir y Levantar el Sistema
```bash
docker compose up -d --build
```

Al ejecutar este comando, Docker Compose realiza todo el aprovisionamiento sin intervención manual:
1. **`factupos-mariadb` (`3309:3306`)**: Inicializa el motor MariaDB 11.4 y sincroniza la base de datos `factupos` y las credenciales definidas en `.env`.
2. **`factupos-backend` (`7000:7000`)**: Espera a que MariaDB esté saludable (`service_healthy`), sincroniza variables, genera `APP_KEY`, ejecuta las migraciones (`php artisan migrate --force`) y los datos semilla (`php artisan db:seed --force`).
3. **`factupos-frontend` (`5173:5173`)**: Levanta la interfaz React 19 + Vite y redirige automáticamente las peticiones `/api/*` hacia `http://backend:7000`.

### 4. Despliegue y Configuración de Dominio en ALpanel
Si despliegas **FactuPOS** desde **ALpanel**:
1. Clona el proyecto o usa **`Git Pull & Restart`** / **`Rebuild`**.
2. En la sección **Dominios & SSL**, vincula tu dominio (ej. `https://factupos.sudoalexmar.tech`):
   - **Frontend**: `factupos-frontend` — Puerto **`5173`**
   - **Ruta `/api/*`** (opcional o vía proxy interno): `factupos-backend` — Puerto **`7000`**
3. No necesitas modificar `VITE_API_URL=/api` ni `APP_URL` en el `.env` al agregar o cambiar el dominio SSL.

### 5. Credenciales de Acceso por Defecto (Creadas por el Seeder)

| Usuario | Contraseña | Rol |
| :--- | :--- | :--- |
| `admin` | `admin123` | `Admin` |
| `Admin` | `123456` | `Admin` |

> También puedes crear, editar o cambiar contraseñas de usuarios en caliente desde el botón **`Usuarios App`** de **ALpanel**.

---

## 📂 Estructura del Proyecto (Monorepo)

```text
Factupos/
├── API_REST/               # Backend Laravel 11 (PHP 8.2 + Sanctum + SnappyPDF)
│   ├── app/
│   │   ├── Http/Controllers/ # Controladores REST (Ventas, Facturas, Inventario, Taller, Caja, Proveedores)
│   │   └── Models/           # Modelos Eloquent (User, Customer, Vehicle, Product, Supplier, Expense, WorkerSettlement...)
│   ├── database/
│   │   ├── migrations/       # Esquema relacional de tablas y evoluciones modulares
│   │   └── seeders/          # Usuarios admin, mecánicos y categorías iniciales
│   ├── resources/views/      # Plantillas Blade para facturas PDF/HTML imprimibles
│   ├── routes/api.php        # Definición de endpoints RESTful bajo /api
│   ├── Dockerfile            # Imagen PHP 8.2 CLI + Composer + wkhtmltopdf
│   └── .env.example          # Plantilla de entorno interna del Backend
├── Frontend/               # Frontend React 19 + Vite 6 + Tailwind CSS v4
│   ├── src/
│   │   ├── assets/           # Identidad visual y logos vectoriales de FactuPOS
│   │   ├── components/       # Modales, visor de facturas, buscadores, alertas y barra de navegación
│   │   ├── screens/          # Vistas: Login, Dashboard, POS, Taller Kanban, Inventario, Consultas, Reportes
│   │   └── services/         # Servicios auxiliares de consulta a la API
│   ├── vite.config.js        # Configuración de puertos dinámicos y proxy /api -> backend
│   ├── Dockerfile            # Imagen Node 20 Alpine + Vite
│   └── .env.example          # Plantilla de entorno del Frontend
├── docker-compose.yml      # Orquestador de MariaDB 11.4 + Backend + Frontend
├── .env.example            # Archivo maestro de variables de entorno
└── README.md               # Documentación general del sistema
```

---

## 🧩 Módulos Principales del Sistema

1. **Panel Principal (`/dashboard`)**:
   - Resumen operativo con accesos directos a todos los módulos del sistema y atajo rápido a nueva factura POS.
2. **Facturación POS y Cotizaciones (`/sales`)**:
   - Registro rápido de **Clientes** y **Vehículos** con alertas de vencimiento de **SOAT**, **Tecnomecánica** y **Cambio de Aceite**.
   - Carga automática de órdenes de trabajo pendientes al seleccionar un cliente.
   - Buscador con soporte de **Lector de Código de Barras / SKU** (agrega al carrito al presionar `Enter`).
   - Modo **Generar Cotización** (imprimible y enviable por WhatsApp sin descontar inventario) y **Facturar Venta** con soporte para `Efectivo`, `Transferencia / Nequi`, `Tarjeta`, `Pago Mixto` y `Crédito / Pendiente`.
3. **Agendamiento y Tablero Kanban de Taller (`/schedule`)**:
   - Recepción de vehículos con **Kilometraje de Ingreso**, **Estado Inicial** y **Observaciones / Inventario de Recepción**.
   - **Tablero Kanban de Taller en Vivo** con cambio de estado en 1 clic y botón de aviso directo por **WhatsApp** al propietario.
4. **Inventario Valorizado y Proveedores (`/inventory`)**:
   - Alta de productos con **Código SKU**, **Costo de Compra**, **Precio de Venta**, cálculo en vivo de **Margen de Ganancia (%)**, **Stock Mínimo** y **Proveedor**.
   - Entradas de mercancía con actualización opcional del costo unitario y directorio completo de **Proveedores**.
5. **Consultas, Cartera y Exportación (`/look`)**:
   - Explorador unificado para **Productos**, **Clientes**, **Vehículos**, **Ventas** (con botón **Abonar / Saldar Cartera**), **Pendientes** y **Movimientos**.
   - Botón **Exportar a Excel (CSV)** en cada pestaña para descargar la vista filtrada actual.
6. **Reportes, Arqueo de Caja y Liquidaciones (`/reports`)**:
   - **KPIs Financieros y Valorización de Bodega**: Total Facturado, Utilidad Neta Real, Repuestos + Ganancia, Mano de Obra, Cartera por Cobrar, Gastos de Caja, Capital en Bodega (Costo) y Valor Venta Potencial.
   - **Comisiones y Liquidación de Mecánicos**: Configuración de `% Comisión` por técnico, desglose `Bruto Día` / `Pago Técnico` / `Utilidad Taller` e **Historial de Liquidaciones Pagadas**.
   - **Gastos Menores / Salidas de Caja** y centro de **Exportación Contable a Excel (CSV)**.

---

## 🔌 Endpoints Principales de la API REST (`/api`)

Todas las rutas bajo `/api` (excepto `POST /api/login`) están protegidas mediante `auth:sanctum` (`Authorization: Bearer <token>`):

| Recurso | Endpoint Base | Operaciones | Descripción |
| :--- | :--- | :--- | :--- |
| **Autenticación** | `/api/login`, `/api/logout` | `POST` | Emisión y revocación de tokens Bearer |
| **Clientes** | `/api/customers` | `GET`, `POST`, `PUT`, `DELETE` | CRUD de clientes |
| **Vehículos** | `/api/vehicles` | `GET`, `POST`, `PUT`, `DELETE` | CRUD de vehículos, kilometraje, aceite, SOAT y Tecnomecánica |
| **Proveedores** | `/api/suppliers` | `GET`, `POST`, `PUT`, `DELETE` | Directorio de proveedores de repuestos e insumos |
| **Productos** | `/api/products` | `GET`, `POST`, `PUT`, `DELETE` | Catálogo con SKU, costo de compra, precio de venta y stock |
| **Inventario** | `/api/inventory` | `GET`, `POST`, `PUT` | Consulta y ajuste de existencias |
| **Movimientos** | `/api/inventory-movements` | `GET`, `POST`, `PUT`, `DELETE` | Entradas y salidas con actualización de costo y reversión |
| **Ventas y Cartera** | `/api/sales` | `GET`, `POST`, `PUT`, `DELETE` | Ventas POS, pagos mixtos, créditos y registro de abonos |
| **Detalles Venta** | `/api/sale-details` | `GET`, `POST`, `PUT`, `DELETE` | Ítems vendidos y descuento transaccional de stock |
| **Mano de Obra** | `/api/services` | `GET`, `POST` | Servicios por venta, cliente o vehículo |
| **Facturas** | `/api/invoices`, `/api/invoices/pdf/{id}` | `GET`, `POST`, `DELETE` | Numeración `FACT-XXXXXX` y visor PDF/HTML |
| **Taller / Kanban** | `/api/schedules` | `GET`, `POST`, `PUT`, `DELETE` | Órdenes de taller, kilometraje, notas y estados Kanban |
| **Gastos de Caja** | `/api/expenses` | `GET`, `POST`, `DELETE` | Salidas de caja menor y gastos operativos para arqueo |
| **Mecánicos y Pagos** | `/api/workers`, `/api/workers/{id}/settle`, `/api/workers/settlements` | `GET`, `POST`, `PUT`, `DELETE` | Técnicos, `%` de comisión y liquidaciones auditables |

