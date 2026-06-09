<?php
// Configuración del Bot de Telegram
// 1. Abre Telegram y busca @BotFather
// 2. Envía /newbot y sigue las instrucciones
// 3. Copia el token que te da BotFather
// 4. Renombra este archivo a config.php y pega el token

define('TELEGRAM_BOT_TOKEN', 'AQUI_VA_TU_TOKEN_DE_BOTFATHER');
define('TELEGRAM_BOT_USERNAME', 'piccavzla_bot');

// ID del chat del administrador (opcional - para reenviar mensajes)
// Para obtener tu ID, envía un mensaje al bot y visita:
// https://api.telegram.org/bot<TU_TOKEN>/getUpdates
define('ADMIN_CHAT_ID', '');
