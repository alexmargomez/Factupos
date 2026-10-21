# 🧾 FactuPOS — Sistema Integral POS, Facturación, Inventario Valorizado y Gestión Kanban de Taller

**FactuPOS** es una plataforma web autoalojada (self-hosted), rápida y transaccional diseñada para la administración integral de puntos de venta (**POS**), emisión de facturas y cotizaciones en PDF/HTML y WhatsApp, control de **inventario valorizado (Costo vs. Venta)** con lector de código de barras (**SKU**), directorio de proveedores, **tablero Kanban de órdenes de taller en vivo**, alertas preventivas de vehículos (**SOAT, Tecnomecánica y Cambio de Aceite**), arqueo de caja con control de cartera (créditos/abonos) y liquidación auditable de comisiones de mecánicos.

Construido bajo una arquitectura **Monorepo Multicontenedor** (`Backend API REST Laravel 11` + `Frontend React 19` + `Base de Datos MariaDB 11.4`), está optimizado para desplegarse tanto en servidores Linux VPS administrados con **ALpanel** como en entornos locales mediante un único archivo `.env` y `docker compose`.

![Laravel 11](https://img.shields.io/badge/Laravel-11.x_Enterprise-FF2D20?logo=laravel&logoColor=white)
![PHP 8.2](https://img.shields.io/badge/PHP-8.2_CLI_(50.7%25)-777BB4?logo=php&logoColor=white)
![React 19](https://img.shields.io/badge/React-19.1-61DAFB?logo=react&logoColor=black)
![Vite 6](https://img.shields.io/badge/Vite-6.3-646CFF?logo=vite&logoColor=white)
![Tailwind CSS v4](https://img.shields.io/badge/Tailwind_CSS-v4.1-06B6D4?logo=tailwindcss&logoColor=white)
![Laravel Sanctum](https://img.shields.io/badge/Auth-Laravel_Sanctum_(Bearer)-FF2D20?logo=laravel&logoColor=white)
![MariaDB 11.4](https://img.shields.io/badge/MariaDB-11.4_LTS-003545?logo=mariadb&logoColor=white)
![Docker Compose](https://img.shields.io/badge/Docker-Multi--Container-2496ED?logo=docker&logoColor=white)
![Architecture](https://img.shields.io/badge/Architecture-Domain_Services_%2B_FormRequests_%2B_Resources-8A2BE2)
![PHPUnit Tests](https://img.shields.io/badge/Tests-14_Suites_(PHPUnit_11)-3693F3?logo=php&logoColor=white)

---

## 🚀 ¿Qué hace único a FactuPOS?

A diferencia de sistemas POS genéricos que solo facturan productos sueltos de mostrador o agendas aisladas que no cruzan información con caja ni bodega:

1. **🛒 Motor POS Híbrido (Repuestos + Mano de Obra + Cotizaciones sin Descuento de Stock):**
   - Permite facturar en una sola transacción **repuestos físicos del inventario** y **servicios de mano de obra** asignados a un técnico específico.
   - Incluye modo **Generar Cotización / Presupuesto Imprimible**: calcula el total y genera el documento PDF/HTML o el mensaje de **WhatsApp en 1 clic** sin descontar unidades de la bodega ni afectar el cierre de caja hasta que el cliente apruebe el trabajo.
   - Soporte nativo para **Lector de Código de Barras / SKU**: al escanear un código en el buscador del POS y presionar `Enter`, el repuesto se agrega instantáneamente al carrito.
2. **💳 Pagos Mixtos, Cartera de Clientes (Créditos / Abonos) y Arqueo de Caja Real:**
   - Soporta ventas en **`Efectivo`**, **`Transferencia / Nequi`**, **`Tarjeta`**, **`Pago Mixto`** (combinando efectivo y transferencia con nota de desglose) y **`Crédito / Pendiente`**.
   - **Gestión de Cuentas por Cobrar:** registra el monto pagado (`paid_amount`) y el saldo pendiente (`pending_balance`), permitiendo realizar **abonos parciales o saldar la deuda total** posteriormente desde el módulo de Consultas (`/look`).
   - **Módulo de Gastos Menores / Salidas de Caja:** registra pagos operativos diarios (insumos, transporte, servicios) y los descuenta automáticamente en el **Arqueo de Caja y Utilidad Neta Real**.
3. **📦 Inventario Valorizado (Costo vs. Venta), Margen Comercial y Proveedores:**
   - Cada producto controla su **Código SKU**, **Costo de Compra Unitario (`cost_price`)**, **Precio de Venta (`price`)**, **Stock Mínimo de Alerta (`min_stock`)** y **Proveedor asociado**.
   - Calcula en tiempo real la **Utilidad Unitaria ($)**, el **Margen de Ganancia (%)**, el **Capital Invertido en Bodega al Costo** y el **Valor Potencial de Venta**.
   - Al registrar una entrada de mercancía, permite actualizar el costo de compra unitario y documentar la factura o referencia del proveedor en el Kardex.
4. **🛠️ Tablero Kanban de Taller en Vivo + Recepción Técnica con Kilometraje:**
   - Las órdenes de trabajo evolucionan visualmente a través de 5 etapas operativas: **`Recibido`** $\rightarrow$ **`En Diagnóstico`** $\rightarrow$ **`Esperando Repuesto`** $\rightarrow$ **`En Reparación`** $\rightarrow$ **`Listo para Entrega`**.
   - Registra el **kilometraje de ingreso** (actualizando automáticamente el odómetro del vehículo) y las **observaciones de recepción** (estado físico, nivel de combustible, accesorios dejados).
   - Cuando el cliente pasa a caja en `/sales`, el POS detecta sus órdenes pendientes y precarga automáticamente los servicios realizados para facturarlos sin doble digitación.
5. **🔔 Alertas Preventivas (SOAT, Tecnomecánica y Aceite) + Notificaciones WhatsApp en 1 Clic:**
   - Semáforo automático en `/sales`, `/look` y por consola (`factupos:check-alerts`) cuando un vehículo supera el kilometraje de su **próximo cambio de aceite** o tiene el **SOAT** / **Tecnomecánica** vencidos o próximos a vencer.
   - Integración directa con **`wa.me`** sin costo de API externa para enviar: comprobantes de factura, cotizaciones detalladas, avisos de *"Vehículo Listo para Entrega"* y recordatorios preventivos al propietario.
6. **👨‍🔧 Comisiones Configurables por Mecánico, Liquidación Auditable y Exportación Excel (CSV):**
   - Cada técnico posee su propio **`% de Comisión`** configurable (ej. `60% Técnico / 40% Taller` o `50% / 50%`).
   - Al cerrar el turno y pulsar **Liquidar**, el sistema ejecuta una transacción atómica que guarda el pago en el **Historial Auditable de Liquidaciones (`WorkerSettlements`)** y reinicia `totalDia = 0`.
   - Exportación contable inmediata a **Excel (CSV con BOM UTF-8)** desde `/reports`, `/look` y por CLI Artisan.

---

## 💻 Tecnologías Usadas

El sistema está dividido en tres capas desacopladas y orquestadas con Docker:

### 1. Backend API REST (`API_REST/` — 52.2% del repositorio)
- **[Laravel 11](https://laravel.com/) (`^11.9`) sobre [PHP 8.2](https://www.php.net/)** — Arquitectura por capas con **Controladores REST**, **7 Servicios de Dominio (`app/Services/`)**, **14 `FormRequests` (`app/Http/Requests/`)**, **9 `JsonResources` (`app/Http/Resources/`)**, **5 Comandos Artisan CLI (`app/Console/Commands/`)** y transacciones ACID (`DB::transaction`).
- **[Laravel Sanctum](https://laravel.com/docs/11.x/sanctum) (`^4.0`)** — Autenticación stateless basada en tokens Bearer (`Authorization: Bearer <token>`).
- **[Laravel Snappy PDF](https://github.com/barryvdh/laravel-snappy) (`barryvdh/laravel-snappy` + `wkhtmltopdf`)** — Motor de renderizado de facturas comerciales en PDF y plantillas Blade HTML listas para impresión térmica o carta.
- **[PHPUnit 11](https://phpunit.de/) (`^11.0.1`)** — Suite de **14 clases de pruebas automatizadas** (`Feature` y `Unit`) y **8 `Model Factories`** para validación continua.

### 2. Frontend SPA (`Frontend/` — 46.9% del repositorio)
- **[React 19](https://react.dev/) (`^19.1.0`)** — Interfaz reactiva modular basada en componentes funcionales y Hooks.
- **[Vite 6](https://vite.dev/) (`^6.3.2`)** — Servidor de desarrollo y bundler con proxy interno dinámico (`/api` $\rightarrow$ `backend`) y soporte de dominios externos (`allowedHosts: true`).
- **[Tailwind CSS v4](https://tailwindcss.com/) (`^4.1.4` + `@tailwindcss/vite`)** — Motor de estilos utilitarios para diseño responsivo de alta densidad visual.
- **[React Router DOM v7](https://reactrouter.com/) (`^7.5.0`)** — Enrutamiento SPA con protección de rutas privadas (`PrivateRoute`).
- **[React Icons](https://react-icons.github.io/react-icons/) (`^5.5.0`)** — Iconografía vectorial para tableros Kanban, POS y reportes.

### 3. Base de Datos e Infraestructura (`docker-compose.yml`)
- **[MariaDB 11.4 LTS](https://mariadb.org/)** — Motor relacional con volumen persistente (`mariadb_data`), `healthcheck` de auto-sincronización de credenciales y compatibilidad nativa con el inspector **Base de Datos** y **Usuarios App** de **ALpanel**.
- **[Docker & Docker Compose v2](https://docs.docker.com/compose/)** — Orquestación de los 3 contenedores (`factupos-mariadb`, `factupos-backend`, `factupos-frontend`) en red bridge aislada (`factupos-network`).

---

## 📦 Cómo Instalarlo y Desplegarlo (Local o VPS con ALpanel)

### 1. Requisitos previos
- [Git](https://git-scm.com/)
- [Docker](https://docs.docker.com/get-docker/) y [Docker Compose v2](https://docs.docker.com/compose/install/)

### 2. Clonar el Repositorio y Configurar `.env`
```bash
git clone https://github.com/alexmargomez/Factupos.git
cd Factupos
cp .env.example .env
```

Toda la configuración del stack (puertos, credenciales de MariaDB, URLs y proxy entre contenedores) se controla desde un **único archivo `.env` en la raíz**:

```ini
# ==========================================
# Variables de Entorno - Docker Compose (Raíz)
# ==========================================

# 1. Puertos (Sincronizados automáticamente por ALpanel)
BACKEND_PORT=7000
FRONTEND_PORT=5173
DB_EXTERNAL_PORT=3309

# 2. Base de Datos (MariaDB 11.4)
DB_CONNECTION=mariadb
DB_HOST=factupos-mariadb
DB_PORT=3306
DB_DATABASE=factupos
DB_USERNAME=factupos_user
DB_PASSWORD=factupos_pass
DB_ROOT_PASSWORD=factupos_pass

# 3. Backend (Laravel 11 API)
APP_NAME="Factupos"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:${BACKEND_PORT}
PHP_CLI_SERVER_WORKERS=4

# 4. Frontend (React 19 + Vite 6)
VITE_API_URL=/api
```

### 3. Construir y Levantar los Contenedores
```bash
docker compose up -d --build
```

Al iniciar el stack, Docker Compose ejecuta el aprovisionamiento completo sin intervención manual:
1. **`factupos-mariadb` (`3309:3306`)**: Inicializa MariaDB 11.4 y sincroniza la base de datos `factupos` y el usuario `factupos_user` definidos en `.env`.
2. **`factupos-backend` (`7000:7000`)**: Espera a que MariaDB esté saludable (`service_healthy`), genera `APP_KEY`, ejecuta todas las migraciones (`php artisan migrate --force`) y los datos semilla (`php artisan db:seed --force`).
3. **`factupos-frontend` (`5173:5173`)**: Levanta la SPA React 19 + Vite y redirige internamente `/api/*` hacia `http://backend:7000`.

*(Opcional)* Si deseas poblar la base de datos con un catálogo demostrativo completo de repuestos valorizados, proveedores y técnicos con porcentajes de comisión:
```bash
docker exec -it factupos-backend php artisan db:seed --class=WorkshopCatalogSeeder
```

---

## 📐 Convención de Variables y Despliegue en ALpanel

**FactuPOS** sigue el estándar de **3 Variables de Puerto** de **ALpanel**, permitiendo desplegarlo y vincularle un dominio HTTPS en segundos:

| Variable en `.env` | Comportamiento en ALpanel |
| :--- | :--- |
| **`FRONTEND_PORT`** (`5173`) | Puerto del contenedor `factupos-frontend` expuesto hacia el proxy inverso SSL (Caddy). |
| **`BACKEND_PORT`** (`7000`) | Puerto del contenedor `factupos-backend` (API Laravel 11) y destino del proxy interno de Vite. |
| **`DB_EXTERNAL_PORT`** (`3309`) | Puerto externo de MariaDB auto-asignado para conexiones desde DBeaver/TablePlus y backups `.sql` en 1 clic. |

### Credenciales de Acceso Iniciales (Seeder por Defecto)

| Usuario | Contraseña | Rol |
| :--- | :--- | :--- |
| `admin` | `admin123` | `Admin` |
| `Admin` | `123456` | `Admin` |

> También puedes crear usuarios adicionales o actualizar contraseñas en caliente desde el botón **`Usuarios App`** de **ALpanel**.

---

## ⚙️ Arquitectura de Ingeniería Backend Laravel 11 & Árbol del Proyecto

El backend en `API_REST/` implementa una arquitectura limpia desacoplada en **Controladores**, **Servicios de Dominio (`app/Services/`)**, **Validadores (`app/Http/Requests/`)**, **Transformadores (`app/Http/Resources/`)**, **Comandos CLI (`app/Console/Commands/`)** y **Pruebas Automatizadas (`tests/`)**:

```text
Factupos/
├── API_REST/                                # ⚙️ BACKEND LARAVEL 11 (PHP 8.2 + Sanctum + SnappyPDF + PHPUnit)
│   ├── app/
│   │   ├── Console/Commands/                # 🖥️ Comandos Artisan CLI de Diagnóstico, Auditoría y Exportación
│   │   │   ├── AuditInventoryCommand.php            # -> php artisan factupos:audit-inventory
│   │   │   ├── CheckVehicleAlertsCommand.php        # -> php artisan factupos:check-alerts
│   │   │   ├── CashboxCloseSummaryCommand.php       # -> php artisan factupos:cashbox-summary
│   │   │   ├── MechanicsSettlementReportCommand.php # -> php artisan factupos:mechanics-report
│   │   │   └── ExportAccountingCsvCommand.php       # -> php artisan factupos:export-csv
│   │   ├── Http/
│   │   │   ├── Controllers/                 # 🌐 Controladores RESTful de los 14 módulos de la API
│   │   │   │   ├── AnalyticsController.php          # Consolidado ejecutivo financiero, bodega, Kanban y alertas
│   │   │   │   ├── SaleController.php               # Ventas POS, pagos mixtos, créditos y abonos de cartera
│   │   │   │   ├── SaleDetailController.php         # Descuento transaccional de stock y reversión
│   │   │   │   ├── ProductController.php            # Catálogo SKU, costo vs venta y compras a proveedores
│   │   │   │   ├── SupplierController.php           # Directorio de proveedores mayoristas
│   │   │   │   ├── ScheduleController.php           # Órdenes de taller, kilometraje y flujo Kanban
│   │   │   │   ├── WorkerController.php             # Técnicos, % de comisión y liquidación auditable
│   │   │   │   ├── ExpenseController.php            # Gastos menores y salidas de caja para arqueo
│   │   │   │   ├── VehicleController.php            # Vehículos, odómetro, próximo aceite, SOAT y Tecno
│   │   │   │   ├── CustomerController.php           # Clientes y propietarios
│   │   │   │   ├── InvoiceController.php            # Numeración FACT-XXXXXX y renderizado PDF/HTML
│   │   │   │   ├── InventoryController.php          # Existencias en bodega
│   │   │   │   ├── InventoryMovementController.php  # Kardex de entradas y salidas con costo unitario
│   │   │   │   └── ServiceController.php            # Servicios de mano de obra e historial técnico
│   │   │   ├── Requests/                    # 🛡️ 14 Clases FormRequest con reglas tipadas y mensajes en español
│   │   │   │   ├── StoreProductRequest.php / UpdateProductRequest.php
│   │   │   │   ├── StoreSaleRequest.php / UpdateSalePaymentRequest.php
│   │   │   │   ├── StoreScheduleRequest.php / UpdateScheduleRequest.php
│   │   │   │   ├── StoreVehicleRequest.php / UpdateVehicleRequest.php
│   │   │   │   ├── StoreSupplierRequest.php / UpdateSupplierRequest.php
│   │   │   │   ├── StoreCustomerRequest.php / UpdateCustomerRequest.php
│   │   │   │   ├── StoreWorkerRequest.php / SettleWorkerRequest.php
│   │   │   │   ├── StoreExpenseRequest.php
│   │   │   │   └── StoreInventoryMovementRequest.php
│   │   │   └── Resources/                   # 📦 9 Clases Eloquent JsonResource con cálculos derivados
│   │   │       ├── ProductResource.php              # Incluye unit_profit, margin_percentage y valorización
│   │   │       ├── SaleResource.php                 # Incluye estado de cartera y saldo pendiente
│   │   │       ├── VehicleResource.php              # Incluye flags de alertas de aceite, SOAT y Tecno
│   │   │       ├── ScheduleResource.php
│   │   │       ├── WorkerResource.php               # Incluye desglose pago técnico vs utilidad taller
│   │   │       ├── WorkerSettlementResource.php
│   │   │       ├── SupplierResource.php
│   │   │       ├── ExpenseResource.php
│   │   │       ├── CustomerResource.php
│   │   │       └── InventoryMovementResource.php
│   │   ├── Models/                          # 🗄️ Modelos Eloquent Relacionales
│   │   │   ├── User.php, Customer.php, Vehicle.php, Category.php
│   │   │   ├── Product.php, Supplier.php, Inventory.php, InventoryMovement.php
│   │   │   ├── Sale.php, SaleDetail.php, Service.php, Invoice.php
│   │   │   └── Schedule.php, Worker.php, WorkerSettlement.php, Expense.php
│   │   └── Services/                        # 🧠 Servicios de Dominio y Analítica Financiera
│   │       ├── FinancialReportService.php           # Arqueo de caja, utilidad neta real y cartera por edades
│   │       ├── InventoryValuationService.php        # Valorización de bodega al costo/venta y punto de reorden
│   │       ├── WorkshopKanbanService.php            # Tablero Kanban y detector de alertas SOAT/Tecno/Aceite
│   │       ├── WorkerCommissionService.php          # Liquidaciones atómicas y nómina técnica por porcentaje
│   │       ├── PosBillingService.php                # Desglose de pagos mixtos/créditos y generador WhatsApp
│   │       ├── CsvAccountingExportService.php       # Exportador contable CSV con BOM UTF-8 para Excel
│   │       └── CustomerLoyaltyAndHistoryService.php # Expediente técnico de clientes y ranking de productos
│   ├── database/
│   │   ├── factories/                       # 🏭 9 Model Factories para pruebas automatizadas
│   │   ├── migrations/                      # 📐 Migraciones relacionales (incluyendo Fases 1 a 4)
│   │   └── seeders/                         # 🌱 DatabaseSeeder y WorkshopCatalogSeeder
│   ├── resources/views/                     # 🧾 Plantillas Blade para facturas PDF y vista de impresión
│   ├── routes/api.php                       # 🔌 Definición de todos los endpoints RESTful bajo /api
│   └── tests/                               # 🧪 Suite de 14 Clases de Pruebas Automatizadas (PHPUnit 11)
│       ├── Feature/                         # 11 Suites de integración HTTP, BD, Comandos y Recursos
│       └── Unit/                            # 3 Suites unitarias de matemáticas financieras y comisiones
│
├── Frontend/                                # 🎨 FRONTEND SPA (React 19 + Vite 6 + Tailwind CSS v4)
│   ├── src/
│   │   ├── screens/
│   │   │   ├── dashboard.jsx                # Panel principal de accesos rápidos
│   │   │   ├── sale.jsx                     # POS: Carrito, Lector SKU, Cotización, Pago Mixto y Crédito
│   │   │   ├── schedule.jsx                 # Recepción de vehículos y Tablero Kanban de Taller en vivo
│   │   │   ├── inventory.jsx                # Inventario valorizado (Costo/Margen %), Compras y Proveedores
│   │   │   ├── look.jsx                     # Explorador unificado de 6 módulos con buscador instantáneo
│   │   │   └── report.jsx                   # KPIs, Utilidad Neta, Arqueo, Gastos, Comisiones y Exportación CSV
│   │   └── components/
│   │       ├── DatesRegister.jsx            # Selector/alta de clientes y vehículos con alertas SOAT/Aceite
│   │       ├── LookProduct.jsx              # Buscador de catálogo con lector de código de barras (Enter)
│   │       ├── LookDetail.jsx               # Tablas interactivas, modal de Abonos de Cartera y Exportación CSV
│   │       ├── FactuView.jsx                # Visor de facturas y envío de comprobantes por WhatsApp
│   │       ├── ReportCustomer.jsx           # Expediente histórico de compras y servicios por cliente
│   │       └── ReportVehicle.jsx            # Hoja de vida técnica por placa de vehículo
│   └── vite.config.js                       # Proxy dinámico /api -> backend y soporte multi-dominio
│
├── docker-compose.yml                       # 🐳 Orquestador de MariaDB 11.4 + Backend + Frontend
├── .env.example                             # 📋 Plantilla maestra de variables de entorno
└── README.md                                # 📖 Documentación integral del sistema
```

---

## 🖥️ Comandos Artisan CLI de Diagnóstico, Auditoría y Exportación

Puedes ejecutar cualquiera de los **5 comandos Artisan especializados** directamente dentro del contenedor backend (`docker exec -it factupos-backend php artisan <comando>`):

| Comando Artisan | Opciones Disponibles | Descripción |
| :--- | :--- | :--- |
| `factupos:audit-inventory` | `--only-alerts`, `--json` | Audita el inventario valorizado (capital al costo, venta potencial, utilidad proyectada, margen ponderado) y lista los SKUs bajo el stock mínimo. |
| `factupos:check-alerts` | `--days=15`, `--km=500`, `--json` | Inspecciona el estado del tablero Kanban y detecta vehículos con cambio de aceite vencido/próximo, SOAT vencido o Tecnomecánica por vencer. |
| `factupos:cashbox-summary` | `--from=YYYY-MM-DD`, `--to=YYYY-MM-DD`, `--json` | Genera el arqueo de caja completo: total facturado, recaudado, cartera por cobrar, utilidad en repuestos, mano de obra, gastos y utilidad neta real. |
| `factupos:mechanics-report` | `--from=YYYY-MM-DD`, `--to=YYYY-MM-DD`, `--json` | Muestra el acumulado pendiente de liquidar por mecánico y el consolidado histórico de comisiones pagadas vs. utilidad retenida por el taller. |
| `factupos:export-csv` | `--type=all\|sales\|inventory\|settlements`, `--stdout` | Exporta los reportes contables en formato CSV (con BOM UTF-8 listo para Excel) en `storage/app/exports/` o por salida estándar. |

---

## 🧪 Pruebas Automatizadas Centralizadas (`API_REST/tests/` + PHPUnit 11)

El backend cuenta con **14 suites de pruebas automatizadas** divididas entre pruebas de integración (`tests/Feature/`) y pruebas unitarias (`tests/Unit/`):

```bash
# Ejecutar toda la suite de pruebas dentro del contenedor Backend
docker exec -it factupos-backend php artisan test

# Ejecutar una suite específica (ej. Ventas POS, Créditos y Abonos)
docker exec -it factupos-backend php artisan test --filter=PosSalesAndReceivablesTest
```

### Cobertura de las 14 Suites de Pruebas
1. **`PosSalesAndReceivablesTest`**: Registro de ventas de contado, ventas a crédito con saldo pendiente, múltiples abonos parciales hasta saldar cartera y descuento automático de inventario en Kardex.
2. **`InventoryValuationAndSuppliersTest`**: Creación de proveedores, alta de productos con SKU/costo/precio/stock mínimo, entradas de mercancía con actualización de costo unitario y endpoint de valorización de bodega.
3. **`WorkshopKanbanAndVehicleAlertsTest`**: Creación de órdenes de trabajo con actualización automática del kilometraje del vehículo, transiciones por las 5 columnas del tablero Kanban y detección de alertas de aceite, SOAT y Tecnomecánica.
4. **`WorkerCommissionsAndSettlementsTest`**: Configuración de porcentaje de comisión por técnico (`%`), acumulación de mano de obra diaria y liquidación transaccional auditable en `WorkerSettlements`.
5. **`CashboxExpensesAndReportsTest`**: Registro de gastos menores de caja y verificación matemática de utilidad bruta de repuestos, mano de obra, flujo operativo de caja y utilidad neta real.
6. **`AnalyticsEndpointsAndExportTest`**: Validación del endpoint ejecutivo `/api/analytics/summary`, exportación contable CSV y construcción de mensajes y enlaces profundos de WhatsApp (`wa.me`).
7. **`SanctumAuthenticationAndSecurityTest`**: Emisión de tokens Bearer en `/api/login`, rechazo de credenciales inválidas, bloqueo `HTTP 401` en endpoints protegidos y revocación de tokens en `/api/logout`.
8. **`CustomerAndVehicleHistoryReportTest`**: Trazabilidad histórica de repuestos y mantenimientos por cliente y por placa de vehículo, y generación de facturas `FACT-XXXXXX`.
9. **`CustomerLoyaltyAndTopProductsTest`**: Consolidación del expediente financiero/técnico del cliente (`CustomerLoyaltyAndHistoryService`), ranking de productos más vendidos y ejecución idempotente de `WorkshopCatalogSeeder`.
10. **`ArtisanDiagnosticCommandsTest`**: Ejecución automatizada de los comandos CLI `factupos:audit-inventory`, `factupos:check-alerts`, `factupos:cashbox-summary` y `factupos:mechanics-report`.
11. **`FormRequestValidationRulesTest`**: Validación exhaustiva de reglas y mensajes personalizados en español de las 14 clases `FormRequest`.
12. **`EloquentApiResourcesSerializationTest`**: Verificación de contratos JSON y campos calculados en las 9 clases `JsonResource`.
13. **`WorkerCommissionCalculationsUnitTest` & `InventoryValuationCalculationsUnitTest` & `FinancialAndMarginCalculationsTest`**: Pruebas unitarias puras en memoria de partición exacta de comisiones (`0%` a `100%`), márgenes comerciales, normalización de pagos mixtos/créditos y codificación CSV UTF-8 BOM.

---

## 🔌 Endpoints Principales de la API REST (`/api`)

Todas las rutas bajo `/api` (excepto `POST /api/login`) están protegidas mediante `auth:sanctum` (`Authorization: Bearer <token>`):

| Recurso | Endpoint Base | Operaciones | Descripción |
| :--- | :--- | :--- | :--- |
| **Autenticación** | `/api/login`, `/api/logout` | `POST` | Emisión y revocación de tokens Bearer Sanctum |
| **Analítica Ejecutiva** | `/api/analytics/summary`, `/financial`, `/inventory-valuation`, `/vehicle-alerts` | `GET` | Consolidado financiero, valorización de bodega, Kanban y alertas |
| **Clientes** | `/api/customers` | `GET`, `POST`, `PUT`, `DELETE` | CRUD de clientes y propietarios |
| **Vehículos** | `/api/vehicles` | `GET`, `POST`, `PUT`, `DELETE` | CRUD de vehículos, kilometraje, aceite, SOAT y Tecnomecánica |
| **Proveedores** | `/api/suppliers` | `GET`, `POST`, `PUT`, `DELETE` | Directorio de proveedores de repuestos e insumos |
| **Productos** | `/api/products`, `/api/products/{id}/purchase` | `GET`, `POST`, `PUT`, `DELETE` | Catálogo con SKU, costo de compra, precio de venta y stock |
| **Inventario** | `/api/inventory` | `GET`, `POST`, `PUT` | Consulta y ajuste de existencias en bodega |
| **Movimientos Kardex** | `/api/inventory-movements` | `GET`, `POST`, `PUT`, `DELETE` | Entradas y salidas con actualización de costo y reversión |
| **Ventas y Cartera** | `/api/sales` | `GET`, `POST`, `PUT`, `DELETE` | Ventas POS, pagos mixtos, créditos y registro de abonos |
| **Detalles Venta** | `/api/sale-details`, `/api/sale-details/customer/{id}` | `GET`, `POST`, `PUT`, `DELETE` | Ítems vendidos y descuento transaccional de stock |
| **Mano de Obra** | `/api/services`, `/api/services/customer/{id}`, `/vehicle/{id}` | `GET`, `POST` | Servicios por venta, cliente o placa de vehículo |
| **Facturas PDF** | `/api/invoices`, `/api/invoices/pdf/{id}` | `GET`, `POST`, `PUT`, `DELETE` | Numeración `FACT-XXXXXX` y visor PDF/HTML imprimible |
| **Taller / Kanban** | `/api/schedules` | `GET`, `POST`, `PUT`, `DELETE` | Órdenes de taller, kilometraje, notas y estados Kanban |
| **Gastos de Caja** | `/api/expenses` | `GET`, `POST`, `DELETE` | Salidas de caja menor y gastos operativos para arqueo |
| **Mecánicos y Pagos** | `/api/workers`, `/api/workers/{id}/settle`, `/api/workers/settlements` | `GET`, `POST`, `PUT`, `DELETE` | Técnicos, `%` de comisión y liquidaciones auditables |
