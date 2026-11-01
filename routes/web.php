<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function auth()
{
    if (empty($_SESSION['user'])) {
        header('Location: ' . BASE_PATH . '/login');
        exit;
    }
}

/* LOGIN */
if ($recurso === 'login') {
    require_once dirname(__DIR__) . '/views/auth/login.php';
    exit;
}

/* RECUPERAÇÃO DE ACESSO (telas públicas) */
if ($recurso === 'recuperar-senha') {
    require_once dirname(__DIR__) . '/views/auth/recuperar.php';
    exit;
}

if ($recurso === 'redefinir-senha') {
    // O token vai na URL: impede que ele seja enviado como "Referer" a outros sites
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store');
    require_once dirname(__DIR__) . '/views/auth/redefinir.php';
    exit;
}

if ($recurso === 'alterar-senha') {
    header('Cache-Control: no-store');
    require_once dirname(__DIR__) . '/views/auth/alterar.php';
    exit;
}

/* LOGOUT */
if ($recurso === 'logout') {
    session_destroy();
    header('Location: ' . BASE_PATH . '/login');
    exit;
}

/* DASHBOARD / HOME */
if ($recurso === 'dashboard' || $recurso === null || $recurso === '') {
    auth();
    require_once dirname(__DIR__) . '/views/home/index.php';
    exit;
}

/* SHAPER / CLIENTE */
if ($recurso === 'shaper' || $recurso === 'shapers') {
    auth();
    require_once dirname(__DIR__) . '/views/shaper/index.php';
    exit;
}

/* PEDIDOS (suporta tanto 'pedido' quanto 'pedidos') */
if ($recurso === 'pedido' || $recurso === 'pedidos') {
    auth();
    require_once dirname(__DIR__) . '/views/pedido/index.php';
    exit;
}

/* STATUS DOS PEDIDOS */
if ($recurso === 'status' || $recurso === 'status-pedido' || $recurso === 'status_pedido') {
    auth();
    // Certifique-se de que a view exista em views/status/index.php ou ajuste o caminho abaixo
    $statusView = dirname(__DIR__) . '/views/status/index.php';
    if (file_exists($statusView)) {
        require_once $statusView;
    } else {
        // Fallback temporário caso a view ainda não tenha sido criada
        require_once dirname(__DIR__) . '/views/pedido/index.php';
    }
    exit;
}

/* DIAGNÓSTICO COM IA (somente usuários logados) */
if ($recurso === 'diagnostico') {
    auth();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once dirname(__DIR__) . '/controllers/DiagnosticoController.php';
        (new DiagnosticoController())->analisar();
        exit;
    }
    require_once dirname(__DIR__) . '/views/diagnostico/index.php';
    exit;
}

/* 404 */
http_response_code(404);
echo '<div style="font-family: sans-serif; text-align: center; margin-top: 50px;">';
echo '<h2>Página não encontrada (404)</h2>';
echo '<p>A rota solicitada não existe no sistema.</p>';
echo '<a href="' . BASE_PATH . '/">Voltar para o Dashboard</a>';
echo '</div>';
exit;