<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SenhaTest extends TestCase
{
    /** @return array<string,array{string,bool}> */
    public static function senhas(): array
    {
        return [
            'forte com 6'             => ['Abc12!', true],
            'forte e longa'           => ['MinhaSenha#2026', true],
            'acentuada'               => ['Áé1!ãõ', true],
            'curta (5)'               => ['Ab1!x', false],
            'sem maiúscula'           => ['abc12!', false],
            'sem minúscula'           => ['ABC12!', false],
            'sem número'              => ['Abcde!', false],
            'sem caractere especial'  => ['Abc123', false],
            'só espaço como especial' => ['Abc 12', false],
            'acima de 72'             => [str_repeat('Aa1!', 19), false],
        ];
    }

    #[DataProvider('senhas')]
    public function testForcaDaSenha(string $senha, bool $valida): void
    {
        $this->assertSame($valida, Senha::validar($senha) === null, $senha);
    }

    public function testRecuperacaoUsaAsMesmasRegras(): void
    {
        $this->assertNotNull(RecuperacaoController::validarNovaSenha('abc123', 'abc123'));
        $this->assertNull(RecuperacaoController::validarNovaSenha('Abc12!', 'Abc12!'));
        $this->assertNotNull(RecuperacaoController::validarNovaSenha('Abc12!', 'Abc12?'));
    }

    public function testAlterarSenhaValidaCampos(): void
    {
        $v = fn(string ...$a) => AlterarSenhaController::validarCampos(...$a);
        $this->assertNull($v('Admin', 'Antiga#1', 'Nova#123', 'Nova#123'));
        $this->assertNotNull($v('Admin', '', 'Nova#123', 'Nova#123'), 'senha atual obrigatória');
        $this->assertNotNull($v('Admin', 'Antiga#1', 'fraca', 'fraca'), 'nova senha fraca');
        $this->assertNotNull($v('Admin', 'Antiga#1', 'Nova#123', 'Nova#124'), 'repetição diferente');
        $this->assertNotNull($v('Admin', 'Nova#123', 'Nova#123', 'Nova#123'), 'igual à atual');
    }
}
