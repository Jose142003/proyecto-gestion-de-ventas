# Manual Técnico — PIC Sistema de Gestión Comercial

## 1. Arquitectura del Sistema

### Stack Tecnológico

| Componente | Tecnología | Versión |
|------------|------------|---------|
| Backend | PHP | 8.0+ |
| Base de Datos | MySQL / MariaDB | 5.7+ / 10.3+ |
| Frontend | HTML5, CSS3, JavaScript | - |
| Frameworks CSS | Bootstrap 5 | 5.x |
| Librerías JS | jQuery 3.x | 3.x |
| Envío de Correos | PHPMailer | 6.x |
| Testing | PHPUnit | 12.x |
| Servidor Web | Apache (mod_rewrite) | 2.4+ |

### Estructura del Proyecto

```
proyecto/
├── src/                    # Clases PSR-4 (Models, Services, Controllers)
├── admin/                  # API de administración
├── backups/                # Gestión de respaldos
├── carrito/                # Lógica del carrito de compras
├── clientes/               # Gestión de clientes
├── compras/                # Órdenes de compra
├── config/                 # Configuración centralizada
├── conexion/               # Conexión PDO + helpers
├── facturacion/            # Módulo de facturación
├── interfaz_usuario/       # Frontend para clientes
├── panel_admin/            # Panel administrativo principal
├── proceso_compra/         # Flujo de compra
├── producto/               # Gestión de productos
├── proveedores/            # Gestión de proveedores
├── reportes/               # Reportes y auditoría
├── sql/                    # Esquema SQL completo
├── stock/                  # Control de inventario
├── tasas/                  # Tasas de cambio BCV
├── usuarios/               # Autenticación y perfiles
├── tests/                  # Pruebas unitarias
├── vendor/                 # Dependencias Composer
├── docs/                   # Documentación técnica
└── uploads/                # Archivos subidos
```

### Patrón Arquitectónico

El sistema sigue un enfoque **modular** con separación en capas:

```
Petición HTTP → PHP Script → Helper/Función → PDO → MySQL
                                        ↓
                              AuthMiddleware → validación de acceso
                                        ↓
                              CSRF Token → verificación de integridad
                                        ↓
                              Prepared Statements → seguridad SQL
                                        ↓
                              JSON Response → respuesta al cliente
```

---

## 2. Configuración del Entorno

### Variables de Entorno (`.env`)

| Variable | Descripción | Valor por Defecto |
|----------|-------------|-------------------|
| DB_HOST | Host de la BD | localhost |
| DB_NAME | Nombre de la BD | carrito_db |
| DB_USER | Usuario de BD | root |
| DB_PASS | Contraseña de BD | (vacío) |
| DB_CHARSET | Juego de caracteres | utf8mb4 |
| APP_ENV | Entorno (development/production) | production |
| APP_DEBUG | Mostrar errores (true/false) | false |
| APP_URL | URL base del proyecto | http://localhost/proyecto |
| SMTP_HOST | Servidor SMTP | smtp.gmail.com |
| SMTP_USER | Usuario SMTP | (configurar) |
| SMTP_PASS | Contraseña SMTP (App Password) | (configurar) |
| SMTP_PORT | Puerto SMTP | 587 |
| SMTP_FROM_EMAIL | Email remitente | (configurar) |
| SMTP_FROM_NAME | Nombre remitente | PIC - Productos Industriales |

### Instalación

```bash
# 1. Clonar repositorio
git clone <url-del-repositorio> proyecto
cd proyecto

# 2. Configurar variables de entorno
cp .env.example .env
# Editar .env con credenciales reales

# 3. Importar base de datos
mysql -u root -p < sql/registro_usuarios.sql

# 4. Instalar dependencias
composer install

# 5. Configurar Apache
# Asegurar que mod_rewrite está habilitado
# DocumentRoot debe apuntar a la carpeta del proyecto

# 6. Ejecutar pruebas
composer test
```

### Requisitos del Servidor

- PHP 8.0+ con extensiones: PDO, MySQLi, GD, cURL, mbstring
- MySQL 5.7+ / MariaDB 10.3+
- Apache con mod_rewrite
- Composer (gestor de dependencias)

---

## 3. Base de Datos

### Esquema de 20 Tablas

| # | Tabla | Propósito |
|---|-------|-----------|
| 1 | `users` | Clientes del sistema |
| 2 | `admin_users` | Administradores y vendedores |
| 3 | `clientes` | Clientes fiscales (facturación) |
| 4 | `products` | Catálogo de productos (80 iniciales) |
| 5 | `cart_items` | Carrito de compras |
| 6 | `pedidos` | Órdenes de pedido |
| 7 | `pedido_detalles` | Líneas de pedido |
| 8 | `facturas` | Facturas emitidas |
| 9 | `factura_detalles` | Líneas de factura |
| 10 | `historial_stock` | Historial manual de stock |
| 11 | `movimientos_inventario` | Movimientos automatizados |
| 12 | `proveedores` | Proveedores registrados |
| 13 | `compras` | Órdenes de compra |
| 14 | `compra_detalles` | Líneas de compra |
| 15 | `caja_arqueos` | Apertura/cierre de caja |
| 16 | `caja_movimientos` | Ingresos/egresos de caja |
| 17 | `configuracion_sistema` | Parámetros configurables |
| 18 | `backups` | Historial de respaldos |
| 19 | `auditoria_logs` | Registro de actividades |
| 20 | `secuencias_facturacion` | Correlativos numéricos |

### Procedimientos Almacenados

- `sp_generar_numero_pedido`: Genera número correlativo de pedido por año
- `sp_generar_numero_factura`: Genera número correlativo de factura por año

---

## 4. API de Conexión a Base de Datos

### Clase Database (Singleton)

```php
// Ubicación: conexion/conexion.php
class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::$instance = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        }
        return self::$instance;
    }
}
```

### Función Global

```php
function conectarDB(): PDO {
    return Database::getConnection();
}
```

### Wrapper para Respuestas

```php
function jsonResponse(mixed $data, int $status = 200): void
function errorResponse(string $message, int $status = 400): void
```

---

## 5. Pruebas Unitarias (PHPUnit)

### Ejecución

```bash
composer test                    # Ejecutar todas las pruebas
composer test:verbose            # Ejecutar con información detallada
.\vendor\bin\phpunit --coverage-html coverage/  # Reporte de cobertura
```

### Casos de Prueba Implementados

| Test | Descripción | Estado |
|------|-------------|--------|
| `testDatabaseConstantsDefined` | Verifica constantes de BD | ✅ |
| `testBaseUrlConstantDefined` | Verifica BASE_URL | ✅ |
| `testSmptConstantsDefined` | Verifica constantes SMTP | ✅ |
| `testConfigEnvLoading` | Prueba carga de .env | ✅ |
| `testPasswordHashing` | Verifica bcrypt funciona | ✅ |
| `testCsrfTokenGeneration` | Verifica token CSRF de 64 caracteres | ✅ |
| `testEmailValidation` | Valida emails correctos/incorrectos | ✅ |
| `testJsonResponseStructure` | Verifica estructura JSON | ✅ |
| `testStockValidation` | Verifica cálculo de stock | ✅ |

### Reporte de Cobertura

**9 tests, 32 assertions — 100% de pruebas pasando.**

---

## 6. Seguridad Implementada

| Control | Implementación | Archivo |
|---------|---------------|---------|
| Hashing bcrypt | `password_hash(PASSWORD_BCRYPT)` | `procesar-login.php`, `procesar-registro.php` |
| Prepared Statements | PDO con marcadores `?` | Todos los archivos SQL |
| CSRF Token | `random_bytes(32)` + `hash_equals()` | `conexion/conexion.php` |
| Session HttpOnly | `session.cookie_httponly=1` | `procesar-login.php` |
| SameSite Strict | `session.cookie_samesite=Strict` | `procesar-login.php` |
| Rate Limiting | 5 intentos / 15 min | `procesar-login.php` |
| XSS Prevention | `htmlspecialchars(ENT_QUOTES, UTF-8)` | Múltiples archivos |
| CORS Restringido | Origen específico | `conexion/conexion.php` |
| Auditoría | Log de todas las acciones | `auditoria_logs` |

---

## 7. Funcionalidades Clave

### Carrito de Compras
- Validación de stock en tiempo real
- Impedir compras de administradores
- Actualización mediante PDO + prepared statements

### Facturación
- Números correlativos por año
- IVA configurable desde `configuracion_sistema`
- Transacciones atómicas (rollback automático en errores)

### PWA (Progressive Web App)
- Service worker: `sw.js`
- Manifest: `manifest.json`
- Estrategia de caché: stale-while-revalidate

### Tasas BCV
- Scraping del sitio oficial del BCV
- 4 APIs alternativas como fallback
- Cache local de 1 hora

---

## 8. Mantenimiento

### Logs

Los logs del sistema se almacenan en:

```
logs/
├── sistema_YYYY-MM-DD.log    # Log general del sistema
└── error_log.txt             # Errores de depuración
```

### Backups

Ubicación: `backups/` (archivos SQL comprimidos)

### Actualización de Tasas BCV

Endpoint: `/tasas/bcv_scraper.php`
Frecuencia: Cada hora (cache automático)
Fallbacks: ExchangeRate-API, Frankfurter, CurrencyAPI

### Licencia

Este proyecto es propiedad de **Proyectos Industriales Del Centro (PIC)**.
