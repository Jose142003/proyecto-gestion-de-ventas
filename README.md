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

### 🧠 IA Predictiva (Nuevo)
- Predicción de ventas por producto usando promedios móviles y regresión lineal
- Alertas inteligentes de stock (crítico, bajo, exceso, sin movimiento)
- Recomendaciones de reabastecimiento basadas en tendencias
- Pronóstico de demanda con días estimados para agotar inventario
- Dashboard visual con comparación real vs pronóstico

### 📊 BI Dashboard (Nuevo)
- Panel de Business Intelligence con 6 KPIs en tiempo real
- Gráficos interactivos (Chart.js): tendencia de ventas 12 meses, distribución por método de pago, ventas por categoría
- Top 10 productos más vendidos y mejores clientes
- Tendencia de nuevos clientes y stock por categoría
- Análisis de crecimiento mes a mes

### 🔐 Autenticación 2FA (Nuevo)
- Autenticación de dos factores TOTP (Google Authenticator compatible)
- Generación de QR para escaneo rápido con Google Authenticator
- 8 códigos de respaldo de un solo uso
- Verificación obligatoria en inicio de sesión admin
- Activar/desactivar desde el panel de seguridad
- **Login QR mejorado** — El QR ahora genera una URL `otpauth://` válida escaneable por Google Authenticator; flujo en 2 pasos: correo → QR + contraseña + código 2FA
- **Verificación 2FA periódica** — Similar a WhatsApp: cada 24 horas pide re-ingresar el código de Google Authenticator, tanto en clientes (`pagina_modernizada`) como en admin (`panel_admin`)

### 🔔 Encuestas de satisfacción (Nuevo)
- Envío automático de encuesta de satisfacción por correo al confirmar un pedido
- Integración con el flujo de pago (transferencia y pago móvil)
- Función reutilizable para ser llamada desde cualquier parte del sistema

### 💰 Facturación automática (Mejora)
- Facturas por transferencia bancaria y pago móvil se marcan automáticamente como **pagadas**
- Facturas por efectivo y pago mixto quedan como **pendientes** hasta recibir el pago en persona
- Corrección de ID de producto en transferencia.html y pago_movil.html para evitar errores al procesar el pedido

### 💬 Telegram (Nuevo)
- Integración con Telegram Bot API
- Notificaciones automáticas de nuevos pedidos
- Alertas de stock bajo por Telegram
- Configuración desde el panel administrativo
- Mensajes de prueba para validar la integración

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

El sistema utiliza 28+ tablas, incluyendo:

`users` — `products` — `admin_users` — `clientes` — `pedidos` — `pedido_detalles` — `pedido_historial` — `facturas` — `factura_detalles` — `cart_items` — `historial_stock` — `movimientos_inventario` — `proveedores` — `compras` — `compra_detalles` — `caja_arqueos` — `caja_movimientos` — `configuracion_sistema` — `backups` — `auditoria_logs` — `secuencias_facturacion` — `predicciones_ventas` — `alertas_stock` — `sesiones_2fa` — `bi_metricas_diarias` — `formulas_tecnicas` — `compatibilidad_marcas` — `configuraciones_tablero` — `alertas_mantenimiento`

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
├── usuarios/           # Autenticación y perfiles
├── predicciones/       # IA Predictiva - pronósticos y alertas
├── 2fa/                # Autenticación de dos factores
├── bi/                 # Business Intelligence - analítica avanzada
└── telegram/           # Integración con Telegram Bot
```

## Tecnologías

- **Backend:** PHP 8+, PDO, MySQL
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5.2.3, jQuery 3.6+, Font Awesome 6
- **Entorno:** Laragon / XAMPP / WAMP
