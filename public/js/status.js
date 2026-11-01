// ===============================================
// STATUS.JS - Cadastro, ordenação e ativação dos status
// ===============================================

window.editIdStatus = null;
window.statusCache = {};

function apiStatus(id = null) {
  const baseUrl = window.BASE_URL || '/sistema';
  if (id !== null && id !== undefined && id !== "") {
    return `${baseUrl}/api/status/${encodeURIComponent(id)}`;
  }
  return `${baseUrl}/api/status`;
}

function escaparHtml(texto) {
  return String(texto ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

function statusEstaAtivo(s) {
  return s && (s.ativo === true || s.ativo === 1 || s.ativo === "1");
}

function idValido(id) {
  return !(
    id === null ||
    id === undefined ||
    id === "" ||
    id === "NaN" ||
    id === "undefined" ||
    id === "null" ||
    (typeof id === "number" && Number.isNaN(id))
  );
}

function idParaApi(id) {
  return /^\d+$/.test(String(id)) ? Number(id) : id;
}

function avisoStatus(titulo, dados) {
  if (typeof showToast === "function") showToast(titulo, dados);
}

async function enviarStatus(id, body) {
  const res = await fetch(apiStatus(id), {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "same-origin",
    body: JSON.stringify(body),
  });
  const texto = await res.text();
  let result = null;
  try {
    result = JSON.parse(texto);
  } catch (_) {}
  const falhou = !res.ok || (result && result.sucesso === false);
  return { res, texto, result, falhou };
}

function mensagemErro(r) {
  return (
    (r.result && (r.result.erro || r.result.mensagem)) ||
    `Erro (HTTP ${r.res.status})`
  );
}

window.listarStatus = async function () {
  try {
    const res = await fetch(apiStatus() + `?t=${Date.now()}`, {
      credentials: "same-origin",
    });
    const data = await res.json();
    const lista = document.getElementById("lista-status");
    if (!lista) return;

    if (!res.ok || !Array.isArray(data)) {
      console.error("Erro ao listar os status:", res.status, data);
      avisoStatus("Erro", {
        erro:
          (data && (data.erro || data.mensagem)) ||
          `Erro ao listar os status (HTTP ${res.status})`,
      });
      return;
    }

    window.statusCache = {};
    data.forEach((s) => {
      const idReal = s.id_status !== undefined && s.id_status !== null ? s.id_status : s.id;
      s.id = idReal;
      window.statusCache[idReal] = s;
    });

    lista.innerHTML = "";

    if (data.length === 0) {
      lista.innerHTML = `
        <tr class="sem-cadastro">
          <td colspan="5" class="text-center py-4">
            <p class="mb-1 fw-semibold">Nenhum status cadastrado ainda.</p>
            <p class="text-muted small mb-3">Sem status não dá para atualizar o andamento dos pedidos. Cadastre pelo campo acima ou crie o fluxo padrão.</p>
            <button type="button" class="btn btn-sm btn-primary" id="btnStatusPadrao">Criar status padrão</button>
            <div class="small text-muted mt-2">Aguardando, Em produção, Secagem, Acabamento, Pronto, Finalizado</div>
          </td>
        </tr>`;
      return;
    }

    data.forEach((s) => {
      const tr = document.createElement("tr");
      tr.setAttribute("data-id", s.id);
      tr.setAttribute("draggable", "true");

      tr.innerHTML = `
        <td class="reorder-handle text-center" style="cursor: grab;">
            <i class="fa-solid fa-grip-vertical text-muted me-2" aria-hidden="true"></i>
        </td>
        <td>${escaparHtml(s.id)}</td>
        <td>${escaparHtml(s.descricao)}</td>
        <td class="text-center">
            <div class="form-check form-switch d-inline-block">
                <input class="form-check-input switch-ativo-status" type="checkbox" role="switch"
                       aria-label="Status ${escaparHtml(s.descricao)} ativo"
                       data-id="${escaparHtml(s.id)}" ${statusEstaAtivo(s) ? "checked" : ""}>
            </div>
        </td>
        <td>
            <div class="btn-group btn-group-sm" role="group" aria-label="Ações do status ${escaparHtml(s.descricao)}">
                <button type="button" class="btn btn-outline-secondary btn-mover-status" data-dir="up"
                        title="Mover para cima" aria-label="Mover ${escaparHtml(s.descricao)} para cima">
                    <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-mover-status" data-dir="down"
                        title="Mover para baixo" aria-label="Mover ${escaparHtml(s.descricao)} para baixo">
                    <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-editar-status" data-id="${escaparHtml(s.id)}"
                        title="Editar" aria-label="Editar ${escaparHtml(s.descricao)}">
                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                </button>
                <button type="button" class="btn btn-outline-danger btn-excluir-status" data-id="${escaparHtml(s.id)}"
                        title="Excluir" aria-label="Excluir ${escaparHtml(s.descricao)}">
                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                </button>
            </div>
        </td>
        <td class="col-ordem d-none">${escaparHtml(s.ordem)}</td>
      `;
      lista.appendChild(tr);
    });
    window.buscarStatus();

    if (typeof Sortable !== "undefined") {
      let oldSortable = Sortable.get(lista);
      if (oldSortable) oldSortable.destroy();
      new Sortable(lista, {
        animation: 150,
        handle: ".reorder-handle",
        draggable: "tr",
        delay: 100,
        delayOnTouchOnly: true,
        touchStartThreshold: 5,
        direction: "vertical",
        onEnd: async function () {
          await window.salvarNovaOrdemStatus();
        },
      });
    }
  } catch (error) {
    console.error("Erro ao listar os status:", error);
  }
};

if (!window.__statusEventosRegistrados) {
  window.__statusEventosRegistrados = true;

  // Busca em tempo real (delegado: o campo só existe depois que a tela é carregada)
  document.addEventListener("input", function (e) {
    if (e.target && e.target.id === "buscarStatus") window.buscarStatus();
  });

  document.addEventListener("change", function (e) {
    const sw = e.target.closest && e.target.closest(".switch-ativo-status");
    if (!sw) return;
    window.alternarAtivoStatus(sw.getAttribute("data-id"), sw.checked, sw);
  });

  document.addEventListener("click", function (e) {
    if (e.target.closest && e.target.closest("#btnStatusPadrao")) {
      window.criarStatusPadrao();
      return;
    }

    // Alternativa ao arrastar: botões de subir/descer (acessível por teclado e leitor de tela)
    const btnMover = e.target.closest && e.target.closest(".btn-mover-status");
    if (btnMover) {
      const tr = btnMover.closest("tr");
      if (!tr) return;
      if (btnMover.getAttribute("data-dir") === "up" && tr.previousElementSibling) {
        tr.parentNode.insertBefore(tr, tr.previousElementSibling);
      } else if (btnMover.getAttribute("data-dir") === "down" && tr.nextElementSibling) {
        tr.parentNode.insertBefore(tr.nextElementSibling, tr);
      } else {
        return;
      }
      window.salvarNovaOrdemStatus();
      return;
    }

    const btnEditar = e.target.closest && e.target.closest(".btn-editar-status");
    if (btnEditar) {
      const s = window.statusCache[btnEditar.getAttribute("data-id")];
      if (s) window.editarStatus(s);
      return;
    }

    const btnExcluir = e.target.closest && e.target.closest(".btn-excluir-status");
    if (btnExcluir) {
      window.abrirModalStatusExcluir(btnExcluir.getAttribute("data-id"));
    }
  });
}

window.abrirModalStatusExcluir = function (id) {
  if (!idValido(id)) {
    avisoStatus("Erro", { erro: "ID do status inválido para exclusão." });
    return;
  }

  const modalElement = document.getElementById("modalStatusExcluirBase");
  const btnConfirmar = document.getElementById("btnConfirmarExcluirStatus");

  if (!modalElement || !btnConfirmar) {
    if (confirm("Deseja realmente excluir este status?")) {
      window.deletarStatus(id);
    }
    return;
  }

  btnConfirmar.disabled = false;
  btnConfirmar.onclick = async () => {
    btnConfirmar.disabled = true;
    await window.deletarStatus(id);
    btnConfirmar.disabled = false;
    btnConfirmar.blur();
    bootstrap.Modal.getOrCreateInstance(modalElement).hide();
  };

  bootstrap.Modal.getOrCreateInstance(modalElement).show();
};

window.deletarStatus = async (id) => {
  if (!idValido(id)) {
    avisoStatus("Erro", { erro: "ID do status inválido para exclusão." });
    return;
  }

  try {
    const r = await enviarStatus(id, { _method: "DELETE" });

    if (!r.falhou) {
      avisoStatus("Sucesso", r.result || { mensagem: "Status excluído com sucesso." });
      await window.listarStatus();
    } else {
      console.error("Erro ao excluir o status:", r.res.status, r.texto);
      avisoStatus("Atenção", {
        erro:
          (r.result && (r.result.erro || r.result.mensagem)) ||
          `Não foi possível excluir (HTTP ${r.res.status}).`,
      });
    }
  } catch (error) {
    console.error("Erro na comunicação com a API:", error);
    avisoStatus("Erro", { erro: "Falha de conexão ao excluir o status." });
  }
};

window.alternarAtivoStatus = async function (id, isChecked, checkbox) {
  const s = window.statusCache[id];
  if (!s) return;

  const novoValor = isChecked ? 1 : 0;
  if (checkbox) checkbox.disabled = true;

  try {
    const r = await enviarStatus(id, {
      _method: "PUT",
      id: idParaApi(id),
      descricao: s.descricao,
      ordem: Number(s.ordem),
      ativo: novoValor,
      somenteAtivo: true,
    });

    if (r.falhou) {
      console.error("Erro ao alterar o status:", r.res.status, r.texto);
      throw new Error(mensagemErro(r));
    }

    s.ativo = novoValor;
    await window.listarStatus();
  } catch (error) {
    console.error("Erro ao alterar o status:", error);
    if (checkbox) checkbox.checked = !isChecked;
    avisoStatus("Erro", {
      erro: "Não foi possível alterar o status: " + error.message,
    });
  } finally {
    if (checkbox) checkbox.disabled = false;
  }
};

window.criarStatusPadrao = async function () {
  const botao = document.getElementById("btnStatusPadrao");
  if (botao) botao.disabled = true;

  const nomes = ["Aguardando", "Em produção", "Secagem", "Acabamento", "Pronto", "Finalizado"];
  try {
    for (let i = 0; i < nomes.length; i++) {
      const r = await enviarStatus(null, { descricao: nomes[i], ordem: i + 1, ativo: 1 });
      if (r.falhou) throw new Error(mensagemErro(r));
    }
    avisoStatus("Sucesso", { mensagem: "Status padrão criados." });
  } catch (error) {
    console.error("Erro ao criar os status padrão:", error);
    avisoStatus("Erro", { erro: "Não foi possível criar os status padrão: " + error.message });
  }
  await window.listarStatus();
};

window.salvarNovaOrdemStatus = async function () {
  const rows = document.querySelectorAll("#lista-status tr[data-id]");
  let houveErro = false;

  for (let i = 0; i < rows.length; i++) {
    const id = rows[i].getAttribute("data-id");
    const s = window.statusCache[id];
    if (!s) continue;

    const novaOrdem = i + 1;
    if (Number(s.ordem) === novaOrdem) continue;

    const body = {
      _method: "PUT",
      id: idParaApi(id),
      descricao: s.descricao,
      ordem: novaOrdem,
    };
    if ("ativo" in s) body.ativo = statusEstaAtivo(s) ? 1 : 0;

    try {
      const r = await enviarStatus(id, body);
      if (r.falhou) {
        houveErro = true;
        console.error("Erro ao salvar a ordem:", id, r.res.status, r.texto);
      }
    } catch (error) {
      houveErro = true;
      console.error("Erro ao salvar a ordem:", error);
    }
  }

  if (houveErro) {
    avisoStatus("Erro", { erro: "Não foi possível salvar a nova ordem." });
  }
  window.listarStatus();
};

window.salvarCadastroStatus = async function () {
  const descricaoEl = document.getElementById("descricao");
  if (!descricaoEl) return;
  const descricao = descricaoEl.value.trim();

  if (!descricao) {
    avisoStatus("Atenção", { erro: "Preencha a descrição." });
    return;
  }

  const body = { descricao };

  if (window.editIdStatus) {
    body._method = "PUT";
    body.id = idParaApi(window.editIdStatus);

    const atual = window.statusCache[window.editIdStatus];
    const ordemInput = document.getElementById("ordem")?.value;
    body.ordem = Number(ordemInput || (atual && atual.ordem) || 1);

    if (atual && "ativo" in atual) body.ativo = statusEstaAtivo(atual) ? 1 : 0;
  } else {
    const linhas = document.querySelectorAll("#lista-status tr[data-id]");
    body.ordem = linhas.length + 1;
    body.ativo = 1;
  }

  try {
    const r = await enviarStatus(window.editIdStatus, body);

    if (!r.falhou) {
      window.cancelarEdicaoStatus();
      window.listarStatus();
    } else {
      console.error("Erro ao salvar o status:", r.res.status, r.texto, body);
      avisoStatus("Erro", { erro: mensagemErro(r) });
    }
  } catch (error) {
    console.error("Erro ao salvar o status:", error);
    avisoStatus("Erro", { erro: "Falha de conexão ao salvar o status." });
  }
};

window.editarStatus = function (s) {
  window.editIdStatus = s.id;
  const descEl = document.getElementById("descricao");
  const ordemEl = document.getElementById("ordem");
  const btnSubmit = document.getElementById("btnSubmitStatus");
  const btnCancelar = document.getElementById("btnCancelarEdicao");

  if (descEl) descEl.value = s.descricao;
  if (ordemEl) ordemEl.value = s.ordem;
  if (btnSubmit) {
    btnSubmit.innerHTML = '<i class="fa-solid fa-save"></i> Atualizar';
    btnSubmit.classList.replace("btn-primary", "btn-success");
  }
  if (btnCancelar) btnCancelar.classList.remove("d-none");
  if (descEl) descEl.focus();
};

window.cancelarEdicaoStatus = function () {
  window.editIdStatus = null;
  const descEl = document.getElementById("descricao");
  const ordemEl = document.getElementById("ordem");
  const btnSubmit = document.getElementById("btnSubmitStatus");
  const btnCancelar = document.getElementById("btnCancelarEdicao");

  if (descEl) descEl.value = "";
  if (ordemEl) ordemEl.value = "";
  if (btnSubmit) {
    btnSubmit.innerHTML = '<i class="fa-solid fa-plus"></i> Cadastrar';
    btnSubmit.classList.replace("btn-success", "btn-primary");
  }
  if (btnCancelar) btnCancelar.classList.add("d-none");
};

// Texto da linha sem a coluna oculta "ordem" (senão digitar 2 acharia o status que está na posição 2)
function textoVisivel(linha) {
  return [...linha.children]
    .filter((td) => !td.classList.contains("col-ordem"))
    .map((td) => td.textContent)
    .join(" ")
    .toLowerCase();
}

window.buscarStatus = function () {
  const buscarEl = document.getElementById("buscarStatus");
  const corpo = document.getElementById("lista-status");
  if (!buscarEl || !corpo) return;
  const termo = buscarEl.value.toLowerCase().trim();
  const antigo = corpo.querySelector("tr.sem-resultado");
  if (antigo) antigo.remove();
  const linhas = corpo.querySelectorAll("tr[data-id]");
  let visiveis = 0;
  linhas.forEach((linha) => {
    // Número digitado = ID exato; qualquer termo também procura na descrição
    const idLinha = (linha.getAttribute("data-id") || "").toLowerCase();
    const mostrar = !termo
      || (/^\d+$/.test(termo) && idLinha === termo)
      || textoVisivel(linha).includes(termo);
    linha.style.display = mostrar ? "" : "none";
    if (mostrar) visiveis++;
  });
  if (termo && linhas.length > 0 && visiveis === 0) {
    const tr = document.createElement("tr");
    tr.className = "sem-resultado";
    tr.innerHTML = '<td colspan="5" class="text-center text-muted">Nenhum status encontrado para essa busca.</td>';
    corpo.appendChild(tr);
  }
};
