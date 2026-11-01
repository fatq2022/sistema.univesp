<?php
declare(strict_types=1);

// Mude para true SOMENTE enquanto estiver depurando. Em produção deixe false.
const API_DEBUG = false;

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function responder(int $status, array $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Extrai e divide a URL para preencher o array $uri corretamente
$url = $_GET['url'] ?? '';
if (!is_string($url)) {
    $url = '';
}
$uri = explode('/', trim($url, '/'));

$subRecurso = ($uri[1] ?? '') !== '' ? $uri[1] : null;
$id = ($uri[2] ?? '') !== '' ? $uri[2] : null;
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// O id, quando existir, precisa ser numérico
if ($id !== null && !ctype_digit($id) && !($subRecurso === 'status' && $id === 'proximo')) {
    responder(400, ['sucesso' => false, 'mensagem' => 'Identificador inválido.']);
}

if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    header('Allow: GET, POST, PUT, PATCH, DELETE');
    responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
}

// Se vier corpo JSON, ele precisa ser válido
if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
    $tipo = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($tipo, 'application/json') !== false) {
        $corpo = file_get_contents('php://input');
        if ($corpo !== '' && $corpo !== false) {
            json_decode($corpo, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                responder(400, ['sucesso' => false, 'mensagem' => 'JSON inválido.']);
            }
        }
    }
}

// Suporte a _method no corpo (o front envia PUT/DELETE via POST)
if ($method === 'POST') {
    $corpoOverride = json_decode((string) file_get_contents('php://input'), true);
    if (is_array($corpoOverride) && isset($corpoOverride['_method']) && is_string($corpoOverride['_method'])) {
        $override = strtoupper($corpoOverride['_method']);
        if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
            $method = $override;
        }
    }
}

if ($subRecurso === 'login') {
    if ($method !== 'POST') {
        header('Allow: POST');
        responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    }

    require_once dirname(__DIR__) . '/controllers/AuthController.php';
    (new AuthController())->login();
    exit;
}

// Recuperação de acesso: rotas públicas (a pessoa ainda não consegue entrar)
if ($subRecurso === 'recuperar-senha' || $subRecurso === 'redefinir-senha') {
    require_once dirname(__DIR__) . '/controllers/RecuperacaoController.php';
    $recuperacao = new RecuperacaoController();

    if ($subRecurso === 'recuperar-senha') {
        if ($method !== 'POST') {
            header('Allow: POST');
            responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
        }
        $recuperacao->solicitar();
    } elseif ($method === 'GET') {
        $recuperacao->validar();
    } elseif ($method === 'POST') {
        $recuperacao->redefinir();
    } else {
        header('Allow: GET, POST');
        responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    }
    exit;
}

// Alterar a senha (tela pública, a partir do login): exige usuário + senha atual
if ($subRecurso === 'alterar-senha') {
    if ($method !== 'POST') {
        header('Allow: POST');
        responder(405, ['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    }
    require_once dirname(__DIR__) . '/controllers/AlterarSenhaController.php';
    (new AlterarSenhaController())->alterar();
    exit;
}

if (empty($_SESSION['user'])) {
    responder(401, ['sucesso' => false, 'mensagem' => 'Não autorizado.']);
}

if ($subRecurso === 'logout') {
    require_once dirname(__DIR__) . '/controllers/AuthController.php';
    (new AuthController())->logout();
    exit;
}

if ($subRecurso === 'usuario') {
    require_once dirname(__DIR__) . '/models/Usuario.php';
    try {
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->buscaUsuario((string) $_SESSION['user']);
        if (!$usuario) {
            responder(404, ['sucesso' => false, 'mensagem' => 'Usuário não encontrado.']);
        }
        responder(200, [
            'id_usuario' => $usuario['id_usuario'] ?? $usuario['id'] ?? null,
            'usuario' => $usuario['usuario'] ?? null,
            'nome' => $usuario['nome'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('Erro ao consultar usuário: ' . $e->getMessage());
        responder(500, ['sucesso' => false, 'mensagem' => 'Erro ao consultar usuário.']);
    }
}

$map = [
    'shapers' => 'ShaperController',
    'cores' => 'CorController',
    'composicoes' => 'ComposicaoController',
    'variacoes' => 'VariacaoController',
    'acabamentos' => 'AcabamentoController',
    'tecidos' => 'TecidoController',
    'configuracaoquilhas' => 'ConfiguracaoquilhaController',
    'sistemaquilhas' => 'SistemaquilhaController',
    'pedidos' => 'PedidoController',
    'pedidos-status' => 'PedidoStatusController',
    'status' => 'StatusController',
];

if ($subRecurso === null || !isset($map[$subRecurso])) {
    responder(404, ['sucesso' => false, 'mensagem' => 'Rota da API não encontrada.']);
}

$controllerName = $map[$subRecurso];
$controllerFile = dirname(__DIR__) . '/controllers/' . $controllerName . '.php';

if (!is_file($controllerFile)) {
    error_log('Controller não encontrado: ' . $controllerFile);
    responder(500, ['sucesso' => false, 'mensagem' => 'Arquivo do controlador não encontrado.']);
}

try {
    require_once $controllerFile;

    if (!class_exists($controllerName)) {
        responder(500, ['sucesso' => false, 'mensagem' => 'Classe do controlador não encontrada.']);
    }

    $controller = new $controllerName();

    if (!method_exists($controller, 'processRequest')) {
        responder(500, ['sucesso' => false, 'mensagem' => 'Controlador sem processRequest().']);
    }

    $controller->processRequest($method, $id);
} catch (Throwable $e) {
    // O detalhe completo sempre vai para o log do servidor
    error_log(sprintf(
        'Erro na API [%s]: %s em %s:%d',
        $subRecurso,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    $resposta = ['sucesso' => false, 'mensagem' => 'Erro interno no servidor.'];

    // E só aparece no navegador se API_DEBUG estiver ligado
    if (API_DEBUG) {
        $resposta['mensagem'] = 'Erro detalhado: ' . $e->getMessage();
        $resposta['arquivo'] = $e->getFile();
        $resposta['linha'] = $e->getLine();
    }

    responder(500, $resposta);
}
exit;