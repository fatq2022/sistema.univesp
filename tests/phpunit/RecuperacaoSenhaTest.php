<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Expiração do token e fluxo de recuperação de ponta a ponta.
 * Roda no MySQL (o SQL do sistema usa NOW()/DATE_ADD). Usa uma tabela TEMPORARY "usuario",
 * que só existe nesta conexão e "esconde" a real: nenhum dado do sistema é alterado.
 *
 * Para ligar:  TEST_DB_DSN="mysql:host=localhost;dbname=sistema;charset=utf8mb4" vendor/bin/phpunit
 * Sem TEST_DB_DSN, estes testes aparecem como "skipped" (pulados), não como aprovados.
 */
final class RecuperacaoSenhaTest extends TestCase
{
    private PDO $db;
    private Usuario $usuarios;

    protected function setUp(): void
    {
        $dsn = getenv('TEST_DB_DSN');
        if (!$dsn) {
            $this->markTestSkipped('Defina TEST_DB_DSN (MySQL) para rodar os testes de expiração e recuperação.');
        }

        $this->db = new PDO($dsn, (string) getenv('TEST_DB_USER'), (string) getenv('TEST_DB_PASS'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->db->exec('DROP TEMPORARY TABLE IF EXISTS usuario');
        $this->db->exec('CREATE TEMPORARY TABLE usuario (
            id_usuario INT AUTO_INCREMENT PRIMARY KEY,
            usuario VARCHAR(50) NOT NULL,
            nome VARCHAR(150) NOT NULL,
            email VARCHAR(150) DEFAULT NULL,
            senha VARCHAR(255) NOT NULL,
            reset_token VARCHAR(255) DEFAULT NULL,
            token_expires_at DATETIME DEFAULT NULL)');
        $this->db->prepare('INSERT INTO usuario (usuario, nome, email, senha) VALUES (?, ?, ?, ?)')
            ->execute(['teste', 'Teste', 'teste@example.com', password_hash('Antiga#1', PASSWORD_DEFAULT)]);

        $this->usuarios = new Usuario($this->db);
    }

    private function idUsuario(): int
    {
        return (int) $this->db->query("SELECT id_usuario FROM usuario WHERE usuario = 'teste'")->fetchColumn();
    }

    public function testTokenRecemGeradoEhValido(): void
    {
        [$token, $hash] = RecuperacaoController::gerarToken();
        $this->usuarios->salvarTokenRecuperacao($this->idUsuario(), $hash, RecuperacaoController::VALIDADE_MINUTOS);

        $achado = $this->usuarios->buscaPorTokenValido(RecuperacaoController::hashToken($token));
        $this->assertNotFalse($achado);
        $this->assertSame('teste', $achado['usuario']);
    }

    public function testTokenExpiradoNaoEhAceito(): void
    {
        [$token, $hash] = RecuperacaoController::gerarToken();
        $this->usuarios->salvarTokenRecuperacao($this->idUsuario(), $hash, RecuperacaoController::VALIDADE_MINUTOS);
        // simula a passagem do tempo: vence 1 minuto atrás
        $this->db->exec('UPDATE usuario SET token_expires_at = DATE_SUB(NOW(), INTERVAL 1 MINUTE)');

        $this->assertFalse($this->usuarios->buscaPorTokenValido(RecuperacaoController::hashToken($token)));
        $this->assertFalse(
            $this->usuarios->redefinirSenhaComToken($this->idUsuario(), $hash, password_hash('Nova#123', PASSWORD_DEFAULT)),
            'não pode trocar a senha com token vencido'
        );
    }

    public function testTokenExpiraDepoisDeUmaHora(): void
    {
        [, $hash] = RecuperacaoController::gerarToken();
        $this->usuarios->salvarTokenRecuperacao($this->idUsuario(), $hash, RecuperacaoController::VALIDADE_MINUTOS);

        $minutos = (int) $this->db->query(
            'SELECT TIMESTAMPDIFF(MINUTE, NOW(), token_expires_at) FROM usuario'
        )->fetchColumn();
        $this->assertGreaterThanOrEqual(59, $minutos);
        $this->assertLessThanOrEqual(60, $minutos);
    }

    public function testRecuperacaoCompletaTrocaASenhaEInvalidaOToken(): void
    {
        [$token, $hash] = RecuperacaoController::gerarToken();
        $id = $this->idUsuario();

        // 1) o e-mail informado é encontrado e o token é gravado
        $usuario = $this->usuarios->buscaPorEmail('teste@example.com');
        $this->assertSame($id, (int) $usuario['id_usuario']);
        $this->usuarios->salvarTokenRecuperacao($id, $hash, RecuperacaoController::VALIDADE_MINUTOS);

        // 2) a nova senha precisa ser forte
        $this->assertNotNull(RecuperacaoController::validarNovaSenha('fraca', 'fraca'));
        $this->assertNull(RecuperacaoController::validarNovaSenha('Nova#123', 'Nova#123'));

        // 3) redefine com o token
        $this->assertTrue($this->usuarios->redefinirSenhaComToken($id, RecuperacaoController::hashToken($token), password_hash('Nova#123', PASSWORD_DEFAULT)));
        $linha = $this->usuarios->buscaUsuario('teste');
        $this->assertTrue(password_verify('Nova#123', $linha['senha']));
        $this->assertFalse(password_verify('Antiga#1', $linha['senha']));

        // 4) o mesmo link não funciona de novo (uso único)
        $this->assertFalse($this->usuarios->buscaPorTokenValido(RecuperacaoController::hashToken($token)));
        $this->assertFalse($this->usuarios->redefinirSenhaComToken($id, RecuperacaoController::hashToken($token), password_hash('Outra#123', PASSWORD_DEFAULT)));
    }

    public function testEmailDesconhecidoNaoAchaUsuario(): void
    {
        $this->assertFalse($this->usuarios->buscaPorEmail('ninguem@example.com'));
    }

    public function testAlterarSenhaPeloUsuarioTrocaOHash(): void
    {
        $id = $this->idUsuario();
        $this->assertTrue($this->usuarios->atualizarSenha($id, password_hash('Nova#123', PASSWORD_DEFAULT)));
        $this->assertTrue(password_verify('Nova#123', $this->usuarios->buscaUsuario('teste')['senha']));
    }
}
