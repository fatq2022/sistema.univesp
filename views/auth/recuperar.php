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
    <title>Recuperar acesso | Sistema de Controle de Laminação</title>

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
                        Recuperar acesso
                    </h1>
                    <p class="text-muted text-center">
                        Informe o e-mail cadastrado e enviaremos um link para você criar uma nova senha.
                    </p>

                    <div id="alerta" class="alert d-none" role="alert" aria-live="polite"></div>

                    <form id="formRecuperar" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" id="email" name="email" class="form-control"
                                   placeholder="voce@exemplo.com" autocomplete="email"
                                   maxlength="150" required aria-required="true" autofocus>
                        </div>

                        <div class="d-grid">
                            <button type="submit" id="btnRecuperar" class="btn btn-primary">
                                Enviar link de recuperação
                            </button>
                        </div>
                    </form>

                    <p class="text-center mt-3 mb-0">
                        <a href="<?= htmlspecialchars($base . '/login', ENT_QUOTES, 'UTF-8') ?>">Voltar para o login</a>
                    </p>
                </div>
            </section>
        </div>
    </main>

    <script src="<?= htmlspecialchars($asset('public/js/recuperacao.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>
