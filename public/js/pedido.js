// ========================================
// PEDIDO.JS - Completo, Blindado e Corrigido
// ========================================

window.colunaOrdenacaoAtual = localStorage.getItem("pedido_ordem_coluna") || "id_pedido";
window.ordemAscendente = localStorage.getItem("pedido_ordem_dir") === "true";
window.dadosPedidosAtuais = [];

/* ========================================
   ESTILOS DO CHOICES DENTRO DE INPUT-GROUP
======================================== */
(function injetarEstilosPedido() {
  if (document.getElementById("estilos-pedido-choices")) return;

  const style = document.createElement("style");
  style.id = "estilos-pedido-choices";
  style.textContent = `
    .input-group > .choices {
      flex: 1 1 auto;
      width: 1%;
      min-width: 0;
      margin-bottom: 0;
    }
    .choices__inner {
      min-height: 38px;
      padding: 4px 8px;
      background: #fff;
      border: 1px solid #dee2e6;
      border-radius: .375rem;
    }
    .input-group > .choices .choices__inner {
      border-top-right-radius: 0;
      border-bottom-right-radius: 0;
    }
    .input-group > .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 38px;
    }
    .choices.is-focused .choices__inner {
      border-color: #86b7fe;
      box-shadow: 0 0 0 .25rem rgba(13, 110, 253, .25);
    }
  `;
  document.head.appendChild(style);
})();

function escaparHtmlPedido(texto) {
  return String(texto ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

function obterTextoStatus(p) {
  if (!p.status || !p.status_ids) return "Sem Status";
  const nomes = p.status.split(",");
  const ids = p.status_ids.split(",");
  let maxId = -1;
  let idx = 0;
  ids.forEach((id, i) => {
    const n = parseInt(id.trim());
    if (n > maxId) {
      maxId = n;
      idx = i;
    }
  });
  return nomes[idx].trim();
}

window.getUltimoStatusValue = function (p) {
  if (!p.status || !p.status_ids) return "Sem Status";
  const nomes = p.status.split(",");
  const ids = p.status_ids.split(",");
  let maxId = -1;
  let indiceMaisRecente = 0;
  ids.forEach((idStr, index) => {
    const idNum = parseInt(idStr.trim());
    if (idNum > maxId) {
      maxId = idNum;
      indiceMaisRecente = index;
    }
  });
  return nomes[indiceMaisRecente] ? nomes[indiceMaisRecente].trim() : "Sem Status";
};

if (window.PEDIDO_MODULE_LOADED) {
  console.warn("pedido.js já carregado");
} else {
  window.PEDIDO_MODULE_LOADED = true;

  let contextoAtual = null;
  let editIdGenerico = null;

  const modalConfigs = {
    composicao: { icon: '<i class="fa-solid fa-flask"></i>', titulo: "Gerenciar Composição", label: "Composição", selectId: "id_composicaoPedido", recurso: "composicoes" },
    variacao: { icon: '<i class="fa-solid fa-sliders"></i>', titulo: "Gerenciar Variação", label: "Variação", selectId: "id_variacaoPedido", recurso: "variacoes" },
    acabamento: { icon: '<i class="fa-solid fa-brush"></i>', titulo: "Gerenciar Acabamento", label: "Acabamento", selectId: "id_acabamentoPedido", recurso: "acabamentos" },
    configQuilha: { icon: '<i class="fa-solid fa-sliders"></i>', titulo: "Gerenciar Config. Quilha", label: "Configuração de Quilha", selectId: "id_configuracaoquilhaPedido", recurso: "configuracaoquilhas" },
    sistemaQuilha: { icon: '<i class="fa-solid fa-sitemap"></i>', titulo: "Gerenciar Sistema Quilha", label: "Sistema de Quilha", selectId: "id_sistemaquilhaPedido", recurso: "sistemaquilhas" },
    cores: { icon: '<i class="fa-solid fa-fill-drip"></i>', titulo: "Gerenciar Cores", label: "Cor", selectId: "id_cor", recurso: "cores" },
    tecido: { icon: '<i class="fa-solid fa-layer-group"></i>', titulo: "Gerenciar Tecidos", label: "Tecido", selectId: "id_tecidoPedido", recurso: "tecidos" },
  };

  window.abrirModalGenerico = function (tipo) {
    contextoAtual = modalConfigs[tipo];
    editIdGenerico = null;
    if (!contextoAtual) return;

    const modalTitulo = document.querySelector("#modalGenerico .modal-title");
    if (modalTitulo) modalTitulo.innerHTML = contextoAtual.icon + " " + contextoAtual.titulo;

    const input = document.getElementById("modalInput");
    if (input) {
      input.value = "";
      input.placeholder = contextoAtual.label;
    }

    resetModalState();
    carregarListaModal();

    const modalEl = document.getElementById("modalGenerico");
    if (modalEl) new bootstrap.Modal(modalEl).show();
  };

  async function carregarListaModal() {
    if (!contextoAtual) return;
    try {
      const baseUrl = window.BASE_URL || '/sistema';
      const response = await fetch(`${baseUrl}/api/${contextoAtual.recurso}`);
      if (!response.ok) return;
      const data = await response.json();

      const lista = document.getElementById("listaComposicoesModal");
      if (!lista) return;
      lista.innerHTML = "";

      data.forEach((item) => {
        const idReal = item.id || item.id_composicao || item.id_variacao || item.id_acabamento || item.id_configuracaoquilha || item.id_sistemaquilha || item.id_cor || item.id_tecido;
        const celulaAtivo = item.ativo === undefined || item.ativo === null
          ? '<span class="text-muted">—</span>'
          : `<div class="form-check form-switch d-inline-block">
               <input class="form-check-input" type="checkbox" role="switch" ${Number(item.ativo) === 1 ? "checked" : ""} onchange="window.alternarAtivoGenerico(${idReal}, this.checked)">
             </div>`;

        lista.innerHTML += `
        <tr>
            <td>${idReal}</td>
            <td>${escaparHtmlPedido(item.descricao)}</td>
            <td class="text-center align-middle">${celulaAtivo}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" data-desc="${escaparHtmlPedido(item.descricao)}" onclick="window.editarItem(${idReal}, this.dataset.desc)"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn btn-outline-danger" onclick="window.abrirModalExcluirGerenciamento(${idReal})"><i class="fa-solid fa-trash"></i></button>
                </div>
            </td>
        </tr>`;
      });
    } catch (err) {
      console.warn("Aviso ao carregar lista modal:", err);
    }
  }

  window.salvarComposicao = async function () {
    const inputEl = document.getElementById("modalInput");
    if (!inputEl) return;
    const valor = inputEl.value.trim();
    if (!valor) {
      alert("Digite algo");
      return;
    }

    const baseUrl = window.BASE_URL || '/sistema';
    let url = `${baseUrl}/api/${contextoAtual.recurso}`;
    let body = { descricao: valor };

    if (editIdGenerico) {
      body._method = "PUT";
      url = `${baseUrl}/api/${contextoAtual.recurso}/${editIdGenerico}`;
    }

    try {
      const response = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });
      const data = await response.json();
      if (!response.ok) {
        alert(data.erro || "Erro ao salvar");
        return;
      }
      resetModalState();
      carregarListaModal();
      if (typeof popularSelects === "function") popularSelects();
    } catch (err) {
      console.warn("Erro ao salvar:", err);
    }
  };

  window.editarItem = function (id, descricao) {
    editIdGenerico = id;
    const inputEl = document.getElementById("modalInput");
    if (inputEl) inputEl.value = descricao;

    const btnSalvar = document.getElementById("btnSalvarComposicao");
    const btnCancelar = document.getElementById("btnCancelarComposicao");
    if (!btnSalvar || !btnCancelar) return;

    btnSalvar.textContent = "Editar";
    btnSalvar.classList.remove("btn-primary");
    btnSalvar.classList.add("btn-warning");
    btnCancelar.classList.remove("d-none");
  };

  window.abrirModalExcluirGerenciamento = function (id) {
    const modalEl = document.getElementById("modalExcluirGerenciamento");
    const btnSim = document.getElementById("btnConfirmarExcluirGerenciamento");
    if (!modalEl || !btnSim) return;

    btnSim.onclick = async () => {
      await window.deletarItemReal(id);
      bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    };
    let inst = bootstrap.Modal.getInstance(modalEl);
    if (inst) inst.dispose();
    new bootstrap.Modal(modalEl).show();
  };

  window.deletarItemReal = async function (id) {
    try {
      const baseUrl = window.BASE_URL || '/sistema';
      const response = await fetch(`${baseUrl}/api/${contextoAtual.recurso}/${id}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ _method: "DELETE" }),
      });
      const data = await response.json();
      if (response.ok) {
        if (typeof showToast === "function") showToast("Sucesso", data);
        carregarListaModal();
        if (typeof popularSelects === "function") popularSelects();
      } else {
        if (typeof showToast === "function") {
          showToast("Impedimento", { erro: data.erro, mensagem: data.mensagem || "Este item está em uso." });
        }
      }
    } catch (err) {
      console.warn("Erro na requisição:", err);
    }
  };

  function resetModalState() {
    editIdGenerico = null;
    const inputEl = document.getElementById("modalInput");
    if (inputEl) inputEl.value = "";
    const btnSalvar = document.getElementById("btnSalvarComposicao");
    const btnCancelar = document.getElementById("btnCancelarComposicao");
    if (!btnSalvar || !btnCancelar) return;
    btnSalvar.innerHTML = '<i class="fa-solid fa-plus"></i> Cadastrar';
    btnSalvar.classList.remove("btn-warning");
    btnSalvar.classList.add("btn-primary");
    btnCancelar.classList.add("d-none");
  }

  window.cancelarEdicaoComposicao = function () {
    resetModalState();
  };

  window.editIdPedido = null;
  let tecidosChoice;
  let coresChoice;

  window.apiUrlPedido = function (recurso, id = null) {
    const baseUrl = window.BASE_URL || '/sistema';
    if (id) return `${baseUrl}/api/${recurso}/${id}`;
    return `${baseUrl}/api/${recurso}`;
  };

  function encaixarChoicesNoInputGroup(choiceInstance, tipo, selectId) {
    if (!choiceInstance) return;
    const selectEl = document.getElementById(selectId);
    const wrapper = (selectEl && selectEl.closest(".choices")) || (choiceInstance.containerOuter && (choiceInstance.containerOuter.element || choiceInstance.containerOuter));
    if (!wrapper) return;

    let btn = Array.from(document.querySelectorAll('button[onclick*="abrirModalGenerico"]')).find((b) => {
      const oc = b.getAttribute("onclick") || "";
      return oc.includes(`'${tipo}'`) || oc.includes(`"${tipo}"`);
    });

    if (!btn) {
      const coluna = wrapper.closest('[class*="col-"]') || wrapper.parentNode.parentNode;
      btn = coluna ? coluna.querySelector("button") : null;
    }
    if (!btn) return;

    let group = wrapper.closest(".input-group");
    if (!group) {
      group = document.createElement("div");
      group.className = "input-group flex-nowrap";
      wrapper.parentNode.insertBefore(group, wrapper);
      group.appendChild(wrapper);
    }
    if (btn.parentNode !== group) group.appendChild(btn);
    btn.className = "btn btn-outline-secondary";
  }

  window.popularSelects = async function (limparMemoria = false) {
    const memoria = limparMemoria ? {} : {
      data: document.getElementById("dataPedido")?.value,
      tipo_bloco: document.getElementById("tipo_blocoPedido")?.value,
      obs: document.getElementById("observacaoPedido")?.value,
      shaper: document.getElementById("id_shaperPedido")?.value,
      composicao: document.getElementById("id_composicaoPedido")?.value,
      variacao: document.getElementById("id_variacaoPedido")?.value,
      acabamento: document.getElementById("id_acabamentoPedido")?.value,
      quilha: document.getElementById("id_configuracaoquilhaPedido")?.value,
      sistema: document.getElementById("id_sistemaquilhaPedido")?.value,
      cores: typeof coresChoice !== "undefined" ? coresChoice.getValue(true) : [],
      tecidos: typeof tecidosChoice !== "undefined" ? tecidosChoice.getValue(true) : [],
    };

    try {
      const cargas = [
        ["id_shaperPedido", "shapers", "id_shaper", "nome"],
        ["id_composicaoPedido", "composicoes", "id_composicao", "descricao"],
        ["id_variacaoPedido", "variacoes", "id_variacao", "descricao"],
        ["id_acabamentoPedido", "acabamentos", "id_acabamento", "descricao"],
        ["id_tecidoPedido", "tecidos", "id_tecido", "descricao"],
        ["id_configuracaoquilhaPedido", "configuracaoquilhas", "id_configuracaoquilha", "descricao"],
        ["id_sistemaquilhaPedido", "sistemaquilhas", "id_sistemaquilha", "descricao"],
        ["id_cor", "cores", "id_cor", "descricao"],
      ];

      await Promise.allSettled(cargas.map((c) => carregarSelect(...c)));

      if (document.getElementById("id_tecidoPedido")) {
        if (tecidosChoice) tecidosChoice.destroy();
        tecidosChoice = new Choices("#id_tecidoPedido", { removeItemButton: true, placeholder: true, placeholderValue: "Selecione..." });
        encaixarChoicesNoInputGroup(tecidosChoice, "tecido", "id_tecidoPedido");
      }

      if (document.getElementById("id_cor")) {
        if (coresChoice) coresChoice.destroy();
        coresChoice = new Choices("#id_cor", { removeItemButton: true, placeholder: true, placeholderValue: "Selecione..." });
        encaixarChoicesNoInputGroup(coresChoice, "cores", "id_cor");
      }

      if (!limparMemoria) {
        if (memoria.data && document.getElementById("dataPedido")) document.getElementById("dataPedido").value = memoria.data;
        if (memoria.tipo_bloco && document.getElementById("tipo_blocoPedido")) document.getElementById("tipo_blocoPedido").value = memoria.tipo_bloco;
        if (memoria.obs && document.getElementById("observacaoPedido")) document.getElementById("observacaoPedido").value = memoria.obs;
        if (memoria.shaper && document.getElementById("id_shaperPedido")) document.getElementById("id_shaperPedido").value = memoria.shaper;
        if (memoria.composicao && document.getElementById("id_composicaoPedido")) document.getElementById("id_composicaoPedido").value = memoria.composicao;
        if (memoria.variacao && document.getElementById("id_variacaoPedido")) document.getElementById("id_variacaoPedido").value = memoria.variacao;
        if (memoria.acabamento && document.getElementById("id_acabamentoPedido")) document.getElementById("id_acabamentoPedido").value = memoria.acabamento;
        if (memoria.quilha && document.getElementById("id_configuracaoquilhaPedido")) document.getElementById("id_configuracaoquilhaPedido").value = memoria.quilha;
        if (memoria.sistema && document.getElementById("id_sistemaquilhaPedido")) document.getElementById("id_sistemaquilhaPedido").value = memoria.sistema;
        if (memoria.cores?.length > 0 && coresChoice) coresChoice.setChoiceByValue(memoria.cores);
        if (memoria.tecidos?.length > 0 && tecidosChoice) tecidosChoice.setChoiceByValue(memoria.tecidos);
      }
    } catch (e) {
      console.warn("Aviso ao popular selects:", e);
    }
  };

  async function carregarSelect(selectId, recurso, idCampo, textoCampo) {
    const baseUrl = window.BASE_URL || '/sistema';
    const response = await fetch(`${baseUrl}/api/${recurso}?somenteAtivos=1&t=${Date.now()}`, {
      headers: { Accept: "application/json" },
      credentials: "same-origin",
    });
    if (!response.ok) return;
    const data = await response.json();
    if (!Array.isArray(data)) return;

    const select = document.getElementById(selectId);
    if (!select) return;
    select.innerHTML = "";

    if (!select.hasAttribute("multiple")) {
      const opt = document.createElement("option");
      opt.value = "";
      opt.textContent = "Selecione...";
      opt.disabled = true;
      opt.selected = true;
      select.appendChild(opt);
    }

    data.forEach((item) => {
      const option = document.createElement("option");
      option.value = item[idCampo] ?? item.id;
      option.textContent = item[textoCampo] ?? item.nome ?? item.descricao;
      select.appendChild(option);
    });
  }

  window.listarPedidos = async function (coluna = window.colunaOrdenacaoAtual, inverter = true) {
    try {
      if (arguments.length === 0 || window.dadosPedidosAtuais.length === 0) {
        window.dadosPedidosAtuais = [];
      }

      if (window.dadosPedidosAtuais.length === 0) {
        const baseUrl = window.BASE_URL || '/sistema';
        const response = await fetch(`${baseUrl}/api/pedidos?t=${Date.now()}`, {
          credentials: "same-origin",
          cache: "no-store",
          headers: { "Accept": "application/json" }
        });
        if (!response.ok) {
          throw new Error(`Falha ao carregar pedidos (HTTP ${response.status}).`);
        }
        const resposta = await response.json();
        if (!Array.isArray(resposta)) {
          throw new Error(resposta?.mensagem || resposta?.erro || "A API não retornou uma lista de pedidos.");
        }
        window.dadosPedidosAtuais = resposta;
      }

      if (inverter) {
        window.ordemAscendente = !window.ordemAscendente;
      }

      if (Array.isArray(window.dadosPedidosAtuais)) {
        window.dadosPedidosAtuais.sort((a, b) => {
          let valA = coluna === "status" ? obterTextoStatus(a) : a[coluna] || "";
          let valB = coluna === "status" ? obterTextoStatus(b) : b[coluna] || "";
          if (coluna === "id_pedido") {
            return window.ordemAscendente ? valA - valB : valB - valA;
          }
          return window.ordemAscendente
            ? valA.toString().localeCompare(valB.toString())
            : valB.toString().localeCompare(valA.toString());
        });
      }

      localStorage.setItem("pedido_ordem_coluna", coluna);
      localStorage.setItem("pedido_ordem_dir", window.ordemAscendente);
      window.colunaOrdenacaoAtual = coluna;

      const lista = document.getElementById("listaPedidos");
      if (!lista) return;
      lista.innerHTML = "";

      if (Array.isArray(window.dadosPedidosAtuais) && window.dadosPedidosAtuais.length === 0) {
        lista.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Nenhum pedido cadastrado.</td></tr>';
      } else if (Array.isArray(window.dadosPedidosAtuais)) {
        let linhasHtml = "";
        window.dadosPedidosAtuais.forEach((pedido) => {
          const nomeStatus = obterTextoStatus(pedido);
          const badgeClass = nomeStatus === "Sem Status" ? "bg-secondary" : "bg-primary";
          const idPedido = Number(pedido.id_pedido);
          const dataTexto = window.formatarDataBR ? window.formatarDataBR(pedido.data) : pedido.data;

          linhasHtml += `
            <tr>
                <td>${idPedido}</td>
                <td><span class="badge ${badgeClass}">${escaparHtmlPedido(nomeStatus)}</span></td>
                <td>${escaparHtmlPedido(dataTexto)}</td>
                <td>${escaparHtmlPedido(pedido.shaper)}</td>
                <td class="small text-muted">${escaparHtmlPedido(pedido.observacao ?? "")}</td>
                <td>
                    <div class="btn-group" role="group" aria-label="Ações do pedido ${idPedido}">
                        <button type="button" class="btn btn-outline-secondary" title="Detalhes" aria-label="Ver detalhes do pedido ${idPedido}" onclick="window.abrirModalPedidoDetalhe(${idPedido})"><i class="fa-solid fa-ellipsis" aria-hidden="true"></i></button>
                        <button type="button" class="btn btn-outline-secondary" title="Editar" aria-label="Editar o pedido ${idPedido}" onclick="preencherEdicaoPedido(${idPedido})"><i class="fa-solid fa-file-pen" aria-hidden="true"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" title="Excluir" aria-label="Excluir o pedido ${idPedido}" onclick="window.abrirModalDeletarPedido(${idPedido})"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                </td>
            </tr>`;
        });
        lista.innerHTML = linhasHtml;
      }
    } catch (e) {
      console.error("Erro ao listar pedidos:", e);
      const lista = document.getElementById("listaPedidos");
      if (lista) {
        lista.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Não foi possível carregar os pedidos. ${escaparHtmlPedido(e.message || "Verifique a conexão com o servidor.")}</td></tr>`;
      }
    }
  };

  // initPedidoStatus e executarBuscaPedido ficam em pedido-status.js (timeline).
  // Havia cópias antigas aqui que, por carregarem depois, substituíam a timeline por uma lista simples.

  window.cadastrarOuAtualizarPedido = async function () {
    const data = document.getElementById("dataPedido")?.value;
    const tipo_bloco = document.getElementById("tipo_blocoPedido")?.value;
    const id_shaper = document.getElementById("id_shaperPedido")?.value;
    const id_composicao = document.getElementById("id_composicaoPedido")?.value;
    const id_variacao = document.getElementById("id_variacaoPedido")?.value || null;
    const id_acabamento = document.getElementById("id_acabamentoPedido")?.value || null;
    const id_configuracaoquilha = document.getElementById("id_configuracaoquilhaPedido")?.value || null;
    const id_sistemaquilha = document.getElementById("id_sistemaquilhaPedido")?.value || null;
    const observacao = document.getElementById("observacaoPedido")?.value || "";

    const cores = typeof coresChoice !== "undefined" ? coresChoice.getValue(true) : [];
    const tecidos = typeof tecidosChoice !== "undefined" ? tecidosChoice.getValue(true) : [];

    if (!data || !id_shaper || !id_composicao) {
      alert("Por favor, preencha os campos obrigatórios (Data, Shaper e Composição).");
      return;
    }

    const bodyData = {
      data,
      tipo_bloco,
      id_shaper,
      id_composicao,
      id_variacao,
      id_acabamento,
      id_configuracaoquilha,
      id_sistemaquilha,
      observacao,
      tecidos,
      cores
    };

    let url = apiUrlPedido("pedidos");
    if (window.editIdPedido) {
      bodyData._method = "PUT";
      url = apiUrlPedido("pedidos", window.editIdPedido);
    }

    try {
      const response = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify(bodyData),
      });

      const texto = await response.text();
      let dataRes = null;
      try { dataRes = JSON.parse(texto); } catch (_) {}

      if (response.ok) {
        if (typeof showToast === "function") showToast("Pedido", dataRes || { mensagem: "Sucesso!" });
        else alert("Pedido guardado com sucesso!");

        window.dadosPedidosAtuais = [];
        if (typeof resetFormPedido === "function") window.resetFormPedido();
        if (window.listarPedidos) window.listarPedidos(window.colunaOrdenacaoAtual, false);
        if (typeof window.listarPedidosStatus === "function") window.listarPedidosStatus();
      } else {
        const msg = (dataRes && (dataRes.erro || dataRes.mensagem)) || `Erro ao salvar (HTTP ${response.status})`;
        alert(msg);
      }
    } catch (error) {
      console.error("Erro na requisição:", error);
      alert("Erro de conexão ao salvar o pedido.");
    }
  };

  window.preencherEdicaoPedido = async function (id) {
    try {
      const response = await fetch(apiUrlPedido("pedidos", id));
      if (!response.ok) return;
      const pedido = await response.json();
      window.editIdPedido = pedido.id_pedido;

      const garantirValorNoSelect = (selectId, valorId, textoExibicao) => {
        const select = document.getElementById(selectId);
        if (!select) return;
        const existe = Array.from(select.options).some((opt) => opt.value == valorId);
        if (!existe && valorId) {
          const opt = document.createElement("option");
          opt.value = valorId;
          opt.textContent = textoExibicao + " (Inativo)";
          select.appendChild(opt);
        }
        select.value = valorId;
      };

      if (document.getElementById("dataPedido")) document.getElementById("dataPedido").value = pedido.data;
      if (document.getElementById("tipo_blocoPedido")) document.getElementById("tipo_blocoPedido").value = pedido.tipo_bloco || "PU";
      if (document.getElementById("observacaoPedido")) document.getElementById("observacaoPedido").value = pedido.observacao || "";

      garantirValorNoSelect("id_shaperPedido", pedido.id_shaper, pedido.shaper);
      garantirValorNoSelect("id_composicaoPedido", pedido.id_composicao, pedido.composicao);
      garantirValorNoSelect("id_variacaoPedido", pedido.id_variacao, pedido.variacao);
      garantirValorNoSelect("id_acabamentoPedido", pedido.id_acabamento, pedido.acabamento);
      garantirValorNoSelect("id_configuracaoquilhaPedido", pedido.id_configuracaoquilha, pedido.configuracaoquilha);
      garantirValorNoSelect("id_sistemaquilhaPedido", pedido.id_sistemaquilha, pedido.sistemaquilha);

      if (tecidosChoice) tecidosChoice.removeActiveItems();
      if (coresChoice) coresChoice.removeActiveItems();
      if (pedido.tecidos_ids && tecidosChoice) tecidosChoice.setChoiceByValue(pedido.tecidos_ids.split(","));
      if (pedido.cores_ids && coresChoice) coresChoice.setChoiceByValue(pedido.cores_ids.split(","));

      const btnSubmit = document.getElementById("btnSubmitPedido");
      if (btnSubmit) {
        btnSubmit.textContent = "Atualizar";
        btnSubmit.classList.remove("btn-primary");
        btnSubmit.classList.add("btn-warning");
      }

      const btnCancelar = document.getElementById("btnCancelarPedido");
      if (btnCancelar) btnCancelar.classList.remove("d-none");
    } catch (e) {
      console.warn("Aviso ao preencher edição:", e);
    }
  };

  window.cancelarEdicaoPedido = function () {
    window.resetFormPedido();
  };

  window.abrirModalDeletarPedido = function (id) {
    const modalEl = document.getElementById("modalPedidoExcluir");
    const btnConfirmar = document.getElementById("btnConfirmarDeletarPedido");
    if (!modalEl || !btnConfirmar) return;

    btnConfirmar.onclick = async () => {
      await window.deletarPedido(id);
      bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    };
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  };

  window.deletarPedido = async function (id) {
    try {
      const response = await fetch(apiUrlPedido("pedidos", id), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ _method: "DELETE" }),
        credentials: "same-origin"
      });
      const data = await response.json();
      if (response.ok) {
        window.dadosPedidosAtuais = [];
        if (typeof showToast === "function") showToast("Pedido", data);
        listarPedidos();
        if (typeof window.listarPedidosStatus === "function") window.listarPedidosStatus();
      }
    } catch (error) {
      console.warn("Erro ao deletar pedido:", error);
    }
  };

  window.resetFormPedido = function () {
    window.editIdPedido = null;
    if (document.getElementById("dataPedido")) document.getElementById("dataPedido").value = "";
    if (document.getElementById("tipo_blocoPedido")) document.getElementById("tipo_blocoPedido").value = "PU";
    if (document.getElementById("observacaoPedido")) document.getElementById("observacaoPedido").value = "";

    const btnSubmit = document.getElementById("btnSubmitPedido");
    if (btnSubmit) {
      btnSubmit.classList.replace("btn-warning", "btn-primary");
      btnSubmit.textContent = "Cadastrar";
    }
    const btnCancelar = document.getElementById("btnCancelarPedido");
    if (btnCancelar) btnCancelar.classList.add("d-none");

    if (window.popularSelects) window.popularSelects(true);
  };

  window.initPedido = function () {
    if (!document.getElementById("listaPedidos") && !document.getElementById("accordionPanelsStayOpenExample")) return;
    listarPedidos();
    if (typeof initPedidoStatus === "function") initPedidoStatus();
    popularSelects();
  };
}

window.abrirModalPedidoDetalhe = async function (id) {
  const modalEl = document.getElementById("modalPedidoDetalhe");
  if (!modalEl) return;
  const corpoModal = modalEl.querySelector(".modal-body");
  const tituloModal = modalEl.querySelector(".modal-title");

  if (tituloModal) tituloModal.innerHTML = `<i class="fa-solid fa-file-invoice"></i> Detalhes do Pedido #${id}`;
  if (corpoModal) corpoModal.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm"></div> Carregando...</div>';

  bootstrap.Modal.getOrCreateInstance(modalEl).show();

  try {
    const response = await fetch(window.apiUrlPedido ? window.apiUrlPedido("pedidos", id) : `${window.BASE_URL}/api/pedidos/${id}`, { credentials: "same-origin" });
    if (!response.ok) {
      if (corpoModal) corpoModal.innerHTML = '<div class="alert alert-danger">Não foi possível carregar o pedido.</div>';
      return;
    }
    const p = await response.json();

    const responsepe = await fetch(window.apiUrlPedido ? window.apiUrlPedido("pedidos-status", id) : `${window.BASE_URL}/api/pedidos-status/${id}`, { credentials: "same-origin" });
    const ppe = responsepe.ok ? await responsepe.json() : [];

    let statusTexto = "Sem Status";
    if (Array.isArray(ppe) && ppe.length > 0) statusTexto = ppe[0].descricao;

    if (corpoModal) {
      corpoModal.innerHTML = `
            <div class="row g-2">
                <div class="col-6"><strong>Data:</strong><br> ${window.formatarDataBR ? window.formatarDataBR(p.data) : p.data}</div>
                <div class="col-6"><strong>Tipo de Bloco:</strong><br> <span class="badge bg-secondary">${escaparHtmlPedido(p.tipo_bloco || "PU")}</span></div>
                <div class="col-6 mt-2"><strong>Status Atual:</strong><br> <span class="badge bg-primary text-uppercase">${escaparHtmlPedido(statusTexto)}</span></div>
                <div class="col-12 mt-2"><strong>Shaper / Cliente:</strong><br> ${escaparHtmlPedido(p.shaper || "-")}</div>
                <hr class="my-2">
                <div class="col-6"><strong>Composição:</strong><br> ${escaparHtmlPedido(p.composicao || "-")}</div>
                <div class="col-6"><strong>Variação:</strong><br> ${escaparHtmlPedido(p.variacao || "-")}</div>
                <div class="col-6 mt-2"><strong>Acabamento:</strong><br> ${escaparHtmlPedido(p.acabamento || "-")}</div>
                <div class="col-6 mt-2"><strong>Config. Quilha:</strong><br> ${escaparHtmlPedido(p.configuracaoquilha || "-")}</div>
                <div class="col-12 mt-2"><strong>Observação:</strong><br> ${escaparHtmlPedido(p.observacao || "Nenhuma")}</div>
            </div>`;
    }
  } catch (err) {
    if (corpoModal) corpoModal.innerHTML = '<div class="alert alert-danger">Erro ao carregar detalhes.</div>';
  }
};