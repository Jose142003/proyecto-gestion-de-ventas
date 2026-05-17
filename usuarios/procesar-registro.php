<?php
// procesar-registro.php - VERSIÓN CORREGIDA
session_start();
require_once '../conexion/conexion.php';

header('Content-Type: application/json');

// ========== CONFIGURACIÓN ==========
define('REGISTRO_ADMIN_PERMITIDO', true);
define('REQUERIR_TOKEN_ADMIN', false);

$debug_file = 'debug_registro.log';
file_put_contents($debug_file, date('Y-m-d H:i:s') . ' - INICIO PROCESAMIENTO' . PHP_EOL, FILE_APPEND);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    // ========== RECOLECTAR DATOS ==========
    $nombre = trim($_POST['nombre'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $direccion = trim($_POST['direccion'] ?? '');
    $tipo_cuenta = trim($_POST['tipo_cuenta'] ?? 'cliente');
    $token_admin = trim($_POST['token_admin'] ?? '');
    
    file_put_contents($debug_file, "Tipo cuenta recibido: '$tipo_cuenta'" . PHP_EOL, FILE_APPEND);
    
    // VALIDACIÓN: Verificar que tipo_cuenta sea válido
    if ($tipo_cuenta !== 'cliente' && $tipo_cuenta !== 'admin') {
        $tipo_cuenta = 'cliente';
    }
    
    // ========== VALIDACIONES ==========
    $campos_requeridos = ['nombre', 'cedula', 'telefono', 'correo', 'password', 'direccion'];
    $campos_faltantes = [];
    
    foreach ($campos_requeridos as $campo) {
        if (empty($_POST[$campo] ?? '')) {
            $campos_faltantes[] = $campo;
        }
    }
    
    if (!empty($campos_faltantes)) {
        echo json_encode([
            "success" => false, 
            "message" => "Campos obligatorios faltantes: " . implode(', ', $campos_faltantes)
        ]);
        exit;
    }
    
    // Validar email
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "El formato del correo no es válido"]);
        exit;
    }
    
    // Validar cédula
    if (!preg_match('/^[0-9]{6,12}$/', $cedula)) {
        echo json_encode(["success" => false, "message" => "La cédula debe tener entre 6 y 12 dígitos numéricos"]);
        exit;
    }
    
    // Validar teléfono
    if (!preg_match('/^0[0-9]{10}$/', $telefono)) {
        echo json_encode(["success" => false, "message" => "El teléfono debe tener 11 dígitos (código + número)"]);
        exit;
    }
    
    // Validar contraseña
    if (strlen($password) < 6) {
        echo json_encode(["success" => false, "message" => "La contraseña debe tener al menos 6 caracteres"]);
        exit;
    }
    
    // Validar dirección
    if (strlen($direccion) < 5) {
        echo json_encode(["success" => false, "message" => "La dirección debe tener al menos 5 caracteres"]);
        exit;
    }
    
    try {
        // ========== REGISTRO DE ADMINISTRADOR ==========
        if ($tipo_cuenta === 'admin') {
            file_put_contents($debug_file, "PROCESANDO REGISTRO ADMIN" . PHP_EOL, FILE_APPEND);
            
            if (!REGISTRO_ADMIN_PERMITIDO) {
                echo json_encode([
                    "success" => false, 
                    "message" => "El registro de administradores está deshabilitado."
                ]);
                exit;
            }
            
            // Verificar si ya existe en admin_users
            $check_admin = "SELECT id FROM admin_users WHERE correo = :correo";
            $stmt_check_admin = $db->prepare($check_admin);
            $stmt_check_admin->bindParam(":correo", $correo);
            $stmt_check_admin->execute();
            
            if ($stmt_check_admin->rowCount() > 0) {
                echo json_encode([
                    "success" => false, 
                    "message" => "Ya existe un administrador con este correo."
                ]);
                exit;
            }
            
            // Verificar si el correo existe en users
            $check_user = "SELECT id FROM users WHERE correo = :correo";
            $stmt_check_user = $db->prepare($check_user);
            $stmt_check_user->bindParam(":correo", $correo);
            $stmt_check_user->execute();
            
            if ($stmt_check_user->rowCount() > 0) {
                echo json_encode([
                    "success" => false, 
                    "message" => "Este correo ya está registrado como usuario normal."
                ]);
                exit;
            }
            
            // ========== REGISTRAR EN admin_users ==========
            // Usar SHA256 (como en procesar-login.php)
            $hashed_password = strtoupper(hash('sha256', $password));
            $rol_admin = 'admin';
            $usuario_admin = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $nombre));
            
            // Asegurar username único
            $counter = 1;
            $usuario_temp = $usuario_admin;
            while (true) {
                $check_usuario = $db->prepare("SELECT id FROM admin_users WHERE usuario = :usuario");
                $check_usuario->bindParam(":usuario", $usuario_temp);
                $check_usuario->execute();
                if ($check_usuario->rowCount() == 0) break;
                $usuario_temp = $usuario_admin . $counter;
                $counter++;
            }
            $usuario_admin = $usuario_temp;
            
            $query_admin = "INSERT INTO admin_users (nombre, correo, usuario, contrasena, rol, activo, fecha_registro) 
                           VALUES (:nombre, :correo, :usuario, :contrasena, :rol, 1, NOW())";
            
            $stmt_admin = $db->prepare($query_admin);
            $stmt_admin->bindParam(":nombre", $nombre);
            $stmt_admin->bindParam(":correo", $correo);
            $stmt_admin->bindParam(":usuario", $usuario_admin);
            $stmt_admin->bindParam(":contrasena", $hashed_password);
            $stmt_admin->bindParam(":rol", $rol_admin);
            
            if ($stmt_admin->execute()) {
                $admin_id = $db->lastInsertId();
                file_put_contents($debug_file, "Admin insertado correctamente con ID: $admin_id" . PHP_EOL, FILE_APPEND);
                
                // Establecer sesión para ADMIN
                $_SESSION = array();
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $admin_id;
                $_SESSION['user_nombre'] = $nombre;
                $_SESSION['user_correo'] = $correo;
                $_SESSION['user_rol'] = $rol_admin;
                $_SESSION['tabla_origen'] = 'admin_users';
                $_SESSION['es_admin'] = true;
                $_SESSION['is_cliente'] = false;
                
                echo json_encode([
                    "success" => true, 
                    "message" => "Administrador registrado exitosamente",
                    "redirect_url" => "/proyecto/admin-panel/panel_admin.php"
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Error al registrar el administrador"]);
            }
            exit;
        }
        
        // ========== REGISTRO DE CLIENTE (USUARIO NORMAL) ==========
        if ($tipo_cuenta === 'cliente') {
            file_put_contents($debug_file, "PROCESANDO REGISTRO CLIENTE" . PHP_EOL, FILE_APPEND);
            
            // Verificar si ya existe en admin_users
            $check_admin = "SELECT id FROM admin_users WHERE correo = :correo";
            $stmt_check_admin = $db->prepare($check_admin);
            $stmt_check_admin->bindParam(":correo", $correo);
            $stmt_check_admin->execute();
            
            if ($stmt_check_admin->rowCount() > 0) {
                echo json_encode([
                    "success" => false, 
                    "message" => "Este correo pertenece a un administrador."
                ]);
                exit;
            }
            
            // Verificar si el usuario ya existe
            $check_user = "SELECT id FROM users WHERE correo = :correo OR cedula = :cedula";
            $stmt_check = $db->prepare($check_user);
            $stmt_check->bindParam(":correo", $correo);
            $stmt_check->bindParam(":cedula", $cedula);
            $stmt_check->execute();
            
            if ($stmt_check->rowCount() > 0) {
                echo json_encode([
                    "success" => false, 
                    "message" => "El correo o cédula ya están registrados."
                ]);
                exit;
            }
            
            // ========== REGISTRAR EN users ==========
            $rol = 'usuario';
            $estado = 'activo';
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO users (nombre, cedula, telefono, correo, password, direccion, estado, rol, is_active, email_verified, created_at) 
                      VALUES (:nombre, :cedula, :telefono, :correo, :password, :direccion, :estado, :rol, 1, 1, NOW())";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":cedula", $cedula);
            $stmt->bindParam(":telefono", $telefono);
            $stmt->bindParam(":correo", $correo);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":rol", $rol);
            $stmt->bindParam(":direccion", $direccion);
            $stmt->bindParam(":estado", $estado);
            
            if ($stmt->execute()) {
                $user_id = $db->lastInsertId();
                file_put_contents($debug_file, "Cliente insertado correctamente con ID: $user_id" . PHP_EOL, FILE_APPEND);
                
                // Insertar también en clientes
                try {
                    $insert_cliente = "INSERT INTO clientes (tipo_documento, documento, nombre, email, telefono, direccion, estado, fecha_registro) 
                                      VALUES ('cedula', :cedula, :nombre, :correo, :telefono, :direccion, :estado, NOW())";
                    $stmt_cliente = $db->prepare($insert_cliente);
                    $stmt_cliente->bindParam(":cedula", $cedula);
                    $stmt_cliente->bindParam(":nombre", $nombre);
                    $stmt_cliente->bindParam(":correo", $correo);
                    $stmt_cliente->bindParam(":telefono", $telefono);
                    $stmt_cliente->bindParam(":direccion", $direccion);
                    $stmt_cliente->bindParam(":estado", $estado);
                    $stmt_cliente->execute();
                } catch (Exception $e) {
                    file_put_contents($debug_file, "Error en clientes: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
                }
                
                // ========== ESTABLECER SESIÓN PARA CLIENTE ==========
                $_SESSION = array();
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_nombre'] = $nombre;
                $_SESSION['user_correo'] = $correo;
                $_SESSION['user_rol'] = $rol;
                $_SESSION['tabla_origen'] = 'users';
                $_SESSION['es_admin'] = false;
                $_SESSION['is_cliente'] = true;
                
                echo json_encode([
                    "success" => true, 
                    "message" => "Usuario registrado exitosamente",
                    "redirect_url" => "/proyecto/usuario/pagina_modernizada.html"
                ]);
            } else {
                echo json_encode(["success" => false, "message" => "Error al registrar el usuario"]);
            }
            exit;
        }
        
        echo json_encode(["success" => false, "message" => "Tipo de cuenta no válido"]);
        
    } catch (PDOException $e) {
        file_put_contents($debug_file, "EXCEPCIÓN: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        echo json_encode(["success" => false, "message" => "Error de base de datos"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
}
?>