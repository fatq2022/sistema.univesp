<?php
declare(strict_types=1);

require_once __DIR__ . '/Mailer.php';

/**
 * E-mail enviado ao cliente/shaper quando o pedido entra em "Em produção".
 * O transporte (Brevo, por padrão) pode ser trocado em testes.
 */
final class NotificacaoProducao
{
    public static function assunto(int $idPedido, string $tipo): string
    {
        return 'Produção iniciada - Bloco ' . strtoupper($tipo) . ' (pedido #' . $idPedido . ')';
    }

    public static function dataBr(string $ymd): string
    {
        $d = DateTimeImmutable::createFromFormat('Y-m-d', substr($ymd, 0, 10));

        return $d ? $d->format('d/m/Y') : $ymd;
    }

    public static function html(string $nomeShaper, int $idPedido, string $tipo, string $previsaoYmd): string
    {
        $nome = htmlspecialchars($nomeShaper, ENT_QUOTES, 'UTF-8');
        $tipo = htmlspecialchars(strtoupper($tipo), ENT_QUOTES, 'UTF-8');
        $previsao = htmlspecialchars(self::dataBr($previsaoYmd), ENT_QUOTES, 'UTF-8');

        return "<h2>Olá, {$nome}!</h2>"
            . "<p>A produção do seu bloco <strong>{$tipo}</strong> (pedido #{$idPedido}) foi <strong>iniciada</strong>.</p>"
            . "<p>Data prevista de entrega: <strong>{$previsao}</strong>.</p>"
            . '<p><em>Equipe de Controle de Laminação</em></p>';
    }

    /**
     * @param array{id_pedido:int,tipo_bloco:string,shaper_nome:string,shaper_email:?string} $pedido
     * @param callable|null $transporte function(string $email, string $nome, string $assunto, string $html): bool
     */
    public static function enviar(array $pedido, string $previsaoYmd, ?callable $transporte = null): bool
    {
        $email = trim((string) ($pedido['shaper_email'] ?? ''));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $transporte = $transporte ?? [Mailer::class, 'enviar'];
        $id = (int) $pedido['id_pedido'];
        $tipo = (string) $pedido['tipo_bloco'];
        $nome = (string) ($pedido['shaper_nome'] ?? '');

        try {
            return (bool) $transporte(
                $email,
                $nome,
                self::assunto($id, $tipo),
                self::html($nome, $id, $tipo, $previsaoYmd)
            );
        } catch (Throwable $e) {
            error_log('Falha ao notificar início de produção: ' . $e->getMessage());

            return false;
        }
    }
}
