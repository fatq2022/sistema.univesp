<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user'])) {
    http_response_code(401);
    exit;
}
?>
<div class="container-fluid">

    <h2 class="h4 mb-4"><i class="fa-solid fa-box" aria-hidden="true"></i> Pedido</h2>

    <ul class="nav nav-tabs" role="tablist" aria-label="Seções de pedidos">

        <li class="nav-item">
            <button type="button" class="nav-link active" id="tabBtnPedidoStatus" role="tab" aria-selected="true" aria-controls="tabPedidoStatus" data-bs-toggle="tab" data-bs-target="#tabPedidoStatus">
                <i class="fa-solid fa-arrows-turn-to-dots" aria-hidden="true"></i>
                <!-- Mobile -->
                <span class="d-inline d-md-none"> Status</span>
                <!-- Desktop -->
                <span class="d-none d-md-inline"> Status do Pedido</span>
            </button>
        </li>

        <li class="nav-item">
            <button type="button" class="nav-link btn-config" id="tabBtnGerenciamentoPedido" role="tab" aria-selected="false" aria-controls="tabGerenciamentoPedido" data-bs-toggle="tab" data-bs-target="#tabGerenciamentoPedido">
                <i class="fa-solid fa-gear" aria-hidden="true"></i>
                <!-- Mobile -->
                <span class="d-inline d-md-none"> Gerenciamento</span>
                <!-- Desktop -->
                <span class="d-none d-md-inline"> Gerenciamento de Pedido</span>
            </button>
        </li>

    </ul>

    <div class="tab-content pt-3 module-content">
        <!-- STATUS -->
        <div class="tab-pane fade show active" id="tabPedidoStatus" role="tabpanel" aria-labelledby="tabBtnPedidoStatus" tabindex="0">
            <div class="container-fluid">
                <div class="input-group input-group-sm mb-3">
                    <!-- O ID tem de ser exatamente igual ao do JS -->
                    <label for="inputBuscaPedido" class="visually-hidden">Buscar pedido ou shaper</label>
                    <input type="text" id="inputBuscaPedido" class="form-control" placeholder="Buscar pedido ou shaper...">
                    <button class="btn btn-primary" type="button" title="Buscar" aria-label="Buscar" onclick="window.executarBuscaPedido()">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="accordion accordion-flush" id="accordionPanelsStayOpenExample">

                </div>
                <div id="buscaPedidoVazia" class="alert alert-secondary d-none" role="status">Nenhum pedido encontrado para essa busca.</div>
            </div>
        </div>

        <!-- GERENCIAMENTO -->
        <div class="tab-pane fade" id="tabGerenciamentoPedido" role="tabpanel" aria-labelledby="tabBtnGerenciamentoPedido" tabindex="0">

            <div class="container-fluid">

                <div id="alertaPedido" class="alert d-none" role="alert"></div>

                <div class="card mb-4 border-0">

                    <div class="card-body">

                        <div class="row g-3">

                            <div class="col-md-4">
                                <label for="dataPedido" class="form-label">Data</label>
                                <input type="date" id="dataPedido" class="form-control form-control-sm">
                            </div>

                            <!-- TIPO DE BLOCO -->
                            <div class="col-md-3">
                                <label for="tipo_blocoPedido" class="form-label">Tipo de Bloco</label>
                                <select id="tipo_blocoPedido" class="form-control form-control-sm">
                                    <option value="PU">PU (10 dias)</option>
                                    <option value="EPS">EPS (25 dias)</option>
                                </select>
                            </div>

                            <div class="col-md-5">
                                <label for="id_shaperPedido" class="form-label">Shaper/Cliente</label>
                                <div class="input-group input-group-sm mb-3">
                                    <select id="id_shaperPedido" class="form-control">
                                    </select>
                                </div>
                            </div>

                            <!-- COMPOSIÇÃO -->
                            <div class="col-md-3">
                                <label for="id_composicaoPedido" class="form-label">Composição</label>
                                <div class="input-group input-group-sm mb-3">
                                    <select id="id_composicaoPedido" class="form-control"></select>
                                    <button class="btn btn-outline-secondary btn-config" type="button" title="Gerenciar composições" aria-label="Gerenciar composições" onclick="abrirModalGenerico('composicao')">
                                        <i class="fa-solid fa-gear" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- VARIAÇÃO -->
                            <div class="col-md-3">
                                <label for="id_variacaoPedido" class="form-label">Variação</label>
                                <div class="input-group input-group-sm mb-3">
                                    <select id="id_variacaoPedido" class="form-control"></select>
                                    <button class="btn btn-outline-secondary btn-config" type="button" title="Gerenciar variações" aria-label="Gerenciar variações" onclick="abrirModalGenerico('variacao')">
                                        <i class="fa-solid fa-gear" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- ACABAMENTO -->
                            <div class="col-md-3">
                                <label for="id_acabamentoPedido" class="form-label">Acabamento</label>
                                <div class="input-group input-group-sm mb-3">
                                    <select id="id_acabamentoPedido" class="form-control"></select>
                                    <button class="btn btn-outline-secondary btn-config" type="button" title="Gerenciar acabamentos" aria-label="Gerenciar acabamentos" onclick="abrirModalGenerico('acabamento')">
                                        <i class="fa-solid fa-gear" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- CONFIG QUILHA -->
                            <div class="col-md-3">
                                <label for="id_configuracaoquilhaPedido" class="form-label">Config. Quilha</label>
                                <div class="input-group input-group-sm mb-3">
                                    <select id="id_configuracaoquilhaPedido" class="form-control"></select>
                                    <button class="btn btn-outline-secondary btn-config" type="button" title="Gerenciar configurações de quilha" aria-label="Gerenciar configurações de quilha" onclick="abrirModalGenerico('configQuilha')">
                                        <i class="fa-solid fa-gear" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- SISTEMA QUILHA -->
                            <div class="col-md-3">
                                <label for="id_sistemaquilhaPedido" class="form-label">Sistema Quilha</label>
                                <div class="input-group input-group-sm mb-3">
                                    <select id="id_sistemaquilhaPedido" class="form-control"></select>
                                    <button class="btn btn-outline-secondary btn-config" type="button" title="Gerenciar sistemas de quilha" aria-label="Gerenciar sistemas de quilha" onclick="abrirModalGenerico('sistemaQuilha')">
                                        <i class="fa-solid fa-gear" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- CORES -->
                            <div class="col-md-6 mb-3">
                                <label for="id_cor" class="form-label">Cores</label>
                                <div class="input-group-custom">
                                    <select id="id_cor" placeholder="Selecione..." multiple></select>
                                    <button class="choice_plus" type="button" title="Gerenciar cores" aria-label="Gerenciar cores" onclick="abrirModalGenerico('cores')">
                                        <i class="fa-solid fa-gear" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- TECIDO -->
                            <div class="col-md-6 mb-3">
                                <label for="id_tecidoPedido" class="form-label">Tecido</label>
                                <div class="input-group-custom">
                                    <select id="id_tecidoPedido" placeholder="Selecione..." multiple></select>
                                    <button class="choice_plus btn-config" type="button" title="Gerenciar tecidos" aria-label="Gerenciar tecidos" onclick="abrirModalGenerico('tecido')">
                                        <i class="fa-solid fa-gear" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- OBSERVAÇÃO -->
                            <div class="col-12">
                                <label for="observacaoPedido" class="form-label">Observação</label>
                                <textarea id="observacaoPedido" class="form-control form-control-sm"></textarea>
                            </div>

                            <!-- BOTÕES -->
                            <div class="col-12">
                                <button type="button" id="btnSubmitPedido" class="btn btn-sm btn-primary" onclick="cadastrarOuAtualizarPedido()">
                                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Cadastrar
                                </button>

                                <button type="button" id="btnCancelarPedido" class="btn btn-sm btn-outline-secondary d-none" onclick="cancelarEdicaoPedido()">
                                    <i class="fa-solid fa-ban" aria-hidden="true"></i> Cancelar
                                </button>
                            </div>

                        </div> <!-- FECHA ROW AQUI -->

                    </div>
                </div>
            </div>

            <div class="table-responsive rounded-1">

                <table class="table table-striped table-hover table-sm">
                    <caption class="visually-hidden">Lista de pedidos cadastrados</caption>

                    <thead>
                        <tr class="table-dark">
                            <th scope="col"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none fw-bold" onclick="window.listarPedidos('id_pedido')" aria-label="Ordenar por ID">ID <i class="fa-solid fa-sort" aria-hidden="true"></i></button></th>
                            <th scope="col"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none fw-bold" onclick="window.listarPedidos('status')" aria-label="Ordenar por Status">Status <i class="fa-solid fa-sort" aria-hidden="true"></i></button></th>
                            <th scope="col"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none fw-bold" onclick="window.listarPedidos('data')" aria-label="Ordenar por Data">Data <i class="fa-solid fa-sort" aria-hidden="true"></i></button></th>
                            <th scope="col"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none fw-bold" onclick="window.listarPedidos('shaper')" aria-label="Ordenar por Shaper">Shaper <i class="fa-solid fa-sort" aria-hidden="true"></i></button></th>
                            <th scope="col"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none fw-bold" onclick="window.listarPedidos('observacao')" aria-label="Ordenar por Obs">Obs <i class="fa-solid fa-sort" aria-hidden="true"></i></button></th>
                            <th scope="col">Ações</th>
                        </tr>
                    </thead>

                    <tbody id="listaPedidos"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>