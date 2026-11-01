<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Ao registrar a etapa "Em produção" (mesmo caminho do PedidoStatusController):
 * grava o marco zero + a previsão e dispara o e-mail ao shaper. Usa SQLite em memória
 * e um transporte falso no lugar da Brevo, então não precisa de MySQL nem de internet.
 */
final class NotificacaoProducaoTest extends TestCase
{
    private const EM_PRODUCAO = 2;
    private const FINALIZADO = 3;
    private const AGUARDANDO = 1;

    private PDO $db;
    private ProducaoService $servico;
    private PedidoStatus $statusModel;
    /** @var array<int,array{email:string,nome:string,assunto:string,html:string}> */
    private array $enviados = [];
    private bool $transporteFunciona = true;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Habilite a extensão pdo_sqlite no php.ini para rodar este teste.');
        }

        $this->db = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->db->exec('CREATE TABLE shaper (id_shaper INTEGER PRIMARY KEY, nome TEXT, email TEXT)');
        $this->db->exec('CREATE TABLE status (id_status INTEGER PRIMARY KEY, descricao TEXT)');
        $this->db->exec('CREATE TABLE pedido (id_pedido INTEGER PRIMARY KEY, data TEXT, tipo_bloco TEXT,
            data_prevista_entrega TEXT, data_inicio_producao TEXT, data_finalizacao TEXT, id_shaper INTEGER)');
        $this->db->exec('CREATE TABLE pedido_status (id_pedido_status INTEGER PRIMARY KEY AUTOINCREMENT,
            id_pedido INTEGER, id_status INTEGER, data TEXT, id_usuario INTEGER, observacao TEXT)');
        $this->db->exec("INSERT INTO status VALUES (1, 'Aguardando'), (2, 'Em produção'), (3, 'Finalizado')");

        $this->enviados = [];
        $this->transporteFunciona = true;
        $this->servico = new ProducaoService($this->db, function (string $email, string $nome, string $assunto, string $html): bool {
            $this->enviados[] = compact('email', 'nome', 'assunto', 'html');

            return $this->transporteFunciona;
        });
        $this->statusModel = new PedidoStatus($this->db);
    }

    private function criarPedido(string $tipo, ?string $email = 'cliente@example.com', string $nome = 'Shaper Teste'): int
    {
        $this->db->prepare('INSERT INTO shaper (nome, email) VALUES (?, ?)')->execute([$nome, $email]);
        $idShaper = (int) $this->db->lastInsertId();
        $this->db->prepare("INSERT INTO pedido (data, tipo_bloco, id_shaper) VALUES ('2026-10-20', ?, ?)")
            ->execute([$tipo, $idShaper]);

        return (int) $this->db->lastInsertId();
    }

    /** Faz o mesmo que o PedidoStatusController::POST: grava o status e sincroniza com notificação. */
    private function mudarStatus(int $idPedido, int $idStatus, string $data): array
    {
        $this->statusModel->criar($idPedido, $idStatus, 1, $data, null);

        return $this->servico->sincronizar($idPedido, true);
    }

    private function pedido(int $id): array
    {
        return $this->db->query("SELECT * FROM pedido WHERE id_pedido = {$id}")->fetch();
    }

    public function testEmProducaoPuGravaMarcoZeroPrevisaoEEnviaEmail(): void
    {
        $id = $this->criarPedido('PU');
        $r = $this->mudarStatus($id, self::EM_PRODUCAO, '2026-11-01 09:00:00');

        $this->assertSame('enviada', $r['notificacao']);
        $this->assertSame('2026-11-01', $this->pedido($id)['data_inicio_producao']);
        $this->assertSame('2026-11-11', $this->pedido($id)['data_prevista_entrega']);

        $this->assertCount(1, $this->enviados);
        $mail = $this->enviados[0];
        $this->assertSame('cliente@example.com', $mail['email']);
        $this->assertStringContainsString('PU', $mail['assunto']);
        $this->assertStringContainsString("#{$id}", $mail['assunto']);
        $this->assertStringContainsString('iniciada', $mail['html']);
        $this->assertStringContainsString('11/11/2026', $mail['html']);
    }

    public function testEmProducaoEpsUsa25Dias(): void
    {
        $id = $this->criarPedido('EPS');
        $this->mudarStatus($id, self::EM_PRODUCAO, '2026-11-01 09:00:00');

        $this->assertSame('2026-11-26', $this->pedido($id)['data_prevista_entrega']);
        $this->assertStringContainsString('EPS', $this->enviados[0]['assunto']);
        $this->assertStringContainsString('26/11/2026', $this->enviados[0]['html']);
    }

    public function testOutrasEtapasNaoEnviamEmail(): void
    {
        $id = $this->criarPedido('PU');
        $r = $this->mudarStatus($id, self::AGUARDANDO, '2026-11-01 09:00:00');

        $this->assertSame('nao_aplicavel', $r['notificacao']);
        $this->assertCount(0, $this->enviados);
        $this->assertNull($this->pedido($id)['data_inicio_producao']);
    }

    public function testNaoReenviaSeJaEstavaEmProducao(): void
    {
        $id = $this->criarPedido('PU');
        $this->mudarStatus($id, self::EM_PRODUCAO, '2026-11-01 09:00:00');
        $this->mudarStatus($id, self::EM_PRODUCAO, '2026-11-02 09:00:00');

        $this->assertCount(1, $this->enviados);
        $this->assertSame('2026-11-01', $this->pedido($id)['data_inicio_producao'], 'o marco zero é o primeiro registro');
    }

    public function testClienteSemEmailNaoQuebraORegistro(): void
    {
        $id = $this->criarPedido('PU', '');
        $r = $this->mudarStatus($id, self::EM_PRODUCAO, '2026-11-01 09:00:00');

        $this->assertSame('sem_email', $r['notificacao']);
        $this->assertSame('2026-11-01', $this->pedido($id)['data_inicio_producao']);
    }

    public function testFalhaNoEnvioNaoImpedeOMarcoZero(): void
    {
        $this->transporteFunciona = false;
        $id = $this->criarPedido('PU');
        $r = $this->mudarStatus($id, self::EM_PRODUCAO, '2026-11-01 09:00:00');

        $this->assertSame('falhou', $r['notificacao']);
        $this->assertSame('2026-11-11', $this->pedido($id)['data_prevista_entrega']);
    }

    public function testFinalizadoGravaADataQueEncerraOsAlertas(): void
    {
        $id = $this->criarPedido('PU');
        $this->mudarStatus($id, self::EM_PRODUCAO, '2026-11-01 09:00:00');
        $this->mudarStatus($id, self::FINALIZADO, '2026-11-20 09:00:00');

        $p = $this->pedido($id);
        $this->assertSame('2026-11-20', $p['data_finalizacao']);
        $r = Prazo::classificar($p['tipo_bloco'], $p['data_inicio_producao'], $p['data_finalizacao'], new DateTimeImmutable('2026-12-31'));
        $this->assertSame('finalizado', $r['nivel']);
    }

    public function testExcluirOStatusEmProducaoLimpaOMarcoZero(): void
    {
        $id = $this->criarPedido('PU');
        $this->mudarStatus($id, self::EM_PRODUCAO, '2026-11-01 09:00:00');
        $idRegistro = (int) $this->db->lastInsertId();

        $this->statusModel->deletarPorPedidoStatus($idRegistro);
        $this->servico->sincronizar($id, false);

        $this->assertNull($this->pedido($id)['data_inicio_producao']);
        // volta à estimativa provisória, a partir da data do pedido (20/10 + 10 dias)
        $this->assertSame('2026-10-30', $this->pedido($id)['data_prevista_entrega']);
    }

    public function testNomeDoClienteEhEscapadoNoEmail(): void
    {
        $id = $this->criarPedido('PU', 'cliente@example.com', '<script>alert(1)</script>');
        $this->mudarStatus($id, self::EM_PRODUCAO, '2026-11-01 09:00:00');

        $this->assertStringNotContainsString('<script>', $this->enviados[0]['html']);
    }
}
