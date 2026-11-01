<?php
// controllers/PedidoController.php

require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/models/Pedido.php';
require_once dirname(__DIR__) . '/helpers/Prazo.php';
require_once dirname(__DIR__) . '/helpers/ProducaoService.php';

class PedidoController
{
    private PDO $conn;
    private Pedido $model;

    // Regra de prazo: PU = 10 dias, EPS = 25 dias
    private const PRAZOS = Prazo::PRAZOS;

    public function __construct()
    {
        $this->conn = (new Database())->connect();
        $this->model = new Pedido($this->conn);
    }

    public function processRequest($method, $id = null)
    {
        $input = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = [];
        }
        if ($method === 'POST' && isset($input['_method'])) {
            $method = strtoupper((string) $input['_method']);
        }

        switch ($method) {
            case 'GET':
                $id ? $this->buscar($id) : $this->listar();
                break;
            case 'POST':
                $this->criar($input);
                break;
            case 'PUT':
            case 'PATCH':
                $id ? $this->atualizar($id, $input) : $this->erro(400, 'ID não informado para atualização.');
                break;
            case 'DELETE':
                $id ? $this->deletar($id) : $this->erro(400, 'ID não informado para exclusão.');
                break;
            default:
                $this->erro(405, 'Método não permitido.');
        }
    }

    private function listar(): void
    {
        try {
            $this->json($this->model->listar() ?: []);
        } catch (Throwable $e) {
            error_log('Erro ao listar pedidos: ' . $e->getMessage());
            $this->erro(500, 'Erro ao listar pedidos.');
        }
    }

    private function buscar($id): void
    {
        try {
            $pedido = $this->model->buscar((int) $id);
            $pedido ? $this->json($pedido) : $this->erro(404, 'Pedido não encontrado.');
        } catch (Throwable $e) {
            error_log('Erro ao buscar pedido: ' . $e->getMessage());
            $this->erro(500, 'Erro ao buscar pedido.');
        }
    }

    private function criar(array $d): void
    {
        $campos = $this->validar($d);
        if ($campos === null) return;

        try {
            $this->conn->beginTransaction();
            $ok = $this->model->criar(
                $campos['data'], $campos['id_shaper'], $campos['id_composicao'], $campos['id_variacao'],
                $campos['id_acabamento'], $campos['id_configuracaoquilha'], $campos['id_sistemaquilha'],
                $campos['observacao'], $campos['tecidos'], $campos['cores'],
                $campos['tipo_bloco'], $campos['data_prevista_entrega']
            );
            $novoId = (int) $ok;

            if ($novoId <= 0) {
                $this->conn->rollBack();
                $this->erro(500, 'Não foi possível criar o pedido.');
                return;
            }
            $this->conn->commit();

            $this->enviarNotificacaoEmail($novoId, 'Criado', (int) $campos['id_shaper']);
            $this->json(['sucesso' => true, 'mensagem' => 'Pedido criado com sucesso.', 'id_pedido' => $novoId], 201);
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log('Erro ao criar pedido: ' . $e->getMessage());
            $this->erro(500, 'Erro ao criar pedido.');
        }
    }

    private function atualizar($id, array $d): void
    {
        $campos = $this->validar($d);
        if ($campos === null) return;

        try {
            if (!$this->model->buscar((int) $id)) {
                $this->erro(404, 'Pedido não encontrado.');
                return;
            }
            $this->conn->beginTransaction();
            $this->model->atualizar(
                (int) $id, $campos['data'], $campos['id_shaper'], $campos['id_composicao'], $campos['id_variacao'],
                $campos['id_acabamento'], $campos['id_configuracaoquilha'], $campos['id_sistemaquilha'],
                $campos['observacao'], $campos['tecidos'], $campos['cores'],
                $campos['tipo_bloco'], $campos['data_prevista_entrega']
            );
            $this->conn->commit();
            // Se o pedido já entrou em produção, a previsão volta a contar do marco zero (tipo pode ter mudado)
            try {
                (new ProducaoService($this->conn))->sincronizar((int) $id, false);
            } catch (Throwable $e) {
                error_log('Erro ao recalcular prazo do pedido ' . $id . ': ' . $e->getMessage());
            }
            $this->enviarNotificacaoEmail((int) $id, 'Atualizado', (int) $campos['id_shaper']);
            $this->json(['sucesso' => true, 'mensagem' => 'Pedido atualizado com sucesso.']);
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log('Erro ao atualizar pedido: ' . $e->getMessage());
            $this->erro(500, 'Erro ao atualizar pedido.');
        }
    }

    private function deletar($id): void
    {
        $this->model->deletar((int) $id)
            ? $this->json(['sucesso' => true, 'mensagem' => 'Pedido excluído com sucesso.'])
            : $this->erro(404, 'Pedido não encontrado ou não pôde ser excluído.');
    }

    /** Valida o corpo e devolve os campos prontos, ou null (já respondendo 400). */
    private function validar(array $d): ?array
    {
        $obrigatorios = ['id_shaper', 'id_composicao', 'id_variacao', 'id_acabamento', 'id_configuracaoquilha', 'id_sistemaquilha'];
        foreach ($obrigatorios as $c) {
            if (!isset($d[$c]) || !ctype_digit((string) $d[$c])) {
                $this->erro(400, "Campo obrigatório inválido: {$c}.");
                return null;
            }
        }

        $data = (string) ($d['data'] ?? '');
        $dt = DateTime::createFromFormat('Y-m-d', $data);
        if (!$dt || $dt->format('Y-m-d') !== $data) {
            $data = date('Y-m-d');
            $dt = new DateTime($data);
        }

        $tipo = strtoupper((string) ($d['tipo_bloco'] ?? 'PU'));
        if (!isset(self::PRAZOS[$tipo])) {
            $this->erro(400, 'Tipo de bloco inválido (use PU ou EPS).');
            return null;
        }

        $ids = fn($lista) => array_values(array_unique(array_map('intval', array_filter(
            is_array($lista) ? $lista : [],
            fn($v) => ctype_digit((string) $v)
        ))));

        return [
            'data' => $data,
            'tipo_bloco' => $tipo,
            'data_prevista_entrega' => self::calcularPrevisao($dt, $tipo),
            'id_shaper' => (int) $d['id_shaper'],
            'id_composicao' => (int) $d['id_composicao'],
            'id_variacao' => (int) $d['id_variacao'],
            'id_acabamento' => (int) $d['id_acabamento'],
            'id_configuracaoquilha' => (int) $d['id_configuracaoquilha'],
            'id_sistemaquilha' => (int) $d['id_sistemaquilha'],
            'observacao' => mb_substr(trim((string) ($d['observacao'] ?? '')), 0, 800),
            'tecidos' => $ids($d['tecidos'] ?? []),
            'cores' => $ids($d['cores'] ?? []),
        ];
    }

    public static function calcularPrevisao(DateTime $data, string $tipo): string
    {
        return Prazo::calcularPrevisao($data, $tipo);
    }

    private function json(array $dados, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    }

    private function erro(int $status, string $mensagem): void
    {
        $this->json(['sucesso' => false, 'mensagem' => $mensagem], $status);
    }

    private function enviarNotificacaoEmail(int $idPedido, string $statusAcao, int $idShaper): void
    {
        try {
            $cfgPath = dirname(__DIR__) . '/config/config.json';
            $cfg = is_file($cfgPath) ? json_decode((string) file_get_contents($cfgPath), true) : [];
            $email = $cfg['email'] ?? [];
            $apiKey = (string) ($email['brevo_api_key'] ?? '');
            $remetente = (string) ($email['remetente_email'] ?? '');
            if ($apiKey === '' || $remetente === '' || !function_exists('curl_init')) {
                return; // e-mail não configurado: segue sem enviar
            }

            $stmt = $this->conn->prepare('SELECT email, nome FROM shaper WHERE id_shaper = ?');
            $stmt->execute([$idShaper]);
            $shaper = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$shaper || !filter_var($shaper['email'], FILTER_VALIDATE_EMAIL)) {
                return;
            }

            $nome = htmlspecialchars((string) $shaper['nome'], ENT_QUOTES, 'UTF-8');
            $acao = htmlspecialchars($statusAcao, ENT_QUOTES, 'UTF-8');
            $dados = [
                'sender' => ['name' => $email['remetente_nome'] ?? 'Controle de Laminação', 'email' => $remetente],
                'to' => [['email' => $shaper['email'], 'name' => $shaper['nome']]],
                'subject' => "Atualização no Pedido #{$idPedido} - Sistema de Laminação",
                'htmlContent' => "<h2>Olá, {$nome}!</h2><p>O seu pedido <strong>#{$idPedido}</strong> teve uma atualização: <strong>{$acao}</strong>.</p><p><em>Equipe de Controle de Laminação</em></p>",
            ];

            $ch = curl_init('https://api.brevo.com/v3/smtp/email');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_POSTFIELDS => json_encode($dados),
                CURLOPT_HTTPHEADER => ['accept: application/json', 'api-key: ' . $apiKey, 'content-type: application/json'],
            ]);
            curl_exec($ch);
            if ($erro = curl_error($ch)) {
                error_log('Erro cURL ao enviar e-mail: ' . $erro);
            }
            curl_close($ch);
        } catch (Throwable $e) {
            error_log('Erro ao enviar e-mail: ' . $e->getMessage());
        }
    }
}
