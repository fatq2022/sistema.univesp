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
    <meta name="robots" content="noindex, nofollow">
    <title>Alterar a senha | Sistema de Controle de Laminação</title>

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
                        <i class="fa-solid fa-key me-2" aria-hidden="true"></i>
                        Alterar a senha
                    </h1>

                    <div id="alerta" class="alert d-none" role="alert" aria-live="polite"></div>

                    <form id="formAlterar" novalidate>
                        <div class="mb-3">
                            <label for="usuario" class="form-label">Usuário</label>
                            <input type="text" id="usuario" name="usuario" class="form-control"
                                   autocomplete="username" required aria-required="true" autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="senhaAtual" class="form-label">Senha atual</label>
                            <input type="password" id="senhaAtual" name="senha_atual" class="form-control"
                                   autocomplete="current-password" required aria-required="true">
                        </div>

                        <div class="mb-3">
                            <label for="novaSenha" class="form-label">Nova senha</label>
                            <input type="password" id="novaSenha" name="nova_senha" class="form-control"
                                   autocomplete="new-password" maxlength="72" required aria-required="true"
                                   aria-describedby="ajudaNova listaRegras">
                            <div id="ajudaNova" class="form-text">A senha precisa atender a todas as regras abaixo:</div>
                            <ul id="listaRegras" class="senha-regras" aria-live="polite"></ul>
                        </div>

                        <div class="mb-3">
                            <label for="confirmar" class="form-label">Repita a nova senha</label>
                            <input type="password" id="confirmar" name="confirmar" class="form-control"
                                   autocomplete="new-password" maxlength="72" required aria-required="true">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" id="btnConfirmar" class="btn btn-primary flex-fill">Confirmar</button>
                            <a id="btnCancelar" class="btn btn-outline-secondary flex-fill"
                               href="<?= htmlspecialchars($base . '/login', ENT_QUOTES, 'UTF-8') ?>">Cancelar</a>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <script src="<?= htmlspecialchars($asset('public/js/senha.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <script src="<?= htmlspecialchars($asset('public/js/alterar-senha.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>
