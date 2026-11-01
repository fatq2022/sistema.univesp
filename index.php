<?php

/* =========================
   SESSÃO
========================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   ERROS
========================= */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

/* =========================
   CONFIGURAÇÃO
========================= */
$config_path = __DIR__ . '/config/config.json';
$base = '/sistema';

if (file_exists($config_path)) {
    $config = json_decode(
        file_get_contents($config_path),
        true
    );

    if (is_array($config)) {
        $base = $config['app']['base_path'] ?? $base;
    }
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', $base);
}

/* =========================
   CAPTURA DA URL
========================= */
$url = $_GET['url'] ?? '';
$url = trim($url, '/');

$uri = ($url === '') ? [] : explode('/', $url);

$recurso = $uri[0] ?? null;
$subRecurso = $uri[1] ?? null;

/* =========================
   ROTEAMENTO
========================= */
if ($recurso === 'api') {

    $apiPath = __DIR__ . '/routes/api.php';

    if (!file_exists($apiPath)) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'erro' => 'Arquivo routes/api.php não encontrado'
        ]);

        exit;
    }

    require_once $apiPath;
    exit;
}

/* =========================
   ROTAS WEB
========================= */
$webPath = __DIR__ . '/routes/web.php';

if (!file_exists($webPath)) {
    http_response_code(500);
    echo 'Arquivo routes/web.php não encontrado';
    exit;
}

require_once $webPath;
exit;