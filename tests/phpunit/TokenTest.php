<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/** Geração de token (sem banco). */
final class TokenTest extends TestCase
{
    public function testTokenTem64CaracteresHexadecimais(): void
    {
        [$token] = RecuperacaoController::gerarToken();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertTrue(RecuperacaoController::tokenComFormatoValido($token));
    }

    public function testTokensSaoImprevisiveisEUnicos(): void
    {
        $tokens = [];
        for ($i = 0; $i < 50; $i++) {
            $tokens[] = RecuperacaoController::gerarToken()[0];
        }
        $this->assertCount(50, array_unique($tokens));
    }

    public function testBancoGuardaSoOHashENuncaOToken(): void
    {
        [$token, $hash] = RecuperacaoController::gerarToken();
        $this->assertNotSame($token, $hash);
        $this->assertSame(hash('sha256', $token), $hash);
        $this->assertSame($hash, RecuperacaoController::hashToken($token));
    }

    public function testFormatoInvalidoEhRecusado(): void
    {
        foreach (['', 'abc', str_repeat('g', 64), str_repeat('a', 63), str_repeat('A', 64), "' OR 1=1 --"] as $ruim) {
            $this->assertFalse(RecuperacaoController::tokenComFormatoValido($ruim), $ruim);
        }
    }

    public function testLinkApontaParaAPaginaDeRedefinicaoComOToken(): void
    {
        [$token] = RecuperacaoController::gerarToken();
        $cfg = ['app' => ['base_path' => '/sistema', 'url_publica' => 'https://meusite.com.br']];
        $this->assertSame(
            "https://meusite.com.br/sistema/redefinir-senha?token={$token}",
            RecuperacaoController::montarLink($cfg, $token)
        );
    }

    public function testTokenValeUmaHora(): void
    {
        $this->assertSame(60, RecuperacaoController::VALIDADE_MINUTOS);
    }
}
