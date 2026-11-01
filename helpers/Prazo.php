<?php
declare(strict_types=1);

/**
 * Regras de prazo de entrega e de alerta por tipo de bloco.
 *
 * Marco zero = data em que o pedido entrou na etapa "Em produção".
 * Dias contados em dias corridos a partir do marco zero.
 *
 *            normal     crítico      vencido
 *   PU       0 a 6      7 a 10       11 em diante   (prazo final: 10 dias)
 *   EPS      0 a 15     16 a 25      26 em diante   (prazo final: 25 dias)
 *
 * A mesma regra existe em JavaScript em public/js/pedido-status.js (função classificarPrazo).
 * Se mudar uma, mude a outra.
 */
final class Prazo
{
    public const PRAZOS = ['PU' => 10, 'EPS' => 25];
    public const LIMITE_NORMAL = ['PU' => 6, 'EPS' => 15];

    public const NIVEL_AGUARDANDO = 'aguardando';
    public const NIVEL_NORMAL = 'normal';
    public const NIVEL_CRITICO = 'critico';
    public const NIVEL_VENCIDO = 'vencido';
    public const NIVEL_FINALIZADO = 'finalizado';

    public static function tipoValido(string $tipo): bool
    {
        return isset(self::PRAZOS[strtoupper($tipo)]);
    }

    public static function diasDoPrazo(string $tipo): int
    {
        return self::PRAZOS[strtoupper($tipo)] ?? self::PRAZOS['PU'];
    }

    /** Data final prevista (Y-m-d) = marco zero + 10 dias (PU) ou + 25 dias (EPS). */
    public static function calcularPrevisao(DateTimeInterface $inicio, string $tipo): string
    {
        $dias = self::diasDoPrazo($tipo);

        return DateTimeImmutable::createFromFormat('Y-m-d', $inicio->format('Y-m-d'))
            ->modify("+{$dias} days")
            ->format('Y-m-d');
    }

    /** Dias corridos entre o marco zero e hoje (negativo se o marco zero for no futuro). */
    public static function diasDecorridos(string $inicio, ?DateTimeInterface $hoje = null): int
    {
        $a = new DateTimeImmutable(substr($inicio, 0, 10));
        $b = new DateTimeImmutable(($hoje ?? new DateTimeImmutable('today'))->format('Y-m-d'));

        return (int) $a->diff($b)->format('%r%a');
    }

    /**
     * @return array{nivel:string,dias_decorridos:?int,dias_restantes:?int,previsao:?string}
     */
    public static function classificar(
        string $tipo,
        ?string $inicioProducao,
        ?string $dataFinalizacao = null,
        ?DateTimeInterface $hoje = null
    ): array {
        $tipo = strtoupper($tipo);
        $vazio = ['dias_decorridos' => null, 'dias_restantes' => null, 'previsao' => null];

        // Finalizado: os alertas cessam
        if ($dataFinalizacao !== null && $dataFinalizacao !== '') {
            return ['nivel' => self::NIVEL_FINALIZADO] + $vazio;
        }
        // Ainda não entrou em produção: sem alerta
        if ($inicioProducao === null || $inicioProducao === '') {
            return ['nivel' => self::NIVEL_AGUARDANDO] + $vazio;
        }

        $inicio = new DateTimeImmutable(substr($inicioProducao, 0, 10));
        $decorridos = self::diasDecorridos($inicioProducao, $hoje);
        $prazo = self::diasDoPrazo($tipo);
        $limiteNormal = self::LIMITE_NORMAL[$tipo] ?? self::LIMITE_NORMAL['PU'];

        if ($decorridos > $prazo) {
            $nivel = self::NIVEL_VENCIDO;
        } elseif ($decorridos > $limiteNormal) {
            $nivel = self::NIVEL_CRITICO;
        } else {
            $nivel = self::NIVEL_NORMAL;
        }

        return [
            'nivel' => $nivel,
            'dias_decorridos' => $decorridos,
            'dias_restantes' => $prazo - $decorridos,
            'previsao' => self::calcularPrevisao($inicio, $tipo),
        ];
    }
}
