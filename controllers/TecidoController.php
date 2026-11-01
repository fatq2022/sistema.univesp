<?php
// controllers/TecidoController.php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Tecido.php';

class TecidoController {
    private $model;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->model = new Tecido($db);
    }

    public function processRequest($method, $id = null) {
        header("Content-Type: application/json; charset=UTF-8");
        $inputJSON = file_get_contents("php://input");
        $input = json_decode($inputJSON, true) ?? $_POST;

        if ($method === 'POST' && isset($input['_method'])) {
            $method = strtoupper($input['_method']);
        }

        try {
            switch ($method) {
                case 'GET':
                    if ($id) {
                        $item = $this->model->buscar($id);
                        if ($item) {
                            echo json_encode($item);
                        } else {
                            http_response_code(404);
                            echo json_encode(["erro" => "Tecido não encontrado"]);
                        }
                    } else {
                        // Retorna a listagem ignorando parâmetros de ativos
                        echo json_encode($this->model->listar());
                    }
                    break;

                case 'POST':
                    if (empty($input['descricao'])) {
                        http_response_code(400);
                        echo json_encode(["erro" => "Descrição obrigatória"]);
                        return;
                    }
                    $ok = $this->model->criar($input['descricao']);
                    if ($ok) {
                        echo json_encode(["mensagem" => "Criado com sucesso", "id" => $ok]);
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
                    $ok = $this->model->deletar($id);
                    if ($ok) {
                        echo json_encode(["mensagem" => "Deletado com sucesso"]);
                    } else {
                        http_response_code(404);
                        echo json_encode(["erro" => "Registro não encontrado"]);
                    }
                    break;

                default:
                    http_response_code(405);
                    echo json_encode(["erro" => "Método não permitido"]);
                    break;
            }
        } catch (PDOException $e) {
            $codigoMy = $e->errorInfo[1] ?? null;
            if ($method === DELETE && $e->getCode() === 23000) {
                http_response_code(409);
                echo json_encode(["erro" => "Este item está vinculado a pedidos e não pode ser excluído."]);
            } elseif ($codigoMy == 1062) {
                http_response_code(409);
                echo json_encode(["erro" => "Já existe um registro com essa descrição."]);
            } else {
                error_log("Erro de banco em " . __CLASS__ . ": " . $e->getMessage());
                http_response_code(500);
                echo json_encode(["erro" => "Erro interno no servidor."]);
            }
        } catch (Exception $e) {
            error_log("Erro em " . __CLASS__ . ": " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["erro" => "Erro interno no servidor."]);
        }
    }
}