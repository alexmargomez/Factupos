# factupos-Frontend — Sistema POS, Inventario y Taller

Aplicación web moderna para punto de venta (POS), facturación, agendamiento de taller, control de inventario y reportes financieros de **Factupos**, construida con **React 19**, **Vite 6**, **Tailwind CSS v4** y **React Router v7**.

---

## Módulos de la Aplicación

1. **Inicio de Sesión (`/`)**:
   - Autenticación conectada con Laravel Sanctum, estados de carga y manejo de sesión persistente.
2. **Panel Principal (`/dashboard`)**:
   - Tarjetas de acceso rápido a todos los módulos operativos y botón directo a nueva factura POS.
3. **Facturación POS (`/sale`)**:
   - Registro y autocompletado inteligente de **Clientes** y **Vehículos** (con soporte para ventas de mostrador o taller).
   - Carga automática de servicios desde **citas pendientes** del cliente seleccionado.
   - Catálogo de **Productos** con indicadores de stock en tiempo real y pestaña de **Mano de Obra / Servicios**.
   - Carrito POS interactivo con controles de cantidad (`+` / `-`), edición directa de precios de servicios, selección de mecánico y método de pago (`Efectivo`, `Transferencia / Nequi`, `Tarjeta`).
   - Generación e impresión inmediata de la factura comercial en PDF/HTML.
4. **Agendamiento de Taller (`/schedule`)**:
   - Programación de citas de mantenimiento asignando cliente, vehículo, mecánico y lista de servicios por realizar.
5. **Gestión de Inventario (`/inventory`)**:
   - Creación de productos con stock inicial y ajuste rápido de entradas (`+`) y salidas (`-`) con buscador instantáneo.
6. **Consultas y Historiales (`/look`)**:
   - Navegación por pestañas para **Productos**, **Clientes** (con modal de historial de servicios por cliente), **Vehículos** (con modal de historial por placa), **Ventas** (con previsualización y reimpresión de factura), **Pendientes** y **Movimientos de Inventario** (entradas y salidas).
7. **Reportes y Cierre de Caja (`/report`)**:
   - KPIs financieros filtrables por **Hoy**, **Este Mes** e **Histórico** (Total Facturado, Ingresos por Repuestos, Ingresos por Mano de Obra).
   - Liquidación diaria de mecánicos con opción de **Cerrar Día (reiniciar acumulado a `$0`)** y creación de nuevos técnicos.
   - Panel de alertas de inventario para productos con stock bajo (`<= 5` unidades).

---

## Variables de Entorno (`.env`)

Copia el archivo `.env.example` a `.env`:

```bash
cp .env.example .env
```

Contenido de `.env.example`:

```env
# URL base de la API REST del Backend (Laravel)
VITE_API_URL="http://localhost:8000/api"
```

---

## Ejecución con Docker Compose (Recomendado)

Desde el directorio raíz del proyecto (`factuposAL/`):

```bash
docker compose up -d --build
```

- **Frontend disponible en**: `http://localhost:5173`
- **Backend API en**: `http://localhost:8000/api`
- **Base de datos**: MariaDB 11.4 (puerto externo `3307`)

---

## Ejecución Local para Desarrollo (Sin Docker)

```bash
# 1. Instalar dependencias
npm install

# 2. Configurar variables de entorno
cp .env.example .env

# 3. Iniciar servidor de desarrollo (puerto 5173)
npm run dev

# 4. Compilar para producción
npm run build
```

---

## Credenciales de Acceso por Defecto

Una vez ejecutadas las migraciones y el seeder del backend:

- **Usuario**: `admin` / **Contraseña**: `admin123`
- **Alternativo**: `Admin` / **Contraseña**: `123456`
