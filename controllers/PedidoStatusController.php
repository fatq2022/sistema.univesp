<?php 
require_once __DIR__ . '/../config/Database.php'; 
require_once __DIR__ . '/../models/Pedidostatus.php'; 
require_once __DIR__ . '/../helpers/ProducaoService.php'; 

class PedidostatusController { 
    private $model; 
    private $producao; 

    public function __construct() { 
        $database = new Database(); 
        $db = $database->connect(); 
        $this->model = new Pedidostatus($db); 
        $this->producao = new ProducaoService($db); 
    } 

    private function idUsuarioLogado() {
        if (empty($_SESSION['user'])) { return null; }
        require_once __DIR__ . '/../models/Usuario.php';
        $u = (new Usuario())->buscaUsuario((string) $_SESSION['user']);
        return $u ? (int) $u['id_usuario'] : null;
    }

    /** Nunca deixa uma falha de prazo/e-mail impedir o registro do status. */
    private function sincronizarProducao(int $idPedido, bool $notificar): array {
        try {
            return $this->producao->sincronizar($idPedido, $notificar);
        } catch (Throwable $e) {
            error_log('Erro ao sincronizar produção do pedido ' . $idPedido . ': ' . $e->getMessage());
            return [];
        }
    }

    public function processRequest($method, $id = null) { 
        header("Content-Type: application/json; charset=UTF-8"); 
        $raw = file_get_contents("php://input"); 
        $input = json_decode($raw, true); 
        
        if (!is_array($input)) { $input = []; } 

        if ($method === 'POST' && isset($input['_method'])) { 
            $method = strtoupper($input['_method']); 
        } 

        try { 
            switch ($method) { 
                case 'GET': 
                    if (!$id) { 
                        http_response_code(400); 
                        echo json_encode(["erro" => "ID do pedido obrigatório"]); 
                        return; 
                    } 
                    echo json_encode($this->model->listarPorPedido($id)); 
                    break; 

                case 'POST': 
                    if ( empty($input['id_pedido']) || empty($input['id_status']) || empty($input['data']) ) { 
                        http_response_code(400); 
                        echo json_encode(["erro" => "Campos obrigatórios faltando"]); 
                        return; 
                    } 

                    // Captura os dados do input
                    $idPedido = (int)$input['id_pedido']; 
                    $idStatus = (int)$input['id_status']; 
                    $data     = $input['data']; 
                    $obs      = $input['observacao'] ?? null; 
                    
                    // O responsável pelo status é SEMPRE o usuário logado (não confiar no corpo da requisição)
                    $idUsuario = $this->idUsuarioLogado();
                    if ($idUsuario === null) {
                        http_response_code(401);
                        echo json_encode(["erro" => "Sessão expirada. Faça login novamente."]);
                        return;
                    }

                    // 🔥 CORREÇÃO: Passa o $idUsuario como o 3º parâmetro (conforme a ordem da tabela)
                    $ok = $this->model->criar( 
                        $idPedido, 
                        $idStatus, 
                        $idUsuario, 
                        $data, 
                        $obs 
                    ); 

                    $mensagem = $ok ? "Criado com sucesso" : "Erro ao criar"; 
                    $extra = []; 
                    if ($ok) { 
                        // Marco zero + previsão + e-mail ("Em produção") acontecem juntos 
                        $extra = $this->sincronizarProducao($idPedido, true); 
                        if (($extra['notificacao'] ?? '') === 'enviada') { 
                            $mensagem .= ". Produção iniciada: e-mail enviado ao cliente."; 
                        } elseif (($extra['notificacao'] ?? '') === 'falhou') { 
                            $mensagem .= ". Produção iniciada, mas o e-mail não pôde ser enviado (veja o log)."; 
                        } elseif (($extra['notificacao'] ?? '') === 'sem_email') { 
                            $mensagem .= ". Produção iniciada, mas o cliente não tem e-mail cadastrado."; 
                        } 
                    } 
                    echo json_encode([ "mensagem" => $mensagem ] + $extra, JSON_UNESCAPED_UNICODE); 
                    break; 

                case 'PUT': 
                    if ( !$id || empty($input['id_status']) || empty($input['data']) ) { 
                        http_response_code(400); 
                        echo json_encode(["erro" => "Dados inválidos"]); 
                        return; 
                    } 
                    // Se o seu atualizar também exigir usuário, adicione aqui
                    $ok = $this->model->atualizar( 
                        $id, 
                        $input['id_status'], 
                        $input['data'], 
                        $input['observacao'] ?? null 
                    ); 
                    if ($ok) { 
                        $reg = $this->model->buscar((int) $id); 
                        if ($reg) { $this->sincronizarProducao((int) $reg['id_pedido'], false); } 
                    } 
                    echo json_encode([ "mensagem" => $ok ? "Atualizado com sucesso" : "Erro ao atualizar" ]); 
                    break; 

                case 'DELETE': 
                    $idStatusPedido = $_GET['id_pedido_status'] ?? null; 
                    if (!$idStatusPedido) { 
                        http_response_code(400); 
                        echo json_encode(["erro" => "Dados incompletos"]); 
                        return; 
                    } 
                    $reg = $this->model->buscar((int) $idStatusPedido); 
                    $ok = $this->model->deletarPorPedidoStatus($idStatusPedido); 
                    if ($ok && $reg) { $this->sincronizarProducao((int) $reg['id_pedido'], false); } 
                    echo json_encode([ "mensagem" => $ok ? "Deletado com sucesso" : "Erro ao deletar" ]); 
                    break; 

                default: 
                    http_response_code(405); 
                    echo json_encode(["erro" => "Método não permitido"]); 
            } 
        } catch (PDOException $e) { 
            http_response_code(500); 
            error_log("Erro em PedidostatusController: " . $e->getMessage());
            echo json_encode(["erro" => "Erro interno no servidor."]); 
        } 
    } 
}
