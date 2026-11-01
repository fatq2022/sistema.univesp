// home.js - carregamento das telas do sistema
// (usa window.* para que outros scripts possam forçar o recarregamento)
window.dashboardCarregado = false;
window.pedidoCarregado = false;
window.statusCarregado = false;
window.shaperCarregado = false;

window.formatarDataBR = function (dataSQL) {
  if (!dataSQL || dataSQL === "0000-00-00") return "-";
  const partes = dataSQL.split(" ")[0].split("-");
  if (partes.length !== 3) return dataSQL;
  return `${partes[2]}/${partes[1]}/${partes[0]}`;
};

window.mascaraTelefone = function (input) {
  let v = input.value.replace(/\D/g, "");
  if (v.length > 11) v = v.substring(0, 11);
  if (v.length > 10) {
    v = v.replace(/^(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
  } else if (v.length > 5) {
    v = v.replace(/^(\d{2})(\d{4})(\d{4})/, "($1) $2-$3");
  } else if (v.length > 2) {
    v = v.replace(/^(\d{2})(\d+)/, "($1) $2");
  } else if (v.length > 0) {
    v = v.replace(/^(\d+)/, "($1");
  }
  input.value = v;
};

// Busca views/<caminho>/index.php e coloca dentro do container.
// Se falhar, mostra na própria tela o código do erro e a URL tentada.
async function carregarView(caminho, containerId) {
  const baseUrl = window.BASE_URL || "/sistema";
  const url = `${baseUrl}/views/${caminho}/index.php?t=${Date.now()}`;
  const container = document.getElementById(containerId);

  const response = await fetch(url, { credentials: "same-origin" });

  if (!response.ok) {
    console.warn(`Falha ao carregar a tela "${caminho}": HTTP ${response.status}\n${url}`);
    if (container) {
      container.innerHTML = `
        <div class="alert alert-danger">
          <strong>Não foi possível carregar esta tela.</strong><br>
          <small>HTTP ${response.status} &mdash; ${url}</small>
        </div>`;
    }
    return false;
  }

  const html = await response.text();
  if (container) container.innerHTML = html;
  return true;
}

async function carregarUsuario() {
  try {
    const baseUrl = window.BASE_URL || "/sistema";
    const response = await fetch(`${baseUrl}/api/usuario`);
    if (!response.ok) return;
    const data = await response.json();

    if (data && data.nome) {
      window.usuarioId = data.id_usuario;
      const elUsuario = document.getElementById("usuario-logado");
      if (elUsuario) {
        const esc = window.escHtml || ((v) => String(v ?? ""));
        elUsuario.innerHTML = `<i class="fa-solid fa-user" aria-hidden="true"></i> ${esc(data.usuario)} | ${esc(data.nome)}`;
      }
      initTooltips();
    }
  } catch (erro) {
    console.warn("Aviso ao carregar utilizador:", erro);
  }
}

async function carregarDashboard() {
  try {
    if (!window.dashboardCarregado) {
      const ok = await carregarView("dashboard", "conteudo-dashboard");
      if (ok) {
        window.dashboardCarregado = true;
        initTooltips();
      }
    }
    if (window.dashboardCarregado && window.atualizarDashboard) {
      window.atualizarDashboard();
    }
  } catch (erro) {
    console.warn("Aviso dashboard:", erro);
  }
}

async function carregarPedido() {
  try {
    if (typeof resetVisualAbas === "function") resetVisualAbas("pedido");
    if (typeof window.dadosPedidosAtuais !== "undefined") window.dadosPedidosAtuais = [];

    if (!window.pedidoCarregado) {
      const ok = await carregarView("pedido", "conteudo-pedido");
      if (!ok) return;
      window.pedidoCarregado = true;
    }

    // Chamadas protegidas individualmente para evitar que uma falha isole a renderização da tela
    if (window.popularSelects) {
      window.popularSelects().catch(err => console.warn("Erro ao popular selects:", err));
    }
    if (window.listarPedidos) {
      window.listarPedidos(window.colunaOrdenacaoAtual).catch(err => console.warn("Erro ao listar pedidos:", err));
    }
    if (typeof window.initPedidoStatus === "function") window.initPedidoStatus();
    if (typeof window.vincularEventosTabs === "function") window.vincularEventosTabs();
    initTooltips();
  } catch (erro) {
    console.warn("Aviso ao carregar pedido:", erro);
  }
}

async function carregarStatus() {
  try {
    if (!window.statusCarregado) {
      const ok = await carregarView("status", "conteudo-status");
      if (!ok) return;
      window.statusCarregado = true;
    }
    if (window.listarStatus) window.listarStatus();
    initTooltips();
  } catch (erro) {
    console.warn("Aviso ao carregar status:", erro);
  }
}

async function carregarShaper() {
  try {
    if (!window.shaperCarregado) {
      const ok = await carregarView("shaper", "conteudo-shaper");
      if (!ok) return;
      window.shaperCarregado = true;
    }
    if (window.listarShapers) window.listarShapers();
    initTooltips();
  } catch (erro) {
    console.warn("Aviso ao carregar shaper:", erro);
  }
}

function initTooltips() {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return bootstrap.Tooltip.getOrCreateInstance(tooltipTriggerEl);
  });
}

document.addEventListener("DOMContentLoaded", () => {
  initTooltips();
  carregarDashboard();
  carregarUsuario();
});

window.atualizarDashboard = async function () {
  const elPedidos = document.getElementById("cardTotalPedidos");
  const elShapers = document.getElementById("cardTotalShapers");
  const containerStatus = document.getElementById("containerStatusDinamico");
  const baseUrl = window.BASE_URL || "/sistema";
  const esc = window.escHtml || ((v) => String(v ?? ""));

  // Busca uma lista na API e explica claramente o que deu errado
  async function buscarLista(recurso) {
    const controle = new AbortController();
    const temporizador = setTimeout(() => controle.abort(), 15000);
    let resposta;
    try {
      resposta = await fetch(`${baseUrl}/api/${recurso}?t=${Date.now()}`, {
        credentials: "same-origin",
        signal: controle.signal,
      });
    } catch (e) {
      throw new Error(`/api/${recurso} não respondeu (${e.name === "AbortError" ? "tempo esgotado" : e.message})`);
    } finally {
      clearTimeout(temporizador);
    }
    if (!resposta.ok) {
      throw new Error(`/api/${recurso} respondeu HTTP ${resposta.status}`);
    }
    const dados = await resposta.json();
    if (!Array.isArray(dados)) {
      throw new Error(`/api/${recurso} não devolveu uma lista`);
    }
    return dados;
  }

  const resultados = await Promise.allSettled([
    buscarLista("pedidos"),
    buscarLista("shapers"),
    buscarLista("status"),
  ]);
  const [rPedidos, rShapers, rStatus] = resultados;

  const pedidos = rPedidos.status === "fulfilled" ? rPedidos.value : [];
  const shapers = rShapers.status === "fulfilled" ? rShapers.value : [];
  const statusList = rStatus.status === "fulfilled" ? rStatus.value : [];

  if (elPedidos) elPedidos.innerText = pedidos.length;
  if (elShapers) elShapers.innerText = shapers.length;
  if (!containerStatus) return;

  const erros = resultados
    .filter((r) => r.status === "rejected")
    .map((r) => (r.reason && r.reason.message) || "falha de conexão");
  if (erros.length > 0) {
    console.warn("Dashboard: erro na API", erros);
    containerStatus.innerHTML = `
      <div class="col-12">
        <div class="alert alert-warning mb-0">
          <strong>Não foi possível carregar todos os dados do painel.</strong>
          <ul class="mb-2 mt-2">${erros.map((e) => `<li>${esc(e)}</li>`).join("")}</ul>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.atualizarDashboard()">
            Tentar novamente
          </button>
          <div class="small text-muted mt-2">Veja o erro detalhado em xampp/apache/logs/error.log</div>
        </div>
      </div>`;
    return;
  }

  const contagemStatus = {};
  statusList.forEach((st) => {
    contagemStatus[st.id_status] = 0;
  });
  let totalSemStatus = 0;

  pedidos.forEach((p) => {
    const bruto = (p.status_ids || "").trim();
    if (bruto === "") {
      totalSemStatus++;
      return;
    }
    const ids = bruto
      .split(",")
      .map((id) => parseInt(id.trim(), 10))
      .filter((n) => !Number.isNaN(n));
    if (ids.length === 0) {
      totalSemStatus++;
      return;
    }
    const atual = Math.max(...ids);
    if (contagemStatus[atual] !== undefined) {
      contagemStatus[atual]++;
    }
  });

  const cartao = (classe, titulo, valor, classeTitulo) => `
    <div class="col-md-3 col-6 mb-3">
      <div class="card ${classe} mb-3 shadow-sm border-0 h-100">
        <div class="card-body">
          <h6 class="card-subtitle mb-2 ${classeTitulo} text-uppercase fw-bold" style="font-size: 0.7rem;">${esc(titulo)}</h6>
          <h2 class="card-title mb-0 fw-bold">${valor}</h2>
        </div>
      </div>
    </div>`;

  let html =
    cartao("text-bg-primary", "Total Geral", pedidos.length, "text-white-50") +
    cartao("text-bg-danger", "Sem Status", totalSemStatus, "text-white-50");

  statusList.forEach((st) => {
    const total = contagemStatus[st.id_status] || 0;
    const inativo = String(st.ativo) === "0";
    if (inativo && total === 0) return;
    html += cartao("text-bg-light", st.descricao, total, "text-muted");
  });

  containerStatus.innerHTML = html;
};