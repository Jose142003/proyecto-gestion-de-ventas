# Manual de Usuario — PIC Sistema de Gestión Comercial

## 1. Introducción

**PIC (Productos Industriales y Comerciales)** es un sistema integral de gestión comercial diseñado para la administración de tienda, facturación, inventario y control de caja. El sistema cuenta con dos interfaces: una para **clientes** (tienda en línea) y otra para **administradores** (panel de gestión).

---

## 2. Roles de Usuario

| Rol | Acceso | Funcionalidades |
|-----|--------|-----------------|
| **Cliente** | Tienda en línea | Ver productos, comprar, historial de pedidos |
| **Administrador** | Panel admin | Gestionar productos, pedidos, facturación, reportes |
| **Superadmin** | Panel admin + Config | Gestión de usuarios, configuración del sistema, backups |

---

## 3. Módulo Cliente (Tienda en Línea)

### 3.1. Inicio de Sesión
1. Acceder a `/interfaz_usuario/login.html`
2. Ingresar correo electrónico y contraseña
3. Seleccionar "Cliente" como tipo de usuario
4. Click en "Iniciar Sesión"

### 3.2. Registro de Cliente
1. Click en "Registrarse"
2. Completar: nombre, correo, contraseña, cédula, teléfono
3. Aceptar términos y condiciones
4. Click en "Crear Cuenta"

### 3.3. Catálogo de Productos
- Navegar por categorías (Sensores, Contactores, Relés, etc.)
- Buscar productos por nombre
- Ver detalles del producto (precio, stock, especificaciones)
- Productos destacados aparecen en la página principal

### 3.4. Carrito de Compras
1. Agregar productos al carrito desde la tienda
2. Ajustar cantidades en el carrito
3. Ver resumen con subtotal, IVA y total
4. Proceder al pago

### 3.5. Proceso de Pago
1. Seleccionar método de pago:
   - **Transferencia bancaria**: Ingresar número de referencia
   - **Pago Móvil**: Indicar número de teléfono y banco
   - **Efectivo**: Pago contra entrega
   - **Pago Mixto**: Combinación de efectivo + transferencia
2. Confirmar pedido
3. Recibir confirmación con número de pedido (formato: PED-2026-XXXXXX)

### 3.6. Recuperación de Contraseña
1. Click en "¿Olvidaste tu contraseña?"
2. Ingresar correo registrado
3. Recibir código PIN por email
4. Ingresar PIN y nueva contraseña

---

## 4. Módulo Administrador

### 4.1. Acceso al Panel
1. Acceder a `/interfaz_usuario/login.html`
2. Seleccionar "Administrador"
3. Ingresar credenciales de administrador
4. Redirección al panel: `/panel_admin/panel_admin.php`

### 4.2. Gestión de Productos
- **Listar productos**: Vista general con búsqueda y filtros
- **Crear producto**: Formulario con nombre, SKU, precio, stock, categoría, imagen
- **Editar producto**: Modificar cualquier campo del producto
- **Eliminar producto**: Borrado lógico (soft delete)
- **Productos destacados**: Marcar/desmarcar como destacado en tienda

### 4.3. Gestión de Pedidos
- **Listar pedidos**: Todos los pedidos con filtros por estado/fecha
- **Ver detalle**: Productos, cantidades, totales, datos del cliente
- **Actualizar estado**: Pendiente → Procesando → Facturado → Completado / Cancelado
- **Notificar cliente**: Enviar email de actualización de estado

### 4.4. Facturación
- **Generar factura**: Crear factura a partir de un pedido
- **Historial de facturas**: Búsqueda por fecha, cliente, método de pago
- **Exportar reportes**: Formato Excel y PDF (mensual)
- **Nueva factura**: Facturación directa sin pedido

### 4.5. Control de Inventario / Stock
- **Listar stock**: Vista general del inventario
- **Alertas de stock bajo**: Productos con stock ≤ 5 unidades
- **Historial de movimientos**: Registro de entradas y salidas
- **Ajustar stock**: Corregir cantidades manualmente

### 4.6. Gestión de Proveedores
- **CRUD completo**: Crear, editar, activar/desactivar proveedores
- **Datos**: Nombre comercial, RUC, teléfono, email, condiciones de pago

### 4.7. Compras a Proveedores
- **Orden de compra**: Crear órdenes con productos y cantidades
- **Estados**: Cotización → Aprobada → Enviada → Recibida parcial/total → Anulada
- **Historial**: Todas las compras con filtros

### 4.8. Módulo de Caja
- **Apertura de caja**: Iniciar jornada con monto inicial
- **Registrar movimientos**: Ingresos y egresos
- **Arqueo**: Cierre de caja con conteo de efectivo
- **Reporte de diferencias**: Monto esperado vs real

### 4.9. Reportes y Estadísticas
- **Ventas por vendedor**: Rendimiento de cada vendedor
- **Ventas por cliente**: Historial de compras de clientes
- **Dashboard CEO**: KPIs principales del negocio
- **Exportación**: Reportes en Excel y PDF

### 4.10. Usuarios y Administradores
- **Crear administradores**: Roles (superadmin, admin, vendedor)
- **Gestionar clientes**: Activar/desactivar cuentas
- **Permisos**: Control de acceso basado en roles

### 4.11. Configuración del Sistema
- **Datos de la empresa**: Nombre, RIF, dirección, teléfono, email
- **Parámetros de facturación**: Prefijos, IVA, moneda
- **Notificaciones**: Activar/desactivar notificaciones por email
- **Stock mínimo**: Umbral para alertas de inventario

### 4.12. Backups
- **Crear respaldo**: Backup completo de la base de datos
- **Restaurar**: Recuperar desde un backup previo
- **Historial**: Lista de backups con fecha y tamaño

### 4.13. Auditoría
- Registro detallado de todas las acciones del sistema
- Filtrar por usuario, acción, módulo y fecha
- Visualización de datos anteriores y posteriores a modificaciones

---

## 5. Atajos y Funcionalidades Transversales

### Modo Oscuro
- Botón de alternancia en el panel admin
- Persistencia de preferencia en el navegador

### Búsqueda Global
- Campo de búsqueda en la barra superior del panel admin
- Resultados en tiempo real mientras se escribe

### Notificaciones
- Campana de notificaciones en el panel admin
- Alertas de stock bajo, pedidos nuevos, etc.

### PWA (Progressive Web App)
- Instalable como aplicación en dispositivos móviles
- Modo offline parcial para consultas básicas
- Service worker para caché de recursos estáticos
