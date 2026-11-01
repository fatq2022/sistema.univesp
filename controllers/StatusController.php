<?php
// controllers/StatusController.php

require_once dirname(__DIR__) . '/config/Database.php';

class StatusController {
    private $conn;
    private $table_name = "status";
    private $colunasCache = null;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function processRequest($method, $id = null) {
        switch ($method) {
            case 'GET':
                if ($id === 'proximo') {
                    $this->proximo();
                } elseif ($id) {
                    $this->buscar($id);
                } else {
                    $this->listar();
                }
                break;

            case 'POST':
            case 'PUT':
            case 'PATCH':
                $dados = $this->lerCorpo();
                if (!$id) {
                    if ($this->ehReordenacao($dados)) {
                        $this->reordenar($dados);
                    } else {
                        $this->criar($dados);
                    }
                } elseif (!empty($dados['somenteAtivo'])) {
                    $this->alternarAtivo($id, $dados);
                } elseif ($this->campoDescricao() !== null && $this->temDescricao($dados)) {
                    $this->atualizar($id, $dados);
                } else {
                    $this->alternarAtivo($id, $dados);
                }
                break;

            case 'DELETE':
                if (!$id) {
                    $this->erro(400, 'Informe o id do status.');
                    break;
                }
                $this->excluir($id);
                break;

            default:
                $this->erro(405, 'Método não permitido.');
        }
    }

    // ---------- utilitários ----------

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

    private function colunas() {
        if ($this->colunasCache === null) {
            $stmt = $this->conn->query("SHOW COLUMNS FROM " . $this->table_name);
            $this->colunasCache = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        }
        return $this->colunasCache;
    }

    private function temColuna($nome) {
        return in_array($nome, $this->colunas(), true);
    }

    private function campoDescricao() {
        foreach (['descricao', 'nome', 'titulo', 'status'] as $c) {
            if ($this->temColuna($c)) {
                return $c;
            }
        }
        return null;
    }

    private function temDescricao($dados) {
        return array_key_exists('descricao', $dados) || array_key_exists('nome', $dados);
    }

    private function valorDescricao($dados) {
        return trim((string)($dados['descricao'] ?? $dados['nome'] ?? ''));
    }

    private function ehReordenacao($dados) {
        foreach (['ids', 'ordem', 'itens'] as $k) {
            if (isset($dados[$k]) && is_array($dados[$k])) {
                return true;
            }
        }
        return !empty($dados) && array_keys($dados) === range(0, count($dados) - 1);
    }

    // ---------- operações ----------

    // GET /api/status/proximo?id_atual=N  -> próximo status ativo na ordem (ou {} se não houver)
    private function proximo() {
        try {
            $atual = (isset($_GET['id_atual']) && ctype_digit((string) $_GET['id_atual'])) ? (int) $_GET['id_atual'] : 0;
            $campoDesc = $this->campoDescricao();
            $temOrdem = $this->temColuna('ordem');
            $filtroAtivo = $this->temColuna('ativo') ? ' AND ativo = 1' : '';

            $ordemAtual = 0;
            if ($atual > 0 && $temOrdem) {
                $st = $this->conn->prepare("SELECT ordem FROM " . $this->table_name . " WHERE id_status = ?");
                $st->execute([$atual]);
                $ordemAtual = (int) $st->fetchColumn();
            }

            if ($temOrdem) {
                $sql = "SELECT * FROM " . $this->table_name
                    . " WHERE (ordem > ? OR (ordem = ? AND id_status > ?))" . $filtroAtivo
                    . " ORDER BY ordem ASC, id_status ASC LIMIT 1";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$ordemAtual, $ordemAtual, $atual]);
            } else {
                $sql = "SELECT * FROM " . $this->table_name
                    . " WHERE id_status > ?" . $filtroAtivo . " ORDER BY id_status ASC LIMIT 1";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$atual]);
            }

            $linha = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($linha && $campoDesc !== null && $campoDesc !== 'descricao') {
                $linha['descricao'] = $linha[$campoDesc];
            }
            echo json_encode($linha ?: new stdClass(), JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('Erro ao buscar próximo status: ' . $e->getMessage());
            $this->erro(500, 'Erro ao buscar o próximo status.');
        }
    }

    public function listar() {
        try {
            $ordem = $this->temColuna('ordem') ? 'ordem ASC, id_status ASC' : 'id_status ASC';
            $stmt = $this->conn->prepare("SELECT * FROM " . $this->table_name . " ORDER BY " . $ordem);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        } catch (Throwable $e) {
            error_log('Erro ao listar status: ' . $e->getMessage());
            $this->erro(500, 'Erro ao listar status.');
        }
    }

    public function buscar($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM " . $this->table_name . " WHERE id_status = ?");
            $stmt->execute([$id]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($status) {
                echo json_encode($status);
            } else {
                $this->erro(404, 'Status não encontrado.');
            }
        } catch (Throwable $e) {
            error_log('Erro ao buscar status: ' . $e->getMessage());
            $this->erro(500, 'Erro ao buscar status.');
        }
    }

    public function criar($dados) {
        try {
            $campo = $this->campoDescricao();
            if ($campo === null) {
                $this->erro(500, 'A tabela status não tem coluna de descrição.');
                return;
            }
            $descricao = $this->valorDescricao($dados);
            if ($descricao === '') {
                $this->erro(400, 'A descrição é obrigatória.');
                return;
            }

            $cols = [$campo];
            $vals = [$descricao];

            if ($this->temColuna('ativo')) {
                $cols[] = 'ativo';
                $vals[] = 1;
            }
            if ($this->temColuna('ordem')) {
                $max = $this->conn->query("SELECT COALESCE(MAX(ordem), 0) FROM " . $this->table_name)->fetchColumn();
                $cols[] = 'ordem';
                $vals[] = (int)$max + 1;
            }

            $marcas = implode(', ', array_fill(0, count($cols), '?'));
            $sql = "INSERT INTO " . $this->table_name . " (" . implode(', ', $cols) . ") VALUES (" . $marcas . ")";
            $this->conn->prepare($sql)->execute($vals);

            http_response_code(201);
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Status cadastrado com sucesso.',
                'id_status' => (int)$this->conn->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('Erro ao cadastrar status: ' . $e->getMessage());
            $this->erro(500, 'Erro ao cadastrar status.');
        }
    }

    public function atualizar($id, $dados) {
        try {
            $campo = $this->campoDescricao();
            $descricao = $this->valorDescricao($dados);
            if ($descricao === '') {
                $this->erro(400, 'A descrição é obrigatória.');
                return;
            }
            $sets = [$campo . ' = ?'];
            $vals = [$descricao];
            if ($this->temColuna('ordem') && isset($dados['ordem']) && ctype_digit((string) $dados['ordem'])) {
                $sets[] = 'ordem = ?';
                $vals[] = (int) $dados['ordem'];
            }
            $vals[] = $id;
            $stmt = $this->conn->prepare("UPDATE " . $this->table_name . " SET " . implode(', ', $sets) . " WHERE id_status = ?");
            $stmt->execute($vals);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Status atualizado com sucesso.'], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('Erro ao atualizar status: ' . $e->getMessage());
            $this->erro(500, 'Erro ao atualizar status.');
        }
    }

    public function alternarAtivo($id, $dados = []) {
        try {
            if (!$this->temColuna('ativo')) {
                $this->erro(500, "A tabela status não tem a coluna 'ativo'.");
                return;
            }
            if (isset($dados['ativo'])) {
                $novo = filter_var($dados['ativo'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                $this->conn->prepare("UPDATE " . $this->table_name . " SET ativo = ? WHERE id_status = ?")->execute([$novo, $id]);
            } else {
                $this->conn->prepare("UPDATE " . $this->table_name . " SET ativo = IF(ativo = 1, 0, 1) WHERE id_status = ?")->execute([$id]);
            }

            $stmt = $this->conn->prepare("SELECT ativo FROM " . $this->table_name . " WHERE id_status = ?");
            $stmt->execute([$id]);
            $linha = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$linha) {
                $this->erro(404, 'Status não encontrado.');
                return;
            }
            echo json_encode(['sucesso' => true, 'mensagem' => 'Status alterado com sucesso.', 'ativo' => (int)$linha['ativo']], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('Erro ao alterar status: ' . $e->getMessage());
            $this->erro(500, 'Erro ao alterar status.');
        }
    }

    public function reordenar($dados) {
        try {
            if (!$this->temColuna('ordem')) {
                $this->erro(500, "A tabela status não tem a coluna 'ordem'.");
                return;
            }
            $lista = $dados['ids'] ?? $dados['ordem'] ?? $dados['itens'] ?? $dados;

            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("UPDATE " . $this->table_name . " SET ordem = ? WHERE id_status = ?");
            $pos = 1;
            foreach ($lista as $item) {
                $idItem = is_array($item) ? ($item['id_status'] ?? $item['id'] ?? null) : $item;
                if ($idItem !== null && ctype_digit((string)$idItem)) {
                    $stmt->execute([$pos++, (int)$idItem]);
                }
            }
            $this->conn->commit();

            echo json_encode(['sucesso' => true, 'mensagem' => 'Ordem atualizada com sucesso.'], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('Erro ao reordenar: ' . $e->getMessage());
            $this->erro(500, 'Erro ao reordenar.');
        }
    }

    public function excluir($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM " . $this->table_name . " WHERE id_status = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                $this->erro(404, 'Status não encontrado.');
                return;
            }
            echo json_encode(['sucesso' => true, 'mensagem' => 'Status excluído com sucesso.'], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->erro(409, 'Não é possível excluir: este status está em uso em pedidos. Desative-o em vez de excluir.');
            } else {
                error_log('Erro ao excluir status: ' . $e->getMessage());
            $this->erro(500, 'Erro ao excluir status.');
            }
        } catch (Throwable $e) {
            error_log('Erro ao excluir status: ' . $e->getMessage());
            $this->erro(500, 'Erro ao excluir status.');
        }
    }
}