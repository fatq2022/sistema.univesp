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
    <h2 class="h4 mb-4"><i class="fa-solid fa-mask-face" aria-hidden="true"></i> Shaper/Cliente</h2>
    <div id="alerta" class="alert d-none" role="alert"></div>
    <div class="card mb-4 border-0">
        <div class="card-body">
            <form id="form-shaper" novalidate onsubmit="event.preventDefault(); window.cadastrarOuAtualizar();">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="nome" class="form-label">Nome <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="text" id="nome" class="form-control form-control-sm" maxlength="150" required aria-required="true" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="tel" id="telefone" class="form-control form-control-sm" placeholder="(00) 00000-0000" maxlength="15" inputmode="tel" autocomplete="off" oninput="window.mascaraTelefone(this)">
                    </div>
                    <div class="col-md-4">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" class="form-control form-control-sm" maxlength="150" autocomplete="off">
                    </div>
                    <div class="col-12">
                        <button type="submit" id="btnSubmit" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i> Cadastrar
                        </button>
                        <button type="button" id="btnCancelarShaper" class="btn btn-sm btn-outline-secondary d-none" onclick="window.resetForm()">
                            Cancelar
                        </button>
                    </div>
                </div>
            </form>
            <div class="input-group mt-3 mb-3" role="search">
                <label for="buscarTexto" class="visually-hidden">Buscar cliente por ID, nome, telefone ou email</label>
                <input type="text" id="buscarTexto" class="form-control form-control-sm" placeholder="Buscar por ID, Nome, Telefone ou Email">
                <button type="button" class="btn btn-sm btn-outline-secondary border-secondary-subtle" title="Buscar" aria-label="Buscar" onclick="window.buscarShaper()"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary border-secondary-subtle" title="Limpar busca" aria-label="Limpar busca" onclick="document.getElementById('buscarTexto').value=''; window.listarShapers()"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </div>
        </div>
    </div>
    <div class="table-responsive rounded-1">
        <table class="table table-striped table-bordered table-hover table-sm">
            <caption class="visually-hidden">Lista de shapers e clientes cadastrados</caption>
            <thead class="table-dark">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Nome</th>
                    <th scope="col">Telefone</th>
                    <th scope="col">Email</th>
                    <th scope="col" class="text-center">Ativ/Desat</th>
                    <th scope="col" style="width: 100px;">Ações</th>
                </tr>
            </thead>
            <tbody id="lista"></tbody>
        </table>
    </div>
</div>

<!-- MODAL EXCLUIR SHAPER -->
<div class="modal fade" id="modalShaperExcluir" tabindex="-1" aria-labelledby="modalShaperExcluirTitulo" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modalShaperExcluirTitulo">
                    <i class="fa-regular fa-trash-can" aria-hidden="true"></i> Excluir Shaper
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <p>Tem certeza que deseja realmente excluir o Shaper?</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarExcluirShaper">Sim</button>
            </div>

        </div>
    </div>
</div>
