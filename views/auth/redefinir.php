<?php
$base = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '/sistema';
if ($base === '/') {
    $base = '';
}
$asset = static function (string $path) use ($base): string {
    return $base . '/' . ltrim($path, '/');
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- O endereço desta página contém o token: não repassar a outros sites -->
    <meta name="referrer" content="no-referrer">
    <meta name="robots" content="noindex, nofollow">
    <title>Nova senha | Sistema de Controle de Laminação</title>

    <link rel="stylesheet"
          href="<?= htmlspecialchars($asset('public/css/Bootstrap.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet"
          href="<?= htmlspecialchars($asset('public/css/login.css'), ENT_QUOTES, 'UTF-8') ?>">

    <script>
        window.BASE_URL = <?= json_encode($base, JSON_UNESCAPED_SLASHES) ?>;
    </script>
</head>
<body>
    <a class="visually-hidden-focusable" href="#conteudo-principal">Ir para o conteúdo principal</a>
    <main class="container-fluid p-0" id="conteudo-principal">
        <div class="row g-0 login-container">
            <div class="col-md-6 d-none d-md-block left-side"
                 role="img" aria-label="Imagem ilustrativa do sistema"></div>

            <section class="col-12 col-md-6 d-flex align-items-center justify-content-center right-side">
                <div class="card shadow p-4 pb-5 login-card">
                    <h1 class="h4 text-center mb-3">
                        <i class="fa-solid fa-lock me-2" aria-hidden="true"></i>
                        Criar nova senha
                    </h1>

                    <div id="alerta" class="alert d-none" role="alert" aria-live="polite"></div>

                    <form id="formRedefinir" novalidate class="d-none">
                        <div class="mb-3">
                            <label for="senha" class="form-label">Nova senha</label>
                            <input type="password" id="senha" name="senha" class="form-control"
                                   autocomplete="new-password" maxlength="72"
                                   required aria-required="true" aria-describedby="ajudaSenha listaRegras">
                            <div id="ajudaSenha" class="form-text">A senha precisa atender a todas as regras abaixo:</div>
                            <ul id="listaRegras" class="senha-regras" aria-live="polite"></ul>
                        </div>

                        <div class="mb-3">
                            <label for="confirmar" class="form-label">Confirmar nova senha</label>
                            <input type="password" id="confirmar" name="confirmar" class="form-control"
                                   autocomplete="new-password" maxlength="72"
                                   required aria-required="true">
                        </div>

                        <div class="d-grid">
                            <button type="submit" id="btnRedefinir" class="btn btn-primary">
                                Salvar nova senha
                            </button>
                        </div>
                    </form>

                    <p id="carregandoLink" class="text-muted text-center mb-0">Verificando o link...</p>

                    <p class="text-center mt-3 mb-0">
                        <a id="linkLogin" href="<?= htmlspecialchars($base . '/login', ENT_QUOTES, 'UTF-8') ?>">Ir para o login</a>
                        <span id="linkNovoPedido" class="d-none">
                            &middot; <a href="<?= htmlspecialchars($base . '/recuperar-senha', ENT_QUOTES, 'UTF-8') ?>">Solicitar novo link</a>
                        </span>
                    </p>
                </div>
            </section>
        </div>
    </main>

    <script src="<?= htmlspecialchars($asset('public/js/senha.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <script src="<?= htmlspecialchars($asset('public/js/recuperacao.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>
