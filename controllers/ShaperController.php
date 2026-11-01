<?php
// controllers/ShaperController.php

require_once dirname(__DIR__) . '/config/Database.php';

class ShaperController {
    private $conn;
    private $table_name = "shaper";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function processRequest($method, $id = null) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->buscar($id);
                } else {
                    $this->listar();
                }
                break;

            case 'POST':
                if ($id) {
                    // POST com id: edita (se vier nome) ou alterna ativo/inativo
                    $dados = $this->lerCorpo();
                    if (array_key_exists('nome', $dados) && empty($dados['somenteAtivo'])) {
                        $this->atualizar($id, $dados);
                    } else {
                        $this->alternarAtivo($id, $dados);
                    }
                } else {
                    $this->criar();
                }
                break;

            case 'PUT':
            case 'PATCH':
                if (!$id) {
                    $this->erro(400, 'Informe o id do shaper.');
                    break;
                }
                $dados = $this->lerCorpo();
                if (array_key_exists('nome', $dados) && empty($dados['somenteAtivo'])) {
                    $this->atualizar($id, $dados);
                } else {
                    $this->alternarAtivo($id, $dados);
                }
                break;

            case 'DELETE':
                if (!$id) {
                    $this->erro(400, 'Informe o id do shaper.');
                    break;
                }
                $this->excluir($id);
                break;

            default:
                $this->erro(405, 'Método não permitido.');
        }
    }

    // Lê o corpo da requisição (JSON ou formulário)
    private function lerCorpo() {
        $bruto = file_get_contents('php://input');
        $dados = json_decode($bruto ?: '', true);
        if (!is_array($dados)) {
            $dados = $_POST;
        }
        return is_array($dados) ? $dados : [];
    }

    private function erro($codigo, $mensagem) {
        http_response_code($codigo);
        echo json_encode(['sucesso' => false, 'mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);
    }

    public function listar() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " ORDER BY nome ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $shapers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($shapers ?: []);
        } catch (Throwable $e) {
            error_log('Erro ao listar shapers: ' . $e->getMessage());
            $this->erro(500, 'Erro ao listar shapers.');
        }
    }

    public function buscar($id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id_shaper = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $shaper = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($shaper) {
                echo json_encode($shaper);
            } else {
                $this->erro(404, 'Shaper não encontrado.');
            }
        } catch (Throwable $e) {
            error_log('Erro ao buscar shaper: ' . $e->getMessage());
            $this->erro(500, 'Erro ao buscar shaper.');
        }
    }

    public function criar() {
        try {
            $dados = $this->lerCorpo();
            $nome = trim((string)($dados['nome'] ?? ''));
            if ($nome === '') {
                $this->erro(400, 'O nome é obrigatório.');
                return;
            }
            $telefone = trim((string)($dados['telefone'] ?? ''));
            $email = trim((string)($dados['email'] ?? ''));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->erro(400, 'Informe um e-mail válido.');
                return;
            }

            $query = "INSERT INTO " . $this->table_name . " (nome, telefone, email, ativo) VALUES (?, ?, ?, 1)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$nome, $telefone, $email]);

            http_response_code(201);
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Shaper cadastrado com sucesso.',
                'id_shaper' => (int)$this->conn->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('Erro ao cadastrar shaper: ' . $e->getMessage());
            $this->erro(500, 'Erro ao cadastrar shaper.');
        }
    }

    public function atualizar($id, $dados) {
        try {
            $nome = trim((string)($dados['nome'] ?? ''));
            if ($nome === '') {
                $this->erro(400, 'O nome é obrigatório.');
                return;
            }
            $telefone = trim((string)($dados['telefone'] ?? ''));
            $email = trim((string)($dados['email'] ?? ''));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->erro(400, 'Informe um e-mail válido.');
                return;
            }

            $query = "UPDATE " . $this->table_name . " SET nome = ?, telefone = ?, email = ? WHERE id_shaper = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$nome, $telefone, $email, $id]);

            echo json_encode(['sucesso' => true, 'mensagem' => 'Shaper atualizado com sucesso.'], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('Erro ao atualizar shaper: ' . $e->getMessage());
            $this->erro(500, 'Erro ao atualizar shaper.');
        }
    }

    public function alternarAtivo($id, $dados = []) {
        try {
            if (isset($dados['ativo'])) {
                // Usa o valor enviado pelo front-end
                $novo = filter_var($dados['ativo'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                $stmt = $this->conn->prepare("UPDATE " . $this->table_name . " SET ativo = ? WHERE id_shaper = ?");
                $stmt->execute([$novo, $id]);
            } else {
                // Sem valor: inverte o atual
                $stmt = $this->conn->prepare("UPDATE " . $this->table_name . " SET ativo = IF(ativo = 1, 0, 1) WHERE id_shaper = ?");
                $stmt->execute([$id]);
            }

            $stmt = $this->conn->prepare("SELECT ativo FROM " . $this->table_name . " WHERE id_shaper = ?");
            $stmt->execute([$id]);
            $linha = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$linha) {
                $this->erro(404, 'Shaper não encontrado.');
                return;
            }

            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Status alterado com sucesso.',
                'ativo' => (int)$linha['ativo']
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('Erro ao alterar status: ' . $e->getMessage());
            $this->erro(500, 'Erro ao alterar status.');
        }
    }

    public function excluir($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM " . $this->table_name . " WHERE id_shaper = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                $this->erro(404, 'Shaper não encontrado.');
                return;
            }
            echo json_encode(['sucesso' => true, 'mensagem' => 'Shaper excluído com sucesso.'], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            // Código 23000 = restrição de chave estrangeira (shaper usado em pedidos)
            if ($e->getCode() === '23000') {
                $this->erro(409, 'Não é possível excluir: este shaper está vinculado a pedidos. Desative-o em vez de excluir.');
            } else {
                error_log('Erro ao excluir shaper: ' . $e->getMessage());
                $this->erro(500, 'Erro ao excluir shaper.');
            }
        } catch (Throwable $e) {
            error_log('Erro ao excluir shaper: ' . $e->getMessage());
            $this->erro(500, 'Erro ao excluir shaper.');
        }
    }
}