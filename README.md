# PIC - Sistema de Gestión Comercial

**Proyectos Industriales Del Centro** — Sistema integral de tienda, facturación, inventario y administración.

## Características

- **Autenticación dual** — Clientes y administradores con login separado y recuperación de contraseña por PIN
- **Catálogo de productos** — CRUD completo con categorías, imágenes, precios en BS y USD
- **Carrito de compras** — Gestión de carrito con actualización en tiempo real
- **Facturación completa** — Generación de facturas, PDF, historial, reportes exportables
- **Control de stock** — Historial de movimientos, alertas de inventario bajo
- **Gestión de usuarios** — Roles, permisos, fotos de perfil
- **Caja** — Arqueos y movimientos de caja diarios
- **Compras a proveedores** — Registro y gestión de órdenes de compra
- **Backups** — Creación y restauración de respaldos desde el panel
- **Auditoría** — Registro detallado de todas las acciones del sistema
- **Modo oscuro** — Interfaz adaptable con tema claro/oscuro
- **PWA** — Progressive Web App con service worker y modo offline parcial
- **Multi-moneda** — Precios en Bolívares y dólares con tasas actualizadas

## Seguridad implementada

- Contraseñas con **bcrypt** (password_hash / password_verify)
- Prepared statements con **PDO** contra inyección SQL
- Regeneración de ID de sesión en cada login
- Cookies de sesión con HttpOnly y SameSite=Lax
- Encabezados de seguridad HTTP (X-Content-Type-Options, X-Frame-Options)

## Requisitos

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache con mod_rewrite
- Extensiones PHP: PDO, MySQLi, GD, cURL, mbstring

## Instalación

```bash
# 1. Clonar el proyecto
git clone https://github.com/tu-usuario/proyecto.git

# 2. Importar la base de datos
mysql -u root -p < sql/registro_usuarios.sql

# 3. Configurar credenciales (opcional)
# Editar config/database.php o usar variables de entorno:
#   DB_HOST, DB_NAME, DB_USER, DB_PASS

# 4. Iniciar sesión
#   Admin: crear cuenta desde /interfaz_usuario/login.html
```

> **Nota:** Por defecto las credenciales son `root` sin contraseña en `localhost`.

## Base de datos

El sistema utiliza 20 tablas:

`users` — `products` — `admin_users` — `clientes` — `pedidos` — `pedido_detalles` — `facturas` — `factura_detalles` — `cart_items` — `historial_stock` — `movimientos_inventario` — `proveedores` — `compras` — `compra_detalles` — `caja_arqueos` — `caja_movimientos` — `configuracion_sistema` — `backups` — `auditoria_logs` — `secuencias_facturacion`

## Estructura del proyecto

```
├── admin/              # Panel de administración (API)
├── backups/            # Gestión de backups
├── carrito/            # Lógica del carrito de compras
├── clientes/           # Gestión de clientes
├── compras/            # Órdenes de compra
├── config/             # Configuración centralizada
├── conexion/           # Conexión a base de datos
├── facturacion/        # Módulo de facturación
├── img/                # Recursos gráficos
├── interfaz_usuario/   # Frontend para clientes
├── logs/               # Registros del sistema
├── panel_admin/        # Panel administrativo principal
├── proceso_compra/     # Flujo de compra
├── producto/           # Gestión de productos
├── proveedores/        # Gestión de proveedores
├── reportes/           # Reportes y auditoría
├── sql/                # Esquema de base de datos
├── stock/              # Control de inventario
├── tasas/              # Tasas de cambio (BCV)
├── uploads/            # Archivos subidos
└── usuarios/           # Autenticación y perfiles
```

## Tecnologías

- **Backend:** PHP 8+, PDO, MySQL
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5, jQuery
- **Entorno:** Laragon / XAMPP / WAMP
