<?php
declare(strict_types=1);

/*
 * Testes automáticos SEM banco de dados.
 * Uso (na pasta do projeto):  php tests/PedidoTest.php
 * Saída com código 0 = tudo certo; código 1 = alguma falha.
 */

$raiz = dirname(__DIR__);
require_once $raiz . '/controllers/PedidoController.php';

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

echo "=== TESTES DE REGRAS DE NEGÓCIO ===\n\n";

/* 1. Prazo de entrega (usa a regra REAL do PedidoController) */
$prev = fn(string $data, string $tipo) => PedidoController::calcularPrevisao(new DateTime($data), $tipo);

verifica('PU = +10 dias', $prev('2026-10-15', 'PU') === '2026-10-25', $prev('2026-10-15', 'PU'));
verifica('EPS = +25 dias', $prev('2026-10-15', 'EPS') === '2026-11-09', $prev('2026-10-15', 'EPS'));
verifica('Tipo em minúsculas (eps) é aceito', $prev('2026-10-15', 'eps') === '2026-11-09');
verifica('Prazo atravessa a virada do ano', $prev('2026-12-28', 'PU') === '2027-01-07', $prev('2026-12-28', 'PU'));
verifica('Tipo desconhecido usa o prazo de PU', $prev('2026-10-15', 'XYZ') === '2026-10-25');

/* 2. Senha do usuário inicial do banco.sql confere com "123456" */
$sql = (string) file_get_contents($raiz . '/banco.sql');
$achou = preg_match("/VALUES \('Admin', 'Administrador', '([^']+)'\)/", $sql, $m) === 1;
verifica('banco.sql contém o usuário Admin', $achou);
if ($achou) {
    verifica('Senha do Admin confere com 123456', password_verify('123456', $m[1]));
}

/* 3. Toda coluna usada em "WHERE coluna = ..." nos models existe no banco.sql */
$colunas = [];
foreach (explode("\n", $sql) as $linha) {
    if (preg_match('/^\s+(\w+)\s+(INT|VARCHAR|DATE|DATETIME|TINYINT|ENUM)/i', $linha, $c)) {
        $colunas[strtolower($c[1])] = true;
    }
}
foreach (glob($raiz . '/models/*.php') ?: [] as $arquivo) {
    $codigo = (string) file_get_contents($arquivo);
    preg_match_all('/WHERE\s+(?:\w+\.)?(\w+)\s*=/i', $codigo, $achados);
    foreach (array_unique($achados[1]) as $col) {
        verifica(
            basename($arquivo) . ": coluna '{$col}' existe no banco",
            isset($colunas[strtolower($col)])
        );
    }
}

echo "\n";
if ($falhas > 0) {
    echo "=== {$falhas} DE {$total} TESTES FALHARAM ===\n";
    exit(1);
}
echo "=== TODOS OS {$total} TESTES PASSARAM ===\n";
exit(0);
