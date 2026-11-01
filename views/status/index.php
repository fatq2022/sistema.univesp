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
  <h2 class="h4 mb-4"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Status</h2>
  <div id="alerta-status" class="alert d-none" role="alert"></div>
  <div class="card mb-4 border-0">
    <div class="card-body">
      <form id="form-status" novalidate onsubmit="event.preventDefault(); window.salvarCadastroStatus();">
        <div class="row g-3">
          <div class="col-md">
            <label for="descricao" class="form-label">Descrição <span class="text-danger" aria-hidden="true">*</span></label>
            <input id="descricao" class="form-control form-control-sm" maxlength="45" required aria-required="true" autocomplete="off">
            <input id="ordem" type="hidden">
          </div>
          <div class="col-12">
            <button type="submit" id="btnSubmitStatus" class="btn btn-sm btn-primary">
              <i class="fa-solid fa-plus" aria-hidden="true"></i> Cadastrar
            </button>
            <button type="button" id="btnCancelarEdicao" class="btn btn-sm btn-outline-secondary d-none" onclick="window.cancelarEdicaoStatus()">
              Cancelar
            </button>
          </div>
        </div>
      </form>
      <div class="input-group mt-3 mb-3" role="search">
        <label for="buscarStatus" class="visually-hidden">Buscar status por ID ou descrição</label>
        <input type="text" id="buscarStatus" class="form-control form-control-sm" placeholder="Buscar por ID ou Descrição">
        <button type="button" class="btn btn-sm btn-outline-secondary" title="Buscar" aria-label="Buscar" onclick="window.buscarStatus()"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
        <button type="button" class="btn btn-sm btn-outline-secondary" title="Limpar busca" aria-label="Limpar busca" onclick="document.getElementById('buscarStatus').value=''; window.listarStatus()"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
      </div>
    </div>
  </div>

  <div class="alert alert-info py-2" style="font-size: 0.85rem;">
    <i class="fa-solid fa-grip-vertical me-2" aria-hidden="true"></i> Arraste os itens (ou use as setas de cada linha) para reordenar a sequência do fluxo.
  </div>

  <div class="table-responsive rounded-1">
    <table class="table table-striped table-bordered table-hover table-sm">
      <caption class="visually-hidden">Lista de status dos pedidos, na ordem do fluxo</caption>
      <thead class="table-dark">
        <tr>
          <th scope="col" style="width: 50px;"><span class="visually-hidden">Reordenar</span></th>
          <th scope="col" style="width: 70px;">ID</th>
          <th scope="col">Descrição</th>
          <th scope="col" style="width: 100px;" class="text-center">Ativo</th>
          <th scope="col" style="width: 170px;" class="text-center">Ações</th>
        </tr>
      </thead>
      <tbody id="lista-status"></tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalStatusExcluirBase" tabindex="-1" aria-labelledby="modalStatusExcluirBaseTitulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalStatusExcluirBaseTitulo"><i class="fa-regular fa-trash-can" aria-hidden="true"></i> Excluir Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p>Tem certeza que deseja realmente excluir este Status?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não</button>
        <button type="button" class="btn btn-primary" id="btnConfirmarExcluirStatus">Sim</button>
      </div>
    </div>
  </div>
</div>
