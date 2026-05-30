-- ============================================================================
-- MIGRACIÓN: QR Login + 2FA para Clientes
-- Versión: 1.2.0
-- ============================================================================

-- ##########################################################################
-- 1. TABLA QR LOGIN SESSIONS
-- ##########################################################################
DROP TABLE IF EXISTS qr_login_sessions;
CREATE TABLE qr_login_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) UNIQUE NOT NULL,
    estado ENUM('pending','scanned','approved','expired') DEFAULT 'pending',
    user_id INT NULL,
    user_table VARCHAR(20) NULL,
    user_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    INDEX idx_token (token),
    INDEX idx_estado (estado),
    INDEX idx_expiracion (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ##########################################################################
-- 2. COLUMNAS 2FA EN TABLA users
-- ##########################################################################
DROP PROCEDURE IF EXISTS add_2fa_columns_users;
DELIMITER //
CREATE PROCEDURE add_2fa_columns_users()
BEGIN
    DECLARE cont INT;

    SELECT COUNT(*) INTO cont FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = '2fa_enabled';
    IF cont = 0 THEN
        ALTER TABLE users ADD COLUMN `2fa_enabled` TINYINT(1) DEFAULT 0;
    END IF;

    SELECT COUNT(*) INTO cont FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = '2fa_secret';
    IF cont = 0 THEN
        ALTER TABLE users ADD COLUMN `2fa_secret` VARCHAR(255) NULL;
    END IF;

    SELECT COUNT(*) INTO cont FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = '2fa_backup_codes';
    IF cont = 0 THEN
        ALTER TABLE users ADD COLUMN `2fa_backup_codes` TEXT NULL;
    END IF;

    SELECT COUNT(*) INTO cont FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = '2fa_verified_at';
    IF cont = 0 THEN
        ALTER TABLE users ADD COLUMN `2fa_verified_at` DATETIME NULL;
    END IF;
END//
DELIMITER ;
CALL add_2fa_columns_users();
DROP PROCEDURE IF EXISTS add_2fa_columns_users;

-- ##########################################################################
-- 3. TABLA SESIONES 2FA PARA CLIENTES
-- ##########################################################################
DROP TABLE IF EXISTS sesiones_2fa_clientes;
CREATE TABLE sesiones_2fa_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_verificacion VARCHAR(64) UNIQUE NOT NULL,
    intentos INT DEFAULT 0,
    expiracion DATETIME NOT NULL,
    completado TINYINT(1) DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token_verificacion),
    INDEX idx_expiracion (expiracion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Migracion QR Login + 2FA Clientes completada exitosamente' as Mensaje;
