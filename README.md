# PIC - Sistema de Carrito de Compras

**Proyectos Industriales del Centro (PIC)**

Sistema web integral de comercio electrónico y gestión empresarial desarrollado en PHP nativo con PDO y MySQL.

## Módulos del Sistema

| Módulo | Descripción |
|--------|-------------|
| **Catálogo de Productos** | CRUD completo con categorías, búsqueda, filtros y productos destacados |
| **Carrito de Compras** | Persistencia en BD, cálculo de totales, actualización de cantidades |
| **Procesamiento de Pedidos** | Ciclo de vida completo: pendiente, procesando, enviado, entregado, facturado |
| **Facturación** | Generación de facturas desde pedidos, numeración secuencial, PDF |
| **Gestión de Clientes** | Registro, perfiles con foto, historial de compras |
| **Proveedores** | CRUD con datos de contacto, términos de pago, moneda |
| **Órdenes de Compra** | Órdenes a proveedores con ciclo de aprobación/recepción |
| **Caja / POS** | Apertura/cierre de caja, registro de ingresos/egresos, arqueo |
| **Control de Inventario** | Stock en tiempo real, historial de movimientos, alertas de stock bajo |
| **Reportes** | Reporte ejecutivo, ventas por cliente/vendedor, productos más vendidos, auditoría |
| **Respaldos** | Creación, descarga, restauración y eliminación de backups de BD |
| **Tasas BCV** | Scraper del Banco Central de Venezuela con 4 APIs de respaldo y caché |
| **Seguridad** | Autenticación dual (cliente/admin), sesiones con regeneración, .htaccess |
| **PWA** | Service worker, manifest, offline page, notificaciones push |

## Requisitos del Sistema

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache con mod_rewrite
- Extensiones PHP: PDO, mbstring, intl

## Instalación

1. Clonar el repositorio en `C:\laragon\www\proyecto` (Laragon) o `htdocs/proyecto` (XAMPP)
2. Importar `sql/carrito_db.sql` en phpMyAdmin o MySQL CLI
3. Configurar credenciales en `config/database.php` (por defecto: root sin contraseña)
4. Acceder vía: `http://localhost/proyecto/usuario/pagina_modernizada.html`

### Credenciales por Defecto

- **Admin:** admin@admin.com / Admin123
- **Cliente:** cliente@correo.com / Cliente123

## Arquitectura

```
proyecto/
├── admin/              # API de administración de usuarios
├── admin-panel/        # Panel de administración SPA
├── backups/            # Gestión de respaldos de BD
├── caja/               # Módulo de caja / POS
├── carrito/            # API del carrito de compras
├── clientes/           # API de clientes
├── compras/            # Órdenes de compra a proveedores
├── conexion/           # Conexión centralizada a BD (PDO)
├── config/             # Configuración del sistema
├── facturacion/        # Facturación y PDFs
├── proceso-compra/     # Procesamiento de pedidos y pagos
├── producto/           # CRUD de productos
├── proveedores/        # CRUD de proveedores
├── reportes/           # Reportes y auditoría
├── sql/                # Esquema de base de datos
├── stock/              # Control de inventario
├── tasas/              # Tasas BCV
├── usuario/            # Frontend del usuario (tienda)
├── usuarios/           # Autenticación y perfil
├── tests/              # Pruebas unitarias (PHPUnit)
├── vendor/             # Dependencias (Composer)
├── composer.json       # Dependencias PHP
└── phpunit.xml         # Configuración de PHPUnit
```

## Pruebas

```bash
composer test
```

## Base de Datos

El esquema incluye 20 tablas normalizadas con claves foráneas, índices compuestos, tipos ENUM, stored procedures para auto-numeración y soporte UTF8MB4 completo.

## Licencia

Proyecto académico - Trabajo Especial de Grado
