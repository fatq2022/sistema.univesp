<?php
declare(strict_types=1);

/**
 * Envio de e-mail transacional pela API da Brevo (config/config.json -> "email").
 * Se a chave da API ou o remetente não estiverem preenchidos, não envia e devolve false.
 */
class Mailer
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    public static function configuracao(): array
    {
        $caminho = dirname(__DIR__) . '/config/config.json';
        $cfg = is_file($caminho) ? json_decode((string) file_get_contents($caminho), true) : [];

        return is_array($cfg) ? $cfg : [];
    }

    public static function configurado(array $cfg): bool
    {
        $email = $cfg['email'] ?? [];

        return trim((string) ($email['brevo_api_key'] ?? '')) !== ''
            && filter_var((string) ($email['remetente_email'] ?? ''), FILTER_VALIDATE_EMAIL) !== false
            && function_exists('curl_init');
    }

    public static function enviar(string $paraEmail, string $paraNome, string $assunto, string $html): bool
    {
        $cfg = self::configuracao();
        if (!self::configurado($cfg)) {
            return false;
        }

        $email = $cfg['email'];
        $dados = [
            'sender' => [
                'name' => (string) ($email['remetente_nome'] ?? 'Controle de Laminação'),
                'email' => (string) $email['remetente_email'],
            ],
            'to' => [['email' => $paraEmail, 'name' => $paraNome !== '' ? $paraNome : $paraEmail]],
            'subject' => $assunto,
            'htmlContent' => $html,
        ];

        try {
            $ch = curl_init(self::ENDPOINT);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_POSTFIELDS => json_encode($dados, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => [
                    'accept: application/json',
                    'api-key: ' . $email['brevo_api_key'],
                    'content-type: application/json',
                ],
            ]);
            $resposta = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $erroCurl = curl_error($ch);
            curl_close($ch);

            if ($resposta === false || $status < 200 || $status >= 300) {
                // Nunca registra a chave da API; só o motivo da falha
                error_log('Falha ao enviar e-mail (Brevo): HTTP ' . $status . ' ' . $erroCurl . ' ' . substr((string) $resposta, 0, 200));
                return false;
            }

            return true;
        } catch (Throwable $e) {
            error_log('Erro ao enviar e-mail: ' . $e->getMessage());
            return false;
        }
    }
}
