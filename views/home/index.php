<?php
/* ========================================
   SISTEMA DE CONTROLE DE LAMINAÇÃO
   Layout profissional e responsivo
======================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base = isset($base) ? $base : '/sistema';
$base = '/' . trim($base, '/');
if ($base === '/') {
    $base = '';
}

$asset = function ($path) use ($base) {
    return $base . '/' . ltrim($path, '/');
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Sistema de Controle de Laminação</title>

    <script>
        window.BASE_URL = <?= json_encode(
            rtrim($base, '/'),
            JSON_UNESCAPED_SLASHES
        ) ?>;
    </script>

    <!-- Bootstrap -->
    <link rel="stylesheet"
          href="<?= htmlspecialchars($asset('public/css/Bootstrap.css'), ENT_QUOTES, 'UTF-8') ?>">

    <!-- Ícones -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bibliotecas do sistema -->
    <link rel="stylesheet"
          href="<?= htmlspecialchars($asset('public/css/Choices.css'), ENT_QUOTES, 'UTF-8') ?>">

    <link rel="stylesheet"
          href="<?= htmlspecialchars($asset('public/css/app.css'), ENT_QUOTES, 'UTF-8') ?>?v=1.1.0">

    <link rel="stylesheet"
          href="<?= htmlspecialchars($asset('public/css/home.css'), ENT_QUOTES, 'UTF-8') ?>?v=1.0.6">

    <link rel="stylesheet"
          href="<?= htmlspecialchars($asset('lib/rtimeline/css/rtimeline.css'), ENT_QUOTES, 'UTF-8') ?>">

    <!-- Layout profissional -->
    <style>
        :root {
            --menu-width: 250px;
            --header-height: 66px;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --sidebar-bg: #111827;
            --sidebar-text: #cbd5e1;
            --page-bg: #f3f6fb;
            --border-color: #e5eaf2;
        }

        * {
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding-top: var(--header-height);
            background: var(--page-bg);
            color: #1e293b;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            font-size: 14px;
        }

        button {
            font: inherit;
        }

        /* CABEÇALHO */

        .app-header {
            position: fixed;
            z-index: 1040;
            inset: 0 0 auto;
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 0 25px;
            color: #fff;
            background: #fff;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 2px 12px rgba(15, 23, 42, .04);
        }

        .app-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            color: #0f172a;
            text-decoration: none;
        }

        .app-brand-icon {
            display: grid;
            place-items: center;
            flex-shrink: 0;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(145deg, #3b82f6, #1d4ed8);
            box-shadow: 0 4px 10px rgba(37, 99, 235, .2);
            font-size: 19px;
        }

        .app-brand-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .app-brand-text strong {
            overflow: hidden;
            font-size: 15px;
            font-weight: 750;
            line-height: 1.3;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .app-brand-text small {
            margin-top: 3px;
            color: #64748b;
            font-size: 11px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .system-status {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 7px 10px;
            border: 1px solid #d1fae5;
            border-radius: 20px;
            color: #047857;
            background: #ecfdf5;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .system-status span {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .user-avatar {
            display: grid;
            place-items: center;
            flex-shrink: 0;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            color: #1d4ed8;
            background: #dbeafe;
            font-size: 15px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
            max-width: 190px;
        }

        .user-info strong {
            overflow: hidden;
            color: #1e293b;
            font-size: 12px;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-info small {
            margin-top: 2px;
            color: #64748b;
            font-size: 11px;
        }

        .logout-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 12px;
            border: 1px solid #fee2e2;
            border-radius: 9px;
            color: #b91c1c;
            background: #fff;
            font-size: 12px;
            font-weight: 650;
            transition: .2s;
        }

        .logout-btn:hover {
            color: #fff;
            background: #dc2626;
            border-color: #dc2626;
        }

        /* MENU LATERAL */

        .app-sidebar {
            position: fixed;
            z-index: 1030;
            top: var(--header-height);
            bottom: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            width: var(--menu-width);
            padding: 24px 15px 16px;
            overflow-y: auto;
            color: var(--sidebar-text);
            background: var(--sidebar-bg);
        }

        .sidebar-heading {
            margin: 0 12px 13px;
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.3px;
            text-transform: uppercase;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .sidebar-nav .nav-item {
            width: 100%;
        }

        .sidebar-nav .nav-link {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            min-height: 46px;
            padding: 0 13px;
            border: 1px solid transparent;
            border-radius: 10px;
            color: var(--sidebar-text);
            background: transparent;
            text-align: left;
            font-size: 13px;
            font-weight: 550;
            transition: background .2s, color .2s;
            cursor: pointer;
        }

        .sidebar-nav .nav-link > i {
            display: grid;
            place-items: center;
            flex-shrink: 0;
            width: 21px;
            color: #94a3b8;
            font-size: 15px;
        }

        .sidebar-nav .nav-link:hover {
            color: #fff;
            background: #1f2937;
        }

        .sidebar-nav .nav-link:hover > i {
            color: #93c5fd;
        }

        .sidebar-nav .nav-link.active {
            color: #fff;
            border-color: rgba(96, 165, 250, .18);
            background: linear-gradient(100deg, #2563eb, #1d4ed8);
            box-shadow: 0 4px 12px rgba(37, 99, 235, .17);
        }

        .sidebar-nav .nav-link.active > i {
            color: #fff;
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 22px;
        }

        .sidebar-footer-card {
            padding: 14px;
            border: 1px solid #293548;
            border-radius: 12px;
            background: #182233;
        }

        .sidebar-footer-card strong {
            display: block;
            margin-bottom: 5px;
            color: #f1f5f9;
            font-size: 12px;
        }

        .sidebar-footer-card small {
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.5;
        }

        .sidebar-footer-line {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: #93c5fd;
            font-size: 11px;
            font-weight: 700;
        }

        /* CONTEÚDO */

        .app-main {
            min-width: 0;
            min-height: calc(100vh - var(--header-height));
            margin-left: var(--menu-width);
            padding: 28px;
        }

        .content-shell {
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
        }

        .tab-content,
        .tab-pane {
            min-width: 0;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        /* TOAST E MODAL */

        .toast-container {
            z-index: 1080;
        }

        #modalPedidoDetalhe {
            z-index: 1060;
        }

        .modal-backdrop {
            z-index: 1050;
        }

        .modal-content {
            overflow: hidden;
            border: 1px solid #e5eaf2;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, .2);
        }

        .modal-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-title {
            font-size: 16px;
            font-weight: 750;
        }

        .modal-body {
            padding: 22px;
        }

        .modal-footer {
            padding: 14px 22px;
            border-top: 1px solid var(--border-color);
        }

        /* NAVEGAÇÃO MOBILE */

        .mobile-nav {
            display: none;
        }

        /* TABLETS */

        @media (max-width: 1199.98px) {
            :root {
                --menu-width: 220px;
            }

            .app-main {
                padding: 22px;
            }

            .app-header {
                padding: 0 18px;
            }

            .system-status {
                display: none;
            }
        }

        /* CELULARES */

        @media (max-width: 991.98px) {
            :root {
                --header-height: 60px;
            }

            body {
                padding-top: var(--header-height);
            }

            .app-header {
                padding: 0 14px;
            }

            .app-brand {
                gap: 9px;
            }

            .app-brand-icon {
                width: 36px;
                height: 36px;
                border-radius: 10px;
                font-size: 16px;
            }

            .app-brand-text strong {
                max-width: 230px;
                font-size: 12px;
            }

            .app-brand-text small {
                font-size: 10px;
            }

            .app-sidebar {
                display: none;
            }

            .app-main {
                min-height: calc(100vh - var(--header-height));
                margin-left: 0;
                padding: 18px 13px 90px;
            }

            .user-info {
                display: none;
            }

            .user-avatar {
                width: 34px;
                height: 34px;
            }

            .header-right {
                gap: 9px;
            }

            .logout-btn {
                width: 36px;
                min-height: 36px;
                padding: 0;
                border-radius: 9px;
            }

            .logout-btn .logout-label {
                display: none;
            }

            .mobile-nav {
                position: fixed;
                z-index: 1040;
                right: 0;
                bottom: 0;
                left: 0;
                display: flex;
                align-items: center;
                justify-content: space-around;
                gap: 4px;
                min-height: 68px;
                padding: 7px 8px calc(7px + env(safe-area-inset-bottom));
                border-top: 1px solid #e2e8f0;
                background: rgba(255, 255, 255, .97);
                box-shadow: 0 -5px 20px rgba(15, 23, 42, .06);
                backdrop-filter: blur(12px);
            }

            .mobile-nav .nav-link {
                display: flex;
                flex: 1;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 5px;
                min-width: 0;
                min-height: 49px;
                padding: 5px 3px;
                border-radius: 10px;
                color: #64748b;
                background: transparent;
                font-size: 10px;
                font-weight: 650;
                white-space: nowrap;
                cursor: pointer;
                border: none;
            }

            .mobile-nav .nav-link i {
                font-size: 17px;
            }

            .mobile-nav .nav-link.active {
                color: #1d4ed8;
                background: #eff6ff;
            }
        }

        @media (max-width: 380px) {
            .app-brand-text strong {
                max-width: 165px;
                font-size: 11px;
            }

            .app-brand-text small {
                display: none;
            }

            .mobile-nav .nav-link {
                font-size: 9px;
            }
        }
    </style>
</head>
<body id="up">

<a class="visually-hidden-focusable" href="#conteudo-principal">Ir para o conteúdo principal</a>

<!-- CABEÇALHO -->
<header class="app-header" role="banner">
    <a href="#dashboard" class="app-brand" onclick="ativarAba('#dashboard'); return false;">
        <span class="app-brand-icon">
            <i class="fa-solid fa-industry" aria-hidden="true"></i>
        </span>
        <span class="app-brand-text">
            <strong>Sistema de Laminação</strong>
            <small>Controle de produção</small>
        </span>
    </a>

    <div class="header-right">
        <div class="system-status">
            <span></span>
            Sistema operacional
        </div>

        <div class="user-area">
            <span class="user-avatar" id="usuario-mobile-tooltip" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Usuário conectado">
                <i class="fa-solid fa-user" aria-hidden="true"></i>
            </span>
            <div class="user-info">
                <strong id="usuario-logado">Usuário conectado</strong>
                <small>Área administrativa</small>
            </div>
        </div>

        <a href="<?= htmlspecialchars($base . '/diagnostico', ENT_QUOTES, 'UTF-8') ?>" class="logout-btn me-2" title="Diagnóstico de erros com IA" aria-label="Diagnóstico de erros com IA" style="text-decoration:none">
            <i class="fa-solid fa-stethoscope" aria-hidden="true"></i>
            <span class="logout-label">Diagnóstico</span>
        </a>

        <button type="button" id="btnLogout" class="logout-btn" title="Sair do sistema" aria-label="Sair do sistema">
            <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
            <span class="logout-label">Sair</span>
        </button>
    </div>
</header>

<!-- MENU LATERAL DESKTOP -->
<aside class="app-sidebar d-none d-lg-flex" aria-label="Menu lateral">
    <div class="sidebar-heading">Menu principal</div>

    <ul class="sidebar-nav">
        <li class="nav-item">
            <button type="button" class="nav-link active" data-menu-target="#dashboard" aria-current="page" onclick="ativarAba('#dashboard');">
                <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
                <span>Dashboard</span>
            </button>
        </li>

        <li class="nav-item">
            <button type="button" class="nav-link" data-menu-target="#pedido" onclick="ativarAba('#pedido');">
                <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                <span>Pedidos</span>
            </button>
        </li>

        <li class="nav-item">
            <button type="button" class="nav-link" data-menu-target="#status" onclick="ativarAba('#status');">
                <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                <span>Status dos pedidos</span>
            </button>
        </li>

        <li class="nav-item">
            <button type="button" class="nav-link" data-menu-target="#shaper" onclick="ativarAba('#shaper');">
                <i class="fa-solid fa-users" aria-hidden="true"></i>
                <span>Shaper / Cliente</span>
            </button>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="sidebar-footer-card">
            <div class="sidebar-footer-line">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                Ambiente de trabalho
            </div>
            <strong>Controle de Laminação</strong>
            <small>Gestão de pedidos, acompanhamento de status e cadastro de clientes.</small>
        </div>
    </div>
</aside>

<!-- CONTEÚDO PRINCIPAL -->
<main class="app-main" id="conteudo-principal" role="main" tabindex="-1">
    <div class="content-shell">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="dashboard" role="region" aria-label="Dashboard">
                <div id="conteudo-dashboard"></div>
            </div>

            <div class="tab-pane fade" id="pedido" role="region" aria-label="Pedidos">
                <div id="conteudo-pedido"></div>
            </div>

            <div class="tab-pane fade" id="status" role="region" aria-label="Status dos pedidos">
                <div id="conteudo-status"></div>
            </div>

            <div class="tab-pane fade" id="shaper" role="region" aria-label="Shaper e clientes">
                <div id="conteudo-shaper"></div>
            </div>
        </div>
    </div>
</main>

<!-- MENU INFERIOR MOBILE -->
<nav class="mobile-nav d-lg-none" aria-label="Navegação principal">
    <button type="button" class="nav-link active" data-menu-target="#dashboard" aria-label="Dashboard" aria-current="page" onclick="ativarAba('#dashboard');">
        <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
        <span>Dashboard</span>
    </button>

    <button type="button" class="nav-link" data-menu-target="#pedido" aria-label="Pedido" onclick="ativarAba('#pedido');">
        <i class="fa-solid fa-box-open" aria-hidden="true"></i>
        <span>Pedido</span>
    </button>

    <button type="button" class="nav-link" data-menu-target="#status" aria-label="Status" onclick="ativarAba('#status');">
        <i class="fa-solid fa-list-check" aria-hidden="true"></i>
        <span>Status</span>
    </button>

    <button type="button" class="nav-link" data-menu-target="#shaper" aria-label="Shaper e clientes" onclick="ativarAba('#shaper');">
        <i class="fa-solid fa-users" aria-hidden="true"></i>
        <span>Clientes</span>
    </button>
</nav>

<!-- NOTIFICAÇÕES -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div class="toast" id="alertToast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
        <div class="toast-header">
            <strong class="me-auto">
                <i class="fa-regular fa-bell me-2" aria-hidden="true"></i>
                <span id="toast-title"></span>
            </strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fechar"></button>
        </div>
        <div class="toast-body">
            <span id="toast-text"></span>
        </div>
    </div>
</div>

<!-- MODAL DETALHE DO PEDIDO -->
<div class="modal fade" id="modalPedidoDetalhe" tabindex="-1" aria-labelledby="modalPedidoDetalheLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPedidoDetalheLabel">
                    <i class="fa-solid fa-clipboard-list me-2 text-primary" aria-hidden="true"></i>
                    Detalhe do Pedido
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Detalhe</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="if (typeof imprimirDetalhePedido === 'function') imprimirDetalhePedido();">
                    <i class="fa-solid fa-print me-1" aria-hidden="true"></i>
                    Abrir / PDF
                </button>
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MODAIS DA TELA DE PEDIDO ===================== -->
<style>
    /* confirmações abertas por cima de outro modal */
    .modal-backdrop ~ .modal-backdrop { z-index: 1065; }
    #modalExcluirGerenciamento, #modalPedidoExcluir, #modalStatusExcluir { z-index: 1070; }
</style>

<!-- Cadastro genérico (Composição, Variação, Acabamento, Quilhas, Cores, Tecidos) -->
<div class="modal fade" id="modalGenerico" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" id="modalInput" class="form-control" maxlength="100" autocomplete="off"
                           onkeydown="if (event.key === 'Enter') { event.preventDefault(); window.salvarComposicao(); }">
                    <button type="button" id="btnSalvarComposicao" class="btn btn-primary" onclick="window.salvarComposicao()">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Cadastrar
                    </button>
                    <button type="button" id="btnCancelarComposicao" class="btn btn-outline-secondary d-none" onclick="window.cancelarEdicaoComposicao()">
                        Cancelar
                    </button>
                </div>
                <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 70px;">ID</th>
                                <th>Descrição</th>
                                <th class="text-center" style="width: 80px;">Ativo</th>
                                <th style="width: 110px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="listaComposicoesModal"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmar exclusão de item do gerenciamento (lixeira) -->
<div class="modal fade" id="modalExcluirGerenciamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-regular fa-trash-can" aria-hidden="true"></i> Excluir item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">Deseja realmente excluir este item?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarExcluirGerenciamento">Sim, excluir</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmar exclusão de pedido -->
<div class="modal fade" id="modalPedidoExcluir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-regular fa-trash-can" aria-hidden="true"></i> Excluir pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">Deseja realmente excluir este pedido? Os status registrados nele também serão removidos.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarDeletarPedido">Sim, excluir</button>
            </div>
        </div>
    </div>
</div>

<!-- Atualizar / editar status de um pedido -->
<div class="modal fade" id="modalStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Atualizar status do pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="statusPedidoId">
                <input type="hidden" id="statusIndex">
                <input type="hidden" id="statusIdReal">
                <div class="mb-3">
                    <label class="form-label" for="statusDescricao">Status</label>
                    <input type="text" id="statusDescricao" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="statusData">Data e hora</label>
                    <input type="datetime-local" id="statusData" class="form-control">
                </div>
                <div>
                    <label class="form-label" for="statusObs">Observação</label>
                    <textarea id="statusObs" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarStatusPedido" onclick="window.salvarStatusPedido()">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmar exclusão de um status do pedido -->
<div class="modal fade" id="modalStatusExcluir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-regular fa-trash-can" aria-hidden="true"></i> Excluir status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">Deseja realmente excluir este registro de status?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarExcluir">Sim, excluir</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Ao fechar uma confirmação aberta sobre outro modal, mantém a rolagem do modal de baixo bloqueada
    document.addEventListener('hidden.bs.modal', function () {
        if (document.querySelector('.modal.show')) {
            document.body.classList.add('modal-open');
        }
    });
</script>

<!-- BOOTSTRAP JS -->
<script src="<?= htmlspecialchars($asset('public/js/Bootstrap.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

<!-- BIBLIOTECAS E SCRIPTS DO SISTEMA -->
<script src="<?= htmlspecialchars($asset('public/js/Choices.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($asset('public/js/Sortable.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

<script src="<?= htmlspecialchars($asset('public/js/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($asset('public/js/home.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($asset('public/js/shaper.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($asset('public/js/pedido-status.js'), ENT_QUOTES, 'UTF-8') ?>?v=1.1.0"></script>
<script src="<?= htmlspecialchars($asset('public/js/pedido.js'), ENT_QUOTES, 'UTF-8') ?>?v=1.1.0"></script>
<script src="<?= htmlspecialchars($asset('public/js/status.js'), ENT_QUOTES, 'UTF-8') ?>?v=1.1.0"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const botaoSair = document.getElementById('btnLogout');
    if (botaoSair) {
        botaoSair.addEventListener('click', function () {
            if (typeof window.logout === 'function') {
                window.logout();
            } else {
                console.error('Função logout() não encontrada.');
                alert('A função de sair não foi carregada.');
            }
        });
    }
});

window.ativarAba = function(seletorAlvo) {
    document.querySelectorAll('[data-menu-target]').forEach(function (btn) {
        const ativo = btn.getAttribute('data-menu-target') === seletorAlvo;
        btn.classList.toggle('active', ativo);
        if (ativo) { btn.setAttribute('aria-current', 'page'); } else { btn.removeAttribute('aria-current'); }
    });

    // Só os painéis do menu principal; as abas internas de Pedido têm o próprio controle (Bootstrap)
    document.querySelectorAll('.content-shell > .tab-content > .tab-pane').forEach(function (pane) {
        pane.classList.remove('show', 'active');
        pane.style.display = 'none';
    });

    const painelAlvo = document.querySelector(seletorAlvo);
    if (painelAlvo) {
        painelAlvo.style.display = 'block';
        setTimeout(() => painelAlvo.classList.add('show', 'active'), 10);
    }

    if (seletorAlvo === '#dashboard' && typeof carregarDashboard === 'function') {
        carregarDashboard();
    } else if (seletorAlvo === '#pedido' && typeof carregarPedido === 'function') {
        carregarPedido();
    } else if (seletorAlvo === '#status' && typeof carregarStatus === 'function') {
        carregarStatus();
    } else if (seletorAlvo === '#shaper' && typeof carregarShaper === 'function') {
        carregarShaper();
    }

    window.scrollTo(0, 0);
};

(function () {
    'use strict';
    ativarAba('#dashboard');

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
        if (window.bootstrap && bootstrap.Tooltip) {
            bootstrap.Tooltip.getOrCreateInstance(element);
        }
    });
})();
</script>
</body>
</html>