<?php
declare(strict_types=1);

/*
 * Testes automáticos da RECUPERAÇÃO DE ACESSO e de regressões de configuração (SEM banco de dados).
 * Uso (na pasta do projeto):  php tests/RecuperacaoTest.php
 * Código de saída 0 = tudo certo; 1 = alguma falha.
 */

$raiz = dirname(__DIR__);
require_once $raiz . '/controllers/RecuperacaoController.php';

$falhas = 0;
$total = 0;

function verifica(string $nome, bool $ok, string $detalhe = ''): void
{
    global $falhas, $total;
    $total++;
    if ($ok) {
        echo "[OK]    {$nome}\n";
    } else {
        $falhas++;
        echo "[FALHA] {$nome}" . ($detalhe !== '' ? " -> {$detalhe}" : '') . "\n";
    }
}

echo "=== TESTES DA RECUPERAÇÃO DE ACESSO ===\n\n";

/* 1. Token: aleatório, 64 caracteres hexadecimais, e o banco guarda só o hash */
[$token, $hash] = RecuperacaoController::gerarToken();
[$token2] = RecuperacaoController::gerarToken();
verifica('Token tem 64 caracteres hexadecimais', preg_match('/^[a-f0-9]{64}$/', $token) === 1, $token);
verifica('Dois tokens gerados são diferentes', $token !== $token2);
verifica('O hash guardado não é o token', $hash !== $token);
verifica('O hash é o SHA-256 do token', $hash === hash('sha256', $token));
verifica('hashToken() é estável', RecuperacaoController::hashToken($token) === $hash);

/* 2. Formato do token (barra lixo e tentativa de injeção antes de ir ao banco) */
verifica('Token válido é aceito', RecuperacaoController::tokenComFormatoValido($token));
verifica('Token vazio é recusado', !RecuperacaoController::tokenComFormatoValido(''));
verifica('Token curto é recusado', !RecuperacaoController::tokenComFormatoValido('abc123'));
verifica('Token com maiúsculas/símbolos é recusado', !RecuperacaoController::tokenComFormatoValido(str_repeat('Z', 64)));
verifica("Token com SQL (' OR 1=1) é recusado", !RecuperacaoController::tokenComFormatoValido("' OR 1=1 -- " . str_repeat('a', 52)));

/* 3. Regras da nova senha */
verifica('Senha curta é recusada', RecuperacaoController::validarNovaSenha('Ab1!', 'Ab1!') !== null);
verifica('Senha de 6 caracteres que cumpre as regras é aceita', RecuperacaoController::validarNovaSenha('Abc12!', 'Abc12!') === null);
verifica('Senha sem maiúscula é recusada', RecuperacaoController::validarNovaSenha('abc12!', 'abc12!') !== null);
verifica('Senha sem número é recusada', RecuperacaoController::validarNovaSenha('Abcde!', 'Abcde!') !== null);
verifica('Senha sem caractere especial é recusada', RecuperacaoController::validarNovaSenha('Abc123', 'Abc123') !== null);
verifica('Confirmação diferente é recusada', RecuperacaoController::validarNovaSenha('Abc12!', 'Abc12?') !== null);
verifica('Senha acima de 72 caracteres é recusada (limite do bcrypt)', RecuperacaoController::validarNovaSenha('Aa1!' . str_repeat('a', 70), 'Aa1!' . str_repeat('a', 70)) !== null);
verifica('Acentos contam como 1 caractere', RecuperacaoController::validarNovaSenha('Áé1!ãõ', 'Áé1!ãõ') === null);
$hashSenha = password_hash('NovaSenha123', PASSWORD_DEFAULT);
verifica('password_hash/password_verify funcionam com a nova senha', password_verify('NovaSenha123', $hashSenha) && !password_verify('outra', $hashSenha));

/* 3.1 Modo teste: só vale em localhost e só com a opção ligada */
$ligado = ['email' => ['modo_teste' => true]];
verifica('Modo teste liga em 127.0.0.1', RecuperacaoController::modoTesteAtivo($ligado, ['REMOTE_ADDR' => '127.0.0.1']));
verifica('Modo teste liga em ::1', RecuperacaoController::modoTesteAtivo($ligado, ['REMOTE_ADDR' => '::1']));
verifica('Modo teste NÃO liga para IP de fora (servidor real)', !RecuperacaoController::modoTesteAtivo($ligado, ['REMOTE_ADDR' => '200.10.20.30']));
verifica('Modo teste desligado no config não liga nunca', !RecuperacaoController::modoTesteAtivo(['email' => ['modo_teste' => false]], ['REMOTE_ADDR' => '127.0.0.1']));
verifica('Sem a chave no config, modo teste desligado', !RecuperacaoController::modoTesteAtivo([], ['REMOTE_ADDR' => '127.0.0.1']));

/* 4. Link do e-mail */
$cfg = ['app' => ['base_path' => '/sistema', 'url_publica' => 'https://meusite.com.br/']];
verifica(
    'Link usa a URL pública do config.json',
    RecuperacaoController::montarLink($cfg, $token) === "https://meusite.com.br/sistema/redefinir-senha?token={$token}",
    RecuperacaoController::montarLink($cfg, $token)
);

$semUrl = ['app' => ['base_path' => '/sistema', 'url_publica' => '']];
verifica(
    'Sem url_publica, usa o host da requisição (http)',
    RecuperacaoController::montarLink($semUrl, $token, ['HTTP_HOST' => 'localhost']) === "http://localhost/sistema/redefinir-senha?token={$token}"
);
verifica(
    'Sem url_publica e com HTTPS ligado, usa https',
    str_starts_with(RecuperacaoController::montarLink($semUrl, $token, ['HTTP_HOST' => 'app.exemplo.com', 'HTTPS' => 'on']), 'https://app.exemplo.com/')
);
verifica(
    'Cabeçalho Host forjado não vira link para outro site',
    str_starts_with(RecuperacaoController::montarLink($semUrl, $token, ['HTTP_HOST' => 'evil.com/phishing?x=']), 'http://localhost/'),
    RecuperacaoController::montarLink($semUrl, $token, ['HTTP_HOST' => 'evil.com/phishing?x='])
);
verifica(
    'Site na raiz do domínio (base_path "/") não gera barras duplas',
    RecuperacaoController::montarLink(['app' => ['base_path' => '/', 'url_publica' => 'https://x.com']], $token) === "https://x.com/redefinir-senha?token={$token}"
);

/* 5. Estrutura do projeto */
$sql = (string) file_get_contents($raiz . '/banco.sql');
preg_match('/CREATE TABLE IF NOT EXISTS usuario \((.*?)\);/s', $sql, $m);
$tabelaUsuario = $m[1] ?? '';
verifica('banco.sql: tabela usuario tem a coluna email', preg_match('/\bemail\s+VARCHAR/i', $tabelaUsuario) === 1);
verifica('banco.sql: tabela usuario tem reset_token e token_expires_at', str_contains($tabelaUsuario, 'reset_token') && str_contains($tabelaUsuario, 'token_expires_at'));
verifica('Migração para bancos antigos existe', is_file($raiz . '/migracoes/001_recuperacao_senha.sql'));

$api = (string) file_get_contents($raiz . '/routes/api.php');
$posRota = strpos($api, "'recuperar-senha'");
$posAuth = strpos($api, "empty(\$_SESSION['user'])");
verifica('API tem a rota recuperar-senha', $posRota !== false);
verifica('Rotas de recuperação vêm ANTES da checagem de login (são públicas)', $posRota !== false && $posAuth !== false && $posRota < $posAuth);

$web = (string) file_get_contents($raiz . '/routes/web.php');
verifica('Rotas web /recuperar-senha e /redefinir-senha existem', str_contains($web, "'recuperar-senha'") && str_contains($web, "'redefinir-senha'"));
verifica('Tela de redefinição envia Referrer-Policy: no-referrer', str_contains($web, 'Referrer-Policy: no-referrer'));

foreach (['views/auth/recuperar.php', 'views/auth/redefinir.php', 'public/js/recuperacao.js', 'helpers/Mailer.php'] as $arq) {
    verifica("Arquivo existe: {$arq}", is_file($raiz . '/' . $arq));
}
verifica('Login tem o link "Esqueci a senha"', str_contains((string) file_get_contents($raiz . '/views/auth/login.php'), 'recuperar-senha'));

/* 6. Segredos e versionamento */
verifica('.gitignore protege config/config.json', str_contains((string) file_get_contents($raiz . '/.gitignore'), 'config/config.json'));
$exemplo = json_decode((string) file_get_contents($raiz . '/config/config.example.json'), true);
verifica('config.example.json é JSON válido', is_array($exemplo));
verifica('config.example.json não traz chave de API nem senha', is_array($exemplo)
    && ($exemplo['email']['brevo_api_key'] ?? 'x') === ''
    && ($exemplo['database']['password'] ?? 'x') === '');
verifica('config.json é JSON válido', is_array(json_decode((string) file_get_contents($raiz . '/config/config.json'), true)));

/* 7. Regressão: textos estranhos (idioma errado) nos JS do sistema */
$proibidas = ['Baelna', 'Nasayaat', 'Palagip', 'Saan a', 'Kayatmo', 'Naikkat', 'Aktualisaren', 'Rehistro', 'Pnoenno'];
foreach (['app', 'home', 'login', 'pedido', 'pedido-status', 'shaper', 'status', 'recuperacao', 'senha', 'alterar-senha'] as $js) {
    $codigo = (string) file_get_contents($raiz . "/public/js/{$js}.js");
    $achou = '';
    foreach ($proibidas as $palavra) {
        if (str_contains($codigo, $palavra)) {
            $achou = $palavra;
            break;
        }
    }
    verifica("{$js}.js sem textos em outro idioma", $achou === '', $achou);
}
verifica('status.js não redefine window.carregarStatus (conflita com home.js)', !str_contains((string) file_get_contents($raiz . '/public/js/status.js'), 'window.carregarStatus ='));
$ped = (string) file_get_contents($raiz . '/public/js/pedido.js');
verifica('pedido.js não redefine initPedidoStatus (sobrescrevia a timeline)', !str_contains($ped, 'window.initPedidoStatus ='));
verifica('pedido.js não redefine executarBuscaPedido', !str_contains($ped, 'window.executarBuscaPedido ='));
$home = (string) file_get_contents($raiz . '/views/home/index.php');
verifica('ativarAba só mexe nos painéis do menu principal (senão as abas internas de Pedido somem ao voltar)', str_contains($home, ".content-shell > .tab-content > .tab-pane") && !str_contains($home, "querySelectorAll('.tab-content .tab-pane')"));
$ps = (string) file_get_contents($raiz . '/public/js/pedido-status.js');
verifica('Atualizar status: não usa mais "|| 1" como status padrão escondido', !preg_match('/statusIdReal[^\n]*\|\|\s*1\b/', $ps));
verifica('Atualizar status: valida id_status/data antes de enviar', str_contains($ps, 'Informe a data e a hora') && str_contains($ps, 'if (!idStatus)'));
verifica('Busca de pedidos: número curto procura o ID do pedido (data-pedido)', str_contains($ps, 'data-pedido=') && str_contains($ps, 'item.dataset.pedido === termo'));
verifica('Tela Status: orientação e botão "Criar status padrão" quando está vazia', str_contains((string) file_get_contents($raiz . '/public/js/status.js'), 'btnStatusPadrao'));
verifica('dashboard.js (chamava função inexistente) foi removido', !is_file($raiz . '/public/js/dashboard.js'));

echo "\n";
if ($falhas > 0) {
    echo "=== {$falhas} DE {$total} TESTES FALHARAM ===\n";
    exit(1);
}
echo "=== TODOS OS {$total} TESTES PASSARAM ===\n";
exit(0);
