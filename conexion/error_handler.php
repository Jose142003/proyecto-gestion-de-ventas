<?php
declare(strict_types=1);

function errorHandlerInit(): void
{
    set_exception_handler(function (\Throwable $e): void {
        $message = 'Error interno del servidor';
        $code = 500;

        if ($e instanceof \PDOException) {
            $message = 'Error de base de datos';
            error_log("DB Error: " . $e->getMessage());
        } elseif ($e instanceof \RuntimeException) {
            $message = $e->getMessage();
            $code = 400;
        }

        if (defined('APP_ENV') && APP_ENV === 'development') {
            $message = $e->getMessage();
        }

        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode([
            'success' => false,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    });

    set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        error_log("PHP Error [$severity] $message in $file:$line");
        return true;
    });
}
