<?php
declare(strict_types=1);

class DiagnosticoController
{
    public function analisar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $cfg = json_decode((string) @file_get_contents(dirname(__DIR__) . '/config/config.json'), true);
        $url = $cfg['diagnostico']['url'] ?? '';
        $token = $cfg['diagnostico']['token'] ?? '';
        if ($url === '' || $token === '' || $token === 'COLE_AQUI_O_TOKEN') {
            http_response_code(500);
            echo json_encode(['erro' => 'Configure "diagnostico.token" em config/config.json.']);
            return;
        }
        if (!function_exists('curl_init')) {
            http_response_code(500);
            echo json_encode(['erro' => 'Ative a extensão curl no php.ini do XAMPP.']);
            return;
        }

        $imagens = [];
        $files = $_FILES['imagens'] ?? null;
        if ($files && is_array($files['tmp_name'])) {
            foreach ($files['tmp_name'] as $i => $tmp) {
                if (count($imagens) >= 4) break;
                if ($files['error'][$i] !== UPLOAD_ERR_OK || $files['size'][$i] > 5 * 1024 * 1024) continue;
                $mime = mime_content_type($tmp) ?: '';
                if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) continue;
                $imagens[] = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($tmp));
            }
        }
        if (!$imagens) {
            http_response_code(400);
            echo json_encode(['erro' => 'Envie ao menos uma imagem PNG, JPG ou WEBP (até 5MB).']);
            return;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Diag-Token: ' . $token],
            CURLOPT_POSTFIELDS => json_encode([
                'imagens' => $imagens,
                'descricao' => mb_substr(trim((string) ($_POST['descricao'] ?? '')), 0, 4000),
            ]),
        ]);
        $resp = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            http_response_code(502);
            echo json_encode(['erro' => 'Sem conexão com o serviço de análise: ' . $err]);
            return;
        }
        http_response_code($status ?: 502);
        echo $resp;
    }
}
