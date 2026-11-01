<?php
declare(strict_types=1);

/*
 * TESTE DE FUMAÇA DA API (precisa do Apache + MySQL ligados e do banco.sql importado).
 *
 * Uso (na pasta do projeto):
 *     php tests/api_smoke.php
 *     php tests/api_smoke.php http://localhost/sistema
 *
 * Cria registros com o prefixo "SMOKE" e APAGA tudo no final.
 * Usuário usado: Admin / 123456 (o do banco.sql). Código 0 = tudo certo, 1 = alguma falha.
 */

if (!function_exists('curl_init')) {
    fwrite(STDERR, "Ative a extensão curl no php.ini (extension=curl) e rode de novo.\n");
    exit(2);
}

$base = rtrim($argv[1] ?? 'http://localhost/sistema', '/');
$cookie = tempnam(sys_get_temp_dir(), 'smoke');
$falhas = 0;
$total = 0;

function chamar(string $metodo, string $caminho, ?array $corpo = null): array
{
    global $base, $cookie;

    // Imita o front-end: tudo que não é GET vai como POST + "_method" no corpo
    $url = $base . '/index.php?url=api/' . ltrim($caminho, '/');
    $ch = curl_init($url);
    $opcoes = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
    ];
    if ($metodo !== 'GET') {
        $corpo = $corpo ?? [];
        if ($metodo !== 'POST') {
            $corpo['_method'] = $metodo;
        }
        $opcoes[CURLOPT_POST] = true;
        $opcoes[CURLOPT_POSTFIELDS] = json_encode($corpo);
    }
    curl_setopt_array($ch, $opcoes);
    $resposta = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erro = curl_error($ch);
    curl_close($ch);

    if ($resposta === false) {
        return ['status' => 0, 'json' => null, 'bruto' => $erro];
    }
    return ['status' => $status, 'json' => json_decode($resposta, true), 'bruto' => $resposta];
}

function verifica(string $nome, bool $ok, array $r = []): void
{
    global $falhas, $total;
    $total++;
    if ($ok) {
        echo "[OK]    {$nome}\n";
        return;
    }
    $falhas++;
    $extra = $r ? ' -> HTTP ' . $r['status'] . ' ' . substr((string) $r['bruto'], 0, 160) : '';
    echo "[FALHA] {$nome}{$extra}\n";
}

function idPorDescricao(string $recurso, string $pk, string $descricao): ?int
{
    $r = chamar('GET', $recurso);
    foreach (is_array($r['json']) ? $r['json'] : [] as $linha) {
        if (($linha['descricao'] ?? null) === $descricao) {
            return (int) $linha[$pk];
        }
    }
    return null;
}

echo "API em: {$base}\n\n== AUTENTICAÇÃO ==\n";

$r = chamar('GET', 'pedidos');
verifica('Sem login, a API recusa (401)', $r['status'] === 401, $r);

$r = chamar('POST', 'login', ['usuario' => 'Admin', 'senha' => 'senha-errada']);
verifica('Login com senha errada é recusado (401)', $r['status'] === 401, $r);

$r = chamar('POST', 'login', ['usuario' => 'Admin', 'senha' => '123456']);
verifica('Login Admin/123456 funciona', $r['status'] === 200 && ($r['json']['sucesso'] ?? false) === true, $r);
if ($r['status'] !== 200) {
    echo "\nSem login não dá para continuar. Confira o banco (php teste-banco.php no navegador).\n";
    exit(1);
}

$r = chamar('GET', 'usuario');
verifica('GET /usuario devolve o usuário logado', ($r['json']['usuario'] ?? '') === 'Admin', $r);

echo "\n== CADASTROS AUXILIARES (criar, listar, editar) ==\n";
$cadastros = [
    'acabamentos' => 'id_acabamento',
    'composicoes' => 'id_composicao',
    'variacoes' => 'id_variacao',
    'cores' => 'id_cor',
    'tecidos' => 'id_tecido',
    'configuracaoquilhas' => 'id_configuracaoquilha',
    'sistemaquilhas' => 'id_sistemaquilha',
];
$ids = [];
foreach ($cadastros as $recurso => $pk) {
    $nome = 'SMOKE ' . $recurso;
    $r = chamar('POST', $recurso, ['descricao' => $nome]);
    verifica("{$recurso}: criar", $r['status'] === 200 || $r['status'] === 201, $r);

    $ids[$recurso] = idPorDescricao($recurso, $pk, $nome);
    verifica("{$recurso}: aparece na listagem", $ids[$recurso] !== null);
    if ($ids[$recurso] === null) {
        continue;
    }

    $r = chamar('PUT', "{$recurso}/{$ids[$recurso]}", ['descricao' => $nome . ' editado']);
    verifica("{$recurso}: editar", $r['status'] === 200, $r);
    verifica(
        "{$recurso}: edição gravada",
        idPorDescricao($recurso, $pk, $nome . ' editado') === $ids[$recurso]
    );

    $r = chamar('POST', $recurso, ['descricao' => '']);
    verifica("{$recurso}: descrição vazia é recusada (400)", $r['status'] === 400, $r);
}

echo "\n== SHAPER E STATUS ==\n";
$r = chamar('POST', 'shapers', ['nome' => 'SMOKE Shaper', 'email' => 'smoke@example.com', 'telefone' => '(11) 90000-0000']);
verifica('Shaper: criar (201)', $r['status'] === 201, $r);
$idShaper = (int) ($r['json']['id_shaper'] ?? 0);

$r = chamar('PUT', "shapers/{$idShaper}", ['nome' => 'SMOKE Shaper editado', 'email' => 'smoke@example.com', 'telefone' => '']);
verifica('Shaper: editar', $r['status'] === 200, $r);

$r = chamar('PUT', "shapers/{$idShaper}", ['ativo' => 0]);
verifica('Shaper: desativar', $r['status'] === 200 && ($r['json']['ativo'] ?? 1) === 0, $r);
$r = chamar('PUT', "shapers/{$idShaper}", ['ativo' => 1]);
verifica('Shaper: reativar', $r['status'] === 200 && ($r['json']['ativo'] ?? 0) === 1, $r);

$r = chamar('POST', 'status', ['descricao' => 'SMOKE Status']);
verifica('Status: criar (201)', $r['status'] === 201, $r);
$idStatus = (int) ($r['json']['id_status'] ?? 0);

$r = chamar('GET', 'status/proximo&id_atual=0');
verifica('Status: próximo status responde (200)', $r['status'] === 200 && is_array($r['json']), $r);

echo "\n== PEDIDO ==\n";
$hoje = date('Y-m-d');
$corpoPedido = [
    'data' => $hoje,
    'tipo_bloco' => 'EPS',
    'id_shaper' => $idShaper,
    'id_composicao' => $ids['composicoes'],
    'id_variacao' => $ids['variacoes'],
    'id_acabamento' => $ids['acabamentos'],
    'id_configuracaoquilha' => $ids['configuracaoquilhas'],
    'id_sistemaquilha' => $ids['sistemaquilhas'],
    'observacao' => 'Teste automático <b>SMOKE</b>',
    'tecidos' => [$ids['tecidos']],
    'cores' => [$ids['cores']],
];
$r = chamar('POST', 'pedidos', $corpoPedido);
verifica('Pedido: criar (201)', $r['status'] === 201, $r);
$idPedido = (int) ($r['json']['id_pedido'] ?? 0);
verifica('Pedido: id devolvido na criação', $idPedido > 0, $r);

$r = chamar('GET', "pedidos/{$idPedido}");
$esperada = (new DateTime($hoje))->modify('+25 days')->format('Y-m-d');
verifica('Pedido EPS: previsão de entrega = data + 25 dias', ($r['json']['data_prevista_entrega'] ?? '') === $esperada, $r);

$corpoPedido['tipo_bloco'] = 'PU';
$r = chamar('PUT', "pedidos/{$idPedido}", $corpoPedido);
verifica('Pedido: editar', $r['status'] === 200, $r);
$r = chamar('GET', "pedidos/{$idPedido}");
$esperada = (new DateTime($hoje))->modify('+10 days')->format('Y-m-d');
verifica('Pedido PU (após editar): previsão = data + 10 dias', ($r['json']['data_prevista_entrega'] ?? '') === $esperada, $r);

$r = chamar('GET', 'pedidos');
$achou = false;
foreach (is_array($r['json']) ? $r['json'] : [] as $p) {
    if ((int) ($p['id_pedido'] ?? 0) === $idPedido) {
        $achou = true;
    }
}
verifica('Pedido aparece na listagem', $achou, $r);

$corpoInvalido = $corpoPedido;
unset($corpoInvalido['id_shaper']);
$r = chamar('POST', 'pedidos', $corpoInvalido);
verifica('Pedido sem shaper é recusado (400)', $r['status'] === 400, $r);

$corpoInvalido = $corpoPedido;
$corpoInvalido['tipo_bloco'] = 'ABC';
$r = chamar('POST', 'pedidos', $corpoInvalido);
verifica('Pedido com tipo de bloco inválido é recusado (400)', $r['status'] === 400, $r);

echo "\n== STATUS DO PEDIDO ==\n";
$r = chamar('POST', 'pedidos-status', ['id_pedido' => $idPedido, 'id_status' => $idStatus, 'data' => date('Y-m-d H:i:s'), 'observacao' => 'smoke']);
verifica('Registrar status no pedido', $r['status'] === 200, $r);
$r = chamar('GET', "pedidos-status/{$idPedido}");
verifica('Histórico do pedido tem 1 registro', is_array($r['json']) && count($r['json']) === 1, $r);

echo "\n== INTEGRIDADE (não pode apagar o que está em uso) ==\n";
$r = chamar('DELETE', "composicoes/{$ids['composicoes']}");
verifica('Composição em uso NÃO é apagada (400/409)', in_array($r['status'], [400, 409], true), $r);
$r = chamar('DELETE', "shapers/{$idShaper}");
verifica('Shaper em uso NÃO é apagado (409)', $r['status'] === 409, $r);

echo "\n== LIMPEZA ==\n";
$r = chamar('DELETE', "pedidos/{$idPedido}");
verifica('Apagar pedido', $r['status'] === 200, $r);
$r = chamar('DELETE', "pedidos/{$idPedido}");
verifica('Apagar de novo devolve 404', $r['status'] === 404, $r);
$r = chamar('DELETE', "status/{$idStatus}");
verifica('Apagar status', $r['status'] === 200, $r);
$r = chamar('DELETE', "shapers/{$idShaper}");
verifica('Apagar shaper', $r['status'] === 200, $r);
foreach ($ids as $recurso => $id) {
    if ($id === null) {
        continue;
    }
    $r = chamar('DELETE', "{$recurso}/{$id}");
    verifica("{$recurso}: apagar", $r['status'] === 200, $r);
}

echo "\n== LOGOUT ==\n";
$r = chamar('POST', 'logout');
verifica('Logout', $r['status'] === 200, $r);
$r = chamar('GET', 'pedidos');
verifica('Após logout a API recusa (401)', $r['status'] === 401, $r);

echo "\n== RECUPERAÇÃO DE ACESSO (rotas públicas, sem login) ==\n";
$r = chamar('POST', 'recuperar-senha', ['email' => 'isto-nao-e-email']);
verifica('Recuperar: e-mail inválido é recusado (400)', $r['status'] === 400, $r);

$r = chamar('POST', 'recuperar-senha', ['email' => 'ninguem-smoke-' . time() . '@example.com']);
verifica('Recuperar: e-mail inexistente responde igual (200), sem revelar cadastro', $r['status'] === 200 && ($r['json']['sucesso'] ?? false) === true, $r);

$r = chamar('GET', 'recuperar-senha');
verifica('Recuperar: GET não é permitido (405)', $r['status'] === 405, $r);

$r = chamar('GET', 'redefinir-senha&token=' . str_repeat('a', 64));
verifica('Redefinir: token desconhecido não vale (410)', $r['status'] === 410, $r);

$r = chamar('GET', 'redefinir-senha&token=lixo');
verifica('Redefinir: token em formato inválido é recusado (400)', $r['status'] === 400, $r);

$r = chamar('POST', 'redefinir-senha', ['token' => str_repeat('b', 64), 'senha' => 'NovaSenha123', 'confirmar' => 'NovaSenha123']);
verifica('Redefinir: token desconhecido não troca senha (410)', $r['status'] === 410, $r);

$r = chamar('POST', 'redefinir-senha', ['token' => str_repeat('b', 64), 'senha' => '123', 'confirmar' => '123']);
verifica('Redefinir: senha curta é recusada (400)', $r['status'] === 400, $r);

@unlink($cookie);
echo "\n";
if ($falhas > 0) {
    echo "=== {$falhas} DE {$total} VERIFICAÇÕES FALHARAM ===\n";
    exit(1);
}
echo "=== TODAS AS {$total} VERIFICAÇÕES PASSARAM ===\n";
exit(0);
