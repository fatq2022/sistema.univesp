<?php
declare(strict_types=1);

/**
 * Identifica as etapas especiais pela descrição cadastrada em "Status":
 * "Em produção" (marco zero) e "Finalizado" (encerra os alertas).
 * Ignora maiúsculas/minúsculas, acentos e espaços sobrando.
 */
final class StatusRegra
{
    public const EM_PRODUCAO = 'em producao';
    public const FINALIZADO = 'finalizado';

    public static function normalizar(string $texto): string
    {
        $t = trim($texto);
        $t = strtr($t, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
            'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ã' => 'a', 'Ä' => 'a',
            'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Ë' => 'e',
            'Í' => 'i', 'Ì' => 'i', 'Î' => 'i', 'Ï' => 'i',
            'Ó' => 'o', 'Ò' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o',
            'Ú' => 'u', 'Ù' => 'u', 'Û' => 'u', 'Ü' => 'u',
            'Ç' => 'c',
        ]);

        return (string) preg_replace('/\s+/', ' ', strtolower($t));
    }

    public static function ehEmProducao(string $descricao): bool
    {
        return self::normalizar($descricao) === self::EM_PRODUCAO;
    }

    public static function ehFinalizado(string $descricao): bool
    {
        return self::normalizar($descricao) === self::FINALIZADO;
    }
}
