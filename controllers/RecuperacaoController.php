<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/models/Usuario.php';
require_once dirname(__DIR__) . '/helpers/Mailer.php';
require_once dirname(__DIR__) . '/helpers/Logger.php';
require_once dirname(__DIR__) . '/helpers/Senha.php';

/**
 * Recuperação de acesso:
 *   1) POST api/recuperar-senha  {email}                     -> envia o link por e-mail
 *   2) GET  api/redefinir-senha?token=...                    -> confere se o link ainda vale
 *   3) POST api/redefinir-senha  {token, senha, confirmar}   -> grava a nova senha
 */
class RecuperacaoController
{
    public const VALIDADE_MINUTOS = 60;
    public const SENHA_MINIMA = Senha::MINIMO;

    /* ---------- regras puras (testáveis sem banco) ---------- */

    /** @return array{0:string,1:string} [token para o e-mail, hash para o banco] */
    public static function gerarToken(): array
    {
        $token = bin2hex(random_bytes(32));

        return [$token, self::hashToken($token)];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function tokenComFormatoValido(string $token): bool
    {
        return preg_match('/^[a-f0-9]{64}$/', $token) === 1;
    }

    /** Devolve a mensagem de erro, ou null se a senha for aceitável. */
    public static function validarNovaSenha(string $senha, string $confirmar): ?string
    {
        $erro = Senha::validar($senha);
        if ($erro !== null) {
            return $erro;
        }
        if ($senha !== $confirmar) {
            return 'A confirmação não confere com a senha.';
        }

        return null;
    }

    /**
     * "Modo teste" (config email.modo_teste = true): só vale quando a requisição vem do próprio
     * computador (127.0.0.1 / ::1). Nesse caso a tela mostra o link e o diagnóstico em vez de
     * depender do envio de e-mail. Em um servidor de verdade, a requisição não vem de loopback,
     * então o modo teste nunca aparece, mesmo que alguém esqueça a opção ligada.
     */
    public static function modoTesteAtivo(array $config, array $servidor): bool
    {
        if (empty($config['email']['modo_teste'])) {
            return false;
        }

        return in_array((string) ($servidor['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true);
    }

    /**
     * Monta o link do e-mail. Prefere "app.url_publica" do config.json para não confiar no
     * cabeçalho Host da requisição (que pode ser forjado para mandar o link para outro site).
     */
    public static function montarLink(array $config, string $token, array $servidor = []): string
    {
        $origem = rtrim((string) ($config['app']['url_publica'] ?? ''), '/');

        if ($origem === '') {
            $host = (string) ($servidor['HTTP_HOST'] ?? 'localhost');
            if (preg_match('/^[A-Za-z0-9.\-]+(:\d{1,5})?$/', $host) !== 1) {
                $host = 'localhost';
            }
            $https = !empty($servidor['HTTPS']) && strtolower((string) $servidor['HTTPS']) !== 'off';
            $origem = ($https ? 'https://' : 'http://') . $host;
        }

        $base = trim((string) ($config['app']['base_path'] ?? '/sistema'), '/');

        return $origem . ($base !== '' ? '/' . $base : '') . '/redefinir-senha?token=' . $token;
    }

    /* ---------- endpoints ---------- */

    public function solicitar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $dados = $this->lerCorpo();
        $email = strtolower(trim((string) ($dados['email'] ?? '')));

        if ($email === '' || strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->responder(400, false, 'Informe um e-mail válido.');
            return;
        }

        $cfg = Mailer::configuracao();
        $teste = self::modoTesteAtivo($cfg, $_SERVER);
        $extra = [];

        try {
            $modelo = new Usuario();
            $modelo->garantirEstruturaRecuperacao();
            $usuario = $modelo->buscaPorEmail($email);

            if (!$usuario) {
                $extra['detalhe_teste'] = 'Nenhum usuário tem este e-mail no banco. Cadastre com: '
                    . "UPDATE usuario SET email = '{$email}' WHERE usuario = 'Admin';";
            } elseif ($modelo->recuperacaoRecente((int) $usuario['id_usuario'], self::VALIDADE_MINUTOS)) {
                // Evita enviar vários e-mails seguidos para o mesmo usuário
                $extra['detalhe_teste'] = 'Já foi gerado um link para este usuário há menos de 1 minuto. Aguarde um pouco e tente de novo.';
            } else {
                [$token, $hash] = self::gerarToken();
                $modelo->salvarTokenRecuperacao((int) $usuario['id_usuario'], $hash, self::VALIDADE_MINUTOS);
                $resultado = $this->enviarEmail($usuario, $token, $cfg);
                if (!$resultado['enviado']) {
                    $extra['detalhe_teste'] = Mailer::configurado($cfg)
                        ? 'O envio pela Brevo falhou. Veja o motivo no log de erros do PHP.'
                        : 'E-mail não enviado: falta brevo_api_key / remetente_email no config.json.';
                    $extra['link_teste'] = $resultado['link'];
                }
            }
        } catch (Throwable $e) {
            error_log('Erro na recuperação de senha: ' . $e->getMessage());
            try {
                Logger::error('Recuperação de senha falhou', ['erro' => $e->getMessage()]);
            } catch (Throwable $ignorado) {
                // o log extra é só conveniência
            }
            $this->responder(
                500,
                false,
                'Não foi possível processar o pedido agora. Tente novamente em instantes.',
                $teste ? ['detalhe_teste' => $e->getMessage()] : []
            );
            return;
        }

        // Mesma resposta exista o e-mail ou não: não revela quem tem cadastro.
        // Os campos *_teste só saem no modo teste e só para quem acessa do próprio computador.
        $this->responder(
            200,
            true,
            'Se este e-mail estiver cadastrado, você receberá em instantes um link para criar uma nova senha. O link vale por ' . self::VALIDADE_MINUTOS . ' minutos.',
            $teste ? $extra : []
        );
    }

    public function validar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $token = (string) ($_GET['token'] ?? '');

        if (!self::tokenComFormatoValido($token)) {
            $this->responder(400, false, 'Link inválido. Solicite uma nova recuperação de senha.');
            return;
        }

        try {
            $modelo = new Usuario();
            $modelo->garantirEstruturaRecuperacao();
            $usuario = $modelo->buscaPorTokenValido(self::hashToken($token));
        } catch (Throwable $e) {
            error_log('Erro ao validar token de recuperação: ' . $e->getMessage());
            $this->responder(500, false, 'Erro interno ao validar o link.');
            return;
        }

        if (!$usuario) {
            $this->responder(410, false, 'Este link expirou ou já foi utilizado. Solicite uma nova recuperação de senha.');
            return;
        }

        $this->responder(200, true, 'Link válido.');
    }

    public function redefinir(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $dados = $this->lerCorpo();
        $token = (string) ($dados['token'] ?? '');
        $senha = (string) ($dados['senha'] ?? '');
        $confirmar = (string) ($dados['confirmar'] ?? '');

        if (!self::tokenComFormatoValido($token)) {
            $this->responder(400, false, 'Link inválido. Solicite uma nova recuperação de senha.');
            return;
        }

        $erroSenha = self::validarNovaSenha($senha, $confirmar);
        if ($erroSenha !== null) {
            $this->responder(400, false, $erroSenha);
            return;
        }

        try {
            $modelo = new Usuario();
            $modelo->garantirEstruturaRecuperacao();
            $hashToken = self::hashToken($token);
            $usuario = $modelo->buscaPorTokenValido($hashToken);

            if (!$usuario) {
                $this->responder(410, false, 'Este link expirou ou já foi utilizado. Solicite uma nova recuperação de senha.');
                return;
            }

            $ok = $modelo->redefinirSenhaComToken(
                (int) $usuario['id_usuario'],
                $hashToken,
                password_hash($senha, PASSWORD_DEFAULT)
            );
        } catch (Throwable $e) {
            error_log('Erro ao redefinir senha: ' . $e->getMessage());
            $this->responder(500, false, 'Erro interno ao salvar a nova senha.');
            return;
        }

        if (!$ok) {
            $this->responder(410, false, 'Este link expirou ou já foi utilizado. Solicite uma nova recuperação de senha.');
            return;
        }

        $this->responder(200, true, 'Senha alterada com sucesso. Você já pode entrar com a nova senha.');
    }

    /* ---------- internos ---------- */

    /** @return array{enviado:bool,link:string} */
    private function enviarEmail(array $usuario, string $token, array $cfg): array
    {
        $link = self::montarLink($cfg, $token, $_SERVER);
        $nome = htmlspecialchars((string) ($usuario['nome'] ?? ''), ENT_QUOTES, 'UTF-8');
        $href = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        $html = "<h2>Olá, {$nome}!</h2>"
            . '<p>Recebemos um pedido para criar uma nova senha de acesso ao Sistema de Laminação.</p>'
            . "<p><a href=\"{$href}\">Clique aqui para criar a nova senha</a></p>"
            . '<p>Este link vale por ' . self::VALIDADE_MINUTOS . ' minutos e só pode ser usado uma vez.</p>'
            . '<p>Se você não fez este pedido, ignore este e-mail: sua senha continua a mesma.</p>'
            . '<p><em>Equipe de Controle de Laminação</em></p>';

        $enviado = Mailer::enviar(
            (string) $usuario['email'],
            (string) ($usuario['nome'] ?? ''),
            'Recuperação de acesso - Sistema de Laminação',
            $html
        );

        // Opcional: grava o link em logs/app.log (o .htaccess bloqueia essa pasta)
        if (!$enviado && !empty($cfg['email']['registrar_link_no_log'])) {
            Logger::info('Link de recuperação (e-mail não enviado)', [
                'usuario' => $usuario['usuario'] ?? null,
                'link' => $link,
            ]);
        }

        return ['enviado' => $enviado, 'link' => $link];
    }

    private function lerCorpo(): array
    {
        $dados = json_decode((string) file_get_contents('php://input'), true);

        return is_array($dados) ? $dados : [];
    }

    private function responder(int $status, bool $sucesso, string $mensagem, array $extra = []): void
    {
        http_response_code($status);
        echo json_encode(['sucesso' => $sucesso, 'mensagem' => $mensagem] + $extra, JSON_UNESCAPED_UNICODE);
    }
}
