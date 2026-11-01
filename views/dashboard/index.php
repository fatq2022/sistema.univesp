<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    http_response_code(401);
    exit;
}
?>
<div class="row g-3 mb-2" role="region" aria-label="Resumo: total de pedidos e de shapers">
    <div class="col-md-3 col-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="d-grid place-items-center text-primary fs-3">
                    <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                </span>
                <div>
                    <h6 class="card-subtitle mb-1 text-uppercase fw-bold" style="font-size: 0.75rem; color:#475569;">Pedidos</h6>
                    <h2 class="card-title mb-0 fw-bold" id="cardTotalPedidos" aria-live="polite" aria-label="Total de pedidos">0</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="d-grid place-items-center text-primary fs-3">
                    <i class="fa-solid fa-users" aria-hidden="true"></i>
                </span>
                <div>
                    <h6 class="card-subtitle mb-1 text-uppercase fw-bold" style="font-size: 0.75rem; color:#475569;">Shapers / Clientes</h6>
                    <h2 class="card-title mb-0 fw-bold" id="cardTotalShapers" aria-live="polite" aria-label="Total de shapers">0</h2>
                </div>
            </div>
        </div>
    </div>
</div>

<h6 class="text-muted text-uppercase fw-bold mt-4 mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">
    Pedidos por status
</h6>

<div class="row" id="containerStatusDinamico">
    <div class="col-12 text-muted">
        <div class="spinner-border spinner-border-sm me-2"></div>
        Carregando...
    </div>
</div>