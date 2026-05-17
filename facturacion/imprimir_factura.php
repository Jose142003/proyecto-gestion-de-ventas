<?php
// imprimir_factura.php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../conexion/conexion.php';

try {
    $pdo = conectarDB();
} catch(PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Verificar si se recibió un ID de factura
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No se especificó el ID de la factura");
}

$factura_id = intval($_GET['id']);

try {
    // Obtener datos de la factura
    $query = "SELECT 
                f.*,
                c.nombre as cliente_nombre,
                c.documento as cliente_documento,
                c.email as cliente_email,
                c.telefono as cliente_telefono,
                c.direccion as cliente_direccion,
                c.ciudad as cliente_ciudad,
                u.nombre as vendedor_nombre
              FROM facturas f
              JOIN clientes c ON f.cliente_id = c.id
              JOIN users u ON f.usuario_id = u.id
              WHERE f.id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$factura_id]);
    $factura = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$factura) {
        die("Error: Factura no encontrada con ID $factura_id");
    }

    // Obtener los detalles de la factura
    $query_detalles = "SELECT 
                        fd.*,
                        p.name as producto_nombre,
                        p.sku as producto_sku,
                        p.description as producto_descripcion
                       FROM factura_detalles fd
                       JOIN products p ON fd.producto_id = p.id
                       WHERE fd.factura_id = ?
                       ORDER BY fd.id";
    $stmt_detalles = $pdo->prepare($query_detalles);
    $stmt_detalles->execute([$factura_id]);
    $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

    // Obtener pagos de la factura
    $query_pagos = "SELECT * FROM pagos WHERE factura_id = ? ORDER BY fecha_pago DESC";
    $stmt_pagos = $pdo->prepare($query_pagos);
    $stmt_pagos->execute([$factura_id]);
    $pagos = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);

    // Calcular saldo pendiente si hay
    $total_pagado = 0;
    foreach ($pagos as $pago) {
        $total_pagado += $pago['monto'];
    }
    $saldo_pendiente = $factura['total'] - $total_pagado;

} catch(PDOException $e) {
    die("Error al consultar la base de datos: " . $e->getMessage());
}

// Configurar datos de la empresa (puedes mover esto a una tabla de configuración)
$empresa = [
    'nombre' => 'Picca Automatización Industrial',
    'rif' => 'J-12345678-9',
    'direccion' => 'Av. Principal, Edificio Centro, Oficina 5B',
    'ciudad' => 'Caracas, Venezuela',
    'telefono' => '+58 212-555-1234',
    'email' => 'picca.ventas@gmail.com',
    'web' => 'www.picca-automatizacion.com',
    'banco' => 'Banco de Venezuela',
    'cuenta' => '0102-1234-56-7890123456'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura <?php echo $factura['numero_factura']; ?></title>
      <!-- PWA Meta Tags -->
    <link rel="manifest" href="/proyecto/manifest.json">
    <meta name="theme-color" content="#050C18">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PIC Industrial">
    <link rel="apple-touch-icon" href="/proyecto/img/pic.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/proyecto/img/pic.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/proyecto/img/pic.png">
    <style>
        /* Estilos para impresión */
        @media print {
            @page {
                margin: 10mm;
                size: A4 portrait;
            }
            
            body {
                margin: 0;
                padding: 0;
                font-size: 12px;
                background: white !important;
            }
            
            .factura-container {
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            
            .no-print, .botones {
                display: none !important;
            }
            
            .page-break {
                page-break-before: always;
            }
            
            .factura-container {
                position: relative;
                width: 190mm;
                min-height: 277mm;
                padding: 15mm;
                box-sizing: border-box;
            }
        }
        
        /* Estilos generales */
        * {
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }
        
        .factura-container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 25mm;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: relative;
        }
        
        /* Cabecera */
        .header {
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .empresa-info {
            flex: 1;
        }
        
        .logo-area {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .logo-placeholder {
            width: 150px;
            height: 50px;
            background: linear-gradient(135deg, #0066cc, #003366);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            border-radius: 5px;
        }
        
        .factura-info {
            text-align: right;
            border: 2px solid #333;
            padding: 15px;
            border-radius: 5px;
            background: #f9f9f9;
            min-width: 250px;
        }
        
        .factura-numero {
            font-size: 24px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 10px;
        }
        
        /* Información del cliente */
        .cliente-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #0066cc;
            margin-bottom: 30px;
        }
        
        .cliente-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .info-box {
            padding: 10px;
            background: white;
            border-radius: 3px;
            border: 1px solid #ddd;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
            display: block;
            margin-bottom: 5px;
        }
        
        /* Tabla de productos */
        .tabla-items {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .tabla-items th {
            background-color: #0066cc;
            color: white;
            padding: 12px 8px;
            text-align: center;
            border: 1px solid #ddd;
            font-size: 13px;
        }
        
        .tabla-items td {
            padding: 10px 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        
        .tabla-items tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .descripcion-col {
            text-align: left;
            width: 40%;
        }
        
        /* Totales */
        .totales-section {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }
        
        .totales {
            width: 300px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .total-label {
            font-weight: bold;
        }
        
        .total-monto {
            font-weight: bold;
            text-align: right;
            min-width: 120px;
        }
        
        .subtotal {
            border-top: 2px solid #ddd;
            padding-top: 12px;
        }
        
        .iva {
            background: #f8f9fa;
        }
        
        .total-grande {
            font-size: 18px;
            font-weight: bold;
            color: #0066cc;
            border-top: 3px solid #333;
            margin-top: 10px;
            padding-top: 15px;
            background: #e8f4ff;
        }
        
        /* Sección de pagos */
        .pagos-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        
        .pagos-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .pagos-table th {
            background: #28a745;
            color: white;
            padding: 10px;
            text-align: center;
        }
        
        .pagos-table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        
        .saldo-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .saldo-pendiente {
            color: #d63031;
            font-weight: bold;
            font-size: 16px;
        }
        
        /* Notas y términos */
        .notas-terminos {
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #ddd;
            page-break-inside: avoid;
        }
        
        .notas-terminos h4 {
            color: #333;
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        /* Firmas */
        .firmas {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 2px solid #ccc;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        
        .firma {
            text-align: center;
            width: 45%;
        }
        
        .linea-firma {
            border-top: 1px solid #333;
            width: 80%;
            margin: 40px auto 10px auto;
        }
        
        /* Pie de página */
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            text-align: center;
            color: #666;
            font-size: 12px;
            page-break-inside: avoid;
        }
        
        /* Botones de acción */
        .botones {
            text-align: center;
            margin: 30px auto;
            padding: 20px;
            background: white;
            max-width: 210mm;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn {
            padding: 12px 25px;
            margin: 0 10px;
            background: #0066cc;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.3s;
            min-width: 180px;
        }
        
        .btn:hover {
            background: #0052a3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-print {
            background: #28a745;
        }
        
        .btn-print:hover {
            background: #218838;
        }
        
        .btn-pdf {
            background: #dc3545;
        }
        
        .btn-pdf:hover {
            background: #c82333;
        }
        
        /* Estados de factura */
        .estado-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .estado-pagada {
            background: #d4edda;
            color: #155724;
        }
        
        .estado-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        
        .estado-anulada {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Estilos específicos para datos */
        h1, h2, h3, h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        p {
            margin: 5px 0;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-bold {
            font-weight: bold;
        }
        
        .mb-20 {
            margin-bottom: 20px;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="factura-container">
        <!-- Cabecera con logo y datos de la empresa -->
        <div class="header">
            <div class="empresa-info">
                <div class="logo-area">
                    <div class="logo-placeholder">
                        PICCA AUTOMATIZACIÓN
                    </div>
                </div>
                <h1><?php echo $empresa['nombre']; ?></h1>
                <p><strong>RIF:</strong> <?php echo $empresa['rif']; ?></p>
                <p><strong>Dirección:</strong> <?php echo $empresa['direccion']; ?></p>
                <p><strong>Ciudad:</strong> <?php echo $empresa['ciudad']; ?></p>
                <p><strong>Teléfono:</strong> <?php echo $empresa['telefono']; ?></p>
                <p><strong>Email:</strong> <?php echo $empresa['email']; ?></p>
                <p><strong>Web:</strong> <?php echo $empresa['web']; ?></p>
            </div>
            
            <div class="factura-info">
                <div class="factura-numero">
                    <?php echo $factura['numero_factura']; ?>
                </div>
                <p><strong>Fecha Emisión:</strong> <?php echo date('d/m/Y', strtotime($factura['fecha_emision'])); ?></p>
                <p><strong>Fecha Vencimiento:</strong> <?php echo date('d/m/Y', strtotime($factura['fecha_vencimiento'])); ?></p>
                <p><strong>Estado:</strong> 
                    <?php echo ucfirst($factura['estado']); ?>
                    <span class="estado-badge estado-<?php echo $factura['estado']; ?>">
                        <?php echo $factura['estado']; ?>
                    </span>
                </p>
                <p><strong>Forma de Pago:</strong> <?php echo ucfirst($factura['metodo_pago']); ?></p>
                <p><strong>Vendedor:</strong> <?php echo $factura['vendedor_nombre']; ?></p>
            </div>
        </div>
        
        <!-- Información del cliente -->
        <div class="cliente-info">
            <h3>DATOS DEL CLIENTE</h3>
            <div class="cliente-section">
                <div>
                    <div class="info-box">
                        <span class="info-label">Cliente:</span>
                        <?php echo htmlspecialchars($factura['cliente_nombre'] ?? ''); ?>
                    </div>
                    <div class="info-box">
                        <span class="info-label">Documento:</span>
                        <?php echo htmlspecialchars($factura['cliente_documento'] ?? ''); ?>
                    </div>
                    <div class="info-box">
                        <span class="info-label">Email:</span>
                        <?php echo htmlspecialchars($factura['cliente_email'] ?? ''); ?>
                    </div>
                </div>
                <div>
                    <div class="info-box">
                        <span class="info-label">Teléfono:</span>
                        <?php echo htmlspecialchars($factura['cliente_telefono'] ?? ''); ?>
                    </div>
                    <div class="info-box">
                        <span class="info-label">Dirección:</span>
                        <?php echo htmlspecialchars($factura['cliente_direccion'] ?? ''); ?>
                    </div>
                    <div class="info-box">
                        <span class="info-label">Ciudad:</span>
                        <?php 
                        // Mostrar ciudad del cliente si existe, sino dejar en blanco
                        if (!empty($factura['cliente_ciudad'])) {
                            echo htmlspecialchars($factura['cliente_ciudad']);
                        } else {
                            echo "No especificada";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de productos -->
        <h3>DETALLE DE PRODUCTOS</h3>
        <table class="tabla-items">
            <thead>
                <tr>
                    <th width="50">#</th>
                    <th width="100">Código</th>
                    <th class="descripcion-col">Descripción</th>
                    <th width="80">Cantidad</th>
                    <th width="100">Precio Unitario</th>
                    <th width="100">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $contador = 1;
                foreach ($detalles as $detalle): 
                ?>
                <tr>
                    <td><?php echo $contador++; ?></td>
                    <td><?php echo htmlspecialchars($detalle['producto_sku'] ?? ''); ?></td>
                    <td class="descripcion-col">
                        <strong><?php echo htmlspecialchars($detalle['producto_nombre'] ?? ''); ?></strong><br>
                        <small><?php 
                            if (!empty($detalle['producto_descripcion'])) {
                                echo substr(htmlspecialchars($detalle['producto_descripcion']), 0, 100) . '...';
                            }
                        ?></small>
                    </td>
                    <td><?php echo number_format($detalle['cantidad'] ?? 0, 0, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($detalle['precio_unitario'] ?? 0, 2, ',', '.'); ?> Bs</td>
                    <td class="text-right"><?php echo number_format($detalle['subtotal'] ?? 0, 2, ',', '.'); ?> Bs</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Totales -->
        <div class="totales-section">
            <div class="totales">
                <div class="total-row subtotal">
                    <span class="total-label">Base Imponible:</span>
                    <span class="total-monto"><?php echo number_format($factura['subtotal'] ?? 0, 2, ',', '.'); ?> Bs</span>
                </div>
                <div class="total-row iva">
                    <span class="total-label">IVA (16%):</span>
                    <span class="total-monto"><?php echo number_format($factura['iva'] ?? 0, 2, ',', '.'); ?> Bs</span>
                </div>
                <div class="total-row total-grande">
                    <span class="total-label">TOTAL FACTURA:</span>
                    <span class="total-monto"><?php echo number_format($factura['total'] ?? 0, 2, ',', '.'); ?> Bs</span>
                </div>
            </div>
        </div>
        
        <!-- Sección de pagos (si existe) -->
        <?php if (!empty($pagos)): ?>
        <div class="pagos-section">
            <h3>HISTORIAL DE PAGOS</h3>
            <table class="pagos-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Método de Pago</th>
                        <th>Referencia</th>
                        <th>Monto Pagado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $pago): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($pago['fecha_pago'] ?? '')); ?></td>
                        <td><?php echo ucfirst($pago['metodo_pago'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($pago['referencia'] ?? ''); ?></td>
                        <td class="text-right"><?php echo number_format($pago['monto'] ?? 0, 2, ',', '.'); ?> Bs</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($saldo_pendiente > 0): ?>
            <div class="saldo-section">
                <div>
                    <strong>TOTAL PAGADO:</strong>
                    <span class="text-right"><?php echo number_format($total_pagado, 2, ',', '.'); ?> Bs</span>
                </div>
                <div class="saldo-pendiente">
                    <strong>SALDO PENDIENTE:</strong>
                    <span><?php echo number_format($saldo_pendiente, 2, ',', '.'); ?> Bs</span>
                </div>
            </div>
            <?php else: ?>
            <div class="saldo-section" style="background: #d4edda; border-color: #c3e6cb;">
                <div class="text-center" style="width: 100%;">
                    <strong style="color: #155724; font-size: 16px;">✅ FACTURA PAGADA COMPLETAMENTE</strong>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Notas y términos -->
        <div class="notas-terminos">
            <h4>INFORMACIÓN IMPORTANTE:</h4>
            <p>1. Esta factura incluye el IVA correspondiente de acuerdo a la legislación vigente.</p>
            <p>2. Favor realizar el pago antes de la fecha de vencimiento para evitar recargos.</p>
            <p>3. Para pagos por transferencia, utilizar los siguientes datos bancarios:</p>
            <p><strong>Banco:</strong> <?php echo $empresa['banco']; ?> | <strong>Cuenta:</strong> <?php echo $empresa['cuenta']; ?></p>
            <p>4. Enviar comprobante de pago al email: <?php echo $empresa['email']; ?></p>
            <p>5. Productos con garantía según términos y condiciones.</p>
            <p>6. Condiciones de pago: 50% al momento de la orden, 50% al momento de la entrega.</p>
        </div>
        
        <!-- Firmas -->
        <div class="firmas">
            <div class="firma">
                <p><strong>RECIBIDO POR</strong></p>
                <div class="linea-firma"></div>
                <p>Nombre y Firma del Cliente</p>
                <p>Fecha: ____________________</p>
            </div>
            <div class="firma">
                <p><strong>AUTORIZADO POR</strong></p>
                <div class="linea-firma"></div>
                <p><?php echo $empresa['nombre']; ?></p>
                <p>Cédula/RIF: <?php echo $empresa['rif']; ?></p>
                <p>Fecha: ____________________</p>
            </div>
        </div>
        
        <!-- Pie de página -->
        <div class="footer">
            <p><strong><?php echo $empresa['nombre']; ?></strong> - <?php echo $empresa['direccion']; ?> - <?php echo $empresa['ciudad']; ?></p>
            <p>Tel: <?php echo $empresa['telefono']; ?> - Email: <?php echo $empresa['email']; ?> - Web: <?php echo $empresa['web']; ?></p>
            <p>Esta factura es un documento legal generado electrónicamente. N° de Control: <?php echo strtoupper(uniqid()); ?></p>
        </div>
    </div>
    
    <!-- Botones de acción (no se imprimen) -->
    <div class="botones no-print">
        <button onclick="window.print()" class="btn btn-print">
            <span>🖨️</span> Imprimir Factura
        </button>
        
        <button onclick="window.history.back()" class="btn">
            <span>←</span> Volver Atrás
        </button>
        
        <a href="/proyecto/admin-panel/panel_admin.html" class="btn">
            <span>🏠</span> Ir al Dashboard
        </a>
        
        <a href="editar_factura.php?id=<?php echo $factura_id; ?>" class="btn" style="background: #ffc107; color: #000;">
            <span>✏️</span> Editar Factura
        </a>
    </div>
    
    <script>
        // Auto-scroll al inicio para mejor visualización
        window.onload = function() {
            window.scrollTo(0, 0);
        };
        
        
        // Detectar si se ha impreso
        window.onafterprint = function() {
            alert('Factura impresa exitosamente');
        };
        
        // Auto-imprimir al cargar (opcional - descomenta si lo quieres)
        /*
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        */
    </script>
</body>
</html>