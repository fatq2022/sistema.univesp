<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/models/Usuario.php';
require_once dirname(__DIR__) . '/helpers/Senha.php';

/**
 * POST api/alterar-senha  {usuario, senha_atual, nova_senha, confirmar}
 * Tela pública (aberta a partir do login). Depois de trocar, encerra qualquer sessão
 * e o front pede novo login com a nova senha.
 */
class AlterarSenhaController
{
    public const MAX_TENTATIVAS = 5;
    public const BLOQUEIO_SEGUNDOS = 300;

    /** Regras que não precisam de banco. Devolve a mensagem de erro ou null. */
    public static function validarCampos(string $usuario, string $atual, string $nova, string $confirmar): ?string
    {
        if ($usuario === '' || $atual === '' || $nova === '' || $confirmar === '') {
            return 'Preencha todos os campos.';
        }
        $erro = Senha::validar($nova);
        if ($erro !== null) {
            return $erro;
        }
        if ($nova !== $confirmar) {
            return 'A confirmação não confere com a nova senha.';
        }
        if ($nova === $atual) {
            return 'A nova senha precisa ser diferente da senha atual.';
        }

        return null;
    }

    public function alterar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Limite de tentativas por sessão (a senha atual não pode ser adivinhada aqui)
        $ate = (int) ($_SESSION['alterar_bloqueio_ate'] ?? 0);
        if ($ate > time()) {
            $this->responder(429, false, 'Muitas tentativas. Aguarde alguns minutos e tente de novo.');
            return;
        }

        $dados = json_decode((string) file_get_contents('php://input'), true);
        $dados = is_array($dados) ? $dados : [];
        $usuario = trim((string) ($dados['usuario'] ?? ''));
        $atual = (string) ($dados['senha_atual'] ?? '');
        $nova = (string) ($dados['nova_senha'] ?? '');
        $confirmar = (string) ($dados['confirmar'] ?? '');

        $erro = self::validarCampos($usuario, $atual, $nova, $confirmar);
        if ($erro !== null) {
            $this->responder(400, false, $erro);
            return;
        }

        try {
            $modelo = new Usuario();
            $user = $modelo->buscaUsuario($usuario);

            if (!$user || !password_verify($atual, (string) $user['senha'])) {
                $n = (int) ($_SESSION['alterar_tentativas'] ?? 0) + 1;
                $_SESSION['alterar_tentativas'] = $n;
                if ($n >= self::MAX_TENTATIVAS) {
                    $_SESSION['alterar_bloqueio_ate'] = time() + self::BLOQUEIO_SEGUNDOS;
                    $_SESSION['alterar_tentativas'] = 0;
                }
                $this->responder(401, false, 'Usuário ou senha atual inválidos.');
                return;
            }

            if (!$modelo->atualizarSenha((int) $user['id_usuario'], password_hash($nova, PASSWORD_DEFAULT))) {
                $this->responder(500, false, 'Não foi possível salvar a nova senha.');
                return;
            }
        } catch (Throwable $e) {
            error_log('Erro ao alterar senha: ' . $e->getMessage());
            $this->responder(500, false, 'Erro interno ao alterar a senha.');
            return;
        }

        // Pede novo login com a nova senha
        $_SESSION = [];
        $this->responder(200, true, 'Senha alterada com sucesso. Entre com a nova senha.');
    }

    private function responder(int $status, bool $sucesso, string $mensagem): void
    {
        http_response_code($status);
        echo json_encode(['sucesso' => $sucesso, 'mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);
    }
}
