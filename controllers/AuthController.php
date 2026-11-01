<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/models/Usuario.php';

class AuthController
{
    public function login(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $data = json_decode((string) file_get_contents('php://input'), true);

            if (!is_array($data)) {
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Dados de login inválidos.'
                ]);
                return;
            }

            $usuario = trim((string) ($data['usuario'] ?? ''));
            $senha = (string) ($data['senha'] ?? '');

            if ($usuario === '' || $senha === '') {
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Informe o usuário e a senha.'
                ]);
                return;
            }

            $usuarioModel = new Usuario();
            $user = $usuarioModel->buscaUsuario($usuario);

            if ($user && isset($user['senha']) && password_verify($senha, $user['senha'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = $user['usuario'];

                http_response_code(200);
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'Login realizado com sucesso.'
                ]);
                return;
            }

            http_response_code(401);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Usuário ou senha inválidos.'
            ]);
        } catch (Throwable $e) {
            error_log('Erro no login: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Erro interno ao processar o login.'
            ]);
        }
    }

    public function logout(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Logout realizado com sucesso.'
        ]);
    }
}
