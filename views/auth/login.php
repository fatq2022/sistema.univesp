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
    <title>Entrar | Sistema de Controle de Laminação</title>

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
    <main class="container-fluid p-0">
        <div class="row g-0 login-container">
            <div class="col-md-6 d-none d-md-block left-side"
                 role="img" aria-label="Imagem ilustrativa do sistema"></div>

            <section class="col-12 col-md-6 d-flex align-items-center justify-content-center right-side">
                <div class="card shadow p-4 pb-5 login-card">
                    <h1 class="h4 text-center mb-4">
                        <i class="fa-solid fa-lock me-2" aria-hidden="true"></i>
                        Acesso ao sistema
                    </h1>

                    <!-- Alerta dinâmico com suporte a leitores de tela -->
                    <div id="alerta" class="alert alert-danger d-none"
                         role="alert" aria-live="polite"></div>

                    <form id="formLogin" autocomplete="on">
                        <div class="mb-3">
                            <label for="usuario" class="form-label">Usuário</label>
                            <input type="text" id="usuario" name="usuario"
                                   class="form-control" placeholder="Digite seu usuário"
                                   autocomplete="username" required autofocus
                                   aria-invalid="false">
                        </div>

                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" id="senha" name="senha"
                                   class="form-control" placeholder="Digite sua senha"
                                   autocomplete="current-password" required
                                   aria-invalid="false">
                        </div>

                        <div class="d-grid">
                            <button type="submit" id="btnLogin" class="btn btn-primary">
                                Entrar
                            </button>
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <a class="btn btn-link p-0" href="<?= htmlspecialchars($base . '/recuperar-senha', ENT_QUOTES, 'UTF-8') ?>">Esqueci a senha</a>
                            <a class="btn btn-link p-0" href="<?= htmlspecialchars($base . '/alterar-senha', ENT_QUOTES, 'UTF-8') ?>">Alterar a senha</a>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <script src="<?= htmlspecialchars($asset('public/js/login.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>