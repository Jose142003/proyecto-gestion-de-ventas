# Justificación de Seguridad

## 1. Protección de Contraseñas — bcrypt

### Implementación
```php
$hash = password_hash($password, PASSWORD_BCRYPT);
// Verificación:
if (password_verify($password, $hash)) { ... }
```

### Justificación Técnica
- **bcrypt** es un algoritmo de hashing adaptativo que incluye un salt automático
- El factor de costo (12 en este sistema) hace que cada hash tome ~100ms, dificultando ataques de fuerza bruta
- A diferencia de MD5/SHA1/SHA256, bcrypt es **deliberadamente lento** y **resistente a ataques con GPU**
- `password_hash()` y `password_verify()` son funciones nativas de PHP que manejan automáticamente el versionado del algoritmo

### Alternativas Descartadas
| Algoritmo | Problema |
|-----------|----------|
| MD5 | Rompible en segundos con tablas rainbow |
| SHA1 | Colisiones demostradas (SHAttered) |
| SHA256 | Demasiado rápido para GPU (~10^10 hashes/segundo) |

## 2. Prevención de Inyección SQL — Prepared Statements

### Implementación
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND activo = 1");
$stmt->execute([$userId]);
```

### Justificación Técnica
- Los **prepared statements** separan la estructura SQL de los datos
- El driver PDO escapa automáticamente los parámetros, haciendo imposible la inyección
- Configuración: `PDO::ATTR_EMULATE_PREPARES => false` (desactiva emulación, usa prepared statements reales del motor MySQL)

### Patrón Incorrecto Evitado
```php
// NUNCA usar:
$sql = "SELECT * FROM users WHERE id = $userId";  // ¡Inyección SQL!
$result = mysqli_query($conn, $sql);
```

## 3. Protección CSRF (Cross-Site Request Forgery)

### Implementación
```php
// Generación del token
function generarTokenCSRF(): string {
    return bin2hex(random_bytes(32));  // 64 caracteres hexadecimales
}

// Validación con comparación de timing constante
function validarTokenCSRF(?string $token): bool {
    return hash_equals($_SESSION['_csrf_token'], $token);
}
```

### Justificación Técnica
- **Token único por sesión** generado con `random_bytes(32)` (criptográficamente seguro)
- **`hash_equals()`** previene **timing attacks** (comparación en tiempo constante)
- **Doble validación**: token se envía por header HTTP (`X-CSRF-Token`) y por campo oculto en formularios
- Aplicado a todos los endpoints que modifican estado (POST/PUT/DELETE)

### Flujo de Protección
1. Servidor genera token y lo almacena en `$_SESSION['_csrf_token']`
2. Token se envía al cliente via header `X-CSRF-Token` y campo `_csrf_token`
3. En cada petición mutante, el servidor verifica que el token coincida
4. Un atacante no puede leer el token (Same-Origin Policy) ni adivinarlo (64 caracteres aleatorios)

## 4. Configuración de Sesión Segura

### Implementación
```php
ini_set('session.cookie_httponly', 1);     // No accesible desde JavaScript
ini_set('session.use_only_cookies', 1);    // Solo cookies, no URL
ini_set('session.cookie_secure', 0);       // Solo HTTPS (1 en producción)
ini_set('session.cookie_samesite', 'Strict'); // No enviada en peticiones cross-site
```

### Justificación
- **HttpOnly**: Previene robo de cookie de sesión via XSS
- **SameSite=Strict**: Previene CSRF a nivel de navegador
- **Regeneración de ID**: `session_regenerate_id(true)` después de cada login exitoso

## 5. Rate Limiting en Login

### Implementación
```php
$maxIntentos = 5;            // Máximo 5 intentos
$ventanaMinutos = 15;        // En ventana de 15 minutos
if ($attempts['count'] >= $maxIntentos) {
    echo "Demasiados intentos. Intenta de nuevo en $espera minuto(s).";
    exit;
}
```

### Justificación
- Previene ataques de **fuerza bruta** y **diccionario**
- Almacena contador en `$_SESSION` (persistente por navegador)
- La ventana de 15 minutos con 5 intentos da un balance entre usabilidad y seguridad
- Mensaje informativo sin revelar si el usuario existe

## 6. Cabeceras de Seguridad HTTP

### Implementación
```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
```

### Justificación
- **X-Content-Type-Options**: Previene MIME-sniffing
- **X-Frame-Options: DENY**: Previene clickjacking
- **Referrer-Policy**: Controla información enviada en cabecera Referer

## 7. Validación de Datos de Entrada

### Implementación
- `filter_var($email, FILTER_VALIDATE_EMAIL)` para emails
- `intval()` y `floatval()` para valores numéricos
- `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')` para salida en HTML
- Validate against allowlists para campos como ORDER BY

### Justificación
- **Validación > Sanitización**: Se prefiere rechazar datos inválidos a modificarlos
- **Output escaping**: Toda salida que proviene de la BD o del usuario se escapa con `htmlspecialchars()` para prevenir XSS

## Resumen de Controles de Seguridad

| Control | Implementación | Severidad |
|---------|---------------|-----------|
| Hashing de contraseñas | bcrypt (PASSWORD_BCRYPT) | Crítica |
| Prepared Statements | PDO con `EMULATE_PREPARES=false` | Crítica |
| CSRF Token | `random_bytes(32)` + `hash_equals()` | Alta |
| HttpOnly Cookies | `session.cookie_httponly=1` | Alta |
| SameSite Cookies | `SameSite=Strict` | Alta |
| Regeneración de Sesión | `session_regenerate_id(true)` | Alta |
| Rate Limiting | 5 intentos / 15 min | Media |
| Output Escaping | `htmlspecialchars()` | Alta |
| Validación de Email | `FILTER_VALIDATE_EMAIL` | Media |
| CORS Restringido | Origen específico en lugar de `*` | Media |
| Logging de Errores | `error_log()` sin exposición pública | Media |
| IVA Configurable | Leído de `configuracion_sistema` | Baja |
