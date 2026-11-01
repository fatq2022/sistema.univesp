<?php
declare(strict_types=1);

require_once __DIR__ . '/Prazo.php';
require_once __DIR__ . '/StatusRegra.php';
require_once __DIR__ . '/NotificacaoProducao.php';

/**
 * Mantém, para cada pedido, o marco zero ("Em produção"), a data de finalização
 * e a previsão de entrega, sempre a partir do histórico em pedido_status.
 *
 * Alterar o status para "Em produção" faz, juntos:
 *   1) grava o marco zero (pedido.data_inicio_producao) e a previsão (pedido.data_prevista_entrega);
 *   2) envia o e-mail de notificação ao shaper (somente na primeira vez que o pedido entra em produção).
 */
final class ProducaoService
{
    /** @var PDO */
    private $db;
    /** @var callable|null */
    private $transporte;

    public function __construct(PDO $db, ?callable $transporte = null)
    {
        $this->db = $db;
        $this->transporte = $transporte;
    }

    /** Cria as colunas novas se o banco for da versão anterior (equivale a migracoes/002). */
    public function garantirColunas(): void
    {
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return;
        }
        $stmt = $this->db->query(
            "SELECT LOWER(COLUMN_NAME) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedido'"
        );
        $colunas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('data_inicio_producao', $colunas, true)) {
            $this->db->exec('ALTER TABLE pedido ADD COLUMN data_inicio_producao DATE DEFAULT NULL');
        }
        if (!in_array('data_finalizacao', $colunas, true)) {
            $this->db->exec('ALTER TABLE pedido ADD COLUMN data_finalizacao DATE DEFAULT NULL');
        }
    }

    /**
     * Recalcula marco zero, finalização e previsão do pedido.
     *
     * @param bool $notificar envia o e-mail se o pedido acabou de entrar em produção
     * @return array{marco_zero:?string,finalizacao:?string,previsao:?string,notificacao:string}
     *         notificacao: 'enviada' | 'falhou' | 'sem_email' | 'nao_aplicavel'
     */
    public function sincronizar(int $idPedido, bool $notificar = false): array
    {
        $this->garantirColunas();

        $stmt = $this->db->prepare(
            'SELECT p.id_pedido, p.data, p.tipo_bloco, p.data_inicio_producao,
                    s.nome AS shaper_nome, s.email AS shaper_email
               FROM pedido p
               JOIN shaper s ON s.id_shaper = p.id_shaper
              WHERE p.id_pedido = ?'
        );
        $stmt->execute([$idPedido]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pedido) {
            return ['marco_zero' => null, 'finalizacao' => null, 'previsao' => null, 'notificacao' => 'nao_aplicavel'];
        }

        $stmt = $this->db->prepare(
            'SELECT ps.data, st.descricao
               FROM pedido_status ps
               JOIN status st ON st.id_status = ps.id_status
              WHERE ps.id_pedido = ?
              ORDER BY ps.data ASC, ps.id_pedido_status ASC'
        );
        $stmt->execute([$idPedido]);

        $inicio = null;
        $fim = null;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $dia = self::soData((string) $linha['data']);
            if ($dia === null) {
                continue;
            }
            if ($inicio === null && StatusRegra::ehEmProducao((string) $linha['descricao'])) {
                $inicio = $dia;
            }
            if ($fim === null && StatusRegra::ehFinalizado((string) $linha['descricao'])) {
                $fim = $dia;
            }
        }

        $tipo = (string) $pedido['tipo_bloco'];
        // Sem marco zero (ainda não entrou em produção): mantém a estimativa a partir da data do pedido
        $base = $inicio ?? self::soData((string) $pedido['data']);
        $previsao = $base !== null
            ? Prazo::calcularPrevisao(new DateTimeImmutable($base), $tipo)
            : null;

        $upd = $this->db->prepare(
            'UPDATE pedido
                SET data_inicio_producao = ?, data_finalizacao = ?, data_prevista_entrega = ?
              WHERE id_pedido = ?'
        );
        $upd->execute([$inicio, $fim, $previsao, $idPedido]);

        $notificacao = 'nao_aplicavel';
        $acabouDeEntrar = $inicio !== null && empty($pedido['data_inicio_producao']);
        if ($notificar && $acabouDeEntrar && $previsao !== null) {
            if (trim((string) ($pedido['shaper_email'] ?? '')) === '') {
                $notificacao = 'sem_email';
            } else {
                $ok = NotificacaoProducao::enviar($pedido, $previsao, $this->transporte);
                $notificacao = $ok ? 'enviada' : 'falhou';
            }
        }

        return ['marco_zero' => $inicio, 'finalizacao' => $fim, 'previsao' => $previsao, 'notificacao' => $notificacao];
    }

    /** "2026-11-01T10:30" ou "2026-11-01 10:30:00" -> "2026-11-01"; inválido -> null. */
    private static function soData(string $valor): ?string
    {
        $d = substr(trim($valor), 0, 10);
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $d);

        return ($dt && $dt->format('Y-m-d') === $d) ? $d : null;
    }
}
