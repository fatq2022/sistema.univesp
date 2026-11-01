<?php
declare(strict_types=1);

/**
 * Regras de força da senha (mesmas do JavaScript em public/js/senha.js):
 * mínimo 6 caracteres, 1 maiúscula, 1 minúscula, 1 número e 1 caractere especial.
 */
final class Senha
{
    public const MINIMO = 6;
    public const MAXIMO = 72; // limite do bcrypt

    /** @return array<string,bool> regra => atendida? */
    public static function avaliar(string $senha): array
    {
        return [
            'tamanho' => mb_strlen($senha) >= self::MINIMO,
            'maiuscula' => preg_match('/\p{Lu}/u', $senha) === 1,
            'minuscula' => preg_match('/\p{Ll}/u', $senha) === 1,
            'numero' => preg_match('/\d/', $senha) === 1,
            'especial' => preg_match('/[^\p{L}\p{N}\s]/u', $senha) === 1,
        ];
    }

    /** Mensagem de erro, ou null se a senha for forte o bastante. */
    public static function validar(string $senha): ?string
    {
        if (mb_strlen($senha) > self::MAXIMO) {
            return 'A senha pode ter no máximo ' . self::MAXIMO . ' caracteres.';
        }
        $r = self::avaliar($senha);
        $faltam = [];
        if (!$r['tamanho']) {
            $faltam[] = 'pelo menos ' . self::MINIMO . ' caracteres';
        }
        if (!$r['maiuscula']) {
            $faltam[] = '1 letra maiúscula';
        }
        if (!$r['minuscula']) {
            $faltam[] = '1 letra minúscula';
        }
        if (!$r['numero']) {
            $faltam[] = '1 número';
        }
        if (!$r['especial']) {
            $faltam[] = '1 caractere especial (ex.: ! @ # $ %)';
        }

        return $faltam ? 'A senha precisa ter ' . implode(', ', $faltam) . '.' : null;
    }
}
