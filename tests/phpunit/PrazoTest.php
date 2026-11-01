<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PrazoTest extends TestCase
{
    private const INICIO = '2026-11-01';

    public function testPrazoPuSao10DiasCorridos(): void
    {
        $this->assertSame('2026-11-11', Prazo::calcularPrevisao(new DateTimeImmutable(self::INICIO), 'PU'));
    }

    public function testPrazoEpsSao25DiasCorridos(): void
    {
        $this->assertSame('2026-11-26', Prazo::calcularPrevisao(new DateTimeImmutable(self::INICIO), 'EPS'));
    }

    public function testPrazoAtravessaViradaDeAno(): void
    {
        $this->assertSame('2027-01-07', Prazo::calcularPrevisao(new DateTimeImmutable('2026-12-28'), 'PU'));
        $this->assertSame('2027-01-22', Prazo::calcularPrevisao(new DateTimeImmutable('2026-12-28'), 'EPS'));
    }

    public function testTipoEmMinusculasFunciona(): void
    {
        $this->assertSame('2026-11-26', Prazo::calcularPrevisao(new DateTimeImmutable(self::INICIO), 'eps'));
    }

    public function testPedidoControllerUsaAMesmaRegra(): void
    {
        require_once dirname(__DIR__, 2) . '/controllers/PedidoController.php';
        $this->assertSame('2026-11-11', PedidoController::calcularPrevisao(new DateTime(self::INICIO), 'PU'));
        $this->assertSame('2026-11-26', PedidoController::calcularPrevisao(new DateTime(self::INICIO), 'EPS'));
    }

    /** @return array<string,array{string,int,string}> */
    public static function limites(): array
    {
        return [
            'PU dia 0'     => ['PU', 0, 'normal'],
            'PU dia 6'     => ['PU', 6, 'normal'],
            'PU dia 7'     => ['PU', 7, 'critico'],
            'PU dia 10'    => ['PU', 10, 'critico'],
            'PU dia 11'    => ['PU', 11, 'vencido'],
            'PU dia 30'    => ['PU', 30, 'vencido'],
            'EPS dia 0'    => ['EPS', 0, 'normal'],
            'EPS dia 15'   => ['EPS', 15, 'normal'],
            'EPS dia 16'   => ['EPS', 16, 'critico'],
            'EPS dia 25'   => ['EPS', 25, 'critico'],
            'EPS dia 26'   => ['EPS', 26, 'vencido'],
        ];
    }

    #[DataProvider('limites')]
    public function testFaixasDeAlerta(string $tipo, int $dias, string $esperado): void
    {
        $hoje = (new DateTimeImmutable(self::INICIO))->modify("+{$dias} days");
        $r = Prazo::classificar($tipo, self::INICIO, null, $hoje);

        $this->assertSame($esperado, $r['nivel']);
        $this->assertSame($dias, $r['dias_decorridos']);
    }

    public function testDiasRestantes(): void
    {
        $hoje = new DateTimeImmutable('2026-11-08'); // dia 7 do PU
        $r = Prazo::classificar('PU', self::INICIO, null, $hoje);
        $this->assertSame(3, $r['dias_restantes']);
        $this->assertSame('2026-11-11', $r['previsao']);
    }

    public function testAlertaCessaQuandoFinalizado(): void
    {
        $hoje = new DateTimeImmutable('2026-12-31'); // muito depois do prazo
        $r = Prazo::classificar('PU', self::INICIO, '2026-11-09', $hoje);
        $this->assertSame('finalizado', $r['nivel']);
    }

    public function testSemMarcoZeroNaoTemAlerta(): void
    {
        $r = Prazo::classificar('EPS', null, null, new DateTimeImmutable('2026-12-31'));
        $this->assertSame('aguardando', $r['nivel']);
        $this->assertNull($r['dias_decorridos']);
    }

    public function testStatusEspeciaisIgnoramAcentoECaixa(): void
    {
        $this->assertTrue(StatusRegra::ehEmProducao('Em produção'));
        $this->assertTrue(StatusRegra::ehEmProducao('  EM PRODUCAO '));
        $this->assertFalse(StatusRegra::ehEmProducao('Em laminação'));
        $this->assertTrue(StatusRegra::ehFinalizado('FINALIZADO'));
        $this->assertFalse(StatusRegra::ehFinalizado('Pronto'));
    }
}
