# Factupos — Sistema POS, Facturación, Inventario y Taller

Repositorio unificado (**Monorepo**) de **Factupos**, compuesto por el Frontend web moderno en React 19 y el Backend API RESTful en Laravel 11 con MariaDB, completamente contenerizado con Docker Compose.

---

## Estructura del Proyecto

```text
factuposAL/
├── API_REST/               # Backend Laravel 11 (PHP 8.2 + Sanctum + SnappyPDF)
│   ├── app/                # Modelos Eloquent y Controladores REST
│   ├── database/           # Migraciones y Seeders iniciales
│   ├── resources/views/    # Plantillas Blade para facturas PDF/HTML
│   ├── routes/api.php      # Endpoints de la API REST
│   ├── Dockerfile          # Imagen PHP 8.2 + Composer + wkhtmltopdf
│   └── .env.example        # Variables de entorno del Backend
├── Frontend/               # Frontend React 19 + Vite 6 + Tailwind CSS v4
│   ├── src/
│   │   ├── components/     # Componentes reutilizables, modales y visores PDF
│   │   ├── screens/        # Vistas: Login, Dashboard, POS, Taller, Inventario, Consultas, Reportes
│   │   └── services/       # Servicios de consulta API
│   ├── Dockerfile          # Imagen Node 20 Alpine + Vite
│   └── .env.example        # Variables de entorno del Frontend
├── docker-compose.yml      # Orquestador de MariaDB 11.4 + API_REST + Frontend
├── .env.example            # Variables globales para Docker Compose
└── README.md               # Documentación general del proyecto
```

---

## Despliegue Rápido con Docker Compose (Recomendado)

### 1. Configurar variables de entorno (opcional)
```bash
cp .env.example .env
cp API_REST/.env.example API_REST/.env
cp Frontend/.env.example Frontend/.env
```

### 2. Construir e iniciar los contenedores
```bash
docker compose up -d --build
```

Al iniciar, Docker Compose levanta automáticamente los **3 servicios**:
1. **`factupos-mariadb` (`db`)**: Base de datos **MariaDB 11.4** con `healthcheck` y persistencia en volumen (`3307:3306`).
2. **`factupos-backend` (`API_REST`)**: Espera a que MariaDB esté lista, genera `APP_KEY`, ejecuta `php artisan migrate --force` y `php artisan db:seed --force`, y sirve la API en **`http://localhost:8000/api`**.
3. **`factupos-frontend` (`Frontend`)**: Levanta la aplicación React 19 + Vite en **`http://localhost:5173`**.

---

## Accesos por Defecto (Creados por el Seeder)

| Usuario | Contraseña | Rol |
| :--- | :--- | :--- |
| `admin` | `admin123` | `Admin` |
| `Admin` | `123456` | `Admin` |

---

## Módulos Principales (`Frontend/`)

- **Facturación POS (`/sale`)**: Selección/creación rápida de clientes y vehículos, carga automática de servicios agendados, catálogo con control de stock, carrito POS con ajuste de cantidades (`+`/`-`), asignación de mecánico, método de pago e impresión inmediata de factura.
- **Agendamiento de Taller (`/schedule`)**: Registro de citas pendientes con cliente, vehículo, mecánico asignado y listado de servicios.
- **Gestión de Inventario (`/inventory`)**: Creación de productos con stock inicial y registro de entradas (`+`) y salidas (`-`) con trazabilidad.
- **Consultas e Historiales (`/look`)**: Búsqueda y edición de Productos, Clientes (con historial de servicios), Vehículos (con historial por placa), Ventas (con visor y reimpresión de facturas), Pendientes y Movimientos de Inventario.
- **Reportes y Cierre de Caja (`/report`)**: Indicadores financieros (Total Facturado, Repuestos, Mano de Obra), alertas de stock bajo y liquidación/reinicio diario por mecánico.

---

## Endpoints Principales (`API_REST/`)

Todas las rutas bajo `/api` (excepto `/api/login`) están protegidas por `auth:sanctum`:

- `POST /api/login` / `POST /api/logout` — Autenticación Bearer Token
- `GET|POST|PUT|DELETE /api/customers` — Gestión de clientes
- `GET|POST|PUT|DELETE /api/vehicles` — Gestión de vehículos
- `GET|POST|PUT|DELETE /api/products` — Catálogo de productos e inventario sincronizado
- `GET|POST|PUT /api/inventory` — Control de existencias
- `GET|POST|DELETE /api/inventory-movements` — Historial de entradas y salidas
- `GET|POST|PUT|DELETE /api/sales` — Ventas POS con relaciones precargadas
- `GET|POST|PUT|DELETE /api/sale-details` — Detalles de venta con descuento automático de stock
- `GET|POST /api/services` — Mano de obra por venta, cliente o vehículo
- `GET|POST|DELETE /api/invoices` y `GET /api/invoices/pdf/{id}` — Facturas y generación PDF/HTML
- `GET|POST|PUT|DELETE /api/schedules` — Citas y agendamientos de taller
- `GET|POST|PUT|DELETE /api/workers` — Mecánicos y acumulado diario (`totalDia`)
