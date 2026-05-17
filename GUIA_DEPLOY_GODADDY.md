# Guía de Montaje — GoDaddy Hosting

## 1. Preparar archivos

En tu PC local:

1. Abre `C:\laragon\www\proyecto`
2. Selecciona TODAS las carpetas y archivos (excepto `.git/`)
3. Comprime en un `.zip` (botón derecho → Enviar a → Carpeta comprimida)
4. Nómbralo `proyecto.zip`

## 2. Subir archivos a GoDaddy

1. Entra a tu **GoDaddy Panel de Control** ([https://myh.godaddy.com](https://myh.godaddy.com))
2. Ve a **"Hosting Web"** → tu plan → **"cadmin-panel"** o **"Administrador"**
3. Abre el **"Administrador de Archivos"** (File Manager)
4. Navega a `public_html/` o `htdocs/`
5. Sube `proyecto.zip`
6. Haz clic derecho en `proyecto.zip` → **"Extraer"** (Extract)
7. Confirma que se creó la carpeta `proyecto/` con todos los archivos dentro

## 3. Crear la Base de Datos

1. En cPanel, ve a **"Bases de Datos MySQL"** (MySQL Databases)
2. **Crear BD:** Escribe un nombre (ej: `ventas_db`) → **Crear**
3. **Crear Usuario:** Abajo, crea un usuario con contraseña segura (ej: `ventas_user` + contraseña)
4. **Asignar Usuario:** Selecciona el usuario y la BD → marca **"TODOS LOS PRIVILEGIOS"** → Añadir
5. Ve a **"phpMyAdmin"**
6. Selecciona tu BD nueva
7. Ve a la pestaña **"SQL"**
8. En tu PC, abre uno de los archivos `.sql` de la carpeta `backups/backups/` con el Bloc de Notas
9. Copia TODO el contenido y pégalo en phpMyAdmin
10. Haz clic en **"Continuar"** — espera a que termine

## 4. Cambiar credenciales de BD en los PHP

En el Administrador de Archivos de GoDaddy, abre cada uno de estos archivos y cambia las líneas de conexión:

### Archivos a modificar (todos dicen `root`/`''`):

| Archivo | Buscar | Reemplazar |
|---|---|---|
| `conexion/conexion.php` | `$username = 'root';` | `$username = 'TU_USUARIO_BD';` |
| `conexion/conexion.php` | `$password = '';` | `$password = 'TU_CONTRASEÑA_BD';` |
| `conexion/conexion.php` | `$dbname = 'carrito_db';` | `$dbname = 'TU_NOMBRE_BD';` |
| Y en TODOS los demás PHP que tengan `$host = 'localhost'` con `$username = 'root'` | Igual | Igual |

**Búsqueda rápida:** Usa la función **"Buscar y Reemplazar"** del Administrador de Archivos de GoDaddy.

## 5. Verificar que funciona

1. Tu página estará en: `https://tudominio.com/proyecto/interfaz%20usuario/pagina_modernizada.html`
2. Prueba: Registrarte como cliente → Iniciar sesión → Ver productos
3. Prueba: Login admin en `https://tudominio.com/proyecto/panel%20admin/panel_admin.php`
4. Si ves errores, dime el texto exacto y los resuelvo

## 6. Activar HTTPS (SSL)

1. En cPanel de GoDaddy, ve a **"SSL/TLS"**
2. Activa el certificado gratuito (AutoSSL)
3. En el `.htaccess` que ya incluí, descomenta las líneas de redirección HTTPS

## Posibles errores comunes

| Error | Causa | Solución |
|---|---|---|
| `No se encuentra la base de datos` | Credenciales mal escritas | Revisa usuario/contraseña/nombre BD |
| `500 Internal Server Error` | PHP version incorrecta | En cPanel → "Seleccionar versión PHP" → Elige 7.4 o 8.0 |
| `Blank page / página en blanco` | Error PHP oculto | Ve a cPanel → "Errores" → Activa errores temporalmente |
| `Service Worker no funciona` | No hay HTTPS | Activa SSL en GoDaddy |

---

¿Necesitas ayuda con algún paso en específico? Avísame cuando estés en el paso de cambiar credenciales y te ayudo a encontrar exactamente qué líneas modificar en cada archivo.
