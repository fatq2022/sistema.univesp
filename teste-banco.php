<?php
// Esta página mostra detalhes técnicos: só funciona na própria máquina (localhost).
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Acesso negado: use esta página apenas em localhost.');
}
// Abra http://localhost/sistema/teste-banco.php para testar o banco de dados.
header('Content-Type: text/html; charset=utf-8');
echo '<h2>Teste do banco de dados</h2>';
require_once __DIR__ . '/config/Database.php';
try {
    $pdo = (new Database())->connect();
    echo '<p style="color:green">✔ Conexão com o MySQL funcionando.</p>';
    $t = $pdo->query("SHOW TABLES LIKE 'usuario'")->fetch();
    if (!$t) { echo '<p style="color:red">✘ A tabela "usuario" não existe. Importe o arquivo banco.sql no phpMyAdmin.</p>'; exit; }
    $n = (int) $pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn();
    echo $n ? "<p style='color:green'>✔ Tabela usuario com $n usuário(s).</p>"
            : '<p style="color:red">✘ Nenhum usuário cadastrado. Importe o banco.sql novamente.</p>';
} catch (Throwable $e) {
    $m = $e->getMessage();
    echo '<p style="color:red">✘ Erro: ' . htmlspecialchars($m) . '</p><p><b>O que fazer:</b> ';
    if (stripos($m, 'Unknown database') !== false) echo 'o banco "teste" não existe — importe o banco.sql no phpMyAdmin.';
    elseif (stripos($m, 'Access denied') !== false) echo 'usuário ou senha do MySQL errados — ajuste config/config.json.';
    elseif (stripos($m, 'refused') !== false || stripos($m, 'No connection') !== false) echo 'o MySQL está desligado — clique em Start no MySQL do XAMPP.';
    elseif (stripos($m, 'could not find driver') !== false) echo 'ative a extensão pdo_mysql no php.ini.';
    else echo 'envie um print desta tela.';
    echo '</p>';
}
