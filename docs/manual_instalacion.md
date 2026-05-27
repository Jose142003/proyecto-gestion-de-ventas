# Manual de Instalación — PIC Sistema de Gestión Comercial

---

## 1. Requisitos del Sistema

### 1.1. Software Requerido (Servidor)

| Componente | Versión Mínima | Recomendada |
|------------|---------------|-------------|
| PHP | 8.0 | 8.2+ |
| MySQL | 5.7 | 8.0+ / MariaDB 10.11+ |
| Apache | 2.4 | 2.4.57+ |
| Composer | 2.0 | 2.7+ |

### 1.2. Extensiones PHP Requeridas

```
ext-pdo       (PDO para conexión segura a BD)
ext-mysqli    (MySQLi para funcionalidades específicas)
ext-gd        (Procesamiento de imágenes)
ext-curl      (Peticiones HTTP: BCV, WhatsApp)
ext-mbstring  (Manejo de caracteres UTF-8)
ext-zip       (Compresión de backups)
ext-bcmath    (Operaciones matemáticas precisas)
```

### 1.3. Hardware Recomendado

| Entorno | RAM | Almacenamiento | Procesador |
|---------|-----|---------------|------------|
| Desarrollo (local) | 4 GB | 500 MB libres | Cualquier dual-core |
| Producción (baja carga) | 4 GB | 10 GB | 2 núcleos |
| Producción (media carga) | 8 GB | 20 GB | 4 núcleos |

### 1.4. Navegadores Soportados

- Google Chrome 120+
- Mozilla Firefox 115+
- Microsoft Edge 120+
- Safari 15+ (iOS)
- Chrome Android 120+

---

## 2. Instalación en Laragon (Recomendado para Windows)

### 2.1. Preparación

1. **Descargar e instalar Laragon** desde https://laragon.org/download/
2. **Iniciar Laragon** y asegurarse de que Apache y MySQL están encendidos (verde)
   - Click en "Start All" si no están iniciados

### 2.2. Configurar PHP

1. Click en Laragon → Menú → PHP → Versión → Seleccionar 8.0+ (si está disponible)
2. Click en Laragon → Menú → PHP → Extensiones → Asegurarse de que están activadas:
   - `gd`
   - `curl`
   - `mbstring`
   - `zip`
   - `bcmath`
   - `pdo_mysql`
   - `mysqli`
3. **Reiniciar Laragon** después de cambiar extensiones

### 2.3. Ubicar el proyecto

1. Abrir la carpeta "www" de Laragon:
   - Click en Laragon → Menú → Root → Abrir "www"
   - O navegar a `C:\laragon\www\`
2. **Copiar** la carpeta del proyecto (ej. `proyecto`) dentro de `C:\laragon\www\`
   - La ruta final debe ser: `C:\laragon\www\proyecto\`

### 2.4. Configurar la Base de Datos

**Opción A — Usando Laragon (recomendado):**

1. Click derecho en Laragon → Tools → Quick Database Admin → MySQL (abrirá HeidiSQL / Adminer)
2. Conexión:
   - Host: `127.0.0.1`
   - Usuario: `root`
   - Contraseña: (vacío)
   - Puerto: `3306`
3. En HeidiSQL / Adminer:
   - Archivo → Cargar archivo SQL → Seleccionar `sql/registro_usuarios.sql`
   - Ejecutar (F9)
   - Repetir con `sql/migracion_nuevas_funcionalidades.sql`
   - Repetir con `sql/asistente_tecnico.sql` (opcional)

**Opción B — Usando línea de comandos:**

```bash
# Abrir terminal en Laragon (Click → Terminal)
mysql -u root -p < sql/registro_usuarios.sql
mysql -u root -p < sql/migracion_nuevas_funcionalidades.sql
mysql -u root -p < sql/asistente_tecnico.sql
```

### 2.5. Configurar Variables de Entorno

1. En la carpeta del proyecto (`C:\laragon\www\proyecto\`), crear el archivo `.env`:

```bash
cp .env.example .env
```

2. Editar `.env` (puede usar Bloc de Notas o VS Code):

```env
DB_HOST=127.0.0.1
DB_NAME=carrito_db
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/proyecto

# SMTP - Configurar solo si se requiere envío de correos
SMTP_HOST=smtp.gmail.com
SMTP_USER=tu_correo@gmail.com
SMTP_PASS=tu_app_password
SMTP_PORT=587
SMTP_FROM_EMAIL=tu_correo@gmail.com
SMTP_FROM_NAME=PIC - Productos Industriales
```

### 2.6. Instalar Dependencias (Composer)

1. Abrir terminal en Laragon:
   - Click en Laragon → Menú → Terminal

2. Navegar al proyecto y ejecutar:
```bash
cd C:\laragon\www\proyecto
composer install
```

### 2.7. Verificar Instalación

1. Click en Laragon → "Start All"
2. Abrir navegador en: `http://localhost/proyecto`
3. Debería ver la página principal del sistema
4. Probar credenciales por defecto:
   - **Administrador**: admin@admin.com / admin123 (verificar en BD)
   - **Cliente**: registrarse desde la página de login

### 2.8. Ejecutar Pruebas (Opcional)

```bash
cd C:\laragon\www\proyecto
composer test
```

---

## 3. Instalación en XAMPP

### 3.1. Preparación

1. **Descargar e instalar XAMPP** desde https://www.apachefriends.org/
2. **Iniciar XAMPP Control Panel**
3. **Activar** Apache y MySQL (click en "Start")
4. Asegurar PHP 8.0+:
   - XAMPP incluye PHP por defecto, verificar versión en http://localhost/dashboard/phpinfo.php

### 3.2. Ubicar el proyecto

1. Abrir la carpeta `C:\xampp\htdocs\`
2. **Copiar** la carpeta del proyecto dentro de `htdocs`
   - Ruta final: `C:\xampp\htdocs\proyecto\`

### 3.3. Configurar la Base de Datos

1. Abrir `http://localhost/phpmyadmin`
2. Click en "Importar"
3. Seleccionar `sql/registro_usuarios.sql` y ejecutar
4. Repetir con `sql/migracion_nuevas_funcionalidades.sql`
5. Repetir con `sql/asistente_tecnico.sql` (opcional)

### 3.4. Configurar Variables de Entorno

```bash
# En la carpeta C:\xampp\htdocs\proyecto\
# Crear .env desde .env.example
```

Editar `.env` con los mismos valores del paso 2.5.

### 3.5. Instalar Dependencias (Composer)

```bash
# Abrir CMD como administrador
cd C:\xampp\htdocs\proyecto
composer install
```

Si Composer no está instalado, descargarlo desde https://getcomposer.org/ o usar:

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
php composer.phar install
```

### 3.6. Habilitar mod_rewrite

1. XAMPP Control Panel → Apache → Config → `httpd.conf`
2. Buscar: `#LoadModule rewrite_module modules/mod_rewrite.so`
3. Eliminar el `#` al inicio de la línea
4. Guardar y reiniciar Apache

### 3.7. Verificar

- Abrir `http://localhost/proyecto/`
- El sistema debería funcionar correctamente

---

## 4. Instalación en Servidor Linux (Producción)

### 4.1. Prerrequisitos

```bash
# Actualizar repositorios
sudo apt update && sudo apt upgrade -y

# Instalar Apache, PHP 8.2, MySQL y extensiones
sudo apt install -y apache2 \
                    mysql-server \
                    php8.2 \
                    php8.2-pdo \
                    php8.2-mysql \
                    php8.2-gd \
                    php8.2-curl \
                    php8.2-mbstring \
                    php8.2-zip \
                    php8.2-bcmath \
                    php8.2-xml \
                    libapache2-mod-php8.2 \
                    unzip \
                    curl

# Instalar Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"
```

### 4.2. Clonar / Subir el proyecto

```bash
# Opción 1: Clonar desde repositorio
cd /var/www/html
git clone <url-del-repositorio> proyecto

# Opción 2: Subir manualmente
# Usar SCP, SFTP o cualquier método para transferir los archivos
# a /var/www/html/proyecto/
```

### 4.3. Configurar Base de Datos

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE carrito_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'proyecto_user'@'localhost' IDENTIFIED BY 'contraseña_segura';
GRANT ALL PRIVILEGES ON carrito_db.* TO 'proyecto_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Importar esquemas
sudo mysql -u proyecto_user -p carrito_db < /var/www/html/proyecto/sql/registro_usuarios.sql
sudo mysql -u proyecto_user -p carrito_db < /var/www/html/proyecto/sql/migracion_nuevas_funcionalidades.sql
sudo mysql -u proyecto_user -p carrito_db < /var/www/html/proyecto/sql/asistente_tecnico.sql
```

### 4.4. Configurar Variables de Entorno

```bash
cd /var/www/html/proyecto
cp .env.example .env
nano .env
```

```env
DB_HOST=localhost
DB_NAME=carrito_db
DB_USER=proyecto_user
DB_PASS=contraseña_segura
DB_CHARSET=utf8mb4

APP_ENV=production
APP_DEBUG=false
APP_URL=https://midominio.com/proyecto

SMTP_HOST=smtp.gmail.com
SMTP_USER=notificaciones@midominio.com
SMTP_PASS=contraseña_app
SMTP_PORT=587
SMTP_FROM_EMAIL=notificaciones@midominio.com
SMTP_FROM_NAME=PIC - Productos Industriales
```

### 4.5. Instalar Dependencias

```bash
cd /var/www/html/proyecto
composer install --no-dev --optimize-autoloader
```

### 4.6. Configurar Permisos

```bash
sudo chown -R www-data:www-data /var/www/html/proyecto
sudo chmod -R 755 /var/www/html/proyecto
sudo chmod -R 775 /var/www/html/proyecto/uploads
sudo chmod -R 775 /var/www/html/proyecto/logs
sudo chmod -R 775 /var/www/html/proyecto/backups
```

### 4.7. Configurar Apache (VirtualHost)

Crear archivo: `/etc/apache2/sites-available/proyecto.conf`

```apache
<VirtualHost *:80>
    ServerName midominio.com
    ServerAdmin admin@midominio.com
    DocumentRoot /var/www/html/proyecto

    <Directory /var/www/html/proyecto>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/proyecto_error.log
    CustomLog ${APACHE_LOG_DIR}/proyecto_access.log combined
</VirtualHost>
```

```bash
# Habilitar mod_rewrite
sudo a2enmod rewrite

# Habilitar el sitio
sudo a2ensite proyecto.conf

# Deshabilitar sitio por defecto
sudo a2dissite 000-default.conf

# Verificar sintaxis
sudo apache2ctl configtest

# Reiniciar Apache
sudo systemctl restart apache2
```

### 4.8. Configurar SSL (HTTPS — Recomendado)

```bash
# Usar Certbot para SSL gratuito
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d midominio.com
```

### 4.9. Verificar

```bash
# Verificar que el sitio responde
curl -I https://midominio.com
```

---

## 5. Post-Instalación

### 5.1. Verificar Funcionamiento Básico

1. Abrir la URL del sistema
2. Registrar un nuevo usuario cliente
3. Iniciar sesión como administrador (usuario semilla en BD)
4. Verificar que el dashboard carga correctamente
5. Probar agregar un producto al carrito
6. Realizar una compra de prueba

### 5.2. Configurar Tareas Programadas (Opcional)

**Backups automáticos** (Linux — Crontab):

```bash
crontab -e
```

```cron
# Backup diario a las 2:00 AM
0 2 * * * /usr/bin/php /var/www/html/proyecto/backups/backup_automatico.php >> /var/log/proyecto-backup.log 2>&1

# Limpiar logs viejos (mayores a 30 días)
0 3 * * 0 find /var/www/html/proyecto/logs -name "*.log" -mtime +30 -delete
```

### 5.3. Configurar WhatsApp Business (Opcional)

1. Ir a Meta for Developers: https://developers.facebook.com/
2. Crear una app con producto WhatsApp
3. Configurar webhook
4. Copiar Token de Acceso y Phone Number ID
5. Agregar al `.env`:
```env
WHATSAPP_API_URL=https://graph.facebook.com/v17.0/<phone-number-id>/messages
WHATSAPP_API_TOKEN=<access-token>
WHATSAPP_NUMBER=584141234567
```

### 5.4. Configurar PWA (Opcional)

Editar `manifest.json` en la raíz del proyecto:

```json
{
  "name": "PIC - Sistema de Gestión Comercial",
  "short_name": "PIC",
  "start_url": "/proyecto/interfaz_usuario/index.html",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#1a237e",
  "icons": [...]
}
```

---

## 6. Solución de Problemas

### 6.1. Error "500 Internal Server Error"

```bash
# Verificar permisos
sudo chmod -R 755 /var/www/html/proyecto
sudo chown -R www-data:www-data /var/www/html/proyecto

# Verificar logs de Apache
sudo tail -f /var/log/apache2/error.log
```

### 6.2. Error "Class not found" o "Composer dependencies missing"

```bash
cd /var/www/html/proyecto
composer dump-autoload
composer install
```

### 6.3. Error de conexión a Base de Datos

- Verificar que MySQL está corriendo
- Verificar credenciales en `.env`
- Probar conexión manual:
```bash
mysql -u root -p -h 127.0.0.1
```

### 6.4. Error "403 Forbidden"

```bash
# Verificar config de Apache
sudo nano /etc/apache2/apache2.conf
```

Asegurar que tiene:
```apache
<Directory /var/www/html/>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### 6.5. Error "mod_rewrite no funciona"

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 6.6. Imágenes no se muestran

```bash
chmod -R 775 /var/www/html/proyecto/uploads
```

### 6.7. Error al enviar correos SMTP

- Verificar credenciales en `.env`
- Si usa Gmail, generar "Contraseña de aplicación" en https://myaccount.google.com/apppasswords
- Verificar que el puerto SMTP (587) no está bloqueado

### 6.8. PHPUnit no se ejecuta

```bash
cd /var/www/html/proyecto
composer install
./vendor/bin/phpunit --colors=always
```

---

## 7. Estructura de Archivos Relevante

```
proyecto/
├── .env                     # Configuración (NO incluir en git)
├── .env.example             # Plantilla de configuración
├── index.php                # Punto de entrada (redirección)
├── .htaccess                # Seguridad y reescritura
├── composer.json            # Dependencias PHP
├── manifest.json            # PWA manifest
├── sw.js                    # Service Worker
│
├── config/
│   ├── database.php         # Conexión a BD y constantes
│   └── i18n.php             # Internacionalización
│
├── sql/
│   ├── registro_usuarios.sql              # Esquema principal
│   ├── migracion_nuevas_funcionalidades.sql  # Migración adicional
│   └── asistente_tecnico.sql              # Módulo técnico
│
├── docs/                    # Documentación
│   ├── manual_tecnico.md
│   ├── manual_usuario.md
│   ├── validacion_sistema.md
│   ├── manual_instalacion.md
│   └── ...
│
├── uploads/                 # Archivos subidos (fotos de perfil)
├── logs/                    # Logs del sistema
├── backups/                 # Backups de base de datos
└── vendor/                  # Dependencias (Composer)
```

---

## 8. Desinstalación

### Eliminar el sistema completamente:

1. **Base de Datos:**
```sql
DROP DATABASE IF EXISTS carrito_db;
```

2. **Archivos:**
```bash
# Linux
sudo rm -rf /var/www/html/proyecto

# Windows (Laragon)
# Eliminar carpeta C:\laragon\www\proyecto
```

3. **Apache (si aplica):**
```bash
sudo a2dissite proyecto.conf
sudo systemctl restart apache2
sudo rm /etc/apache2/sites-available/proyecto.conf
```
