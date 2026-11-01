window.excluirStatusSelecionado = null;
window.statusMap = {};

function apiUrlStatusLocal(recurso, id = null) {
  const baseUrl = window.BASE_URL || '/sistema';
  if (id !== null && id !== undefined && id !== "") {
    return `${baseUrl}/api/${recurso}/${encodeURIComponent(id)}`;
  }
  return `${baseUrl}/api/${recurso}`;
}


// ===============================
// ALERTAS DE PRAZO (espelha helpers/Prazo.php)
// PU: normal 0-6 | crítico 7-10 | vencido 11+      (prazo final: 10 dias)
// EPS: normal 0-15 | crítico 16-25 | vencido 26+   (prazo final: 25 dias)
// Dias contados a partir do marco zero ("Em produção"). Só vale para pedidos em produção.
// ===============================
window.PRAZOS_BLOCO = { PU: { prazo: 10, normal: 6 }, EPS: { prazo: 25, normal: 15 } };

window.classificarPrazo = function (tipo, inicioProducao, dataFinalizacao, hoje) {
  const regra = window.PRAZOS_BLOCO[String(tipo || "PU").toUpperCase()] || window.PRAZOS_BLOCO.PU;
  if (dataFinalizacao) return { nivel: "finalizado", decorridos: null, restantes: null };
  if (!inicioProducao) return { nivel: "aguardando", decorridos: null, restantes: null };

  const dia = (v) => {
    const [a, m, d] = String(v).slice(0, 10).split("-").map(Number);
    return Date.UTC(a, m - 1, d);
  };
  const ref = hoje || new Date().toISOString().slice(0, 10);
  const decorridos = Math.round((dia(ref) - dia(inicioProducao)) / 86400000);
  const restantes = regra.prazo - decorridos;

  let nivel = "normal";
  if (decorridos > regra.prazo) nivel = "vencido";
  else if (decorridos > regra.normal) nivel = "critico";
  return { nivel, decorridos, restantes };
};

// Cada nível tem ícone + texto + identificação semântica (role/data-prazo), além da cor.
window.ALERTAS_PRAZO = {
  aguardando: { classe: "prazo-aguardando", icone: "fa-hourglass-start", rotulo: "Aguardando produção" },
  normal: { classe: "prazo-normal", icone: "fa-circle-check", rotulo: "Prazo normal" },
  critico: { classe: "prazo-critico", icone: "fa-triangle-exclamation", rotulo: "Prazo crítico" },
  vencido: { classe: "prazo-vencido", icone: "fa-circle-xmark", rotulo: "Prazo vencido" },
  finalizado: { classe: "prazo-finalizado", icone: "fa-flag-checkered", rotulo: "Finalizado" },
};

window.montarAlertaPrazo = function (pedido) {
  const c = window.classificarPrazo(pedido.tipo_bloco, pedido.data_inicio_producao, pedido.data_finalizacao);
  const cfg = window.ALERTAS_PRAZO[c.nivel];
  const fmt = (v) => (v ? String(v).slice(0, 10).split("-").reverse().join("/") : "-");

  let detalhe = "";
  if (c.nivel === "aguardando") {
    detalhe = "ainda não entrou em produção";
  } else if (c.nivel === "finalizado") {
    detalhe = "entrega concluída em " + fmt(pedido.data_finalizacao);
  } else if (c.restantes > 0) {
    detalhe = `faltam ${c.restantes} ${c.restantes === 1 ? "dia" : "dias"} (entrega prevista ${fmt(pedido.data_prevista_entrega)})`;
  } else if (c.restantes === 0) {
    detalhe = `entrega prevista para hoje (${fmt(pedido.data_prevista_entrega)})`;
  } else {
    const atraso = Math.abs(c.restantes);
    detalhe = `atrasado há ${atraso} ${atraso === 1 ? "dia" : "dias"} (previsto ${fmt(pedido.data_prevista_entrega)})`;
  }

  const texto = `${cfg.rotulo}: ${detalhe}`;
  return `<span class="badge-prazo ${cfg.classe}" data-prazo="${c.nivel}" title="${texto}">
            <i class="fa-solid ${cfg.icone}" aria-hidden="true"></i>
            <span class="badge-prazo-rotulo">${cfg.rotulo}</span>
            <span class="badge-prazo-detalhe">${detalhe}</span>
          </span>`;
};

// ===============================
// LISTAR STATUS (TIMELINE)
// ===============================
window.listarPedidosStatus = async function () {
  window.statusMap = {};
  const container = document.getElementById("accordionPanelsStayOpenExample");
  if (!container) return;

  const esc = (v) => (window.escHtml ? window.escHtml(v) : String(v ?? ""));
  const aberto = container.querySelector(".accordion-collapse.show");
  let idAberto = aberto ? aberto.id : null;

  // Skeleton Screen (Efeito de carregamento)
  const skeletonHTML = `
        <div class="accordion-item placeholder-glow" aria-hidden="true">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed disabled" type="button">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col-8"><span class="placeholder col-6"></span></div>
                            <div class="col-auto ms-auto"><span class="placeholder col-3"></span></div>
                        </div>
                    </div>
                </button>
            </h2>
        </div>`.repeat(4);

  container.innerHTML = skeletonHTML;

  try {
    const resPedidos = await fetch(`${apiUrlStatusLocal("pedidos")}?t=${Date.now()}`, {
      credentials: "same-origin"
    });
    if (!resPedidos.ok) throw new Error("Erro ao carregar pedidos");
    const pedidos = await resPedidos.json();

    if (!Array.isArray(pedidos)) {
      container.innerHTML = '<div class="alert alert-warning">Nenhum pedido encontrado.</div>';
      return;
    }

    let htmlFinal = "";

    for (const pedido of pedidos) {
      let statusList = [];
      try {
        const resStatus = await fetch(`${apiUrlStatusLocal("pedidos-status", pedido.id_pedido)}?t=${Date.now()}`, {
          credentials: "same-origin"
        });
        if (resStatus.ok) {
          statusList = await resStatus.json();
        }
      } catch (e) {
        console.warn(`Erro ao carregar status do pedido #${pedido.id_pedido}:`, e);
      }

      if (!Array.isArray(statusList)) statusList = [];

      const ultimoStatusNoHisto = statusList[0];
      let btnDisabled = "";
      let btnTexto = "Atualizar Status";

      if (ultimoStatusNoHisto) {
        try {
          const resProx = await fetch(
            `${apiUrlStatusLocal("status")}/proximo?id_atual=${ultimoStatusNoHisto.id_status}&t=${Date.now()}`,
            { credentials: "same-origin" }
          );
          if (resProx.ok) {
            const proximo = await resProx.json();
            if (!proximo || !proximo.id_status) {
              btnDisabled = "disabled";
              btnTexto = "Pedido Finalizado";
            }
          }
        } catch (e) {
          console.error("Erro ao checar fluxo", e);
        }
      }

      let timelineHTML = '<div class="timeline">';
      statusList.forEach((st, i) => {
        const isMaisRecente = i === 0;
        const isPrimeiroStatusCriado = i === statusList.length - 1; 
        window.statusMap[st.id_pedido_status] = st;

        let iconeHTML = "";
        if (isPrimeiroStatusCriado) {
          iconeHTML = '<i class="fa-regular fa-flag"></i>';
        } else if (isMaisRecente) {
          iconeHTML = btnDisabled === "disabled" ? '<i class="fa-solid fa-flag-checkered"></i>' : '<i class="fa-solid fa-ellipsis"></i>';
        } else {
          iconeHTML = '<i class="fa-solid fa-ellipsis"></i>';
        }

        timelineHTML += `
<div class="rtimeline mb-3">
    <div class="row">
        <div class="col-auto d-none d-md-block">
            <span class="badge rounded-pill text-bg-light">
                ${st.data ? new Date(st.data).toLocaleString("pt-BR") : ""}
            </span>
        </div>
        <div class="col-auto circle"><div></div></div>
        <div class="col-auto icon">
            ${iconeHTML}
        </div>
        <div class="col col-lg-6">
            <div class="card text-bg-light shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 col-md-12">
                            <h5 class="card-title">${esc(st.descricao)}</h5>
                        </div>
                        <div class="col-sm-6 col-md-12">
                            <h6 class="card-subtitle mb-2 text-body-secondary float-start float-md-end d-md-none">
                                ${st.data ? new Date(st.data).toLocaleString("pt-BR") : ""}
                            </h6>
                        </div>
                    </div>
                    <p class="card-text">${esc(st.observacao)}</p>
                </div>
                
                <div class="card-footer bg-transparent border-top-0 d-flex justify-content-end pb-3">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary js-editar-status"
                            data-id="${pedido.id_pedido}"
                            data-status-id="${st.id_pedido_status}"
                            title="Editar" aria-label="Editar status ${esc(st.descricao)}">
                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        </button>
                        ${
                          isMaisRecente
                            ? `<button type="button" class="btn btn-outline-danger" title="Excluir" aria-label="Excluir status ${esc(st.descricao)}" onclick="window.abrirModalPedidoStatusExcluir(${Number(st.id_pedido_status)})">
                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                            </button>`
                            : `<button type="button" class="btn btn-outline-danger" disabled aria-label="Excluir status ${esc(st.descricao)} (só o mais recente pode ser excluído)"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>`
                        }
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>`;
      });

      timelineHTML += "</div>";

      const alertaPrazoHTML = window.montarAlertaPrazo(pedido);

      htmlFinal += `
                <div class="accordion-item" data-pedido="${Number(pedido.id_pedido)}">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#p${pedido.id_pedido}" aria-expanded="false" aria-controls="p${pedido.id_pedido}">
                            <div class="container-fluid">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <strong>#${pedido.id_pedido}</strong> - ${esc(pedido.shaper)} 
                                        <span class="badge bg-secondary ms-2">${esc(pedido.tipo_bloco || "PU")}</span>
                                    </div>
                                    <div class="col-auto">
                                        ${alertaPrazoHTML}
                                    </div>
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="p${pedido.id_pedido}" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <div class="text-end mb-2">
                                <button type="button" class="btn btn-primary btn-sm" ${btnDisabled} onclick="window.abrirModalStatus(${pedido.id_pedido})">
                                    <i class="fa-solid fa-add"></i> ${btnTexto}
                                </button>
                            </div>
                            ${timelineHTML}
                        </div>
                    </div>
                </div>`;
    }

    container.innerHTML = htmlFinal || '<div class="alert alert-info">Nenhum pedido registado.</div>';
    if (typeof window.executarBuscaPedido === "function") window.executarBuscaPedido();

    if (idAberto) {
      const el = document.getElementById(idAberto);
      if (el) {
        bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).show();
      }
    }
  } catch (err) {
    console.error("Erro ao listar status:", err);
    container.innerHTML = '<div class="alert alert-danger">Falha ao carregar timeline de pedidos.</div>';
  }
};

// ===============================
// EXCLUIR STATUS (MODAL FIX)
// ===============================
window.abrirModalPedidoStatusExcluir = function (id) {
  const modalEl = document.getElementById("modalStatusExcluir");
  const btnSim = document.getElementById("btnConfirmarExcluir");

  if (!modalEl || !btnSim) return console.error("Modal ou botão de exclusão não encontrado");

  btnSim.onclick = async () => {
    await window.excluirStatusPedido(id);
    const inst = bootstrap.Modal.getInstance(modalEl);
    if (inst) inst.hide();
  };

  let modalInstance = bootstrap.Modal.getInstance(modalEl);
  if (modalInstance) {
    modalInstance.dispose();
  }

  modalEl.classList.remove("show");
  modalEl.style.display = "none";

  const novoModal = new bootstrap.Modal(modalEl, {
    backdrop: true,
    keyboard: true,
    focus: true,
  });

  novoModal.show();

  setTimeout(() => {
    if (!modalEl.classList.contains("show")) {
      modalEl.style.display = "block";
      modalEl.classList.add("show");
      document.body.classList.add("modal-open");
      if (!document.querySelector(".modal-backdrop")) {
        const backdrop = document.createElement("div");
        backdrop.className = "modal-backdrop fade show";
        document.body.appendChild(backdrop);
      }
    }
  }, 500);
};

window.excluirStatusPedido = async function (id) {
  try {
    const res = await fetch(`${apiUrlStatusLocal("pedidos-status")}?id_pedido_status=${id}`, {
      method: "DELETE",
      credentials: "same-origin"
    });
    const result = await res.json();
    if (typeof showToast === "function") showToast("Status do Pedido", result);

    const modalEl = document.getElementById("modalStatusExcluir");
    if (modalEl) {
      const modalInstance = bootstrap.Modal.getInstance(modalEl);
      if (modalInstance) modalInstance.hide();
    }

    setTimeout(() => window.listarPedidosStatus(), 500);
  } catch (err) {
    console.error("Erro ao excluir:", err);
    if (typeof showToast === "function") showToast("Status do Pedido", { erro: "Não foi possível excluir o status. Verifique a conexão e tente de novo." });
  }
};

// ===============================
// SALVAR STATUS
// ===============================
window.salvarStatusPedido = async function () {
  const idPedido = document.getElementById("statusPedidoId")?.value;
  const idStatusItem = document.getElementById("statusIndex")?.value;
  const data = document.getElementById("statusData")?.value;
  const obs = document.getElementById("statusObs")?.value;
  const idStatus = Number(document.getElementById("statusIdReal")?.value || 0);
  const aviso = (erro) => {
    if (typeof showToast === "function") showToast("Status do Pedido", { erro });
  };

  // Validação antes de enviar (o servidor trata 0 como "vazio")
  if (!idPedido) return aviso("Pedido não identificado. Feche e abra de novo.");
  if (!idStatus) return aviso("Nenhum status válido para este pedido. Cadastre ou ative os status em \"Status dos pedidos\".");
  if (!data) return aviso("Informe a data e a hora.");

  const botao = document.getElementById("btnSalvarStatusPedido");
  if (botao) botao.disabled = true;

  try {
    const body = {
      id_pedido: idPedido,
      id_status: idStatus,
      data: data,
      observacao: obs,
    };
    let url = apiUrlStatusLocal("pedidos-status");
    const method = idStatusItem ? "PUT" : "POST";
    if (idStatusItem) url = apiUrlStatusLocal("pedidos-status", idStatusItem);

    const res = await fetch(url, {
      method: method,
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify(body),
    });
    let result = null;
    try {
      result = await res.json();
    } catch (_) {
      // resposta que não é JSON: tratada abaixo
    }

    if (!res.ok || !result || result.erro || result.sucesso === false) {
      // Mantém o modal aberto para a pessoa corrigir e tentar de novo
      aviso((result && (result.erro || result.mensagem)) || `Não foi possível salvar (HTTP ${res.status}).`);
      return;
    }

    if (typeof showToast === "function") showToast("Status do Pedido", result);

    const modalEl = document.getElementById("modalStatus");
    if (modalEl) {
      const modalInstance = bootstrap.Modal.getInstance(modalEl);
      if (modalInstance) modalInstance.hide();
    }

    setTimeout(() => window.listarPedidosStatus(), 300);
  } catch (err) {
    console.error("Erro ao salvar:", err);
    aviso("Não foi possível salvar o status. Verifique a conexão e tente de novo.");
  } finally {
    if (botao) botao.disabled = false;
  }
};

// ===============================
// ABRIR MODAL STATUS (EDITAR/ADICIONAR)
// ===============================
window.abrirModalStatus = async function (idPedido, idStatus = null) {
  const inputPedidoId = document.getElementById("statusPedidoId");
  const inputDescricao = document.getElementById("statusDescricao");
  const inputData = document.getElementById("statusData");
  const inputObs = document.getElementById("statusObs");
  const inputStatusIndex = document.getElementById("statusIndex");
  const inputIdStatusReal = document.getElementById("statusIdReal");

  if (!inputPedidoId) return;

  inputPedidoId.value = idPedido;
  if (inputStatusIndex) inputStatusIndex.value = idStatus || "";

  if (!idStatus) {
    const statusDestePedido = Object.values(window.statusMap)
      .filter((st) => st.id_pedido == idPedido)
      .sort((a, b) => Number(b.id_pedido_status) - Number(a.id_pedido_status));
    const ultimo = statusDestePedido[0];
    let idCategoriaAtual = ultimo ? ultimo.id_status : 0;

    let proximo = null;
    try {
      const res = await fetch(
        `${apiUrlStatusLocal("status")}/proximo?id_atual=${idCategoriaAtual}&t=${Date.now()}`,
        { credentials: "same-origin" }
      );
      if (res.ok) proximo = await res.json();
    } catch (err) {
      console.error("Erro ao buscar o próximo status:", err);
    }

    if (!proximo || !proximo.id_status) {
      // Sem próximo status não há o que registrar: avisa em vez de abrir um modal que vai falhar
      const msg = ultimo
        ? "Este pedido já está no último status do fluxo."
        : "Não há status ativo cadastrado. Cadastre os status em \"Status dos pedidos\" antes de atualizar um pedido.";
      if (typeof showToast === "function") showToast("Status do Pedido", { erro: msg });
      return;
    }
    if (inputDescricao) inputDescricao.value = proximo.descricao;
    if (inputIdStatusReal) inputIdStatusReal.value = proximo.id_status;

    const agora = new Date();
    agora.setMinutes(agora.getMinutes() - agora.getTimezoneOffset());
    if (inputData) inputData.value = agora.toISOString().slice(0, 16);
    if (inputObs) inputObs.value = "";
  } else {
    const st = window.statusMap[idStatus];
    if (!st) return;
    if (inputDescricao) inputDescricao.value = st.descricao || "";
    if (inputIdStatusReal) inputIdStatusReal.value = st.id_status;
    if (inputData) inputData.value = st.data ? st.data.replace(" ", "T").slice(0, 16) : "";
    if (inputObs) inputObs.value = st.observacao || "";
  }

  const modalEl = document.getElementById("modalStatus");
  if (modalEl) {
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }
};

document.addEventListener("click", function (e) {
  const btn = e.target.closest(".js-editar-status");
  if (btn) window.abrirModalStatus(btn.dataset.id, btn.dataset.statusId);
});

window.initPedidoStatus = () => {
  window.listarPedidosStatus();
};

window.solicitarExclusaoGerenciamento = function (id, acaoDeletar) {
  const modalEl = document.getElementById("modalExcluirGerenciamento");
  const btnSim = document.getElementById("btnConfirmarExcluirGerenciamento");
  if (!modalEl || !btnSim) return;

  btnSim.onclick = async () => {
    await acaoDeletar(id);
    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
  };

  let inst = bootstrap.Modal.getInstance(modalEl);
  if (inst) inst.dispose();
  new bootstrap.Modal(modalEl).show();
};

// ===============================
// BUSCA NA LISTA "STATUS DO PEDIDO"
// ===============================
window.executarBuscaPedido = function () {
  const campo = document.getElementById("inputBuscaPedido");
  // "#12" e "12" significam pedido 12
  const termo = campo ? campo.value.trim().toLowerCase().replace(/^#/, "") : "";
  const soNumero = /^\d+$/.test(termo);
  const itens = document.querySelectorAll("#accordionPanelsStayOpenExample > .accordion-item");
  let visiveis = 0;
  itens.forEach((item) => {
    const texto = item.textContent.toLowerCase();
    let mostrar;
    if (!termo) {
      mostrar = true;
    } else if (soNumero) {
      // Número curto = ID do pedido (senão "11" acharia qualquer data com /11/).
      // Com 4+ dígitos (ex.: 2026) também procura no texto.
      mostrar = item.dataset.pedido === termo || (termo.length >= 4 && texto.includes(termo));
    } else {
      mostrar = texto.includes(termo);
    }
    item.style.display = mostrar ? "" : "none";
    if (mostrar) visiveis++;
  });
  const aviso = document.getElementById("buscaPedidoVazia");
  if (aviso) aviso.classList.toggle("d-none", !(termo && itens.length > 0 && visiveis === 0));
};

document.addEventListener("input", function (e) {
  if (e.target && e.target.id === "inputBuscaPedido") window.executarBuscaPedido();
});
document.addEventListener("keydown", function (e) {
  if (e.key === "Enter" && e.target && e.target.id === "inputBuscaPedido") {
    e.preventDefault();
    window.executarBuscaPedido();
  }
});