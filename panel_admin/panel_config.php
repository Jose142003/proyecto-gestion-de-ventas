<?php
// panel_config.php - shared config for standalone admin pages
// Include in <head> after page title
?>
<style>
html body:not(.dark-mode) {
    --primary-color: #1a1f3a;
    --secondary-color: #e8ecf4;
    --accent-color: #3C91ED;
    --light-color: #5aa9e6;
    --bg-color: #f0f2f5;
    --text-color: #1a1f3a;
    --card-bg: #ffffff;
    --sidebar-bg: #1a1f3a;
    --success: #2ed573;
    --warning: #ffa502;
    --danger: #ff4757;
    --info: #3498db;
    --border-color: #d1d8e6;
    --table-hover: rgba(60,145,237,0.05);
}
body.dark-mode {
    --primary-color: #0a0e1a;
    --secondary-color: #1a1f2e;
    --accent-color: #3C91ED;
    --light-color: #5aa9e6;
    --bg-color: #0f1219;
    --text-color: #e4e6eb;
    --card-bg: #1e2436;
    --sidebar-bg: #0a0e1a;
    --success: #2ed573;
    --warning: #ffa502;
    --danger: #ff4757;
    --info: #3498db;
    --border-color: #2c3348;
    --table-hover: rgba(60,145,237,0.1);
}
body.dark-mode .card,
body.dark-mode .modal-content { background:var(--card-bg); color:var(--text-color); border-color:var(--border-color); }
body.dark-mode .modal-header,
body.dark-mode .modal-footer { border-color:var(--border-color); }
body.dark-mode .form-control,
body.dark-mode .form-select { background:#1a1f2e; color:#e4e6eb; border-color:var(--border-color); }
body.dark-mode .form-control:focus,
body.dark-mode .form-select:focus { background:#1a1f2e; color:#e4e6eb; }
body.dark-mode .form-check-input { background:#1a1f2e; border-color:var(--border-color); }
body.dark-mode .data-table td { border-color:var(--border-color); }
body.dark-mode .data-table tr:hover td { background:var(--table-hover); }
body.dark-mode .summary-card { box-shadow:0 2px 8px rgba(0,0,0,0.3); }
body.dark-mode .summary-card .label { color:#999; }
body.dark-mode .table-container { box-shadow:0 2px 8px rgba(0,0,0,0.3); }
body.dark-mode .btn-secondary { background:#2c3348; border-color:#3a4158; color:#e4e6eb; }
body.dark-mode .btn-secondary:hover { background:#3a4158; }
body.dark-mode .bg-light { background:var(--secondary-color) !important; }
body.dark-mode .border { border-color:var(--border-color) !important; }
body.dark-mode .token-display { background:#0a0e1a !important; }
body.dark-mode .text-muted { color:#888 !important; }
body.dark-mode ::-webkit-calendar-picker-indicator { filter:invert(1); }
body.dark-mode .list-group-item { background:var(--card-bg); color:var(--text-color); border-color:var(--border-color); }
body.dark-mode .list-group-item:hover { background:var(--secondary-color); }
body.dark-mode .btn-close { filter:invert(1); }
body.dark-mode .sidebar-controls { border-top-color:rgba(255,255,255,0.15); }

/* Light mode overrides for panel_admin.php elements */
html body:not(.dark-mode) .header { background:linear-gradient(135deg,#1a1f3a,#2a3050); color:#fff; }
html body:not(.dark-mode) .header h2 { color:#fff; }
html body:not(.dark-mode) .user-info { background:rgba(255,255,255,0.05); }
html body:not(.dark-mode) .user-details h3 { color:#fff; }
html body:not(.dark-mode) .user-details p { color:rgba(255,255,255,0.6); }
html body:not(.dark-mode) .logout-btn { background:rgba(255,255,255,0.1); color:rgba(255,255,255,0.8); }
html body:not(.dark-mode) .logout-btn:hover { background:var(--danger); color:#fff; }
html body:not(.dark-mode) .menu-section-title { color:rgba(255,255,255,0.4); }
html body:not(.dark-mode) .sidebar-search input { background:rgba(255,255,255,0.1); color:#fff; }
html body:not(.dark-mode) .sidebar-search input::placeholder { color:rgba(255,255,255,0.5); }
html body:not(.dark-mode) .sidebar { background:var(--sidebar-bg); }
html body:not(.dark-mode) a.menu-item,
html body:not(.dark-mode) .menu-item[data-section],
html body:not(.dark-mode) .sidebar-controls .menu-item { color:rgba(255,255,255,0.8); }
html body:not(.dark-mode) a.menu-item:hover,
html body:not(.dark-mode) .menu-item[data-section]:hover { background:rgba(41,78,144,0.3); color:#fff; }
html body:not(.dark-mode) .kpi-card { background:linear-gradient(135deg,var(--primary-color),#2a3050); color:#fff; }
html body:not(.dark-mode) .stat-label { color:#666; }
html body:not(.dark-mode) .card-footer { color:#666; }
html body:not(.dark-mode) .resultado-seccion .subtexto { color:#888; }
html body:not(.dark-mode) #resultadosBusquedaSecciones { box-shadow:0 10px 30px rgba(0,0,0,0.15); }
html body:not(.dark-mode) .buscador-secciones-wrapper { box-shadow:0 4px 15px rgba(0,0,0,0.1); }
html body:not(.dark-mode) .card,
html body:not(.dark-mode) .stat-card,
html body:not(.dark-mode) .table-container { box-shadow:0 2px 8px rgba(0,0,0,0.08); }
div.sidebar { display:flex; flex-direction:column; }
div.sidebar > .menu { flex:1; overflow-y:auto; min-height:0; }
.sidebar-controls { border-top:1px solid rgba(255,255,255,0.1); padding:10px 15px; }
.sidebar-controls .menu-item { font-size:0.85rem; cursor:pointer; }
.sidebar-controls .menu-item i { margin-right:15px; width:20px; }
</style>
<script>
(function(){
var s=localStorage.getItem("darkMode");
if(s==="enabled"||s===null) document.body.classList.add("dark-mode");
var _t={es:{panel_admin:"Panel Admin",dashboard:"Dashboard",almacenes:"Almacenes",cuentas_cobrar:"Cuentas Cobrar",cuentas_pagar:"Cuentas Pagar",notas_credito:"Notas Crédito",variantes:"Variantes",api_tokens:"API Tokens",almacenes_title:"Gestión de Almacenes",cuentas_cobrar_title:"Cuentas por Cobrar",cuentas_pagar_title:"Cuentas por Pagar",notas_credito_title:"Notas de Crédito",variantes_title:"Variantes de Productos",api_tokens_title:"API Tokens",
mi_cuenta:"Mi Cuenta",mi_perfil:"Mi Perfil",gestion:"Gestión",usuarios:"Usuarios",productos:"Productos",proveedores:"Proveedores",compras:"Compras",ventas:"Ventas",pedidos:"Pedidos",cotizaciones:"Cotizaciones",crm:"CRM",facturacion:"Facturación",caja_arqueo:"Caja / Arqueo",herramientas:"Herramientas",asistente_tecnico:"Asistente Técnico",ia_predictiva:"IA Predictiva",bi_dashboard:"BI Dashboard",reportes:"Reportes",ventas_cliente:"Ventas por Cliente",ventas_vendedor:"Ventas por Vendedor",productos_vendidos:"Productos más Vendidos",historial_compras:"Historial de Compras",auditoria:"Auditoría",reporte_general:"Reporte General Ejecutivo",reporte_especifico:"Reporte Específico",sistema:"Sistema",panel_ceo:"Panel CEO",configuracion:"Configuración",telegram:"Telegram",backup:"Backup",marketing:"Marketing",reporte_stock:"Reporte de Stock",seguridad:"Seguridad",autenticacion_2fa:"Autenticación 2FA",cerrar_sesion:"Cerrar Sesión",
modo_oscuro:"Modo Oscuro",modo_claro:"Modo Claro",idioma:"Idioma: Español",idioma_en:"Language: English",
cargando:"Cargando...",error_conexion:"Error de conexión",guardar:"Guardar",cancelar:"Cancelar",cerrar:"Cerrar",filtrar:"Filtrar",nuevo:"Nuevo",acciones:"Acciones",editar:"Editar",eliminar:"Eliminar",ver:"Ver",estado:"Estado",fecha:"Fecha",total:"Total",cliente:"Cliente",producto:"Producto",cantidad:"Cantidad",precio:"Precio",codigo:"Código",nombre:"Nombre",descripcion:"Descripción",buscar:"Buscar",seleccionar:"Seleccionar...",sin_resultados:"Sin resultados",todos:"Todos",
pendiente:"Pendiente",pagada:"Pagada",vencida:"Vencida",anulada:"Anulada",parcial:"Parcial",
credito:"Crédito",debito:"Débito",transferencia:"Transferencia",pago_movil:"Pago Móvil",efectivo:"Efectivo",mixto:"Mixto",tarjeta:"Tarjeta",
operacion_exitosa:"Operación exitosa",error_guardar:"Error al guardar",confirmar_eliminar:"¿Está seguro de eliminar?",sin_datos:"Sin datos",producto_agregado:"Producto agregado",guardado_exitoso:"Guardado exitosamente",no_autorizado:"No autorizado",registro_guardado:"Registro guardado correctamente",registro_eliminado:"Registro eliminado correctamente",error_servidor:"Error interno del servidor",campo_requerido:"Campo requerido",complete_campos:"Complete todos los campos",
usuarios_registrados:"Usuarios registrados",productos_inventario:"Productos en inventario",ventas_totales:"Ventas Totales",clientes:"Clientes",stock_bajo:"Stock Bajo",caja_del_dia:"Caja del Dia",facturas_hoy:"Facturas emitidas hoy",pendientes:"Pendientes:",
total_usuarios:"Total Usuarios",clientes_activos:"Clientes Activos",productos_inventario2:"Productos en Inventario",proveedores:"Proveedores",ventas_del_mes:"Ventas del Mes",pedidos_pendientes:"Pedidos Pendientes",productos_stock_bajo:"Productos Stock Bajo",compras_del_mes:"Compras del Mes",utilidad_estimada:"Utilidad Estimada",ticket_promedio:"Ticket Promedio",crecimiento_vs_mes:"Crecimiento vs Mes Anterior",
ventas_semana:"Ventas de la Semana",crecimiento:"Crecimiento",ventas_hoy:"Ventas Hoy",
precision_promedio:"Precisión Promedio",nivel_confianza:"Nivel de Confianza",productos_aumento:"Productos en Aumento",productos_descenso:"Productos en Descenso",
total_envios:"Total Envíos",recomendaciones:"Recomendaciones",nuevos_productos:"Nuevos Productos",encuestas:"Encuestas",
datos_personales:"Datos Personales",autenticacion_2fa_label:"Autenticación en Dos Pasos (2FA)",cambiar_contrasena:"Cambiar Contraseña",recuperacion_cuenta:"Recuperación de Cuenta",eliminar_cuenta:"Eliminar Cuenta",
gestionar_2fa:"Gestionar 2FA",abrir_caja:"Abrir Caja",cerrar_caja:"Cerrar Caja",movimiento:"Movimiento",nueva_interaccion:"Nueva Interacción",crear_backup:"Crear Backup",
ventas_por_cliente:"Ventas por Cliente",productos_mas_vendidos:"Productos mas Vendidos",resultados_reporte:"Resultados del Reporte",copias_seguridad:"Copias de Seguridad",historial_envios:"Historial de Envíos",predicciones_producto:"Predicciones por Producto",
no_hay_usuarios:"No hay usuarios registrados",no_hay_proveedores:"No hay proveedores registrados",no_disponible:"N/A",
buscar_usuario:"Buscar usuario...",buscar_producto:"Buscar producto...",buscar_label:"Buscar..."},
en:{panel_admin:"Admin Panel",dashboard:"Dashboard",almacenes:"Warehouses",cuentas_cobrar:"Accounts Receivable",cuentas_pagar:"Accounts Payable",notas_credito:"Credit Notes",variantes:"Variants",api_tokens:"API Tokens",almacenes_title:"Warehouse Management",cuentas_cobrar_title:"Accounts Receivable",cuentas_pagar_title:"Accounts Payable",notas_credito_title:"Credit Notes",variantes_title:"Product Variants",api_tokens_title:"API Tokens",
mi_cuenta:"My Account",mi_perfil:"My Profile",gestion:"Management",usuarios:"Users",productos:"Products",proveedores:"Suppliers",compras:"Purchases",ventas:"Sales",pedidos:"Orders",cotizaciones:"Quotes",crm:"CRM",facturacion:"Invoicing",caja_arqueo:"Cash / Audit",herramientas:"Tools",asistente_tecnico:"Tech Assistant",ia_predictiva:"Predictive AI",bi_dashboard:"BI Dashboard",reportes:"Reports",ventas_cliente:"Sales by Client",ventas_vendedor:"Sales by Seller",productos_vendidos:"Best Selling Products",historial_compras:"Purchase History",auditoria:"Audit",reporte_general:"Executive Report",reporte_especifico:"Specific Report",sistema:"System",panel_ceo:"CEO Panel",configuracion:"Settings",telegram:"Telegram",backup:"Backup",marketing:"Marketing",reporte_stock:"Stock Report",seguridad:"Security",autenticacion_2fa:"2FA Authentication",cerrar_sesion:"Logout",
modo_oscuro:"Dark Mode",modo_claro:"Light Mode",idioma:"Language: English",idioma_en:"Idioma: Español",
cargando:"Loading...",error_conexion:"Connection error",guardar:"Save",cancelar:"Cancel",cerrar:"Close",filtrar:"Filter",nuevo:"New",acciones:"Actions",editar:"Edit",eliminar:"Delete",ver:"View",estado:"Status",fecha:"Date",total:"Total",cliente:"Client",producto:"Product",cantidad:"Quantity",precio:"Price",codigo:"Code",nombre:"Name",descripcion:"Description",buscar:"Search",seleccionar:"Select...",sin_resultados:"No results",todos:"All",
pendiente:"Pending",pagada:"Paid",vencida:"Overdue",anulada:"Cancelled",parcial:"Partial",
credito:"Credit",debito:"Debit",transferencia:"Transfer",pago_movil:"Mobile Payment",efectivo:"Cash",mixto:"Mixed",tarjeta:"Card",
operacion_exitosa:"Operation successful",error_guardar:"Error saving",confirmar_eliminar:"Are you sure you want to delete?",sin_datos:"No data",producto_agregado:"Product added",guardado_exitoso:"Saved successfully",no_autorizado:"Unauthorized",registro_guardado:"Record saved successfully",registro_eliminado:"Record deleted successfully",error_servidor:"Internal server error",campo_requerido:"Required field",complete_campos:"Fill all fields",
usuarios_registrados:"Registered users",productos_inventario:"Products in inventory",ventas_totales:"Total Sales",clientes:"Clients",stock_bajo:"Low Stock",caja_del_dia:"Daily Cash",facturas_hoy:"Invoices issued today",pendientes:"Pending:",
total_usuarios:"Total Users",clientes_activos:"Active Clients",productos_inventario2:"Inventory Products",proveedores:"Suppliers",ventas_del_mes:"Monthly Sales",pedidos_pendientes:"Pending Orders",productos_stock_bajo:"Low Stock Products",compras_del_mes:"Monthly Purchases",utilidad_estimada:"Estimated Profit",ticket_promedio:"Average Ticket",crecimiento_vs_mes:"Growth vs Previous Month",
ventas_semana:"Weekly Sales",crecimiento:"Growth",ventas_hoy:"Today's Sales",
precision_promedio:"Average Precision",nivel_confianza:"Confidence Level",productos_aumento:"Rising Products",productos_descenso:"Declining Products",
total_envios:"Total Sends",recomendaciones:"Recommendations",nuevos_productos:"New Products",encuestas:"Surveys",
datos_personales:"Personal Data",autenticacion_2fa_label:"Two-Step Authentication (2FA)",cambiar_contrasena:"Change Password",recuperacion_cuenta:"Account Recovery",eliminar_cuenta:"Delete Account",
gestionar_2fa:"Manage 2FA",abrir_caja:"Open Register",cerrar_caja:"Close Register",movimiento:"Movement",nueva_interaccion:"New Interaction",crear_backup:"Create Backup",
ventas_por_cliente:"Sales by Client",productos_mas_vendidos:"Best Selling Products",resultados_reporte:"Report Results",copias_seguridad:"Backup Copies",historial_envios:"Send History",predicciones_producto:"Product Predictions",
no_hay_usuarios:"No registered users",no_hay_proveedores:"No registered suppliers",no_disponible:"N/A",
buscar_usuario:"Search user...",buscar_producto:"Search product...",buscar_label:"Search..."},
'Foto de perfil actualizada correctamente':"Profile photo updated successfully",
'Error al subir foto':"Error uploading photo",
'Código enviado a tu correo':"Code sent to your email",
'Código inválido':"Invalid code",
'Código inválido o expirado':"Invalid or expired code",
'Código verificado correctamente':"Code verified successfully",
'Confirma tu nueva contraseña':"Confirm your new password",
'Contraseña cambiada correctamente':"Password changed successfully",
'Contraseña cambiada exitosamente':"Password changed successfully",
'Contraseña incorrecta':"Incorrect password",
'Correo no encontrado. Solicita un nuevo código':"Email not found. Request a new code",
'Cuenta eliminada. Cerrando sesión...':"Account deleted. Logging out...",
'Debes confirmar tu nueva contraseña':"You must confirm your new password",
'Debes ingresar tu contraseña actual':"You must enter your current password",
'Debes ingresar una nueva contraseña':"You must enter a new password",
'El nombre del cliente es requerido':"Client name is required",
'Error de autenticacion. Por favor inicie sesion nuevamente.':"Authentication error. Please login again.",
'Error de conexión':"Connection error",
'Error del servidor':"Server error",
'Ingresa el código de 6 dígitos':"Enter the 6-digit code",
'Ingresa el código de verificación':"Enter the verification code",
'Ingresa tu contraseña para confirmar':"Enter your password to confirm",
'Ingresa tu correo electrónico':"Enter your email",
'Ingresa un precio válido':"Enter a valid price",
'Ingresa una nueva contraseña':"Enter a new password",
'La contraseña debe tener al menos 6 caracteres':"Password must be at least 6 characters",
'La nueva contraseña debe tener al menos 6 caracteres':"New password must be at least 6 characters",
'Las contraseñas no coinciden':"Passwords do not match",
'Las contraseñas nuevas no coinciden':"New passwords do not match",
'Perfil actualizado correctamente':"Profile updated successfully",
'Error al cambiar contraseña':"Error changing password",
'Error al cambiar la contraseña':"Error changing password",
'Error al subir foto':"Error uploading photo",
'Error al solicitar código':"Error requesting code",
'Error al verificar código':"Error verifying code",
'Error al verificar el código':"Error verifying code",
'Selecciona un producto o escribe el nombre':"Select a product or type the name",
'Agrega al menos un producto':"Add at least one product",
'Complete los campos requeridos (Cliente, Tipo, Título)':"Fill required fields (Client, Type, Title)",
'No hay cambios para guardar':"No changes to save",
'No hay configuración para guardar':"No configuration to save",
'No hay datos para exportar':"No data to export",
'Selecciona al menos un pedido':"Select at least one order",
'Pedidos facturados':"Orders invoiced",
'Producto eliminado':"Product deleted",
'Error al eliminar':"Error deleting",
'Error al guardar':"Error saving",
'Error al guardar cambios':"Error saving changes",
'Error del servidor':"Server error",
'Error de conexión con el servidor':"Connection error with server",
'Panel cargado correctamente':"Panel loaded successfully",
'Error al actualizar':"Error updating",
'Error al cargar reporte':"Error loading report",
'Error al crear usuario':"Error creating user",
'Error al eliminar cuenta':"Error deleting account",
'Error al enviar':"Error sending",
'Error al generar':"Error generating",
'Error al procesar':"Error processing",
'Error al facturar':"Error invoicing",
'Backup creado correctamente':"Backup created successfully",
'Backup eliminado':"Backup deleted",
'Error al crear backup':"Error creating backup",
'Error al eliminar backup':"Error deleting backup",
'Configuración guardada correctamente':"Configuration saved successfully",
'Error al guardar configuración':"Error saving configuration",
'✅ 2FA activado correctamente':"2FA activated successfully",
'2FA desactivado':"2FA deactivated",
'Error al configurar 2FA':"Error configuring 2FA",
'Error al desactivar 2FA':"Error deactivating 2FA",
'✅ Configuración de Telegram guardada':"Telegram configuration saved",
'Error al guardar configuración de Telegram':"Error saving Telegram configuration",
'✅ Mensaje de prueba enviado por Telegram':"Test message sent via Telegram",
'Error al enviar mensaje de prueba':"Error sending test message",
'❌ Ingresa el Chat ID':"Enter the Chat ID",
'❌ Ingresa el Token del Bot':"Enter the Bot Token",
'Caja abierta':"Cash register opened",
'Caja cerrada':"Cash register closed",
'Error al abrir caja':"Error opening cash register",
'Error al cerrar caja':"Error closing cash register",
'Movimiento registrado':"Movement recorded",
'Error al registrar movimiento':"Error recording movement",
'Alerta resuelta':"Alert resolved",
'Error al resolver alerta':"Error resolving alert",
'Datos del BI actualizados correctamente':"BI data updated successfully",
'Error al actualizar BI':"Error updating BI",
'Predicciones generadas':"Predictions generated",
'Error al generar predicciones':"Error generating predictions",
'Recomendaciones enviadas':"Recommendations sent",
'Error al enviar recomendaciones':"Error sending recommendations",
'Reporte exportado correctamente':"Report exported successfully",
'Exportación completada':"Export completed",
'Proveedor creado':"Provider created",
'Error al crear proveedor':"Error creating provider",
'Usuario creado':"User created",
'✅ Todas las acciones pendientes fueron procesadas':"All pending actions processed",
'Error al notificar stock por Telegram':"Error notifying stock via Telegram",
'Error cargando productos:':"Error loading products:",
'Error cargando reporte específico:':"Error loading specific report:",
'Error cargando reporte general:':"Error loading general report:",
'Error cargando reporte stock:':"Error loading stock report:",
'Error al cargar auditoría:':"Error loading audit:",
'Error al cargar compras:':"Error loading purchases:",
'Error al cargar configuración:':"Error loading configuration:",
'Error al cargar datos del CEO':"Error loading CEO data",
'Error al cargar detalles del pedido':"Error loading order details",
'Error al cargar el historial:':"Error loading history:",
'Error al cargar historial':"Error loading history",
'Error al cargar ventas por cliente':"Error loading sales by client",
'Error al cargar ventas por vendedor:':"Error loading sales by seller:",
'Error al conectar con el servidor':"Error connecting to server",
'Error de conexión al cargar marketing':"Connection error loading marketing",
'Error en cargarCEO:':"Error loading CEO:",
'Error limpiando caja:':"Error clearing cash register:",
'Error: Correo no encontrado':"Error: Email not found",
'📡 Modo offline - Algunas funciones están limitadas':"Offline mode - Some features are limited",
'🔍 Verificando stock bajo...':"Checking low stock...",
'¿Eliminar este producto?':"Delete this product?",
'¿Eliminar este proveedor?':"Delete this provider?",
'¿Eliminar este usuario?':"Delete this user?",
'Ingrese término de búsqueda':"Enter search term",
'Seleccione un producto':"Select a product",
'Agregue al menos un atributo':"Add at least one attribute",
'No hay atributos para generar variantes':"No attributes to generate variants",
'Seleccione un producto primero':"Select a product first",
'Ingrese ID de factura':"Enter invoice ID",
'Seleccione al menos un producto':"Select at least one product",
'Ingrese un monto válido':"Enter a valid amount",
'Seleccione método de pago':"Select payment method",
'Token copiado al portapapeles':"Token copied to clipboard",
'No se pudo copiar':"Could not copy",
'Facturado':"Invoiced",
'Completado':"Completed",
'Cancelado':"Cancelled",
'No especificado':"Not specified"}};
var _lang=localStorage.getItem("lang");
if(!_lang||!_t[_lang]) _lang="es";
window.__=function(k){return _t[_lang][k]||k};
window.__setLang=function(l){
if(!_t[l])return;
_lang=l;localStorage.setItem("lang",l);
document.documentElement.lang=l==="en"?"en":"es";
document.querySelectorAll("[data-i18n]").forEach(function(el){el.textContent=__(el.getAttribute("data-i18n"))});
document.querySelectorAll("[data-i18n-placeholder]").forEach(function(el){el.placeholder=__(el.getAttribute("data-i18n-placeholder"))});
document.querySelectorAll("[data-i18n-title]").forEach(function(el){el.title=__(el.getAttribute("data-i18n-title"))});
            var lt=document.querySelector("#langToggle span");if(lt)lt.textContent=__('idioma_en');
            var tt=document.querySelector("#themeToggle span");if(tt)tt.textContent=__(document.body.classList.contains("dark-mode")?"modo_oscuro":"modo_claro");
            // Re-render current section after language change
            var curSec=document.querySelector(".content-section.active");
            if(curSec&&typeof switchSection==="function")switchSection(curSec.id);
};
document.addEventListener("DOMContentLoaded",function(){
__setLang(_lang);
var ti=document.querySelector("#themeToggle i");
if(ti)ti.className=document.body.classList.contains("dark-mode")?"fas fa-sun":"fas fa-moon";
document.getElementById("themeToggle").addEventListener("click",function(){
document.body.classList.toggle("dark-mode");
var d=document.body.classList.contains("dark-mode");
localStorage.setItem("darkMode",d?"enabled":"disabled");
var i=this.querySelector("i");if(i)i.className=d?"fas fa-sun":"fas fa-moon";
var s=this.querySelector("span");if(s)s.textContent=__(d?"modo_oscuro":"modo_claro");
});
document.getElementById("langToggle").addEventListener("click",function(){__setLang(_lang==="es"?"en":"es")});
});
})();
</script>
