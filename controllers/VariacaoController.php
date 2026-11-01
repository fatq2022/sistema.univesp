<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Variacao.php';

class VariacaoController {
    private $model;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->model = new Variacao($db);
    }

    public function processRequest($method, $id = null) {
        header("Content-Type: application/json; charset=UTF-8");
        $inputJSON = file_get_contents("php://input");
        $input = json_decode($inputJSON, true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        if ($method === 'POST' && isset($input['_method'])) {
            $method = strtoupper($input['_method']);
        }

        try {
            switch ($method) {
                /* ================= LISTAR / BUSCAR ================= */
                case 'GET':
                    if ($id) {
                        $data = $this->model->buscar($id);
                        if (!$data) {
                            http_response_code(404);
                            echo json_encode(["erro" => "Não encontrado"]);
                            return;
                        }
                        echo json_encode($data);
                    } else {
                        // 🔥 NOVO: Captura se deve filtrar apenas ativos
                        $apenasAtivos = isset($_GET['somenteAtivos']) && $_GET['somenteAtivos'] == '1';
                        echo json_encode($this->model->listar($apenasAtivos));
                    }
                    break;


                    if ($id) {
                        $data = $this->model->buscar($id);
                        if (!$data) {
                            http_response_code(404);
                            echo json_encode(["erro" => "Não encontrado"]);
                            return;
                        }
                        echo json_encode($data);
                        return;
                    }
                    echo json_encode($this->model->listar());
                    break;

                case 'POST':
                    if (empty($input['descricao'])) {
                        http_response_code(400);
                        echo json_encode(["erro" => "Descrição obrigatória"]);
                        return;
                    }
                    $ok = $this->model->criar($input['descricao']);
                    if ($ok) {
                        echo json_encode([
                            "mensagem" => "Criado com sucesso",
                            "id" => $ok
                        ]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["erro" => "Erro ao criar"]);
                    }
                    break;

                case 'PUT':
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(["erro" => "ID não informado"]);
                        return;
                    }

                    // 🔥 NOVO: Caso venha do Switch (Ativar/Desativar)
                    if (isset($input['somenteAtivo'])) {
                        $ok = $this->model->atualizarAtivo($id, $input['ativo']);
                        echo json_encode($ok ? ["mensagem" => "Status atualizado"] : ["erro" => "Falha ao atualizar status"]);
                        return;
                    }

                    // Caso venha do formulário de edição normal
                    if (empty($input['descricao'])) {
                        http_response_code(400);
                        echo json_encode(["erro" => "Descrição obrigatória"]);
                        return;
                    }

                    $ok = $this->model->atualizar($id, $input['descricao']);
                    if ($ok) {
                        echo json_encode(["mensagem" => "Atualizado com sucesso"]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["erro" => "Erro ao atualizar"]);
                    }
                    break;

                case 'DELETE':
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(["erro" => "ID não informado"]);
                        return;
                    }
                    try {
                        $ok = $this->model->deletar($id);
                        if ($ok) {
                            echo json_encode(["mensagem" => "Variação excluída com sucesso"]);
                        } else {
                            http_response_code(404);
                            echo json_encode(["erro" => "Registro não encontrado"]);
                        }
                    } catch (PDOException $e) {
                        if ($e->getCode() == "23000" || strpos($e->getMessage(), '1451') !== false) {
                            http_response_code(400);
                            echo json_encode([
                                "erro" => "Esta variação está vinculada a pedidos e não pode ser excluída."
                            ]);
                        } else {
                            throw $e;
                        }
                    }
                    break;

                default:
                    http_response_code(405);
                    echo json_encode(["erro" => "Método não permitido"]);
                    break;
            }
        } catch (PDOException $e) {
            $codigo = $e->errorInfo[1] ?? null;
            if ($codigo == 1062) {
                preg_match("/for key '(.+?)'/", $e->getMessage(), $matches);
                $campo = $matches[1] ?? "campo único";
                http_response_code(409);
                echo json_encode([
                    "erro" => "Já existe um registro com esse valor ($campo)"
                ]);
                return;
            }
            error_log("Erro de banco: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "erro" => "Erro interno no servidor."
            ]);
        }
    }
}
