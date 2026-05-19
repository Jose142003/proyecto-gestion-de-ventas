# Diagrama de Clases - PIC Sistema de Gestión Comercial

## Diagrama UML (Mermaid)

```mermaid
classDiagram
    %% ===== MODELOS =====
    class Database {
        -instance: ?PDO
        +getConnection(): PDO
        +setHeaders(): void
        +getPdoOrError(): ?PDO
    }

    class Product {
        -pdo: PDO
        +findById(int id): ?array
        +getAll(?string category, ?string search, int limit, int offset): array
        +create(array data): int
        +update(int id, array data): bool
        +delete(int id): bool
        +updateStock(int id, int quantity): bool
    }

    class User {
        -pdo: PDO
        +findById(int id): ?array
        +findByEmail(string email): ?array
        +create(array data): int
        +updateLastLogin(int id): void
        +verifyPassword(string password, string hash): bool
    }

    class Factura {
        -pdo: PDO
        +findById(int id): ?array
        +getAllByMonth(int mes, int anio): array
        +getStatsByMonth(int mes, int anio): array
    }

    %% ===== SERVICIOS =====
    class EmailService {
        -mail: PHPMailer
        +send(string to, string subject, string body, ?string fromName): array
    }

    class StockService {
        -pdo: PDO
        +reduceStock(int productId, int quantity): bool
        +increaseStock(int productId, int quantity): void
        +getLowStockProducts(int threshold): array
        -logMovement(int productId, int quantity, string type): void
    }

    %% ===== CONTROLADORES =====
    class ProductController {
        -productModel: Product
        +index(): void
        +show(int id): void
    }

    %% ===== MIDDLEWARE =====
    class AuthMiddleware {
        +requireLogin(): void
        +requireAdmin(): void
        +requireCsrfToken(): void
    }

    %% ===== RELACIONES =====
    ProductController --> Product : usa
    ProductController --> Database : obtiene PDO via
    StockService --> Database : obtiene PDO via
    EmailService ..> PHPMailer : envoltura
    AuthMiddleware ..> $_SESSION : verifica
    Product ..> PDO : consultas
    User ..> PDO : consultas
    Factura ..> PDO : consultas
```

## Descripción de Capas

### Capa de Modelos (`src/Models/`)
Encapsulan la lógica de acceso a datos usando PDO con prepared statements.

- **Product**: CRUD de productos, consultas con filtros (categoría, búsqueda), control de stock.
- **User**: Autenticación, registro, verificación de contraseñas con bcrypt.
- **Factura**: Consultas de facturación, estadísticas mensuales.

### Capa de Servicios (`src/Services/`)
Lógica de negocio reutilizable.

- **EmailService**: Envío de correos vía SMTP con PHPMailer.
- **StockService**: Control de inventario con registro de movimientos.

### Capa de Controladores (`src/Controllers/`)
Manejan las peticiones HTTP y orquestan la respuesta.

- **ProductController**: Index (listado) y show (detalle) de productos.

### Capa de Middleware (`src/Middleware/`)
Interceptan peticiones para validación de acceso.

- **AuthMiddleware**: Verificación de sesión, roles admin, tokens CSRF.

### Funciones Helper (`conexion/conexion.php`)
- **Database**: Singleton de conexión PDO.
- **conectarDB()**: Wrapper de función global.
- **jsonResponse()**, **errorResponse()**: Respuestas JSON estandarizadas.
- **generarTokenCSRF()**, **validarTokenCSRF()**, **verificarCSRF()**: Protección CSRF.
- **requerirAdmin()**, **requerirSesion()**: Guards de autenticación.
