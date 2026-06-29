<?php
session_name('CLIENTSESSID');
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    if (isset($_COOKIE['persist_token'])) {
        setcookie('persist_token', '', time() - 3600, '/');
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '/proyecto';
        header('Location: ' . $baseUrl . '/interfaz_usuario/login.html');
        exit;
    }
}
@session_write_close();
require_once __DIR__ . '/../config/i18n.php';
require_once __DIR__ . '/../config/i18n_helpers.php';
$locale = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'es';
setcookie('lang', $locale, time() + 86400 * 30, '/');
\I18n::load($locale);
?><!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($locale); ?>">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover" name="viewport"/>
    <title>Proyectos Industriales Del Centro</title>
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="/proyecto/manifest.json">
    <meta name="theme-color" content="#050C18">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PIC Industrial">
    <link rel="apple-touch-icon" href="/proyecto/img/pic.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/proyecto/img/pic.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/proyecto/img/pic.png">
    
    <!-- Open Graph (WhatsApp, Facebook, etc) -->
    <meta property="og:title" content="Proyectos Industriales del Centro">
    <meta property="og:description" content="Suministros industriales de alta calidad - Tienda oficial">
    <meta property="og:image" content="/proyecto/img/pic.png">
    <meta property="og:url" content="https://www.picindustrial.com">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="PIC Industrial">
    <meta name="twitter:card" content="summary_large_image">
    
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <style>
        :root {
            --primary-color: #050C18;
            --secondary-color: #294E90;
            --accent-color: #3C91ED;
            --light-color: #7EBDE9;
            --bg-color: #F3F3F3;
            --text-color: #050C18;
            --card-bg: #ffffff;
            --header-bg: linear-gradient(135deg, #050C18, #294E90);
            --shadow-color: rgba(5, 12, 24, 0.1);
        }

        body.dark-mode {
            --primary-color: #7EBDE9;
            --secondary-color: #3C91ED;
            --accent-color: #294E90;
            --light-color: #1a1a2e;
            --bg-color: #121212;
            --text-color: #F3F3F3;
            --card-bg: #1e1e2e;
            --header-bg: linear-gradient(135deg, #0a0a1a, #1a1a2e);
            --shadow-color: rgba(0,0,0,0.5);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background-color: var(--bg-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .header {
            background: var(--header-bg);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px var(--shadow-color);
            margin-bottom: 25px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            color: #ffffff;
            font-weight: 700;
            font-size: 1.8rem;
            margin: 0;
        }

        .tasa-bcv-container {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            color: white;
            border-radius: 12px;
            padding: 10px 18px;
            min-width: 220px;
        }

        .tasa-bcv-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .btn-refresh-tasa {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-refresh-tasa:hover {
            background: rgba(255,255,255,0.4);
            transform: rotate(180deg);
        }

        .tasa-value {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .tasa-usd {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 5px;
            flex-wrap: wrap;
        }

        .tasa-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .tasa-currency {
            font-size: 0.8rem;
            font-weight: normal;
            opacity: 0.9;
        }

        .tasa-bcv-ref {
            border-top: 1px solid rgba(255,255,255,0.2);
            margin-top: 6px;
            padding-top: 4px;
            font-size: 0.55rem;
            opacity: 0.8;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-container {
            min-width: 260px;
        }

        .search-container .input-group {
            border-radius: 30px;
            overflow: hidden;
        }

        .search-container .form-control {
            border: none;
            border-radius: 30px 0 0 30px;
            padding: 10px 20px;
        }

        .search-container .btn {
            border-radius: 0 30px 30px 0;
            background-color: var(--accent-color);
            color: white;
        }

        .header-icons {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-refresh-products {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-refresh-products:hover {
            background: rgba(255,255,255,0.4);
            transform: rotate(180deg);
        }

        .cart-icon-container {
            position: relative;
            cursor: pointer;
        }

        .cart-icon-container .fas.fa-shopping-cart {
            font-size: 1.6em;
            color: white;
        }

        .cart-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 0.7em;
            min-width: 20px;
            text-align: center;
        }

        .profile-image-container {
            width: 42px;
            height: 42px;
            cursor: pointer;
        }

        .profile-image {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }

        #defaultProfileIcon {
            font-size: 2em;
            color: white;
        }

        .dropdown-menu {
            background-color: var(--card-bg);
            border-radius: 12px;
        }

        .dropdown-item {
            color: var(--text-color);
        }

        .dropdown-header {
            background: var(--header-bg);
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 15px;
        }

        .search-filters-container {
            display: none;
            flex-wrap: wrap;
            gap: 12px;
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            margin: 15px 0;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 130px;
            flex: 1;
        }

        .filter-group label {
            font-size: 0.8rem;
            margin-bottom: 5px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 8px 12px;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background-color: #2a2a2a;
            color: #F3F3F3;
            border-color: #4a4a4a;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 25px;
            margin: 25px 0;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 10px;
            }
            .product-card {
                padding: 12px;
            }
            .product-img-container {
                height: 140px;
            }
            .product-card h5 {
                font-size: 0.85rem;
            }
            .product-card .price {
                font-size: 1.1rem;
            }
            #productModal .modal-dialog {
                margin: 5px;
            }
            #productModal .modal-content {
                border-radius: 12px;
            }
            #productModal .modal-body {
                padding: 12px;
            }
            #productModal .modal-product-image {
                max-height: 160px;
            }
            #productModal h3 {
                font-size: 1.1rem;
            }
            #productModal .quantity-selector {
                margin: 12px 0;
            }
            #productModal .shipping-info {
                padding: 8px;
                margin: 10px 0;
            }
        }

        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .modal-lg .favorites-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }

        .product-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 5px 15px var(--shadow-color);
            text-align: center;
            padding: 18px;
            height: 100%;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            transition: transform 0.3s;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-6px);
        }

        .product-card.has-variants {
            border: 1px solid var(--accent-color);
        }
        .product-card.has-variants .add-to-cart-btn.stock-disabled {
            background: var(--accent-color);
            opacity: 0.8;
            cursor: pointer;
        }

        .product-img-container {
            height: 180px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
            position: relative;
        }

        .product-img-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Estilo solo para el botón de favorito dentro de la tarjeta del producto */
        .product-favorite-btn {
            position: absolute;
            top: 8px;
            left: 8px;
            cursor: pointer;
            font-size: 1.4rem;
            color: rgba(255,255,255,0.9);
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
            transition: all 0.2s;
            z-index: 10;
            background: rgba(0,0,0,0.3);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-favorite-btn:hover {
            transform: scale(1.1);
        }

        .product-favorite-btn.favorito-activo {
            color: #ff4757;
        }

        .product-card .category {
            font-size: 0.7rem;
            background-color: var(--light-color);
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .product-card h5 {
            font-weight: 600;
            color: var(--text-color);
            font-size: 1rem;
            margin-bottom: 8px;
        }

        .product-card .rating {
            color: #ffc107;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .product-card .price {
            font-size: 1.3rem;
            color: var(--accent-color);
            font-weight: bold;
            margin: 8px 0;
        }

        .product-card .price .ves-price {
            font-size: 0.7rem;
            display: block;
        }

        .add-to-cart-btn {
            background-color: var(--accent-color);
            border: none;
            padding: 10px;
            border-radius: 30px;
            color: white;
            cursor: pointer;
            margin-top: auto;
        }

        .add-to-cart-btn.stock-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 35px;
        }

        .pagination {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .page-item .page-link {
            padding: 8px 14px;
            border-radius: 8px;
            background-color: var(--card-bg);
            border: 1px solid var(--light-color);
        }

        .page-item.active .page-link {
            background-color: var(--accent-color);
            color: white;
        }

        .modal-content {
            border-radius: 16px;
            background-color: var(--card-bg);
        }

        .modal-header {
            background: var(--header-bg);
            color: white;
            border-radius: 16px 16px 0 0;
        }

        .modal-product-image {
            max-width: 100%;
            max-height: 250px;
            object-fit: contain;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
            gap: 10px;
        }

        .quantity-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: var(--accent-color);
            color: white;
            border: none;
            cursor: pointer;
        }

        #variant-selector {
            margin: 15px 0;
            padding: 12px;
            background: rgba(60,145,237,0.05);
            border-radius: 10px;
            border: 1px solid rgba(60,145,237,0.15);
        }
        .variant-attr-btn.active {
            background: var(--accent-color) !important;
            color: #fff !important;
            border-color: var(--accent-color) !important;
        }
        .quantity-input {
            width: 60px;
            height: 38px;
            text-align: center;
            border-radius: 8px;
            border: 1px solid #ced4da;
        }

        .shipping-info {
            margin: 18px 0;
            padding: 12px;
            background: var(--bg-color);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .shipping-title {
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--text-color);
        }

        .shipping-carriers {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .carrier-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            background: var(--accent-color);
            color: white;
            font-size: 12px;
            font-weight: 500;
        }

        .shipping-note {
            font-size: 12px;
            color: var(--text-muted, #6c757d);
            margin: 0;
        }

        #floating-cart {
            position: fixed;
            bottom: 25px;
            right: 25px;
            width: 60px;
            height: 60px;
            background-color: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .contact-option {
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .contact-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .related-product-card {
            flex: 0 0 130px;
            background: var(--card-bg);
            border: 1px solid var(--light-color);
            border-radius: 10px;
            padding: 8px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .related-product-card:hover {
            transform: translateY(-3px);
        }
        .related-product-card img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 6px;
        }
        .related-product-card .name {
            font-size: 0.7rem;
            font-weight: 600;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .related-product-card .price {
            font-size: 0.8rem;
            color: var(--accent-color);
            font-weight: bold;
        }

        .heart-beat {
            animation: heartBeat 0.4s ease-in-out;
        }

        @keyframes heartBeat {
            0% { transform: scale(1); }
            30% { transform: scale(1.35); }
            60% { transform: scale(0.9); }
            100% { transform: scale(1); }
        }

        .favorites-section {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px var(--shadow-color);
        }

        .favorites-section h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .favorites-mini-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }

        .favorites-mini-grid .product-card {
            margin-bottom: 0;
        }

        .floating-favorites-btn {
            position: fixed;
            bottom: 95px;
            right: 25px;
            width: 60px;
            height: 60px;
            background-color: #ff4757;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }

        .floating-favorites-btn:hover {
            transform: scale(1.1);
        }

        .floating-favorites-btn .favorites-floating-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #fff;
            color: #ff4757;
            border-radius: 50%;
            padding: 1px 6px;
            font-size: 0.7em;
            min-width: 20px;
            text-align: center;
            font-weight: bold;
            border: 2px solid #ff4757;
        }

        .dark-mode-toggle-fixed {
            position: fixed;
            bottom: 25px;
            left: 25px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            color: white;
            border: none;
            cursor: pointer;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .cart-notification {
            position: fixed;
            top: 25px;
            right: 25px;
            background-color: var(--accent-color);
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            z-index: 1050;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s;
        }

        .cart-notification.show {
            opacity: 1;
            transform: translateY(0);
        }

        .active-filters {
            background-color: var(--card-bg);
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-badge {
            background-color: var(--light-color);
            padding: 5px 12px;
            border-radius: 25px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-badge .close {
            cursor: pointer;
        }

        .footer-industrial {
            background-color: var(--primary-color);
            color: white;
            padding: 50px 20px 25px;
            margin-top: 60px;
            border-top: 3px solid var(--accent-color);
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 35px;
        }

        .footer-section h4 {
            color: var(--light-color);
            font-weight: 700;
            margin-bottom: 18px;
            font-size: 1.1rem;
            text-transform: uppercase;
        }

        .footer-gallery {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .footer-gallery img {
            width: 100%;
            height: 65px;
            object-fit: contain;
            border-radius: 6px;
            background-color: white;
            padding: 5px;
            transition: transform 0.3s ease;
        }

        .footer-gallery img:hover {
            transform: scale(1.05);
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 18px;
            margin-top: 18px;
        }

        .social-links a {
            width: 38px;
            height: 38px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--accent-color);
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.85rem;
            opacity: 0.7;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            .header-right {
                width: 100%;
                justify-content: center;
            }
            .search-container {
                width: 100%;
            }
            .search-filters-container {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
            }
            .footer-container {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .tasa-usd {
                justify-content: center;
            }
        }

        /* ===== RESEÑAS ===== */
        .resenas-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--shadow-color);
        }
        .resenas-section h5 {
            font-weight: 700;
            margin-bottom: 15px;
        }
        .rating-summary {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .rating-average {
            text-align: center;
            min-width: 100px;
        }
        .rating-average .big-number {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
        }
        .rating-average .stars {
            font-size: 1rem;
            color: #f5a623;
        }
        .rating-average .total-count {
            font-size: 0.8rem;
            color: #666;
        }
        .rating-bars {
            flex: 1;
            min-width: 180px;
        }
        .rating-bar-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            margin-bottom: 3px;
        }
        .rating-bar-row .bar-label {
            width: 20px;
            text-align: right;
            color: #f5a623;
        }
        .rating-bar-row .bar-track {
            flex: 1;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        .rating-bar-row .bar-fill {
            height: 100%;
            background: #f5a623;
            border-radius: 4px;
            transition: width 0.3s;
        }
        .rating-bar-row .bar-count {
            width: 24px;
            text-align: left;
            font-size: 0.75rem;
            color: #666;
        }
        .resena-card {
            background: var(--card-bg);
            border: 1px solid var(--shadow-color);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 12px;
        }
        .resena-card .resena-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }
        .resena-card .resena-user {
            font-weight: 600;
            font-size: 0.9rem;
        }
        .resena-card .resena-date {
            font-size: 0.75rem;
            color: #888;
        }
        .resena-card .resena-stars {
            color: #f5a623;
            font-size: 0.85rem;
            margin-bottom: 4px;
        }
        .resena-card .resena-titulo {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }
        .resena-card .resena-comentario {
            font-size: 0.85rem;
            color: var(--text-color);
            line-height: 1.4;
        }
        .resena-card .verified-badge {
            display: inline-block;
            font-size: 0.7rem;
            color: #28a745;
            margin-left: 6px;
        }
        .resena-card .verified-badge i {
            font-size: 0.7rem;
        }
        .review-form-section {
            background: var(--card-bg);
            border: 1px solid var(--shadow-color);
            border-radius: 10px;
            padding: 16px;
            margin-top: 15px;
        }
        .review-form-section h6 {
            font-weight: 700;
            margin-bottom: 10px;
        }
        .star-selector {
            display: flex;
            gap: 4px;
            font-size: 1.5rem;
            cursor: pointer;
            margin-bottom: 10px;
        }
        .star-selector i {
            color: #ccc;
            transition: color 0.2s;
        }
        .star-selector i.active,
        .star-selector i:hover,
        .star-selector i:hover ~ i {
            color: #f5a623;
        }
        .star-selector i.active {
            color: #f5a623;
        }
        .review-form-section .form-control {
            font-size: 0.9rem;
        }
        .review-form-section .btn-primary {
            background: var(--secondary-color);
            border: none;
        }
        .review-form-section .btn-primary:hover {
            background: var(--accent-color);
        }
    </style>
</head>
<body>
<div class="main-content" id="mainContent" style="padding: 20px; max-width: 1400px; margin: 0 auto;">
    <div class="header">
        <div class="header-content">
            <div>
                <h1><?php echo \I18n::trans('company_name'); ?></h1>
                <p><?php echo \I18n::trans('welcome'); ?> - <?php echo \I18n::trans('products'); ?></p>
            </div>
            
            <div class="tasa-bcv-container" id="tasaBcvContainer">
                <div class="tasa-bcv-header">
                    <span><i class="fas fa-money-bill-wave"></i> <?php echo \I18n::trans('tasa_bcv'); ?></span>
                    <button class="btn-refresh-tasa" id="refreshTasaBtn"><i class="fas fa-sync-alt"></i></button>
                </div>
                <div class="tasa-usd">
                    <span class="tasa-label">1 USD =</span>
                    <span class="tasa-value" id="tasaBcvValue">--,--</span>
                    <span class="tasa-currency">Bs</span>
                </div>
                <div class="tasa-info" style="font-size: 0.65rem;">
                    <span id="tasaBcvFecha"><?php echo \I18n::trans('loading'); ?></span>
                </div>
                <div class="tasa-bcv-ref">
                    <span id="tasaBcvReferencia"></span>
                </div>
            </div>

            <div class="header-right">
                <div class="search-container">
                    <div class="input-group">
                        <input type="text" class="form-control" id="search-input" placeholder="<?php echo \I18n::trans('search_products'); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button" id="search-btn"><i class="fas fa-search"></i></button>
                            <button class="btn filter-btn" type="button" id="filter-toggle-btn"><i class="fas fa-filter"></i></button>
                        </div>
                    </div>
                </div>

                <div class="header-icons">
                    <button class="btn-refresh-products" id="refreshProductsBtn">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    
                    <div class="dropdown">
                        <button class="btn btn-user-menu" type="button" id="userMenuButton" data-toggle="dropdown">
                            <div class="profile-image-container">
                                <img src="" alt="<?php echo \I18n::trans('profile'); ?>" class="profile-image" id="profileImage" style="display: none;">
                                <i class="fas fa-user-circle" id="defaultProfileIcon"></i>
                            </div>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <div class="dropdown-header text-center">
                                <div class="mb-2">
                                    <img src="" id="dropdownProfileImage" style="width: 55px; height: 55px; border-radius: 50%; display: none;">
                                    <i class="fas fa-user-circle" id="dropdownDefaultIcon" style="font-size: 3rem;"></i>
                                </div>
                                <span id="usuarioNombre"><?php echo \I18n::trans('guest'); ?></span><br>
                                <small id="usuarioCorreo"></small>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" data-target="#changeProfilePhotoModal" data-toggle="modal"><i class="fas fa-camera"></i> <?php echo \I18n::trans('upload_photo'); ?></a>
                            <a class="dropdown-item" href="#" data-target="#contactOptionsModal" data-toggle="modal"><i class="fas fa-envelope"></i> <?php echo \I18n::trans('contact_us'); ?></a>
                            <a class="dropdown-item" href="#" data-target="#changePasswordModal" data-toggle="modal"><i class="fas fa-lock"></i> <?php echo \I18n::trans('change_password'); ?></a>
                            <a class="dropdown-item" href="#" data-target="#setup2faModal" data-toggle="modal"><i class="fas fa-shield-alt"></i> <?php echo \I18n::trans('2fa_setup'); ?></a>
                            <a class="dropdown-item" href="#" id="viewHistoryBtn"><i class="fas fa-history"></i> <?php echo \I18n::trans('history'); ?></a>
                            <a class="dropdown-item" href="#" id="viewFavoritesBtn"><i class="fas fa-heart"></i> <?php echo \I18n::trans('my_favorites'); ?> <span class="favorites-count-badge" id="favoritesCountBadge" style="display:none;margin-left:5px;background:#ff4757;color:white;border-radius:50%;padding:1px 6px;font-size:0.65rem;vertical-align:super;"></span></a>
                            <a class="dropdown-item" href="#" onclick="cerrarSesion()"><i class="fas fa-sign-out-alt"></i> <?php echo \I18n::trans('logout'); ?></a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" onclick="eliminarCuenta()"><i class="fas fa-trash-alt"></i> <?php echo \I18n::trans('delete_account'); ?></a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" id="toggleDarkMode"><i class="fas fa-moon"></i> <?php echo \I18n::trans('dark_mode'); ?></a>
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-item" style="cursor:default;">
                                <form method="get" style="margin:0;">
                                    <select name="lang" onchange="this.form.submit()" class="form-control form-control-sm" style="font-size:0.8rem;">
                                        <option value="es" <?php echo $locale === 'es' ? 'selected' : ''; ?>><?php echo \I18n::trans('spanish'); ?></option>
                                        <option value="en" <?php echo $locale === 'en' ? 'selected' : ''; ?>><?php echo \I18n::trans('english'); ?></option>
                                    </select>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="cart-icon-container" data-target="#cartModal" data-toggle="modal">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="search-filters-container" id="filters-container">
        <div class="filter-group">
            <label><?php echo \I18n::trans('price'); ?> Mín ($)</label>
            <input type="number" class="form-control" id="price-min" placeholder="0">
        </div>
        <div class="filter-group">
            <label><?php echo \I18n::trans('price'); ?> Máx ($)</label>
            <input type="number" class="form-control" id="price-max" placeholder="1000">
        </div>
        <div class="filter-group">
            <label><?php echo \I18n::trans('rating'); ?></label>
            <select class="form-control" id="rating-min">
                <option value="0"><?php echo \I18n::trans('all'); ?></option>
                <option value="3">3+ ⭐</option>
                <option value="4">4+ ⭐</option>
            </select>
        </div>
        <div class="filter-group">
            <label><?php echo \I18n::trans('category'); ?></label>
            <select class="form-control" id="category-filter">
                <option value=""><?php echo \I18n::trans('all_categories'); ?></option>
            </select>
        </div>
        <div class="filter-group">
            <label><?php echo \I18n::trans('sort_by'); ?></label>
            <select class="form-control" id="sort-by">
                <option value="newest"><?php echo \I18n::trans('newest'); ?></option>
                <option value="name_asc"><?php echo \I18n::trans('name_asc'); ?></option>
                <option value="price_asc"><?php echo \I18n::trans('price_asc'); ?></option>
                <option value="price_desc"><?php echo \I18n::trans('price_desc'); ?></option>
            </select>
        </div>
        <div class="filter-group">
            <button class="btn btn-primary btn-sm" id="apply-filters-btn"><?php echo \I18n::trans('apply'); ?></button>
            <button class="btn btn-secondary btn-sm mt-1" id="clear-filters-btn"><?php echo \I18n::trans('clear_filters'); ?></button>
        </div>
    </div>

    <div class="active-filters" id="active-filters" style="display: none;">
        <small><?php echo \I18n::trans('filter'); ?>:</small>
    </div>

    <div class="favorites-section" id="favoritesSection" style="display:none;">
        <h3><i class="fas fa-heart" style="color:#ff4757;"></i> <?php echo \I18n::trans('my_favorites'); ?></h3>
        <div class="favorites-mini-grid" id="favoritesMiniGrid"></div>
    </div>

    <div class="products-grid" id="product-list"></div>

    <div class="pagination-container">
        <ul class="pagination" id="pagination-list"></ul>
    </div>
</div>

<button class="dark-mode-toggle-fixed" id="darkModeToggleFixed">
    <i class="fas fa-moon"></i>
</button>

<div id="floating-cart" data-target="#cartModal" data-toggle="modal">
    <i class="fas fa-shopping-cart"></i>
    <span class="cart-count">0</span>
</div>

<div class="floating-favorites-btn" id="floatingFavoritesBtn" style="display:none;">
    <i class="fas fa-heart"></i>
    <span class="favorites-floating-count" id="floatingFavoritesCount">0</span>
</div>

<div class="cart-notification" id="cartNotification">
    <i class="fas fa-check-circle"></i>
    <span id="notificationMessage"><?php echo \I18n::trans('product_added'); ?></span>
</div>

<footer class="footer-industrial">
    <div class="footer-container">
        <div class="footer-section">
            <h4><?php echo \I18n::trans('company_name'); ?></h4>
            <p><?php echo \I18n::trans('footer_description'); ?></p>
            <div class="social-links mt-3">
                <a href="https://www.facebook.com/piccavzla" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.linkedin.com/company/piccavzla" target="_blank" rel="noopener"><i class="fab fa-linkedin-in"></i></a>
                <a href="https://www.instagram.com/piccavzla/" target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a>
                <a href="https://t.me/piccavzlabot" target="_blank" rel="noopener"><i class="fab fa-telegram"></i></a>
            </div>
        </div>

        <div class="footer-section">
            <h4><?php echo \I18n::trans('providers'); ?></h4>
            <div class="footer-gallery">
                <img src="../img/autonics.png" alt="Autonics">
                <img src="../img/exceline.png" alt="Exceline">
                <img src="../img/scheneider.png" alt="Schneider">
                <img src="../img/uni-t.png" alt="UNI-T">
            </div>
        </div>

        <div class="footer-section">
            <h4><?php echo \I18n::trans('contact_us'); ?></h4>
            <p><i class="fas fa-map-marker-alt mr-2"></i> Zona Industrial, Centro Michelena</p>
            <p><i class="fas fa-phone mr-2"></i> +58 0424-8323902</p>
            <p><i class="fas fa-envelope mr-2"></i> Picca.ventas@gmail.com</p>
        </div>
    </div>

    <div class="footer-bottom">
        <p><?php echo \I18n::trans('copyright_text'); ?></p>
    </div>
</footer>

<!-- MODALES -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo \I18n::trans('product_detail'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 text-center">
                        <img id="modal-product-image" class="modal-product-image" src="">
                    </div>
                    <div class="col-md-6">
                        <h3 id="modal-product-name"></h3>
                        <p id="modal-product-category" class="category"></p>
                        <div id="modal-product-rating" class="rating"></div>
                        <h3 id="modal-product-price"></h3>
                        <div id="variant-selector" style="display:none;" class="mb-3"></div>
                        <p id="modal-product-description"></p>
                        <div class="shipping-info">
                            <p class="shipping-title"><i class="fas fa-truck"></i> <?php echo \I18n::trans('shipping_options'); ?></p>
                            <div class="shipping-carriers">
                                <span class="carrier-badge"><i class="fas fa-shipping-fast"></i> MRW</span>
                                <span class="carrier-badge"><i class="fas fa-shipping-fast"></i> Tealca</span>
                                <span class="carrier-badge"><i class="fas fa-shipping-fast"></i> Zoom</span>
                            </div>
                            <p class="shipping-note" id="modal-shipping-note"><?php echo \I18n::trans('shipping_note'); ?></p>
                        </div>
                        <div class="quantity-selector">
                            <button class="quantity-btn" id="qty-minus">-</button>
                            <input type="number" class="quantity-input" id="product-quantity" value="1" min="1">
                            <button class="quantity-btn" id="qty-plus">+</button>
                        </div>
                        <div class="d-flex gap-2 mt-2" style="gap:8px;">
                            <button class="btn btn-primary flex-grow-1" id="modal-add-to-cart-btn"><?php echo \I18n::trans('add_to_cart'); ?></button>
                            <button class="btn btn-success" id="modal-share-btn" title="<?php echo \I18n::trans('share'); ?>" style="flex:0 0 48px;">
                                <i class="fas fa-share-alt"></i>
                            </button>
                        </div>
                        <button class="btn btn-outline-danger w-100 mt-2" id="modal-fav-btn"><i class="far fa-heart"></i> <?php echo \I18n::trans('add_to_favorites'); ?></button>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div id="related-products" style="display:none;">
                            <hr>
                            <h6 class="mb-3"><i class="fas fa-tags"></i> <?php echo \I18n::trans('related_products'); ?></h6>
                            <div class="d-flex gap-2" id="related-products-list" style="overflow-x:auto; gap:12px; padding-bottom:8px;"></div>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <div class="resenas-section" id="resenas-section" style="display:none;">
                            <hr>
                            <h5><i class="fas fa-star"></i> <?php echo \I18n::trans('product_reviews'); ?></h5>
                            <div id="resenas-loading" class="text-center py-3">
                                <small class="text-muted"><i class="fas fa-spinner fa-spin"></i> <?php echo \I18n::trans('loading_reviews'); ?></small>
                            </div>
                            <div id="resenas-content"></div>
                            <div id="review-form-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-share-alt"></i> <?php echo \I18n::trans('share'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted small mb-3" id="shareProductName"></p>
                <div class="d-flex flex-wrap justify-content-center gap-3" style="gap:12px;">
                    <a class="btn btn-success btn-lg" id="shareWhatsApp" target="_blank" style="border-radius:12px; min-width:80px;">
                        <i class="fab fa-whatsapp fa-2x"></i><br><small>WhatsApp</small>
                    </a>
                    <a class="btn btn-primary btn-lg" id="shareFacebook" target="_blank" style="border-radius:12px; min-width:80px;">
                        <i class="fab fa-facebook fa-2x"></i><br><small>Facebook</small>
                    </a>
                    <a class="btn btn-secondary btn-lg" id="shareEmail" target="_blank" style="border-radius:12px; min-width:80px;">
                        <i class="fas fa-envelope fa-2x"></i><br><small>Email</small>
                    </a>
                    <button class="btn btn-dark btn-lg" id="shareCopyLink" style="border-radius:12px; min-width:80px;">
                        <i class="fas fa-link fa-2x"></i><br>                        <small><?php echo \I18n::trans('copy_button'); ?></small>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cartModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo \I18n::trans('shopping_cart'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="cart-items">
                <p class="text-center"><?php echo \I18n::trans('cart_empty'); ?></p>
            </div>
            <div class="modal-footer">
                <h4 id="cart-total"><?php echo \I18n::trans('total'); ?>: $0.00</h4>
                <button class="btn btn-primary" id="checkout-btn"><?php echo \I18n::trans('checkout'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passModalTitle"><?php echo \I18n::trans('change_password'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="passModalBody">
                <div id="passChangeSection">
                    <div class="form-group">
                        <label><?php echo \I18n::trans('current_password'); ?></label>
                        <input type="password" class="form-control" id="currentPassword">
                    </div>
                    <div class="form-group">
                        <label><?php echo \I18n::trans('new_password'); ?></label>
                        <input type="password" class="form-control" id="newPassword">
                    </div>
                    <div class="form-group">
                        <label><?php echo \I18n::trans('confirm_password'); ?></label>
                        <input type="password" class="form-control" id="confirmNewPassword">
                    </div>
                    <div class="text-center mt-2">
                        <a href="#" id="forgotPasswordLink" style="font-size:0.85rem;color:var(--secondary-color, #2a5298);"><i class="fas fa-key"></i> <?php echo \I18n::trans('recover_password'); ?></a>
                    </div>
                </div>
                <div id="passRecoverySection" style="display:none;">
                    <p class="text-muted small"><?php echo \I18n::trans('password_recovery_email_sent'); ?></p>
                    <div class="form-group">
                        <label><?php echo \I18n::trans('email'); ?></label>
                        <input type="email" class="form-control" id="recoveryEmail" readonly>
                    </div>
                    <button class="btn btn-primary btn-block" id="sendRecoveryPinBtn"><i class="fas fa-paper-plane"></i> <?php echo \I18n::trans('send_pin'); ?></button>
                    <div id="recoveryMsg" class="alert-message mt-2" style="display:none;"></div>
                    <div id="pinSection" style="display:none;" class="mt-3">
                        <label><?php echo \I18n::trans('pin_code'); ?></label>
                        <input type="text" class="form-control" id="recoveryPin" maxlength="6" placeholder="<?php echo \I18n::trans('enter_pin'); ?>">
                        <button class="btn btn-success btn-block mt-2" id="verifyRecoveryPinBtn"><?php echo \I18n::trans('verify_pin'); ?></button>
                        <div class="form-group mt-2" id="newPassSection" style="display:none;">
                            <label><?php echo \I18n::trans('new_password'); ?></label>
                            <input type="password" class="form-control" id="recoveryNewPassword" minlength="6" placeholder="<?php echo \I18n::trans('min_chars'); ?>">
                            <button class="btn btn-primary btn-block mt-2" id="setNewPasswordBtn"><?php echo \I18n::trans('reset_password'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal"><?php echo \I18n::trans('close'); ?></button>
                <button class="btn btn-primary" id="submitPasswordChange"><?php echo \I18n::trans('save'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="setup2faModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-shield-alt"></i> <?php echo \I18n::trans('2fa_setup'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="cliente2faEstado" style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--card-bg);border-radius:8px;margin-bottom:14px">
                    <div style="font-size:1.5rem"><i class="fas fa-qrcode"></i></div>
                    <div style="flex:1">
                        <div style="font-weight:600;font-size:0.9rem" id="cliente2faLabel">Verificando...</div>
                        <div style="font-size:0.75rem;opacity:0.7" id="cliente2faDesc">Consultando estado...</div>
                    </div>
                    <span class="badge" id="cliente2faBadge" style="padding:4px 10px;border-radius:12px;font-size:0.7rem">...</span>
                </div>

                <div id="cliente2faDisabled" style="display:none">
                    <p style="font-size:0.85rem;color:var(--text-color);opacity:0.8"><?php echo \I18n::trans('2fa_protect_account'); ?></p>
                    <button class="btn btn-primary btn-block" id="btnClienteConfigurar2FA"><i class="fas fa-shield-alt"></i> <?php echo \I18n::trans('2fa_activate'); ?></button>
                </div>

                <div id="cliente2faSetup" style="display:none">
                    <div id="cliente2faQRContainer" style="text-align:center;padding:10px">
                        <p style="font-size:0.85rem;color:var(--text-color);margin-bottom:10px"><?php echo \I18n::trans('scan_qr_code_text'); ?></p>
                        <canvas id="cliente2faQRCanvas"></canvas>
                    </div>
                    <div class="form-group" style="margin-top:10px">
                        <label style="font-size:0.8rem;font-weight:600"><?php echo \I18n::trans('or_enter_manually_label'); ?></label>
                        <input type="text" class="form-control" id="cliente2faSecretDisplay" readonly style="font-size:0.75rem;text-align:center;background:var(--card-bg);color:var(--accent-color);font-family:monospace">
                    </div>
                    <div style="display:flex;gap:8px;justify-content:center;margin:12px 0">
                        <input type="text" class="form-control" id="cliente2faCode1" maxlength="1" style="width:42px;text-align:center;font-size:1.2rem;font-weight:bold;padding:6px" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="form-control" id="cliente2faCode2" maxlength="1" style="width:42px;text-align:center;font-size:1.2rem;font-weight:bold;padding:6px" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="form-control" id="cliente2faCode3" maxlength="1" style="width:42px;text-align:center;font-size:1.2rem;font-weight:bold;padding:6px" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="form-control" id="cliente2faCode4" maxlength="1" style="width:42px;text-align:center;font-size:1.2rem;font-weight:bold;padding:6px" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="form-control" id="cliente2faCode5" maxlength="1" style="width:42px;text-align:center;font-size:1.2rem;font-weight:bold;padding:6px" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="form-control" id="cliente2faCode6" maxlength="1" style="width:42px;text-align:center;font-size:1.2rem;font-weight:bold;padding:6px" inputmode="numeric" pattern="[0-9]">
                    </div>
                    <div id="cliente2faBackupContainer" style="display:none;margin-top:10px;padding:10px;background:var(--card-bg);border-radius:8px">
                        <label style="font-size:0.8rem;font-weight:600"><?php echo \I18n::trans('backup_codes_save'); ?></label>
                        <div id="cliente2faBackupCodes" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px"></div>
                    </div>
                    <button class="btn btn-success btn-block" id="btnClienteVerificar2FA" style="display:none"><i class="fas fa-check-circle"></i> <?php echo \I18n::trans('verify_and_activate'); ?></button>
                </div>

                <div id="cliente2faActive" style="display:none">
                    <div style="text-align:center;padding:10px">
                        <i class="fas fa-check-circle" style="font-size:3rem;color:#28a745"></i>
                        <p style="font-size:0.9rem;margin-top:8px;font-weight:600">✅ <?php echo \I18n::trans('2fa_activated'); ?></p>
                        <p style="font-size:0.8rem;opacity:0.7"><?php echo \I18n::trans('account_protected'); ?></p>
                    </div>
                    <button class="btn btn-danger btn-block" id="btnClienteDesactivar2FA"><i class="fas fa-ban"></i> <?php echo \I18n::trans('deactivate_2fa'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reverify2faModal" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-shield-alt"></i> <?php echo \I18n::trans('periodic_2fa_verification'); ?></h5>
            </div>
            <div class="modal-body text-center">
                <i class="fab fa-google" style="font-size:3rem;color:#4285F4;margin-bottom:10px"></i>
                <p style="font-size:0.9rem;color:var(--text-color);margin-bottom:4px"><?php echo \I18n::trans('verify_your_session'); ?></p>
                <p style="font-size:0.85rem;opacity:0.7;margin-bottom:16px"><?php echo \I18n::trans('open_google_auth'); ?></p>
                <form id="reverify2faForm">
                    <div class="form-group">
                        <input type="text" class="form-control" id="reverify2faCode" inputmode="numeric" autocomplete="one-time-code"
                               maxlength="6" placeholder="000000" required
                               style="text-align:center;font-size:1.5rem;letter-spacing:8px;font-weight:600;border-radius:10px;padding:12px;background:var(--card-bg);color:var(--text-color)">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" id="reverify2faBtn">
                        <i class="fas fa-check-circle"></i> <?php echo \I18n::trans('verify_code'); ?>
                    </button>
                </form>
                <div id="reverify2faMessage" style="display:none;margin-top:0.8rem;padding:8px 12px;border-radius:8px;font-size:0.85rem"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="contactOptionsModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-envelope"></i> <?php echo \I18n::trans('contact_us'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted small mb-3"><?php echo \I18n::trans('contact_us'); ?>:</p>
                <div class="d-flex flex-column gap-2" style="gap:8px;">
                    <button class="btn btn-outline-primary btn-block contact-option" data-channel="gmail">
                        <i class="fab fa-google"></i> Gmail
                    </button>
                    <button class="btn btn-outline-primary btn-block contact-option" data-channel="hotmail">
                        <i class="fab fa-microsoft"></i> Hotmail / Outlook
                    </button>
                    <button class="btn btn-outline-info btn-block contact-option" data-channel="telegram">
                        <i class="fab fa-telegram"></i> Telegram
                    </button>
                    <button class="btn btn-outline-secondary btn-block contact-option" data-channel="email">
                        <i class="fas fa-envelope"></i> <?php echo \I18n::trans('contact_email_form'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo \I18n::trans('contact_us'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><?php echo \I18n::trans('name'); ?></label>
                    <input type="text" class="form-control" id="contactName">
                </div>
                <div class="form-group">
                    <label><?php echo \I18n::trans('email'); ?></label>
                    <input type="email" class="form-control" id="contactEmail">
                </div>
                <div class="form-group">
                    <label><?php echo \I18n::trans('message'); ?></label>
                    <textarea class="form-control" id="contactMessage" rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal"><?php echo \I18n::trans('cancel'); ?></button>
                <button class="btn btn-primary" id="submitContact"><?php echo \I18n::trans('send'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="changeProfilePhotoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo \I18n::trans('upload_photo'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <img id="previewPhoto" style="width: 120px; height: 120px; border-radius: 50%;">
                <input type="file" id="profilePhotoInput" accept="image/*" class="form-control mt-3">
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="uploadPhotoBtn"><?php echo \I18n::trans('save'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo \I18n::trans('history'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="historyContent">
                <p class="text-center"><?php echo \I18n::trans('loading'); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="favoritesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-heart" style="color:#ff4757;"></i> <?php echo \I18n::trans('my_favorites'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="favoritesContent">
                <p class="text-center"><?php echo \I18n::trans('loading'); ?></p>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
<script>
// ===== I18N =====
const i18n = {
    confirm_logout: '<?php echo \I18n::trans('confirm_logout'); ?>',
    cart_empty: '<?php echo \I18n::trans('cart_empty'); ?>',
    confirm_delete_account: '<?php echo \I18n::trans('confirm_delete_account'); ?>',
    no_favorites: '<?php echo \I18n::trans('no_favorites'); ?>',
    must_login: '<?php echo \I18n::trans('must_login'); ?>',
    login_required: '<?php echo \I18n::trans('login_required'); ?>',
    admin_no_purchase: '<?php echo \I18n::trans('admin_no_purchase'); ?>',
    admin_account_warning: '<?php echo \I18n::trans('admin_account_warning'); ?>',
    connection_error: '<?php echo \I18n::trans('connection_error'); ?>',
    session_expired: '<?php echo \I18n::trans('session_expired'); ?>',
    session_invalid: '<?php echo \I18n::trans('session_invalid'); ?>',
    account_inactive: '<?php echo \I18n::trans('account_inactive'); ?>',
    fill_all_fields: '<?php echo \I18n::trans('fill_all_fields'); ?>',
    password_mismatch: '<?php echo \I18n::trans('password_mismatch'); ?>',
    password_too_short: '<?php echo \I18n::trans('password_too_short'); ?>',
    password_changed: '<?php echo \I18n::trans('password_changed'); ?>',
    invalid_email: '<?php echo \I18n::trans('invalid_email'); ?>',
    added_to_favorites: '<?php echo \I18n::trans('added_to_favorites'); ?>',
    removed_from_favorites: '<?php echo \I18n::trans('removed_from_favorites'); ?>',
    not_available: '<?php echo \I18n::trans('not_available'); ?>',
    out_of_stock: '<?php echo \I18n::trans('out_of_stock'); ?>',
    add_to_cart: '<?php echo \I18n::trans('add_to_cart'); ?>',
    add_to_favorites: '<?php echo \I18n::trans('add_to_favorites'); ?>',
    remove_from_favorites: '<?php echo \I18n::trans('remove_from_favorites'); ?>',
    session_changed: '<?php echo \I18n::trans('session_changed'); ?>',
    please_login: '<?php echo \I18n::trans('please_login'); ?>',
    connection_restored: '<?php echo \I18n::trans('connection_restored'); ?>',
    connection_lost: '<?php echo \I18n::trans('connection_lost'); ?>',
    reloading_products: '<?php echo \I18n::trans('reloading_products'); ?>',
    updating_rate: '<?php echo \I18n::trans('updating_rate'); ?>',
    link_copied: '<?php echo \I18n::trans('link_copied'); ?>',
    error_copying: '<?php echo \I18n::trans('error_copying'); ?>',
    no_file_selected: '<?php echo \I18n::trans('no_file_selected'); ?>',
    invalid_format: '<?php echo \I18n::trans('invalid_format'); ?>',
    file_too_large: '<?php echo \I18n::trans('file_too_large'); ?>',
    uploading_photo: '<?php echo \I18n::trans('uploading_photo'); ?>',
    photo_updated: '<?php echo \I18n::trans('photo_updated'); ?>',
    photo_error: '<?php echo \I18n::trans('photo_error'); ?>',
    photo_deleted: '<?php echo \I18n::trans('photo_deleted'); ?>',
    photo_delete_error: '<?php echo \I18n::trans('photo_delete_error'); ?>',
    server_error: '<?php echo \I18n::trans('server_error'); ?>',
    connecting_error: '<?php echo \I18n::trans('connecting_error'); ?>',
    added_to_favorites: '<?php echo \I18n::trans('added_to_favorites'); ?>',
    removed_from_favorites: '<?php echo \I18n::trans('removed_from_favorites'); ?>',
    product_added: '<?php echo \I18n::trans('product_added'); ?>',
    cart_updated: '<?php echo \I18n::trans('cart_updated'); ?>',
    product_removed: '<?php echo \I18n::trans('product_removed'); ?>',
    loading: '<?php echo \I18n::trans('loading'); ?>',
    search: '<?php echo \I18n::trans('search'); ?>',
    cart_empty_msg: '<?php echo \I18n::trans('cart_empty'); ?>',
    login_to_view_history: '<?php echo \I18n::trans('login_to_view_history'); ?>',
    no_orders: '<?php echo \I18n::trans('no_orders'); ?>',
    error_loading_history: '<?php echo \I18n::trans('error_loading_history'); ?>',
    no_products_found: '<?php echo \I18n::trans('no_products_found'); ?>',
    no_products: '<?php echo \I18n::trans('no_products'); ?>',
    error_loading_products: '<?php echo \I18n::trans('error_loading_products'); ?>',
    checkout_redirect: '<?php echo \I18n::trans('checkout_redirect'); ?>',
    deleting_photo: '<?php echo \I18n::trans('deleting_photo'); ?>',
    delete_account_error: '<?php echo \I18n::trans('delete_account_error'); ?>',
    select_photo_first: '<?php echo \I18n::trans('select_photo_first'); ?>',
    '2fa_config_error': '<?php echo \I18n::trans('2fa_config_error'); ?>',
    enter_6_digit_code: '<?php echo \I18n::trans('enter_6_digit_code'); ?>',
    '2fa_activated': '<?php echo \I18n::trans('2fa_activated'); ?>',
    invalid_code: '<?php echo \I18n::trans('invalid_code'); ?>',
    '2fa_verify_error': '<?php echo \I18n::trans('2fa_verify_error'); ?>',
    '2fa_deactivated': '<?php echo \I18n::trans('2fa_deactivated'); ?>',
    incorrect_password: '<?php echo \I18n::trans('incorrect_password'); ?>',
    '2fa_deactivate_error': '<?php echo \I18n::trans('2fa_deactivate_error'); ?>',
    select_rating: '<?php echo \I18n::trans('select_rating'); ?>',
    review_published: '<?php echo \I18n::trans('review_published'); ?>',
    review_error: '<?php echo \I18n::trans('review_error'); ?>',
    dark_mode: '<?php echo \I18n::trans('dark_mode'); ?>',
    light_mode: '<?php echo \I18n::trans('light_mode'); ?>',
    cart_update_error: '<?php echo \I18n::trans('cart_update_error'); ?>',
    cart_remove_error: '<?php echo \I18n::trans('cart_remove_error'); ?>',
    favorites_error: '<?php echo \I18n::trans('favorites_error'); ?>',
    error_sending_message: '<?php echo \I18n::trans('error_sending_message'); ?>',
    guest: '<?php echo \I18n::trans('guest'); ?>',
    login: '<?php echo \I18n::trans('login'); ?>',
    loading_products: '<?php echo \I18n::trans('loading_products'); ?>',
    account_protected: '<?php echo \I18n::trans('account_protected'); ?>',
    activate_2fa_security: '<?php echo \I18n::trans('activate_2fa_security'); ?>',
    '2fa_migration_needed': '<?php echo \I18n::trans('2fa_migration_needed'); ?>',
    no_description: '<?php echo \I18n::trans('no_description'); ?>',
    view_product: '<?php echo \I18n::trans('view_product'); ?>',
    add_short: '<?php echo \I18n::trans('add_short'); ?>',
    select_options: '<?php echo \I18n::trans('select_options'); ?>',
    no_favorites_explore: '<?php echo \I18n::trans('no_favorites_explore'); ?>',
    explore_products: '<?php echo \I18n::trans('explore_products'); ?>',
    error_loading_favorites: '<?php echo \I18n::trans('error_loading_favorites'); ?>',
    filter_label: '<?php echo \I18n::trans('filter_label'); ?>',
    stock_units: '<?php echo \I18n::trans('stock_units'); ?>',
    loading_reviews: '<?php echo \I18n::trans('loading_reviews'); ?>',
    no_reviews_yet: '<?php echo \I18n::trans('no_reviews_yet'); ?>',
    verified_purchase: '<?php echo \I18n::trans('verified_purchase'); ?>',
    write_review: '<?php echo \I18n::trans('write_review'); ?>',
    title_optional: '<?php echo \I18n::trans('title_optional'); ?>',
    comment_optional: '<?php echo \I18n::trans('comment_optional'); ?>',
    send_review: '<?php echo \I18n::trans('send_review'); ?>',
    already_reviewed: '<?php echo \I18n::trans('already_reviewed'); ?>',
    error_loading_reviews: '<?php echo \I18n::trans('error_loading_reviews'); ?>',
    connection_error_reviews: '<?php echo \I18n::trans('connection_error_reviews'); ?>',
    email_not_available: '<?php echo \I18n::trans('email_not_available'); ?>',
    sending: '<?php echo \I18n::trans('sending'); ?>',
    pin_sent_to_email: '<?php echo \I18n::trans('pin_sent_to_email'); ?>',
    error_sending_code: '<?php echo \I18n::trans('error_sending_code'); ?>',
    send_pin_code: '<?php echo \I18n::trans('send_pin_code'); ?>',
    enter_full_pin: '<?php echo \I18n::trans('enter_full_pin'); ?>',
    pin_verified_set_password: '<?php echo \I18n::trans('pin_verified_set_password'); ?>',
    invalid_or_expired_pin: '<?php echo \I18n::trans('invalid_or_expired_pin'); ?>',
    verify_pin_btn: '<?php echo \I18n::trans('verify_pin_btn'); ?>',
    password_reset_login: '<?php echo \I18n::trans('password_reset_login'); ?>',
    error_resetting_password: '<?php echo \I18n::trans('error_resetting_password'); ?>',
    reset_password_btn: '<?php echo \I18n::trans('reset_password_btn'); ?>',
    '2fa_activated_success': '<?php echo \I18n::trans('2fa_activated_success'); ?>',
    verified_ok: '<?php echo \I18n::trans('verified_ok'); ?>',
    filter_search_value: '<?php echo \I18n::trans('filter_search_value'); ?>',
    filter_min_price_value: '<?php echo \I18n::trans('filter_min_price_value'); ?>',
    filter_max_price_value: '<?php echo \I18n::trans('filter_max_price_value'); ?>',
    filter_rating_value: '<?php echo \I18n::trans('filter_rating_value'); ?>',
    filter_category_value: '<?php echo \I18n::trans('filter_category_value'); ?>',
    verify_your_session: '<?php echo \I18n::trans('verify_your_session'); ?>',
    verify_code: '<?php echo \I18n::trans('verify_code'); ?>',
    delete_photo_confirm: '<?php echo \I18n::trans('delete_photo_confirm'); ?>',
    verifying: '<?php echo \I18n::trans('verifying'); ?>',
    checking_status: '<?php echo \I18n::trans('checking_status'); ?>',
    my_favorites: '<?php echo \I18n::trans('my_favorites'); ?>',
    history: '<?php echo \I18n::trans('history'); ?>',
    product: '<?php echo \I18n::trans('product'); ?>',
    error: '<?php echo \I18n::trans('error'); ?>',
};

// ===== VARIABLES GLOBALES =====
let allProducts = [];
let filteredProducts = [];
let currentPage = 1;
const itemsPerPage = 8;
let CURRENT_USER = null;
let tasaBCVActual = 549;
let cartItems = [];
let favoritosSet = new Set();
let csrfToken = '';

async function obtenerCsrfToken() {
    try {
        const res = await fetch('/proyecto/usuarios/obtener_csrf.php', { credentials: 'include' });
        const data = await res.json();
        if (data.token) csrfToken = data.token;
    } catch(e) {}
}

let currentFilters = {
    searchTerm: '',
    minPrice: null,
    maxPrice: null,
    minRating: 0,
    category: '',
    sortBy: 'newest'
};

// ===== OBTENER ELEMENTOS DOM =====
const productList = document.getElementById('product-list');
const searchInput = document.getElementById('search-input');
const searchBtn = document.getElementById('search-btn');
const filterToggleBtn = document.getElementById('filter-toggle-btn');
const filtersContainer = document.getElementById('filters-container');
const applyFiltersBtn = document.getElementById('apply-filters-btn');
const clearFiltersBtn = document.getElementById('clear-filters-btn');
const refreshProductsBtn = document.getElementById('refreshProductsBtn');
const refreshTasaBtn = document.getElementById('refreshTasaBtn');
const darkModeToggle = document.getElementById('darkModeToggleFixed');

// ===== FUNCIONES DE UTILIDAD =====
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function generateStarRating(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) stars += '<i class="fas fa-star"></i>';
        else if (i - 0.5 <= rating) stars += '<i class="fas fa-star-half-alt"></i>';
        else stars += '<i class="far fa-star"></i>';
    }
    return stars;
}

function showNotification(message, isError = false) {
    const notification = document.getElementById('cartNotification');
    const msgSpan = document.getElementById('notificationMessage');
    msgSpan.textContent = message;
    notification.style.backgroundColor = isError ? '#dc3545' : '#3C91ED';
    notification.classList.add('show');
    setTimeout(() => notification.classList.remove('show'), 3000);
}

// ===== FUNCIÓN PARA CARGAR FOTO DE PERFIL =====
async function cargarFotoPerfil() {
    if (!CURRENT_USER || !CURRENT_USER.id || CURRENT_USER.rol === 'guest') {
        return;
    }
    
    try {
        const response = await fetch('/proyecto/usuarios/obtener_foto_perfil.php', { 
            credentials: 'include',
            headers: { 'Cache-Control': 'no-cache' }
        });
        
        if (!response.ok) {
            throw new Error('Error al obtener foto');
        }
        
        const data = await response.json();
        
        const profileImage = document.getElementById('profileImage');
        const defaultProfileIcon = document.getElementById('defaultProfileIcon');
        const dropdownProfileImage = document.getElementById('dropdownProfileImage');
        const dropdownDefaultIcon = document.getElementById('dropdownDefaultIcon');
        
        if (data.success && data.photo_url) {
            const fotoUrl = data.photo_url + '?t=' + Date.now();
            
            if (profileImage) {
                profileImage.src = fotoUrl;
                profileImage.style.display = 'block';
            }
            if (defaultProfileIcon) defaultProfileIcon.style.display = 'none';
            
            if (dropdownProfileImage) {
                dropdownProfileImage.src = fotoUrl;
                dropdownProfileImage.style.display = 'block';
            }
            if (dropdownDefaultIcon) dropdownDefaultIcon.style.display = 'none';
        } else {
            if (profileImage) profileImage.style.display = 'none';
            if (defaultProfileIcon) defaultProfileIcon.style.display = 'block';
            if (dropdownProfileImage) dropdownProfileImage.style.display = 'none';
            if (dropdownDefaultIcon) dropdownDefaultIcon.style.display = 'block';
        }
    } catch (error) {
        console.error('Error cargando foto de perfil:', error);
    }
}

// ===== FUNCIONES PARA FOTO DE PERFIL =====
async function subirFotoPerfil(file) {
    if (!file) {
        showNotification(i18n.no_file_selected, true);
        return;
    }
    
    const tiposPermitidos = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    if (!tiposPermitidos.includes(file.type)) {
        showNotification(i18n.invalid_format, true);
        return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
        showNotification(i18n.file_too_large, true);
        return;
    }
    
    const formData = new FormData();
    formData.append('foto', file);
    formData.append('_csrf_token', csrfToken);
    
    showNotification(i18n.uploading_photo);
    
    try {
        const response = await fetch('/proyecto/usuarios/subir_foto_perfil.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Error al parsear JSON:', text);
            showNotification(i18n.photo_error, true);
            return;
        }
        
        if (data.success) {
            showNotification(data.message || i18n.photo_updated);
            await cargarFotoPerfil();
            $('#changeProfilePhotoModal').modal('hide');
        } else {
            showNotification(data.message || i18n.photo_error, true);
        }
    } catch (error) {
        console.error('Error en subirFotoPerfil:', error);
        showNotification(i18n.connection_error, true);
    }
}

async function eliminarFotoPerfil() {
    if (!confirm(i18n.delete_photo_confirm)) return;
    
    showNotification(i18n.deleting_photo);
    
    try {
        const response = await fetch('/proyecto/usuarios/eliminar_foto_perfil.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message || i18n.photo_deleted);
            await cargarFotoPerfil();
        } else {
            showNotification(data.message || i18n.photo_delete_error, true);
        }
    } catch (error) {
        console.error('Error en eliminarFotoPerfil:', error);
        showNotification(i18n.connection_error, true);
    }
}

// ===== ACCIONES DE USUARIO =====
function cerrarSesion() {
    if (confirm(i18n.confirm_logout)) {
        window.location.href = '/proyecto/usuarios/cerrar_sesion.php';
    }
}

function eliminarCuenta() {
    if (confirm(i18n.confirm_delete_account)) {
        fetch('/proyecto/usuarios/eliminar_cuenta.php', { 
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/proyecto/interfaz_usuario/index.html';
            } else {
                showNotification(data.message || i18n.delete_account_error, true);
            }
        })
        .catch(() => {
            showNotification(i18n.connection_error, true);
        });
    }
}

// ===== MODO OSCURO =====
function initDarkMode() {
    const darkModeToggleFixed = document.getElementById('darkModeToggleFixed');
    const darkModeToggleMenu = document.getElementById('toggleDarkMode');
    
    function updateDarkModeUI(isDark) {
        if (darkModeToggleFixed) {
            darkModeToggleFixed.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        }
        if (darkModeToggleMenu) {
            darkModeToggleMenu.innerHTML = isDark ? '<i class="fas fa-sun"></i> ' + i18n.light_mode : '<i class="fas fa-moon"></i> ' + i18n.dark_mode;
        }
    }
    
    function toggleDarkMode() {
        const isDark = !document.body.classList.contains('dark-mode');
        
        if (isDark) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
        
        localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
        updateDarkModeUI(isDark);
    }
    
    const saved = localStorage.getItem('darkMode');
    const isDarkSaved = saved === 'enabled';
    
    if (isDarkSaved) {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }
    updateDarkModeUI(isDarkSaved);
    
    if (darkModeToggleFixed) {
        darkModeToggleFixed.addEventListener('click', toggleDarkMode);
    }
    if (darkModeToggleMenu) {
        darkModeToggleMenu.addEventListener('click', function(e) {
            e.preventDefault();
            toggleDarkMode();
        });
    }
}

// ===== OBTENER USUARIO ACTUAL =====
async function obtenerUsuarioActual() {
    try {
        const response = await fetch('/proyecto/usuarios/verificar_sesion_cliente.php', { 
            credentials: 'include',
            headers: { 'Cache-Control': 'no-cache' }
        });
        const data = await response.json();
        
        
        if (data.is_admin === true) {
            showNotification(i18n.admin_account_warning, 'warning');
            
            CURRENT_USER = { 
                id: null, 
                nombre: i18n.guest, 
                correo: '',
                rol: 'guest',
                can_purchase: false
            };
            
            const usuarioNombreSpan = document.getElementById('usuarioNombre');
            if (usuarioNombreSpan) {
                usuarioNombreSpan.textContent = i18n.guest;
            }
            
            const profileImage = document.getElementById('profileImage');
            const defaultProfileIcon = document.getElementById('defaultProfileIcon');
            if (profileImage) profileImage.style.display = 'none';
            if (defaultProfileIcon) defaultProfileIcon.style.display = 'block';
            
            return false;
        }
        
        if (data.role === 'cliente' && data.success === true && data.can_purchase !== false) {
            
            CURRENT_USER = {
                id: data.user.id,
                nombre: data.user.nombre,
                correo: data.user.correo,
                rol: data.user.rol || 'cliente',
                can_purchase: true
            };
            
            const usuarioNombreSpan = document.getElementById('usuarioNombre');
            if (usuarioNombreSpan) {
                usuarioNombreSpan.textContent = CURRENT_USER.nombre;
            }
            
            const usuarioCorreoSpan = document.getElementById('usuarioCorreo');
            if (usuarioCorreoSpan && CURRENT_USER.correo) {
                usuarioCorreoSpan.textContent = CURRENT_USER.correo;
            }
            
            await cargarFotoPerfil();
            
            return true;
        }
        
        if (data.force_logout === true) {
            showNotification(i18n.session_invalid, 'warning');
            
            CURRENT_USER = { 
                id: null, 
                nombre: i18n.guest, 
                correo: '',
                rol: 'guest',
                can_purchase: false
            };
            
            const usuarioNombreSpan = document.getElementById('usuarioNombre');
            if (usuarioNombreSpan) {
                usuarioNombreSpan.textContent = i18n.guest;
            }
            
            const profileImage = document.getElementById('profileImage');
            const defaultProfileIcon = document.getElementById('defaultProfileIcon');
            if (profileImage) profileImage.style.display = 'none';
            if (defaultProfileIcon) defaultProfileIcon.style.display = 'block';
            
            return false;
        }
        
        if (data.role === 'inactive') {
            showNotification(i18n.account_inactive, 'error');
            setTimeout(() => {
                window.location.href = '/proyecto/interfaz_usuario/login.html';
            }, 2000);
            return false;
        }
        
        if (data.redirect) {
            window.location.href = data.redirect;
            return false;
        }
        
        CURRENT_USER = { 
            id: null, 
            nombre: i18n.guest, 
            correo: '',
            rol: 'guest',
            can_purchase: false
        };
        
        const usuarioNombreSpan = document.getElementById('usuarioNombre');
        if (usuarioNombreSpan) {
            usuarioNombreSpan.textContent = i18n.guest;
        }
        
        const dropdownMenu = document.querySelector('.dropdown-menu');
        if (dropdownMenu && !document.querySelector('.login-guest-item')) {
            const loginItem = document.createElement('a');
            loginItem.className = 'dropdown-item login-guest-item';
            loginItem.href = '/proyecto/interfaz_usuario/login.html';
            loginItem.innerHTML = '<i class="fas fa-sign-in-alt"></i> ' + i18n.login;
            dropdownMenu.insertBefore(loginItem, dropdownMenu.firstChild);
        }
        
        const profileImage = document.getElementById('profileImage');
        const defaultProfileIcon = document.getElementById('defaultProfileIcon');
        if (profileImage) profileImage.style.display = 'none';
        if (defaultProfileIcon) defaultProfileIcon.style.display = 'block';
        
        return false;
        
    } catch (error) {
        console.error('Error verificando cliente:', error);
        
        CURRENT_USER = { id: null, nombre: i18n.guest, correo: '', rol: 'guest', can_purchase: false };
        
        const usuarioNombreSpan = document.getElementById('usuarioNombre');
        if (usuarioNombreSpan) {
            usuarioNombreSpan.textContent = i18n.guest;
        }
        
        return false;
    }
}

function iniciarVerificacionSesionPeriodica() {
    setInterval(async () => {
        try {
            const response = await fetch('/proyecto/usuarios/verificar_sesion_cliente.php', {
                credentials: 'include',
                cache: 'no-store',
                headers: { 'Cache-Control': 'no-cache' }
            });
            const data = await response.json();
            
            if (data.force_logout === true) {
                showNotification(i18n.session_changed, 'warning');
                
                CURRENT_USER = { id: null, nombre: i18n.guest, correo: '', rol: 'guest', can_purchase: false };
                
                const usuarioNombreSpan = document.getElementById('usuarioNombre');
                if (usuarioNombreSpan) {
                    usuarioNombreSpan.textContent = i18n.guest;
                }
                
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            
            if (!data.success && !data.is_authenticated && CURRENT_USER && CURRENT_USER.id) {
                showNotification(i18n.session_expired, 'warning');
                setTimeout(() => {
                    window.location.href = '/proyecto/interfaz_usuario/login.html';
                }, 2000);
            }
        } catch (error) {
            console.error('Error en verificación periódica:', error);
        }

        // Check periodic 2FA re-verification
        verificarReverificacion2FA();
    }, 60000);
}

async function verificarReverificacion2FA() {
    if (!CURRENT_USER || !CURRENT_USER.id) return;
    try {
        const res = await fetch('/proyecto/2fa/verificar_2fa_periodico.php?action=check&type=cliente', {
            credentials: 'include',
            cache: 'no-store'
        });
        const data = await res.json();
        if (data.success && data.needs_2fa) {
            if (!$('#reverify2faModal').is(':visible')) {
                $('#reverify2faModal').modal('show');
                document.getElementById('reverify2faCode').value = '';
                document.getElementById('reverify2faMessage').style.display = 'none';
            }
        }
    } catch(e) {
        console.error('Error verificando 2FA periódico:', e);
    }
}

document.getElementById('reverify2faForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const code = document.getElementById('reverify2faCode').value.trim();
    if (code.length !== 6) return;
    const btn = document.getElementById('reverify2faBtn');
    const msgDiv = document.getElementById('reverify2faMessage');
    msgDiv.style.display = 'none';
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ' + i18n.verifying;

    try {
        const formData = new FormData();
        formData.append('action', 'verify');
        formData.append('code', code);
        formData.append('type', 'cliente');
        const res = await fetch('/proyecto/2fa/verificar_2fa_periodico.php', { method: 'POST', body: formData, credentials: 'include' });
        const data = await res.json();
        if (data.success) {
            msgDiv.style.cssText = 'display:block;padding:8px 12px;border-radius:8px;font-size:0.85rem;background:#d4edda;color:#155724';
            msgDiv.textContent = i18n.verified_ok;
            setTimeout(() => {
                $('#reverify2faModal').modal('hide');
            }, 800);
        } else {
            msgDiv.style.cssText = 'display:block;padding:8px 12px;border-radius:8px;font-size:0.85rem;background:#f8d7da;color:#721c24';
            msgDiv.textContent = data.message || i18n.invalid_code;
            document.getElementById('reverify2faCode').value = '';
            document.getElementById('reverify2faCode').focus();
        }
    } catch(e) {
        msgDiv.style.cssText = 'display:block;padding:8px 12px;border-radius:8px;font-size:0.85rem;background:#f8d7da;color:#721c24';
        msgDiv.textContent = i18n.connection_error;
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> ' + i18n.verify_code;
    }
});

document.getElementById('reverify2faCode')?.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
    if (this.value.length === 6) {
        document.getElementById('reverify2faForm').dispatchEvent(new Event('submit'));
    }
});

function formatearTasa(tasa, decimales = 4) {
    return tasa.toLocaleString('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: decimales
    });
}

function actualizarPreciosConTasa() {
    document.querySelectorAll('.product-card .price').forEach(priceElement => {
        const usdMatch = priceElement.innerHTML.match(/\$([0-9.]+)/);
        if (usdMatch) {
            const usd = parseFloat(usdMatch[1]);
            const ves = (usd * tasaBCVActual).toFixed(2);
            const vesSpan = priceElement.querySelector('.ves-price');
            if (vesSpan) {
                vesSpan.innerHTML = `(≈ ${ves} Bs)`;
            }
        }
    });
    
    const modalPrice = document.getElementById('modal-product-price');
    if (modalPrice && window.currentModalProduct) {
        const usd = window.currentModalProduct.price;
        const ves = (usd * tasaBCVActual).toFixed(2);
        modalPrice.innerHTML = `$${usd.toFixed(2)}<br><small>≈ ${ves} Bs</small>`;
    }
}

async function cargarTasaBCV() {
    const tasaElement = document.getElementById('tasaBcvValue');
    const fechaElement = document.getElementById('tasaBcvFecha');
    const referenciaElement = document.getElementById('tasaBcvReferencia');
    
    if (!tasaElement) return;
    
    function setDefaultTasa() {
        const ultimaTasa = localStorage.getItem('ultima_tasa_guardada');
        if (ultimaTasa && parseFloat(ultimaTasa) > 20 && parseFloat(ultimaTasa) < 2000) {
            tasaBCVActual = parseFloat(ultimaTasa);
        } else {
            tasaBCVActual = 549;
        }
        tasaElement.textContent = formatearTasa(tasaBCVActual, 2);
        fechaElement.innerHTML = '<small><i class="fas fa-info-circle"></i> ' + i18n.tasa_bcv + '</small>';
        if (referenciaElement) referenciaElement.innerHTML = '';
        actualizarPreciosConTasa();
    }
    
    try {
        tasaElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        fechaElement.innerHTML = '<small>' + i18n.updating_rate + '</small>';
        if (referenciaElement) referenciaElement.innerHTML = '';
        
        const response = await fetch('/proyecto/tasas/bcv_scraper.php?nocache=' + Date.now());
        
        if (!response.ok) {
            throw new Error('Error HTTP: ' + response.status);
        }
        
        const data = await response.json();
        
        if (data && data.success && data.tasa_bcv && data.tasa_bcv > 20 && data.tasa_bcv < 2000) {
            tasaBCVActual = parseFloat(data.tasa_bcv);
            tasaElement.textContent = formatearTasa(tasaBCVActual, 4);
            
            const valorBCVOriginal = (tasaBCVActual * 10).toLocaleString('es-VE', {
                minimumFractionDigits: 4,
                maximumFractionDigits: 6
            });
            if (referenciaElement) {
                referenciaElement.innerHTML = `📌 BCV: USD ${valorBCVOriginal}`;
            }
            
            const fechaActual = new Date().toLocaleTimeString();
            fechaElement.innerHTML = `<small><i class="fas fa-check-circle"></i> ${data.fuente || 'BCV'} | ${fechaActual}</small>`;
            
            localStorage.setItem('ultima_tasa_guardada', tasaBCVActual);
            localStorage.setItem('ultima_tasa_fecha', Date.now());
            
            actualizarPreciosConTasa();
            return;
        }
        
        const backupResponse = await fetch('https://api.exchangerate-api.com/v4/latest/USD');
        const backupData = await backupResponse.json();
        
        if (backupData && backupData.rates && backupData.rates.VES) {
            const tasaBackup = parseFloat(backupData.rates.VES);
            if (tasaBackup > 20 && tasaBackup < 2000) {
                tasaBCVActual = tasaBackup;
                tasaElement.textContent = formatearTasa(tasaBCVActual, 4);
                fechaElement.innerHTML = '<small><i class="fas fa-cloud-upload-alt"></i> API externa</small>';
                if (referenciaElement) referenciaElement.innerHTML = '';
                localStorage.setItem('ultima_tasa_guardada', tasaBCVActual);
                actualizarPreciosConTasa();
                return;
            }
        }
        
        setDefaultTasa();
        
    } catch (error) {
        console.error('Error cargando tasa BCV:', error);
        setDefaultTasa();
    }
}

async function loadProductsFromDB() {
    if (!productList) return;
    
    try {
        productList.innerHTML = '<div class="text-center"><div class="spinner-border text-primary"></div><p>' + i18n.loading_products + '</p></div>';
        
        const response = await fetch('/proyecto/producto/obtener_producto.php');
        const data = await response.json();
        
        if (data.success && data.products && data.products.length > 0) {
            allProducts = data.products.map(p => ({
                id: parseInt(p.id),
                name: p.name || 'Sin nombre',
                price: parseFloat(p.price) || 0,
                image: p.image || 'https://via.placeholder.com/300x300?text=Producto',
                description: p.description || '',
                category: p.category || '',
                rating: parseFloat(p.rating) || 0,
                stock: parseInt(p.stock) || 0,
                active: p.active !== undefined ? p.active : 1,
                has_variants: p.has_variants === true
            }));
            
            const categories = [...new Set(allProducts.map(p => p.category))];
            const categorySelect = document.getElementById('category-filter');
            if (categorySelect) {
                categorySelect.innerHTML = '<option value="">Todas</option>';
                categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat;
                    option.textContent = cat;
                    categorySelect.appendChild(option);
                });
            }
            
            applyFilters();
        } else {
            productList.innerHTML = '<div class="text-center"><p>' + i18n.no_products + '</p></div>';
        }
    } catch (error) {
        console.error('Error:', error);
        productList.innerHTML = '<div class="text-center text-danger"><p>' + i18n.error_loading_products + '</p></div>';
    }
}

function applyFilters() {
    filteredProducts = [...allProducts];
    
    if (currentFilters.searchTerm) {
        const term = currentFilters.searchTerm.toLowerCase();
        filteredProducts = filteredProducts.filter(p => 
            p.name.toLowerCase().includes(term) || 
            (p.description && p.description.toLowerCase().includes(term))
        );
    }
    if (currentFilters.minPrice) {
        filteredProducts = filteredProducts.filter(p => p.price >= parseFloat(currentFilters.minPrice));
    }
    if (currentFilters.maxPrice) {
        filteredProducts = filteredProducts.filter(p => p.price <= parseFloat(currentFilters.maxPrice));
    }
    if (currentFilters.minRating > 0) {
        filteredProducts = filteredProducts.filter(p => p.rating >= currentFilters.minRating);
    }
    if (currentFilters.category) {
        filteredProducts = filteredProducts.filter(p => p.category === currentFilters.category);
    }
    
    switch(currentFilters.sortBy) {
        case 'newest': break;
        case 'name_asc': filteredProducts.sort((a,b) => a.name.localeCompare(b.name)); break;
        case 'price_asc': filteredProducts.sort((a,b) => a.price - b.price); break;
        case 'price_desc': filteredProducts.sort((a,b) => b.price - a.price); break;
    }
    
    currentPage = 1;
    displayProducts();
    updateActiveFilters();
}

function displayProducts() {
    if (!productList) return;
    
    if (filteredProducts.length === 0) {
        productList.innerHTML = '<div class="text-center"><p>' + i18n.no_products_found + '</p></div>';
        const paginationList = document.getElementById('pagination-list');
        if (paginationList) paginationList.innerHTML = '';
        return;
    }
    
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const currentProducts = filteredProducts.slice(start, end);
    
    productList.innerHTML = '';
    
    currentProducts.forEach(product => {
        const precioVES = (product.price * tasaBCVActual).toFixed(2);
        const inactivo = product.active === 0;
        const esFavorito = favoritosSet.has(product.id);
        const tieneVariantes = product.has_variants === true;
        
        const card = document.createElement('div');
        card.className = 'product-card' + (tieneVariantes ? ' has-variants' : '');
        card.setAttribute('data-id', product.id);
        const priceHtml = tieneVariantes
            ? `<div class="price">Desde $${product.price.toFixed(2)}<small class="ves-price">(≈ ${precioVES} Bs)</small></div>`
            : `<div class="price">$${product.price.toFixed(2)}<small class="ves-price">(≈ ${precioVES} Bs)</small></div>`;
        const btnDisabled = product.stock === 0 || inactivo || tieneVariantes;
        const btnClass = btnDisabled ? 'stock-disabled' : '';
        const btnDisabledAttr = btnDisabled ? 'disabled' : '';
        let btnText;
        if (inactivo) btnText = i18n.not_available;
        else if (tieneVariantes) btnText = '<i class="fas fa-list"></i> ' + i18n.select_options;
        else if (product.stock === 0) btnText = i18n.out_of_stock;
        else btnText = '<i class="fas fa-shopping-cart"></i> ' + i18n.add_short;
        
        card.innerHTML = `
            <div class="product-img-container" data-id="${product.id}">
                <img src="${product.image}" alt="${escapeHtml(product.name)}" data-id="${product.id}" onerror="this.src='https://via.placeholder.com/300x300?text=Sin+Imagen'">
                ${inactivo ? '<span style="position:absolute; top:5px; right:5px; background:#dc3545; color:white; padding:2px 6px; border-radius:4px; font-size:11px;">NO DISP</span>' : ''}
                <div class="product-favorite-btn ${esFavorito ? 'favorito-activo' : ''}" data-id="${product.id}">
                    <i class="${esFavorito ? 'fas' : 'far'} fa-heart"></i>
                </div>
            </div>
            <div class="category">${escapeHtml(product.category)}</div>
            <h5 data-id="${product.id}">${escapeHtml(product.name)}</h5>
            <div class="rating">${generateStarRating(product.rating)}</div>
            ${priceHtml}
            <button class="add-to-cart-btn ${btnClass}" data-id="${product.id}" ${btnDisabledAttr}>
                ${btnText}
            </button>
        `;
        productList.appendChild(card);
    });
    
    setupPagination();
    attachProductEvents();
}

function setupPagination() {
    const totalPages = Math.ceil(filteredProducts.length / itemsPerPage);
    const paginationList = document.getElementById('pagination-list');
    if (!paginationList) return;
    paginationList.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = i;
        a.addEventListener('click', (e) => {
            e.preventDefault();
            currentPage = i;
            displayProducts();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        li.appendChild(a);
        paginationList.appendChild(li);
    }
}

function attachProductEvents() {
    document.querySelectorAll('.product-img-container, .product-card h5').forEach(el => {
        el.addEventListener('click', (e) => {
            e.stopPropagation();
            const id = parseInt(el.getAttribute('data-id'));
            const product = allProducts.find(p => p.id === id);
            if (product) openProductModal(product);
        });
    });
    
    document.querySelectorAll('.add-to-cart-btn:not([disabled])').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const id = parseInt(btn.getAttribute('data-id'));
            const product = allProducts.find(p => p.id === id);
            if (product && product.has_variants) {
                openProductModal(product);
            } else {
                addToCart(id, 1, 0);
            }
        });
    });

    document.querySelectorAll('.product-favorite-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const id = parseInt(btn.getAttribute('data-id'));
            toggleFavorito(id, e);
        });
    });
}

function openProductModal(product) {
    window.currentModalProduct = product;
    window.selectedVariantId = 0;
    
    const modalImg = document.getElementById('modal-product-image');
    modalImg.src = product.image;
    modalImg.onerror = function() { this.src = 'https://via.placeholder.com/300x300?text=Sin+Imagen'; };
    document.getElementById('modal-product-name').textContent = product.name;
    document.getElementById('modal-product-category').textContent = product.category;
    document.getElementById('modal-product-rating').innerHTML = generateStarRating(product.rating);
    
    const priceEl = document.getElementById('modal-product-price');
    const descriptionEl = document.getElementById('modal-product-description');
    descriptionEl.textContent = product.description || i18n.no_description;
    
    const modalBtn = document.getElementById('modal-add-to-cart-btn');
    modalBtn.setAttribute('data-id', product.id);
    modalBtn.removeAttribute('data-variant-id');
    
    const variantSelector = document.getElementById('variant-selector');
    
    if (product.has_variants) {
        priceEl.innerHTML = `Desde $${product.price.toFixed(2)}<br><small>≈ ${(product.price * tasaBCVActual).toFixed(2)} Bs</small>`;
        modalBtn.disabled = true;
        modalBtn.textContent = i18n.select_options;
        variantSelector.style.display = 'block';
        variantSelector.innerHTML = '<div class="text-center"><small class="text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando opciones...</small></div>';
        
        fetch(`/proyecto/variantes/obtener_variantes.php?producto_id=${product.id}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.variantes || data.variantes.length === 0) {
                    variantSelector.innerHTML = '<p class="text-muted small">No hay variantes disponibles</p>';
                    return;
                }
                const variantes = data.variantes.filter(v => v.activo === 1);
                if (variantes.length === 0) {
                    variantSelector.innerHTML = '<p class="text-muted small">No hay variantes disponibles</p>';
                    return;
                }
                
                let html = '<label class="form-label" style="font-weight:600;">Selecciona una variante:</label>';
                
                const atributos = {};
                variantes.forEach(v => {
                    if (v.combinacion && typeof v.combinacion === 'object') {
                        Object.keys(v.combinacion).forEach(key => {
                            if (!atributos[key]) atributos[key] = new Set();
                            atributos[key].add(v.combinacion[key]);
                        });
                    }
                });
                
                const attrKeys = Object.keys(atributos);
                if (attrKeys.length > 0) {
                    attrKeys.forEach(attrName => {
                        html += `<div class="mb-2"><label class="small text-muted">${escapeHtml(attrName)}:</label><div class="d-flex flex-wrap gap-1">`;
                        atributos[attrName].forEach(val => {
                            html += `<button type="button" class="btn btn-sm btn-outline-primary variant-attr-btn" data-attr="${escapeHtml(attrName)}" data-value="${escapeHtml(val)}">${escapeHtml(val)}</button>`;
                        });
                        html += `</div></div>`;
                    });
                    html += `<div id="variant-result" class="mt-2 small"></div>`;
                } else {
                    html += `<select class="form-select" id="variant-select">`;
                    variantes.forEach(v => {
                        const totalPrice = product.price + v.precio_adicional;
                        html += `<option value="${v.id}">${escapeHtml(v.nombre_variante)} - $${totalPrice.toFixed(2)} ${v.stock > 0 ? '' : '(Sin stock)'}</option>`;
                    });
                    html += `</select>`;
                }
                
                variantSelector.innerHTML = html;
                window.currentVariants = variantes;
                
                if (attrKeys.length > 0) {
                    document.querySelectorAll('.variant-attr-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const attr = this.getAttribute('data-attr');
                            const value = this.getAttribute('data-value');
                            document.querySelectorAll(`.variant-attr-btn[data-attr="${attr}"]`).forEach(b => b.classList.remove('active'));
                            this.classList.add('active');
                            updateVariantSelection(product);
                        });
                    });
                } else {
                    document.getElementById('variant-select').addEventListener('change', function() {
                        selectVariantById(product, parseInt(this.value));
                    });
                    selectVariantById(product, parseInt(document.getElementById('variant-select').value));
                }
            })
            .catch(err => {
                console.error('Error cargando variantes:', err);
                variantSelector.innerHTML = '<p class="text-danger small">Error al cargar variantes</p>';
            });
    } else {
        priceEl.innerHTML = `$${product.price.toFixed(2)}<br><small>≈ ${(product.price * tasaBCVActual).toFixed(2)} Bs</small>`;
        modalBtn.disabled = product.stock === 0 || product.active === 0;
        modalBtn.textContent = (product.stock === 0 || product.active === 0) ? i18n.not_available : i18n.add_to_cart;
        variantSelector.style.display = 'none';
        variantSelector.innerHTML = '';
    }
    
    const favBtn = document.getElementById('modal-fav-btn');
    const esFav = favoritosSet.has(product.id);
    favBtn.innerHTML = esFav ? '<i class="fas fa-heart"></i> ' + i18n.remove_from_favorites : '<i class="far fa-heart"></i> ' + i18n.add_to_favorites;
    favBtn.className = `btn ${esFav ? 'btn-danger' : 'btn-outline-danger'} w-100 mt-2`;
    favBtn.onclick = async () => {
        await toggleFavorito(product.id);
        const nowFav = favoritosSet.has(product.id);
        favBtn.innerHTML = nowFav ? '<i class="fas fa-heart"></i> ' + i18n.remove_from_favorites : '<i class="far fa-heart"></i> ' + i18n.add_to_favorites;
        favBtn.className = `btn ${nowFav ? 'btn-danger' : 'btn-outline-danger'} w-100 mt-2`;
    };
    
    // Related products
    const relatedContainer = document.getElementById('related-products');
    const relatedList = document.getElementById('related-products-list');
    if (relatedContainer && relatedList) {
        const related = allProducts.filter(p => p.category === product.category && p.id !== product.id && p.active !== 0).slice(0, 6);
        if (related.length > 0) {
            relatedList.innerHTML = related.map(r => `
                <div class="related-product-card" data-id="${r.id}">
                    <img src="${r.image}" alt="${escapeHtml(r.name)}" loading="lazy" onerror="this.src='https://via.placeholder.com/300x300?text=Sin+Imagen'">
                    <div class="name">${escapeHtml(r.name)}</div>
                    <div class="price">$${r.price.toFixed(2)}</div>
                </div>
            `).join('');
            relatedContainer.style.display = 'block';
            relatedList.querySelectorAll('.related-product-card').forEach(el => {
                el.addEventListener('click', () => {
                    const id = parseInt(el.getAttribute('data-id'));
                    const prod = allProducts.find(p => p.id === id);
                    if (prod) openProductModal(prod);
                });
            });
        } else {
            relatedContainer.style.display = 'none';
        }
    }
    
    // Load reviews
    const resenasSection = document.getElementById('resenas-section');
    if (resenasSection) {
        resenasSection.style.display = 'none';
        loadResenas(product.id);
    }

    // Share button
    const shareBtn = document.getElementById('modal-share-btn');
    if (shareBtn) {
        shareBtn.onclick = () => {
            const shareName = product.name;
            const shareUrl = window.location.origin + '/proyecto/producto/obtener_producto.php?id=' + product.id;
            document.getElementById('shareProductName').textContent = shareName;
            document.getElementById('shareWhatsApp').href = `https://wa.me/?text=${encodeURIComponent('¡Mira este producto! ' + shareName + ' - $' + product.price.toFixed(2) + '\n' + shareUrl)}`;
            document.getElementById('shareFacebook').href = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}&quote=${encodeURIComponent(shareName)}`;
            document.getElementById('shareEmail').href = `mailto:?subject=${encodeURIComponent('Producto: ' + shareName)}&body=${encodeURIComponent('Te recomiendo este producto:\n\n' + shareName + '\nPrecio: $' + product.price.toFixed(2) + '\n' + shareUrl)}`;
            document.getElementById('shareCopyLink').onclick = () => {
                navigator.clipboard.writeText(shareUrl).then(() => {
                    showNotification(i18n.link_copied);
                    $('#shareModal').modal('hide');
                }).catch(() => {
                    showNotification(i18n.error_copying, true);
                });
            };
            $('#shareModal').modal('show');
        };
    }
    
    $('#productModal').modal('show');
}

function updateVariantSelection(product) {
    const selectedBtns = document.querySelectorAll('.variant-attr-btn.active');
    if (selectedBtns.length === 0) return;
    
    const selection = {};
    selectedBtns.forEach(btn => {
        selection[btn.getAttribute('data-attr')] = btn.getAttribute('data-value');
    });
    
    const attrCount = Object.keys(selection).length;
    const totalAttrs = new Set(Array.from(document.querySelectorAll('.variant-attr-btn')).map(b => b.getAttribute('data-attr'))).size;
    
    if (attrCount < totalAttrs) {
        document.getElementById('variant-result').innerHTML = '<span class="text-muted">Selecciona todas las opciones</span>';
        const modalBtn = document.getElementById('modal-add-to-cart-btn');
        modalBtn.disabled = true;
        modalBtn.textContent = i18n.select_options;
        return;
    }
    
    const match = window.currentVariants.find(v => {
        if (!v.combinacion) return false;
        const combo = typeof v.combinacion === 'string' ? JSON.parse(v.combinacion) : v.combinacion;
        return Object.keys(selection).every(k => combo[k] === selection[k]);
    });
    
    if (match) {
        selectVariantById(product, match.id);
    } else {
        document.getElementById('variant-result').innerHTML = '<span class="text-danger">Combinación no disponible</span>';
        const modalBtn = document.getElementById('modal-add-to-cart-btn');
        modalBtn.disabled = true;
        modalBtn.textContent = i18n.select_options;
    }
}

function selectVariantById(product, variantId) {
    const variant = window.currentVariants.find(v => v.id === variantId);
    if (!variant) return;
    
    window.selectedVariantId = variantId;
    
    const totalPrice = product.price + variant.precio_adicional;
    const totalVES = (totalPrice * tasaBCVActual).toFixed(2);
    document.getElementById('modal-product-price').innerHTML = `$${totalPrice.toFixed(2)}<br><small>≈ ${totalVES} Bs</small>`;
    
    const resultEl = document.getElementById('variant-result');
    if (resultEl) {
        const combo = typeof variant.combinacion === 'string' ? JSON.parse(variant.combinacion) : variant.combinacion;
        const comboStr = combo ? Object.values(combo).join(' / ') : '';
        resultEl.innerHTML = `<strong>${escapeHtml(variant.nombre_variante)}</strong> ${comboStr ? '- ' + escapeHtml(comboStr) : ''}<br><small class="${variant.stock > 0 ? 'text-success' : 'text-danger'}">${variant.stock > 0 ? 'Stock: ' + variant.stock : 'Sin stock'}</small>`;
    }
    
    if (variant.imagen_url) {
        document.getElementById('modal-product-image').src = variant.imagen_url;
    }
    
    const modalBtn = document.getElementById('modal-add-to-cart-btn');
    modalBtn.setAttribute('data-variant-id', variantId);
    modalBtn.disabled = variant.stock === 0;
    modalBtn.textContent = variant.stock === 0 ? i18n.out_of_stock : i18n.add_to_cart;
}

async function addToCart(productId, quantity, variantId) {
    if (CURRENT_USER && (CURRENT_USER.rol === 'admin' || CURRENT_USER.rol === 'administrador')) {
        showNotification(i18n.admin_no_purchase, true);
        return;
    }
    
    if (!CURRENT_USER || !CURRENT_USER.id) {
        showNotification(i18n.login_required, true);
        setTimeout(() => window.location.href = '/proyecto/interfaz_usuario/login.html', 1500);
        return;
    }
    
    try {
        const bodyData = { user_id: CURRENT_USER.id, product_id: productId, quantity: quantity };
        if (variantId && variantId > 0) bodyData.variant_id = variantId;
        
        const response = await fetch('/proyecto/carrito/anadir_carrito.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify(bodyData)
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.action === 'updated' ? i18n.cart_updated : i18n.product_added);
            await updateCartUI();
        } else {
            showNotification(data.message || i18n.error, true);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(i18n.connection_error, true);
    }
}

async function updateCartUI() {
    if (!CURRENT_USER || !CURRENT_USER.id) return;
    
    try {
        const response = await fetch(`/proyecto/carrito/tomar_carrito.php?user_id=${CURRENT_USER.id}`);
        const data = await response.json();
        
        if (data.success && data.items) {
            cartItems = data.items;
            const totalItems = cartItems.reduce((sum, item) => sum + (item.quantity || 1), 0);
            document.querySelectorAll('.cart-count').forEach(el => el.textContent = totalItems);
            renderCartModal();
        } else {
            cartItems = [];
            document.querySelectorAll('.cart-count').forEach(el => el.textContent = '0');
            renderCartModal();
        }
    } catch (error) {
        console.error('Error cargar carrito:', error);
        cartItems = [];
        renderCartModal();
    }
}

function renderCartModal() {
    const container = document.getElementById('cart-items');
    let total = 0;
    
    if (!cartItems || cartItems.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">🛒 ' + i18n.cart_empty + '</p>';
        document.getElementById('cart-total').textContent = 'Total: $0.00';
        return;
    }
    
    let html = '<div class="cart-items-list">';
    
    cartItems.forEach(item => {
        const productName = item.name || i18n.product;
        const price = item.precio_final !== undefined ? parseFloat(item.precio_final) : (parseFloat(item.price) || 0);
        const quantity = parseInt(item.quantity) || 1;
        const subtotal = price * quantity;
        total += subtotal;
        const productId = item.product_id || item.id;
        const variantId = item.variant_id || 0;
        const variantName = item.nombre_variante || '';
        const dataAttrs = `data-id="${productId}" data-variant-id="${variantId}"`;
        
        html += `
            <div class="cart-item d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom" ${dataAttrs}>
                <div class="cart-item-info" style="flex: 2;">
                    <strong>${escapeHtml(productName)}</strong>
                    ${variantName ? `<div class="small text-primary">${escapeHtml(variantName)}</div>` : ''}
                    <div class="text-muted small">$${price.toFixed(2)} c/u</div>
                </div>
                <div class="cart-item-quantity d-flex align-items-center gap-2" style="flex: 1; justify-content: center;">
                    <button class="btn btn-sm btn-outline-secondary cart-qty-minus" ${dataAttrs} data-current-qty="${quantity}">-</button>
                    <span class="mx-2 quantity-display" style="min-width: 30px; text-align: center;">${quantity}</span>
                    <button class="btn btn-sm btn-outline-secondary cart-qty-plus" ${dataAttrs} data-current-qty="${quantity}">+</button>
                </div>
                <div class="cart-item-subtotal text-end" style="flex: 1; text-align: right;">
                    <strong>$${subtotal.toFixed(2)}</strong>
                    <button class="btn btn-sm btn-danger ms-2 cart-remove" ${dataAttrs} style="margin-left: 10px;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
    document.getElementById('cart-total').textContent = `Total: $${total.toFixed(2)}`;
    
    document.querySelectorAll('.cart-qty-minus').forEach(btn => {
        btn.removeEventListener('click', handleQtyMinus);
        btn.addEventListener('click', handleQtyMinus);
    });
    
    document.querySelectorAll('.cart-qty-plus').forEach(btn => {
        btn.removeEventListener('click', handleQtyPlus);
        btn.addEventListener('click', handleQtyPlus);
    });
    
    document.querySelectorAll('.cart-remove').forEach(btn => {
        btn.removeEventListener('click', handleRemove);
        btn.addEventListener('click', handleRemove);
    });
}

function getBtnVariantId(btn) {
    return parseInt(btn.getAttribute('data-variant-id') || '0');
}

async function handleQtyMinus(e) {
    e.stopPropagation();
    const btn = e.currentTarget;
    const id = parseInt(btn.getAttribute('data-id'));
    const variantId = getBtnVariantId(btn);
    const currentQty = parseInt(btn.getAttribute('data-current-qty'));
    if (currentQty > 1) {
        await actualizarCantidadCarrito(id, -1, variantId);
    } else {
        await removeFromCart(id, variantId);
    }
}

async function handleQtyPlus(e) {
    e.stopPropagation();
    const btn = e.currentTarget;
    const id = parseInt(btn.getAttribute('data-id'));
    const variantId = getBtnVariantId(btn);
    await actualizarCantidadCarrito(id, 1, variantId);
}

async function handleRemove(e) {
    e.stopPropagation();
    const btn = e.currentTarget;
    const id = parseInt(btn.getAttribute('data-id'));
    const variantId = getBtnVariantId(btn);
    await removeFromCart(id, variantId);
}

async function actualizarCantidadCarrito(productId, quantity, variantId) {
    if (!CURRENT_USER || !CURRENT_USER.id) return;
    
    try {
        const bodyData = { user_id: CURRENT_USER.id, product_id: productId, quantity: quantity };
        if (variantId && variantId > 0) bodyData.variant_id = variantId;
        
        const response = await fetch('/proyecto/carrito/anadir_carrito.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify(bodyData)
        });
        const data = await response.json();
        
        if (data.success) {
            await updateCartUI();
        } else {
            showNotification(data.message || i18n.cart_update_error, true);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(i18n.connection_error, true);
    }
}

async function removeFromCart(productId, variantId) {
    if (!CURRENT_USER || !CURRENT_USER.id) return;
    
    try {
        const bodyData = { user_id: CURRENT_USER.id, product_id: productId };
        if (variantId && variantId > 0) bodyData.variant_id = variantId;
        
        const response = await fetch('/proyecto/carrito/remover_carrito.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify(bodyData)
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification(i18n.product_removed);
            await updateCartUI();
        } else {
            showNotification(data.message || i18n.cart_remove_error, true);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(i18n.connection_error, true);
    }
}

async function toggleFavorito(productoId, event) {
    if (!CURRENT_USER || !CURRENT_USER.id) {
        showNotification(i18n.must_login, true);
        setTimeout(() => window.location.href = '/proyecto/interfaz_usuario/login.html', 1500);
        return;
    }

    try {
        const response = await fetch('/proyecto/producto/agregar_favorito.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: CURRENT_USER.id, producto_id: productoId })
        });
        const data = await response.json();

        if (data.success) {
            if (data.favorito) {
                favoritosSet.add(productoId);
                showNotification(i18n.added_to_favorites);
                // Animate the heart icon
                if (event && event.currentTarget) {
                    const btn = event.currentTarget;
                    btn.classList.add('heart-beat');
                    setTimeout(() => btn.classList.remove('heart-beat'), 500);
                } else {
                    document.querySelectorAll(`.product-favorite-btn[data-id="${productoId}"]`).forEach(el => {
                        el.classList.add('heart-beat');
                        setTimeout(() => el.classList.remove('heart-beat'), 500);
                    });
                }
            } else {
                favoritosSet.delete(productoId);
                showNotification(i18n.removed_from_favorites);
            }
            actualizarIconosFavoritos();
            actualizarBadgeFavoritos();
            mostrarSeccionFavoritos();
        } else {
            showNotification(data.message || i18n.favorites_error, true);
        }
    } catch (error) {
        console.error('Error en toggleFavorito:', error);
        showNotification(i18n.connection_error, true);
    }
}

function actualizarIconosFavoritos() {
    document.querySelectorAll('.product-favorite-btn').forEach(btn => {
        const id = parseInt(btn.getAttribute('data-id'));
        const icon = btn.querySelector('i');
        if (favoritosSet.has(id)) {
            icon.className = 'fas fa-heart';
            btn.classList.add('favorito-activo');
        } else {
            icon.className = 'far fa-heart';
            btn.classList.remove('favorito-activo');
        }
    });
}

function actualizarBadgeFavoritos() {
    const count = favoritosSet.size;
    const badge = document.getElementById('favoritesCountBadge');
    const floatingBtn = document.getElementById('floatingFavoritesBtn');
    const floatingCount = document.getElementById('floatingFavoritesCount');

    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline';
        } else {
            badge.style.display = 'none';
        }
    }

    if (floatingBtn) {
        if (count > 0) {
            floatingBtn.style.display = 'flex';
            if (floatingCount) floatingCount.textContent = count;
        } else {
            floatingBtn.style.display = 'none';
        }
    }
}

function mostrarSeccionFavoritos() {
    const section = document.getElementById('favoritesSection');
    const container = document.getElementById('favoritesMiniGrid');
    if (!section || !container) return;

    const favoritedProducts = allProducts.filter(p => favoritosSet.has(p.id));
    if (favoritedProducts.length === 0) {
        section.style.display = 'none';
        return;
    }

    const shuffled = [...favoritedProducts].sort(() => Math.random() - 0.5);
    const selected = shuffled.slice(0, 4);

    let html = '';
    selected.forEach(product => {
        const precioVES = (product.price * tasaBCVActual).toFixed(2);
        html += `
            <div class="product-card" data-id="${product.id}" style="cursor:pointer;">
                <div class="product-img-container" style="height:120px;" data-id="${product.id}">
                    <img src="${product.image}" alt="${escapeHtml(product.name)}" data-id="${product.id}" style="max-height:100px;" onerror="this.src='https://via.placeholder.com/300x300?text=Sin+Imagen'">
                </div>
                <div class="category" style="font-size:0.65rem;">${escapeHtml(product.category)}</div>
                <h5 style="font-size:0.8rem;">${escapeHtml(product.name)}</h5>
                <div class="price" style="font-size:0.9rem;">$${product.price.toFixed(2)}<small class="ves-price">(≈ ${precioVES} Bs)</small></div>
            </div>`;
    });

    container.innerHTML = html;
    section.style.display = 'block';

    container.querySelectorAll('.product-card').forEach(el => {
        el.addEventListener('click', (e) => {
            const id = parseInt(el.getAttribute('data-id'));
            const product = allProducts.find(p => p.id === id);
            if (product) openProductModal(product);
        });
    });
}

async function cargarFavoritos() {
    if (!CURRENT_USER || !CURRENT_USER.id) return;
    try {
        const response = await fetch(`/proyecto/producto/obtener_favoritos.php?user_id=${CURRENT_USER.id}`);
        const data = await response.json();
        if (data.success && data.ids) {
            favoritosSet = new Set(data.ids);
        }
    } catch (error) {
        console.error('Error al cargar favoritos:', error);
    }
}

async function mostrarFavoritos() {
    $('#favoritesModal').modal('show');
    document.getElementById('favoritesContent').innerHTML = '<p class="text-center">' + i18n.loading + '</p>';
    try {
        const response = await fetch(`/proyecto/producto/obtener_favoritos.php?user_id=${CURRENT_USER?.id || 0}`);
        const data = await response.json();
        if (data.success && data.favoritos && data.favoritos.length > 0) {
            let html = '<div class="favorites-grid">';
            data.favoritos.forEach(f => {
                const precioVES = (parseFloat(f.price) * tasaBCVActual).toFixed(2);
                const inactivo = f.active === 0;
                const isOutOfStock = f.stock === 0;
                let stockStatus = '';
                if (inactivo || isOutOfStock) {
                    stockStatus = inactivo ? i18n.not_available : i18n.out_of_stock;
                } else {
                    stockStatus = i18n.stock_units.replace('%s', f.stock);
                }
                const stockColor = (inactivo || isOutOfStock) ? '#dc3545' : '#28a745';
                html += `
                    <div class="product-card" data-id="${f.id}" style="margin-bottom:0; cursor:pointer;">
                        <div class="product-img-container" style="height:140px; position:relative;" data-id="${f.id}">
                            <img src="${f.image || 'https://via.placeholder.com/300x300?text=Sin+Imagen'}" alt="${escapeHtml(f.name)}" data-id="${f.id}" style="max-height:120px;" onerror="this.src='https://via.placeholder.com/300x300?text=Sin+Imagen'">
                            <div class="product-favorite-btn favorito-activo" data-id="${f.producto_id}" style="position:absolute; top:5px; left:5px; cursor:pointer; font-size:1.3rem; z-index:2;">
                                <i class="fas fa-heart"></i>
                            </div>
                            <span style="position:absolute; bottom:5px; right:5px; background:${stockColor}; color:white; padding:2px 8px; border-radius:10px; font-size:0.6rem; font-weight:600;">${stockStatus}</span>
                        </div>
                        <div class="category" style="font-size:0.7rem;">${escapeHtml(f.category || '')}</div>
                        <h5 data-id="${f.id}" style="font-size:0.9rem;">${escapeHtml(f.name)}</h5>
                        <div class="price" style="font-size:1rem;">$${parseFloat(f.price).toFixed(2)}<small class="ves-price">(≈ ${precioVES} Bs)</small></div>
                        <div style="display:flex; gap:6px; margin-top:8px;">
                            <button class="btn btn-sm btn-outline-primary ver-producto-btn" data-id="${f.id}" style="flex:1; font-size:0.7rem; padding:4px 8px; border-radius:20px;">
                                <i class="fas fa-eye"></i> ${i18n.view_product}
                            </button>
                            <button class="add-to-cart-btn ${(isOutOfStock || inactivo) ? 'stock-disabled' : ''}" data-id="${f.id}" ${(isOutOfStock || inactivo) ? 'disabled' : ''} style="flex:1; font-size:0.75rem; padding:4px 12px;">
                                <i class="fas fa-shopping-cart"></i> ${inactivo ? i18n.not_available : (isOutOfStock ? i18n.out_of_stock : i18n.add_short)}
                            </button>
                        </div>
                    </div>`;
            });
            html += '</div>';
            document.getElementById('favoritesContent').innerHTML = html;
            document.querySelectorAll('#favoritesContent .product-favorite-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const id = parseInt(btn.getAttribute('data-id'));
                    await toggleFavorito(id, e);
                    await mostrarFavoritos();
                });
            });
            document.querySelectorAll('#favoritesContent .add-to-cart-btn:not([disabled])').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = parseInt(btn.getAttribute('data-id'));
                    const product = allProducts.find(p => p.id === id);
                    if (product && product.has_variants) {
                        $('#favoritesModal').modal('hide');
                        setTimeout(() => openProductModal(product), 300);
                    } else {
                        addToCart(id, 1, 0);
                    }
                });
            });
            document.querySelectorAll('#favoritesContent .product-card').forEach(el => {
                el.addEventListener('click', (e) => {
                    if (e.target.closest('.product-favorite-btn') || e.target.closest('.add-to-cart-btn')) return;
                    const id = parseInt(el.getAttribute('data-id'));
                    const product = allProducts.find(p => p.id === id);
                    if (product) openProductModal(product);
                });
            });
            document.querySelectorAll('#favoritesContent .ver-producto-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const id = parseInt(btn.getAttribute('data-id'));
                    const product = allProducts.find(p => p.id === id);
                    if (product) openProductModal(product);
                });
            });
        } else {
            document.getElementById('favoritesContent').innerHTML = `
                <div class="text-center" style="padding:50px 20px;">
                    <i class="far fa-heart" style="font-size:4rem; opacity:0.2; margin-bottom:20px; display:block;"></i>
                    <p style="font-size:1.1rem; opacity:0.7; margin-bottom:8px;">${i18n.no_favorites}</p>
                    <p style="font-size:0.9rem; opacity:0.5; margin-bottom:20px;">${i18n.no_favorites_explore}</p>
                    <button class="btn btn-primary" onclick="$('#favoritesModal').modal('hide')">
                        <i class="fas fa-store"></i> ${i18n.explore_products}
                    </button>
                </div>`;
        }
    } catch (error) {
        document.getElementById('favoritesContent').innerHTML = '<p class="text-center" style="color:#ff4757; padding:40px;">' + i18n.error_loading_favorites + '</p>';
    }
}

function updateActiveFilters() {
    const container = document.getElementById('active-filters');
    if (!container) return;
    container.innerHTML = '<small>' + i18n.filter_label + '</small>';
    let has = false;
    
    const addBadge = (text, filterKey) => {
        has = true;
        container.innerHTML += `<span class="filter-badge">${text} <span class="close" data-filter="${filterKey}">&times;</span></span>`;
    };
    
    if (currentFilters.searchTerm) addBadge(i18n.filter_search_value.replace('%s', currentFilters.searchTerm), 'search');
    if (currentFilters.minPrice) addBadge(i18n.filter_min_price_value.replace('%s', currentFilters.minPrice), 'minPrice');
    if (currentFilters.maxPrice) addBadge(i18n.filter_max_price_value.replace('%s', currentFilters.maxPrice), 'maxPrice');
    if (currentFilters.minRating > 0) addBadge(i18n.filter_rating_value.replace('%s', currentFilters.minRating), 'minRating');
    if (currentFilters.category) addBadge(i18n.filter_category_value.replace('%s', currentFilters.category), 'category');
    
    container.style.display = has ? 'flex' : 'none';
    
    document.querySelectorAll('#active-filters .close').forEach(el => {
        el.addEventListener('click', (e) => {
            const filter = e.target.getAttribute('data-filter');
            if (filter === 'search') { currentFilters.searchTerm = ''; searchInput.value = ''; }
            if (filter === 'minPrice') { currentFilters.minPrice = null; document.getElementById('price-min').value = ''; }
            if (filter === 'maxPrice') { currentFilters.maxPrice = null; document.getElementById('price-max').value = ''; }
            if (filter === 'minRating') { currentFilters.minRating = 0; document.getElementById('rating-min').value = '0'; }
            if (filter === 'category') { currentFilters.category = ''; document.getElementById('category-filter').value = ''; }
            applyFilters();
        });
    });
}

document.getElementById('submitPasswordChange')?.addEventListener('click', async () => {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmNewPassword').value;
    
    if (!currentPassword || !newPassword || !confirmPassword) {
        showNotification(i18n.fill_all_fields, true);
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showNotification(i18n.password_mismatch, true);
        return;
    }
    
    if (newPassword.length < 6) {
        showNotification(i18n.password_too_short, true);
        return;
    }
    
    try {
        const response = await fetch('/proyecto/usuarios/cambiar_contraseña.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword,
                confirm_new_password: confirmPassword
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message);
            $('#changePasswordModal').modal('hide');
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmNewPassword').value = '';
        } else {
            showNotification(data.message || i18n.error_changing_password, true);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(i18n.connection_error, true);
    }
});

// Recuperar contraseña sin cerrar sesion
let recoveryEmail = '';
let recoveryPin = '';

document.getElementById('forgotPasswordLink')?.addEventListener('click', function(e) {
    e.preventDefault();
    recoveryEmail = CURRENT_USER?.correo || '';
    recoveryPin = '';
    document.getElementById('passChangeSection').style.display = 'none';
    document.getElementById('passRecoverySection').style.display = 'block';
    document.getElementById('submitPasswordChange').style.display = 'none';
    document.getElementById('recoveryEmail').value = recoveryEmail;
    document.getElementById('passModalTitle').textContent = i18n.recover_password;
});

function showRecoveryMessage(msg, isError = false) {
    const el = document.getElementById('recoveryMsg');
    if (!el) return;
    el.textContent = msg;
    el.style.display = 'block';
    el.className = 'alert-message mt-2' + (isError ? ' alert-danger' : ' alert-success');
}

document.getElementById('sendRecoveryPinBtn')?.addEventListener('click', async function() {
    if (!recoveryEmail) { showRecoveryMessage(i18n.email_not_available, true); return; }
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ' + i18n.sending;
    try {
        const res = await fetch('/proyecto/usuarios/solicitar_token.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: recoveryEmail })
        });
        const data = await res.json();
        if (data.success) {
            showRecoveryMessage(i18n.pin_sent_to_email);
            document.getElementById('pinSection').style.display = 'block';
        } else {
            showRecoveryMessage(data.message || i18n.error_sending_code, true);
        }
    } catch (err) {
        showRecoveryMessage(i18n.connection_error, true);
    } finally {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-paper-plane"></i> ' + i18n.send_pin_code;
    }
});

document.getElementById('verifyRecoveryPinBtn')?.addEventListener('click', async function() {
    const pin = document.getElementById('recoveryPin').value.trim();
    if (!pin || pin.length < 6) { showRecoveryMessage(i18n.enter_full_pin, true); return; }
    recoveryPin = pin;
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ' + i18n.verifying;
    try {
        const res = await fetch('/proyecto/usuarios/verificar_pin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: recoveryEmail, pin: recoveryPin })
        });
        const data = await res.json();
        if (data.success) {
            showRecoveryMessage(i18n.pin_verified_set_password);
            document.getElementById('newPassSection').style.display = 'block';
        } else {
            showRecoveryMessage(data.message || i18n.invalid_or_expired_pin, true);
        }
    } catch (err) {
        showRecoveryMessage(i18n.connection_error, true);
    } finally {
        this.disabled = false;
        this.innerHTML = i18n.verify_pin_btn;
    }
});

document.getElementById('setNewPasswordBtn')?.addEventListener('click', async function() {
    const newPass = document.getElementById('recoveryNewPassword').value.trim();
    if (!newPass || newPass.length < 6) { showRecoveryMessage(i18n.password_too_short, true); return; }
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ' + i18n.loading;
    try {
        const res = await fetch('/proyecto/usuarios/recuperacion_contraseña.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: recoveryEmail, pin: recoveryPin, newPassword: newPass })
        });
        const data = await res.json();
        if (data.success) {
            showRecoveryMessage(i18n.password_reset_login, false);
            document.getElementById('newPassSection').style.display = 'none';
            setTimeout(() => cerrarSesion(), 2500);
        } else {
            showRecoveryMessage(data.message || i18n.error_resetting_password, true);
        }
    } catch (err) {
        showRecoveryMessage(i18n.connection_error, true);
    } finally {
        this.disabled = false;
        this.innerHTML = i18n.reset_password_btn;
    }
});

$('#changePasswordModal').on('hidden.bs.modal', function () {
    document.getElementById('passChangeSection').style.display = 'block';
    document.getElementById('passRecoverySection').style.display = 'none';
    document.getElementById('submitPasswordChange').style.display = '';
    document.getElementById('passModalTitle').textContent = i18n.change_password;
    const msgEl = document.getElementById('recoveryMsg');
    if (msgEl) msgEl.style.display = 'none';
    document.getElementById('pinSection').style.display = 'none';
    document.getElementById('newPassSection').style.display = 'none';
    document.getElementById('recoveryPin').value = '';
    document.getElementById('recoveryNewPassword').value = '';
    recoveryEmail = '';
    recoveryPin = '';
});

document.querySelectorAll('.contact-option').forEach(btn => {
    btn.addEventListener('click', function() {
        const channel = this.getAttribute('data-channel');
        const email = 'Picca.ventas@gmail.com';
        const subject = encodeURIComponent(i18n.contact_us + ' - PIC');
        const body = encodeURIComponent(i18n.contact_us + ' - ' + i18n.products + '\n\n');

        switch (channel) {
            case 'gmail':
                window.open(`https://mail.google.com/mail/?view=cm&fs=1&to=${email}&su=${subject}&body=${body}`, '_blank');
                break;
            case 'hotmail':
                window.open(`https://outlook.live.com/mail/0/compose?to=${email}&subject=${subject}&body=${body}`, '_blank');
                break;
            case 'telegram':
                window.open('https://t.me/piccavzlabot', '_blank');
                break;
            case 'email':
                $('#contactOptionsModal').modal('hide');
                setTimeout(() => $('#contactModal').modal('show'), 300);
                break;
        }
        $('#contactOptionsModal').modal('hide');
    });
});

document.getElementById('submitContact')?.addEventListener('click', async () => {
    const nombre = document.getElementById('contactName').value;
    const email = document.getElementById('contactEmail').value;
    const mensaje = document.getElementById('contactMessage').value;
    
    if (!nombre || !email || !mensaje) {
        showNotification(i18n.fill_all_fields, true);
        return;
    }
    
    if (!email.includes('@') || !email.includes('.')) {
        showNotification(i18n.invalid_email, true);
        return;
    }
    
    try {
        const response = await fetch('/proyecto/usuarios/enviar_mensaje.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombre: nombre,
                email: email,
                asunto: i18n.contact_us,
                mensaje: mensaje
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message);
            $('#contactModal').modal('hide');
            document.getElementById('contactName').value = '';
            document.getElementById('contactEmail').value = '';
            document.getElementById('contactMessage').value = '';
        } else {
            showNotification(data.message || i18n.error_sending_message, true);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(i18n.connection_error, true);
    }
});

function setupEventListeners() {
    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            currentFilters.searchTerm = searchInput.value;
            applyFilters();
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                currentFilters.searchTerm = searchInput.value;
                applyFilters();
            }
        });
    }
    
    if (filterToggleBtn) {
        filterToggleBtn.addEventListener('click', () => {
            filtersContainer.style.display = filtersContainer.style.display === 'none' ? 'flex' : 'none';
        });
    }
    
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', () => {
            currentFilters.minPrice = document.getElementById('price-min').value;
            currentFilters.maxPrice = document.getElementById('price-max').value;
            currentFilters.minRating = parseInt(document.getElementById('rating-min').value);
            currentFilters.category = document.getElementById('category-filter').value;
            currentFilters.sortBy = document.getElementById('sort-by').value;
            applyFilters();
            filtersContainer.style.display = 'none';
        });
    }
    
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', () => {
            document.getElementById('price-min').value = '';
            document.getElementById('price-max').value = '';
            document.getElementById('rating-min').value = '0';
            document.getElementById('category-filter').value = '';
            document.getElementById('sort-by').value = 'newest';
            searchInput.value = '';
            currentFilters = { searchTerm: '', minPrice: null, maxPrice: null, minRating: 0, category: '', sortBy: 'newest' };
            applyFilters();
        });
    }
    
    if (refreshProductsBtn) {
        refreshProductsBtn.addEventListener('click', () => {
            showNotification(i18n.reloading_products);
            loadProductsFromDB();
        });
    }
    
    if (refreshTasaBtn) {
        refreshTasaBtn.addEventListener('click', () => {
            showNotification(i18n.updating_rate);
            cargarTasaBCV();
        });
    }
    
    const checkoutBtn = document.getElementById('checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
            if (CURRENT_USER && (CURRENT_USER.rol === 'admin' || CURRENT_USER.rol === 'administrador')) {
                showNotification(i18n.admin_no_purchase, true);
                return;
            }
            
            if (cartItems.length === 0) {
                showNotification(i18n.cart_empty, true);
                return;
            }
            
            if (!CURRENT_USER || !CURRENT_USER.id) {
                showNotification(i18n.login_required, true);
                setTimeout(() => window.location.href = '/proyecto/interfaz_usuario/login.html', 1500);
                return;
            }
            
            window.location.href = 'pasarela_de_pago.php';
        });
    }
    
    document.getElementById('qty-minus')?.addEventListener('click', () => {
        const input = document.getElementById('product-quantity');
        let val = parseInt(input.value);
        if (val > 1) input.value = val - 1;
    });
    
    document.getElementById('qty-plus')?.addEventListener('click', () => {
        const input = document.getElementById('product-quantity');
        let val = parseInt(input.value);
        input.value = val + 1;
    });
    
    document.getElementById('modal-add-to-cart-btn')?.addEventListener('click', () => {
        const btn = document.getElementById('modal-add-to-cart-btn');
        const id = parseInt(btn.getAttribute('data-id'));
        const variantId = parseInt(btn.getAttribute('data-variant-id') || '0');
        const quantity = parseInt(document.getElementById('product-quantity').value);
        if (id && quantity > 0) {
            addToCart(id, quantity, variantId);
            $('#productModal').modal('hide');
        }
    });
    
    document.getElementById('viewFavoritesBtn')?.addEventListener('click', async () => {
        await mostrarFavoritos();
    });

    document.getElementById('floatingFavoritesBtn')?.addEventListener('click', async () => {
        await mostrarFavoritos();
    });

    document.getElementById('viewHistoryBtn')?.addEventListener('click', async () => {
        $('#historyModal').modal('show');
        document.getElementById('historyContent').innerHTML = '<p class="text-center">' + i18n.loading + '</p>';
        if (!CURRENT_USER?.id) {
            document.getElementById('historyContent').innerHTML = '<p class="text-center" style="color:var(--text-color); opacity:0.7; padding:40px;">' + i18n.login_to_view_history + '</p>';
            return;
        }
        try {
            const response = await fetch(`/proyecto/producto/obtener_historial_pedidos.php?user_id=${CURRENT_USER.id}`);
            const data = await response.json();
            if (data.success && data.historial && data.historial.length > 0) {
                let html = '';
                data.historial.forEach(p => {
                    const fecha = new Date(p.created_at).toLocaleString('es-ES');
                    const displayStatus = p.display_status || p.status || 'pendiente';
                    
                    const estadoBadge = displayStatus === 'Pagado' ? 'background:#2ed573' :
                                       displayStatus === 'Pendiente' ? 'background:#ffa502' :
                                       displayStatus === 'cancelado' ? 'background:#ff4757' : 'background:#6c757d';
                    
                    let metodoBadge = 'background:#6c757d';
                    let metodoLabel = p.payment_method || 'N/A';
                    
                    if (p.es_mixto) {
                        metodoBadge = 'background:#f39c12';
                        metodoLabel = 'Mixto (Efvo + Transf)';
                    } else if (p.payment_method === 'efectivo') {
                        metodoBadge = 'background:#2ed573';
                        metodoLabel = 'Efectivo';
                    } else if (p.payment_method === 'transferencia') {
                        metodoBadge = 'background:#3498db';
                        metodoLabel = 'Transferencia';
                    } else if (p.payment_method === 'pago_movil') {
                        metodoBadge = 'background:#9b59b6';
                        metodoLabel = 'Pago Móvil';
                    }
                    
                    let itemsHtml = '';
                    if (p.items && p.items.length > 0) {
                        itemsHtml = '<div style="margin-top:12px;">';
                        p.items.forEach(it => {
                            itemsHtml += `<div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px dashed var(--light-color);">
                                <span>${escapeHtml(it.product_name)} <small style="opacity:0.7;">x${it.quantity}</small></span>
                                <span style="font-weight:500;">$${parseFloat(it.item_subtotal || 0).toFixed(2)}</span>
                            </div>`;
                        });
                        itemsHtml += '</div>';
                    }
                    
                    let detallesPagoHtml = '';
                    if (p.es_mixto) {
                        detallesPagoHtml = `<div style="margin-top:8px; padding:8px 12px; background:rgba(243,156,18,0.1); border-radius:8px; font-size:0.85rem;">
                            <div style="display:flex; justify-content:space-between;">
                                <span>🔄 Transferencia:</span>
                                <span><strong>$${parseFloat(p.monto_transferencia || 0).toFixed(2)}</strong></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-top:4px;">
                                <span>💵 Efectivo:</span>
                                <span><strong>$${parseFloat(p.monto_efectivo || 0).toFixed(2)}</strong></span>
                            </div>
                        </div>`;
                    }
                    
                    html += `<div style="background:var(--card-bg); border:1px solid var(--light-color); border-radius:12px; padding:16px; margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px; margin-bottom:10px;">
                            <div>
                                <strong style="font-size:1.1rem; color:var(--text-color);">${i18n.order_id} #${p.order_number || p.id}</strong><br>
                                <small style="opacity:0.7;">${fecha}</small>
                                ${p.invoice_number ? `<br><small style="opacity:0.5;">${i18n.invoice_number} ${escapeHtml(p.invoice_number)}</small>` : ''}
                            </div>
                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                <span style="${estadoBadge}; color:white; padding:4px 10px; border-radius:20px; font-size:0.75rem;">${escapeHtml(displayStatus)}</span>
                                <span style="${metodoBadge}; color:white; padding:4px 10px; border-radius:20px; font-size:0.75rem;">${metodoLabel}</span>
                            </div>
                        </div>
                        ${detallesPagoHtml}
                        ${itemsHtml}
                        <div style="text-align:right; margin-top:12px; padding-top:12px; border-top:2px solid #3C91ED;">
                            <div style="color:var(--text-color); opacity:0.8;">${i18n.subtotal_label} $${parseFloat(p.subtotal || 0).toFixed(2)}</div>
                            <div style="color:var(--text-color); opacity:0.8;">${i18n.iva_label} $${parseFloat(p.iva || 0).toFixed(2)}</div>
                            <div style="font-size:1.2rem; font-weight:bold; color:var(--accent-color); margin-top:5px;">${i18n.total_label.toUpperCase()}: $${parseFloat(p.total || 0).toFixed(2)}</div>
                        </div>
                    </div>`;
                });
                document.getElementById('historyContent').innerHTML = html;
            } else {
                document.getElementById('historyContent').innerHTML = '<p class="text-center" style="color:var(--text-color); opacity:0.7; padding:40px;">' + i18n.no_orders + '</p>';
            }
        } catch(error) {
            document.getElementById('historyContent').innerHTML = '<p class="text-center" style="color:#ff4757; padding:40px;">' + i18n.error_loading_history + '</p>';
        }
    });
    
    document.querySelectorAll('[data-target="#changeProfilePhotoModal"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const fileInput = document.getElementById('profilePhotoInput');
            const previewImg = document.getElementById('previewPhoto');
            if (fileInput) fileInput.value = '';
            if (previewImg) {
                const currentProfileImg = document.getElementById('profileImage');
                if (currentProfileImg && currentProfileImg.src && currentProfileImg.style.display !== 'none') {
                    previewImg.src = currentProfileImg.src;
                    previewImg.style.display = 'block';
                } else {
                    previewImg.src = '';
                    previewImg.style.display = 'none';
                }
            }
        });
    });
    
    const profilePhotoInput = document.getElementById('profilePhotoInput');
    if (profilePhotoInput) {
        profilePhotoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewImg = document.getElementById('previewPhoto');
            if (file && previewImg) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    previewImg.src = event.target.result;
                    previewImg.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    const uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
    if (uploadPhotoBtn) {
        uploadPhotoBtn.addEventListener('click', function() {
            const fileInput = document.getElementById('profilePhotoInput');
            const file = fileInput.files[0];
            if (file) {
                subirFotoPerfil(file);
            } else {
                showNotification(i18n.select_photo_first, true);
            }
        });
    }
}

// ============================================================
// 2FA CLIENTE
// ============================================================
let currentCliente2faSecret = '';
let currentCliente2faBackupCodes = [];

async function cargarEstado2faCliente() {
    try {
        const res = await fetch('/proyecto/2fa/configurar_cliente.php?action=estado', { credentials: 'include' });
        const data = await res.json();
        const label = document.getElementById('cliente2faLabel');
        const desc = document.getElementById('cliente2faDesc');
        const badge = document.getElementById('cliente2faBadge');
        const disabled = document.getElementById('cliente2faDisabled');
        const active = document.getElementById('cliente2faActive');
        const setup = document.getElementById('cliente2faSetup');

        if (data.migracion_pendiente) {
            label.textContent = i18n.not_available;
            desc.textContent = i18n['2fa_migration_needed'];
            badge.className = 'badge badge-pending';
            badge.innerHTML = '<i class="fas fa-clock"></i> ' + i18n.pending_status.replace(':', '');
            disabled.style.display = 'none';
            active.style.display = 'none';
            setup.style.display = 'none';
        } else if (data.enabled) {
            label.textContent = i18n['2fa_activated'];
            desc.textContent = i18n.account_protected;
            badge.className = 'badge badge-success';
            badge.innerHTML = '<i class="fas fa-check-circle"></i> ' + i18n.active;
            disabled.style.display = 'none';
            active.style.display = 'block';
            setup.style.display = 'none';
        } else {
            label.textContent = i18n['2fa_deactivated'];
            desc.textContent = i18n.activate_2fa_security;
            badge.className = 'badge badge-warning';
            badge.innerHTML = '<i class="fas fa-clock"></i> ' + i18n.inactive;
            disabled.style.display = 'block';
            active.style.display = 'none';
            setup.style.display = 'none';
        }
    } catch(e) {
        console.error('Error cargando 2FA cliente:', e);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    $('#setup2faModal').on('show.bs.modal', function() {
        cargarEstado2faCliente();
    });
});

document.getElementById('btnClienteConfigurar2FA')?.addEventListener('click', async function() {
    try {
        const res = await fetch('/proyecto/2fa/configurar_cliente.php?action=generar_secreto', {
            method: 'POST',
            credentials: 'include'
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        currentCliente2faSecret = data.secret;
        currentCliente2faBackupCodes = data.backup_codes || [];

        document.getElementById('cliente2faDisabled').style.display = 'none';
        document.getElementById('cliente2faActive').style.display = 'none';
        document.getElementById('cliente2faSetup').style.display = 'block';
        document.getElementById('cliente2faSecretDisplay').value = data.secret;

        if (data.qr_content) {
            const canvas = document.getElementById('cliente2faQRCanvas');
            try {
                new QRious({
                    element: canvas,
                    value: data.qr_content,
                    size: 200,
                    level: 'M'
                });
            } catch(e) {
                console.error('Error generando QR:', e);
            }
        }

        const codesContainer = document.getElementById('cliente2faBackupCodes');
        codesContainer.innerHTML = data.backup_codes.map(c =>
            `<code style="background:#0f1219;padding:4px 8px;border-radius:4px;color:var(--accent-color);font-size:0.7rem">${c}</code>`
        ).join('');
        document.getElementById('cliente2faBackupContainer').style.display = 'block';
        document.getElementById('btnClienteVerificar2FA').style.display = 'block';
    } catch(e) {
        showNotification(i18n['2fa_config_error'] + ': ' + e.message, true);
    }
});

document.getElementById('btnClienteVerificar2FA')?.addEventListener('click', async function() {
    const code = Array.from(['cliente2faCode1','cliente2faCode2','cliente2faCode3','cliente2faCode4','cliente2faCode5','cliente2faCode6'])
        .map(id => document.getElementById(id).value).join('');
    if (code.length !== 6) {
        showNotification(i18n.enter_6_digit_code, true);
        return;
    }

    try {
        const formData = new FormData();
        formData.append('code', code);

        const res = await fetch('/proyecto/2fa/configurar_cliente.php?action=verificar', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        const data = await res.json();
        if (data.success) {
            showNotification(i18n['2fa_activated_success']);
            await cargarEstado2faCliente();
        } else {
            showNotification(data.message || i18n.invalid_code, true);
        }
    } catch(e) {
        showNotification(i18n['2fa_verify_error'], true);
    }
});

document.getElementById('btnClienteDesactivar2FA')?.addEventListener('click', async function() {
    const password = prompt(i18n.enter_password_to_disable_2fa);
    if (!password) return;

    try {
        const formData = new FormData();
        formData.append('password', password);

        const res = await fetch('/proyecto/2fa/configurar_cliente.php?action=desactivar', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        const data = await res.json();
        if (data.success) {
            showNotification(i18n['2fa_deactivated']);
            await cargarEstado2faCliente();
        } else {
            showNotification(data.message || i18n.incorrect_password, true);
        }
    } catch(e) {
        showNotification(i18n['2fa_deactivate_error'], true);
    }
});

// Auto-pin focus for client 2FA
['cliente2faCode1','cliente2faCode2','cliente2faCode3','cliente2faCode4','cliente2faCode5','cliente2faCode6'].forEach((id, idx, arr) => {
    const input = document.getElementById(id);
    if (!input) return;
    input.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value && idx < arr.length - 1) {
            document.getElementById(arr[idx + 1]).focus();
        }
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value && idx > 0) {
            document.getElementById(arr[idx - 1]).focus();
        }
    });
});

async function init() {
    initDarkMode();
    await obtenerCsrfToken();
    await obtenerUsuarioActual();
    await loadProductsFromDB();
    await cargarFavoritos();
    await updateCartUI();
    actualizarBadgeFavoritos();
    mostrarSeccionFavoritos();
    setupEventListeners();
    cargarTasaBCV();
    iniciarVerificacionSesionPeriodica();
}

init();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/proyecto/sw.js')
            .then(registration => {
                console.log('SW registrado:', registration.scope);
            })
            .catch(error => {
                console.log('Error al registrar SW:', error);
            });
    });
}

window.addEventListener('online', () => {
    showNotification(i18n.connection_restored);
});

window.addEventListener('offline', () => {
    showNotification(i18n.connection_lost, true);
});

// ============================================================
// NOTIFICACIONES DE PEDIDOS
// ============================================================
async function checkNotificaciones() {
    if (!CURRENT_USER?.id) return;
    try {
        const res = await fetch('/proyecto/usuarios/notificaciones.php?action=listar', { credentials: 'include' });
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        if (data.no_leidas > 0 && 'Notification' in window && Notification.permission === 'granted') {
            data.notificaciones.forEach(n => {
                if (!n.leida) {
                    try {
                        new Notification(n.titulo, {
                            body: n.mensaje,
                            icon: '/proyecto/img/pic.png'
                        });
                        fetch(`/proyecto/usuarios/notificaciones.php?action=marcar_leida&id=${n.id}`, { credentials: 'include' });
                    } catch(e) {}
                }
            });
        }
    } catch(e) {}
}

if ('Notification' in window && Notification.permission !== 'denied') {
    Notification.requestPermission();
}

setInterval(() => { if (CURRENT_USER?.id) checkNotificaciones(); }, 30000);
if (CURRENT_USER?.id) checkNotificaciones();

// ============================================================
// RESEÑAS (PRODUCT REVIEWS)
// ============================================================
function renderStarsHtml(count) {
    let s = '';
    for (let i = 1; i <= 5; i++) {
        s += i <= count ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
    }
    return s;
}

async function loadResenas(productId) {
    const section = document.getElementById('resenas-section');
    const loading = document.getElementById('resenas-loading');
    const content = document.getElementById('resenas-content');
    const formContainer = document.getElementById('review-form-container');
    if (!section) return;

    section.style.display = 'block';
    loading.style.display = 'block';
    content.innerHTML = '';
    formContainer.innerHTML = '';

    try {
        const res = await fetch(`/proyecto/reseñas/obtener_resenas.php?producto_id=${productId}`);
        const data = await res.json();
        loading.style.display = 'none';

        if (!data.success) {
            content.innerHTML = '<p class="text-danger">' + i18n.error_loading_reviews + '</p>';
            return;
        }

        const total = data.total;
        const promedio = data.rating_promedio;
        const dist = data.distribucion || {5:0,4:0,3:0,2:0,1:0};

        let html = '';

        // Rating summary
        html += '<div class="rating-summary">';
        html += '<div class="rating-average">';
        html += `<div class="big-number">${promedio.toFixed(1)}</div>`;
        html += `<div class="stars">${renderStarsHtml(Math.round(promedio))}</div>`;
        html += `<div class="total-count">${total} ${total === 1 ? i18n.review : i18n.review_plural}</div>`;
        html += '</div>';
        html += '<div class="rating-bars">';
        for (let i = 5; i >= 1; i--) {
            const pct = total > 0 ? (dist[i] / total * 100) : 0;
            html += '<div class="rating-bar-row">';
            html += `<span class="bar-label">${i} <i class="fas fa-star" style="font-size:0.65rem;"></i></span>`;
            html += `<div class="bar-track"><div class="bar-fill" style="width:${pct}%"></div></div>`;
            html += `<span class="bar-count">${dist[i]}</span>`;
            html += '</div>';
        }
        html += '</div>';
        html += '</div>';

        // Review cards
        if (data.resenas.length === 0) {
            html += '<p class="text-muted small">' + i18n.no_reviews_yet + '</p>';
        } else {
            data.resenas.forEach(r => {
                html += '<div class="resena-card">';
                html += '<div class="resena-header">';
                html += `<span class="resena-user">${escapeHtml(r.usuario_nombre)} ${r.es_compra_verificada ? '<span class="verified-badge"><i class="fas fa-check-circle"></i> ' + i18n.verified_purchase + '</span>' : ''}</span>`;
                html += `<span class="resena-date">${r.created_at ? r.created_at.substring(0, 10) : ''}</span>`;
                html += '</div>';
                html += `<div class="resena-stars">${renderStarsHtml(r.puntuacion)}</div>`;
                if (r.titulo) html += `<div class="resena-titulo">${escapeHtml(r.titulo)}</div>`;
                if (r.comentario) html += `<div class="resena-comentario">${escapeHtml(r.comentario)}</div>`;
                html += '</div>';
            });
        }

        content.innerHTML = html;

        // Review form (if user logged in)
        if (CURRENT_USER && CURRENT_USER.id && CURRENT_USER.rol !== 'guest') {
            // Check if user already reviewed this product
            const hasReviewed = data.resenas.some(r => parseInt(r.usuario_id) === parseInt(CURRENT_USER.id));
            if (!hasReviewed) {
                formContainer.innerHTML = `
                    <div class="review-form-section">
                        <h6>${i18n.write_review}</h6>
                        <form id="resena-form">
                            <div class="star-selector" id="star-selector">
                                <i class="far fa-star" data-val="1"></i>
                                <i class="far fa-star" data-val="2"></i>
                                <i class="far fa-star" data-val="3"></i>
                                <i class="far fa-star" data-val="4"></i>
                                <i class="far fa-star" data-val="5"></i>
                            </div>
                            <input type="text" id="resena-titulo" class="form-control mb-2" placeholder="${i18n.title_optional}" maxlength="255">
                            <textarea id="resena-comentario" class="form-control mb-2" rows="3" placeholder="${i18n.comment_optional}"></textarea>
                            <input type="hidden" id="resena-puntuacion" value="0">
                            <button type="button" class="btn btn-primary btn-sm" onclick="submitResena(${productId})">${i18n.send_review}</button>
                        </form>
                    </div>
                `;

                // Star selector events
                const stars = document.querySelectorAll('#star-selector i');
                const puntuacionInput = document.getElementById('resena-puntuacion');
                stars.forEach(star => {
                    star.addEventListener('mouseenter', function() {
                        const val = parseInt(this.getAttribute('data-val'));
                        stars.forEach(s => {
                            const sv = parseInt(s.getAttribute('data-val'));
                            s.className = sv <= val ? 'fas fa-star active' : 'far fa-star';
                        });
                    });
                    star.addEventListener('mouseleave', function() {
                        const selected = parseInt(puntuacionInput.value);
                        stars.forEach(s => {
                            const sv = parseInt(s.getAttribute('data-val'));
                            s.className = sv <= selected ? 'fas fa-star active' : 'far fa-star';
                        });
                    });
                    star.addEventListener('click', function() {
                        const val = parseInt(this.getAttribute('data-val'));
                        puntuacionInput.value = val;
                        stars.forEach(s => {
                            const sv = parseInt(s.getAttribute('data-val'));
                            s.className = sv <= val ? 'fas fa-star active' : 'far fa-star';
                        });
                    });
                });
            } else {
                formContainer.innerHTML = '<p class="text-muted small mt-2"><i class="fas fa-check"></i> ' + i18n.already_reviewed + '</p>';
            }
        }
    } catch (e) {
        loading.style.display = 'none';
        content.innerHTML = '<p class="text-danger">' + i18n.connection_error_reviews + '</p>';
    }
}

async function submitResena(productId) {
    const puntuacion = parseInt(document.getElementById('resena-puntuacion').value);
    if (puntuacion < 1 || puntuacion > 5) {
        showNotification(i18n.select_rating, true);
        return;
    }

    const titulo = document.getElementById('resena-titulo').value.trim();
    const comentario = document.getElementById('resena-comentario').value.trim();

    try {
        const res = await fetch('/proyecto/reseñas/agregar_resena.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                producto_id: productId,
                usuario_id: CURRENT_USER.id,
                puntuacion: puntuacion,
                titulo: titulo,
                comentario: comentario
            })
        });
        const data = await res.json();
        if (data.success) {
            showNotification(i18n.review_published);
            loadResenas(productId);
        } else {
            showNotification(data.message || i18n.review_error, true);
        }
    } catch (e) {
        showNotification(i18n.connection_error, true);
    }
}
</script>
</body>
</html>