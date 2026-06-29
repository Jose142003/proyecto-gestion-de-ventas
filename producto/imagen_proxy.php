<?php
error_reporting(0);
ini_set('display_errors', 0);

$url = $_GET['url'] ?? '';
if (empty($url)) {
    http_response_code(400);
    header('Content-Type: image/svg+xml');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300"><rect width="300" height="300" fill="#f0f0f0" rx="12"/><g transform="translate(150,130)"><circle cx="0" cy="-20" r="35" fill="#ddd"/><path d="M-50 50 C-50 10 -30 -10 0 -10 C30 -10 50 10 50 50" fill="none" stroke="#ddd" stroke-width="6" stroke-linecap="round"/></g><rect x="115" y="215" width="70" height="8" rx="4" fill="#e0e0e0"/><rect x="105" y="232" width="90" height="8" rx="4" fill="#e0e0e0"/><rect x="125" y="249" width="50" height="8" rx="4" fill="#e0e0e0"/></svg>';
    exit;
}

$url = base64_decode($url);
if ($url === false || !preg_match('#^https?://#', $url)) {
    http_response_code(400);
    exit;
}

// SSRF protection: block private/internal IPs (IPv4 + IPv6), allow all public HTTPS sources
$host = parse_url($url, PHP_URL_HOST);
if ($host === false || $host === null) {
    http_response_code(400);
    exit;
}
// Resolve host to IPs and block private/internal ranges
$ips = [];
$ipv4 = gethostbyname($host);
if ($ipv4 !== $host) {
    $ips[] = $ipv4;
}
$dnsRecords = @dns_get_record($host, DNS_AAAA);
if ($dnsRecords) {
    foreach ($dnsRecords as $record) {
        if (isset($record['ipv6'])) {
            $ips[] = $record['ipv6'];
        }
    }
}
foreach ($ips as $ip) {
    $isPrivate = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    if ($isPrivate) {
        http_response_code(403);
        header('Content-Type: image/svg+xml');
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300"><rect width="300" height="300" fill="#f0f0f0" rx="12"/><g transform="translate(150,130)"><circle cx="0" cy="-20" r="35" fill="#ddd"/><path d="M-50 50 C-50 10 -30 -10 0 -10 C30 -10 50 10 50 50" fill="none" stroke="#ddd" stroke-width="6" stroke-linecap="round"/></g><rect x="115" y="215" width="70" height="8" rx="4" fill="#e0e0e0"/><rect x="105" y="232" width="90" height="8" rx="4" fill="#e0e0e0"/><rect x="125" y="249" width="50" height="8" rx="4" fill="#e0e0e0"/></svg>';
        exit;
    }
}

$cache_dir = __DIR__ . '/../uploads/cache/';
$cache_key = 'img_' . md5($url);
$cache_file = $cache_dir . $cache_key;
$cache_ext = '.dat';

$cache_path = $cache_file . $cache_ext;
$cache_info_path = $cache_file . '.json';

if (file_exists($cache_path) && file_exists($cache_info_path)) {
    $info = json_decode(file_get_contents($cache_info_path), true);
    if ($info && (time() - $info['cached_at'] < 86400 * 7)) {
        header('Content-Type: ' . $info['mime']);
        header('Content-Length: ' . filesize($cache_path));
        header('Cache-Control: public, max-age=86400');
        header('X-Cache: HIT');
        readfile($cache_path);
        exit;
    }
}

$image_data = null;
$http_code = 0;
$content_type = null;

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_REFERER => '',
    ]);
    $image_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
} elseif (ini_get('allow_url_fopen')) {
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0']]);
    $image_data = @file_get_contents($url, false, $ctx);
    if ($image_data !== false) {
        $http_code = 200;
        $content_type = 'application/octet-stream';
    }
}

if ($http_code !== 200 || empty($image_data)) {
    http_response_code(502);
    header('Content-Type: image/svg+xml');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300"><rect width="300" height="300" fill="#f0f0f0" rx="12"/><g transform="translate(150,130)"><circle cx="0" cy="-20" r="35" fill="#ddd"/><path d="M-50 50 C-50 10 -30 -10 0 -10 C30 -10 50 10 50 50" fill="none" stroke="#ddd" stroke-width="6" stroke-linecap="round"/></g><rect x="115" y="215" width="70" height="8" rx="4" fill="#e0e0e0"/><rect x="105" y="232" width="90" height="8" rx="4" fill="#e0e0e0"/><rect x="125" y="249" width="50" height="8" rx="4" fill="#e0e0e0"/></svg>';
    exit;
}

if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

$mime = $content_type ?: 'application/octet-stream';
if (strpos($mime, 'image/') !== 0) $mime = 'image/jpeg';
if (!is_dir($cache_dir)) @mkdir($cache_dir, 0755, true);
file_put_contents($cache_path, $image_data);
file_put_contents($cache_info_path, json_encode([
    'mime' => $mime,
    'cached_at' => time(),
    'source' => $url
]));

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($image_data));
header('Cache-Control: public, max-age=86400');
header('X-Cache: MISS');
echo $image_data;
