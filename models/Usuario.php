<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/Database.php';

class Usuario
{
    private PDO $conn;

    /** @param PDO|null $conn conexão pronta (usada nos testes); sem ela, conecta pelo config.json */
    public function __construct(?PDO $conn = null)
    {
        $this->conn = $conn ?? (new Database())->connect();
    }

    public function buscaUsuario(string $usuario): array|false
    {
        $stmt = $this->conn->prepare(
            'SELECT * FROM usuario WHERE usuario = ? LIMIT 1'
        );
        $stmt->execute([$usuario]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* =========================
       RECUPERAÇÃO DE ACESSO
       (o token em texto puro nunca é gravado: só o hash dele)
    ========================= */

    /**
     * Cria as colunas da recuperação de senha se o banco ainda for da versão antiga
     * (equivale a rodar migracoes/001_recuperacao_senha.sql). Só mexe no que falta.
     */
    public function garantirEstruturaRecuperacao(): void
    {
        $stmt = $this->conn->query(
            "SELECT LOWER(COLUMN_NAME) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario'"
        );
        $colunas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('email', $colunas, true)) {
            $this->conn->exec('ALTER TABLE usuario ADD COLUMN email VARCHAR(150) DEFAULT NULL AFTER nome');
            try {
                $this->conn->exec('ALTER TABLE usuario ADD UNIQUE KEY uq_usuario_email (email)');
            } catch (Throwable $e) {
                error_log('Aviso: não foi possível criar o índice único de e-mail: ' . $e->getMessage());
            }
        }
        if (!in_array('reset_token', $colunas, true)) {
            $this->conn->exec('ALTER TABLE usuario ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL');
        }
        if (!in_array('token_expires_at', $colunas, true)) {
            $this->conn->exec('ALTER TABLE usuario ADD COLUMN token_expires_at DATETIME DEFAULT NULL');
        }
    }

    public function buscaPorEmail(string $email): array|false
    {
        $stmt = $this->conn->prepare(
            'SELECT id_usuario, usuario, nome, email FROM usuario WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Já foi gerado um link para este usuário há menos de 1 minuto?
     * Usa o relógio do próprio MySQL, então não depende do fuso do PHP.
     */
    public function recuperacaoRecente(int $idUsuario, int $validadeMinutos): bool
    {
        $limite = max(1, $validadeMinutos - 1);
        $stmt = $this->conn->prepare(
            "SELECT 1 FROM usuario
              WHERE id_usuario = ?
                AND reset_token IS NOT NULL
                AND token_expires_at > DATE_ADD(NOW(), INTERVAL {$limite} MINUTE)"
        );
        $stmt->execute([$idUsuario]);

        return (bool) $stmt->fetchColumn();
    }

    public function salvarTokenRecuperacao(int $idUsuario, string $hashToken, int $validadeMinutos): void
    {
        $minutos = max(1, $validadeMinutos);
        $stmt = $this->conn->prepare(
            "UPDATE usuario
                SET reset_token = ?, token_expires_at = DATE_ADD(NOW(), INTERVAL {$minutos} MINUTE)
              WHERE id_usuario = ?"
        );
        $stmt->execute([$hashToken, $idUsuario]);
    }

    public function buscaPorTokenValido(string $hashToken): array|false
    {
        $stmt = $this->conn->prepare(
            'SELECT id_usuario, usuario, nome FROM usuario
              WHERE reset_token = ? AND token_expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$hashToken]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Troca a senha e invalida o token no mesmo comando (uso único).
     * Se dois cliques chegarem juntos, só um deles encontra o token e altera a linha.
     */
    public function redefinirSenhaComToken(int $idUsuario, string $hashToken, string $novoHashSenha): bool
    {
        $stmt = $this->conn->prepare(
            'UPDATE usuario
                SET senha = ?, reset_token = NULL, token_expires_at = NULL
              WHERE id_usuario = ? AND reset_token = ? AND token_expires_at > NOW()'
        );
        $stmt->execute([$novoHashSenha, $idUsuario, $hashToken]);

        return $stmt->rowCount() === 1;
    }

    /** Troca a senha (usada em "Alterar a senha") e descarta qualquer token de recuperação pendente. */
    public function atualizarSenha(int $idUsuario, string $novoHashSenha): bool
    {
        $stmt = $this->conn->prepare(
            'UPDATE usuario SET senha = ?, reset_token = NULL, token_expires_at = NULL WHERE id_usuario = ?'
        );
        $stmt->execute([$novoHashSenha, $idUsuario]);

        return $stmt->rowCount() === 1;
    }
}
