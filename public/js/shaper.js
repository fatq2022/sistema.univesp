// ==========================================
// SHAPER.JS - Completo e Corrigido
// ==========================================

window.editIdShaper = null;
window.shaperCache = {};

function apiShaper(id = null) {
    if (id !== null && id !== undefined && id !== "") {
        return `${window.BASE_URL}/api/shapers/${encodeURIComponent(id)}`;
    }
    return `${window.BASE_URL}/api/shapers`;
}

function escaparHtmlShaper(texto) {
    return String(texto ?? "").replace(/[&<>"']/g, (c) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
    }[c]));
}

function shaperEstaAtivo(s) {
    return s && (s.ativo === true || s.ativo === 1 || s.ativo === "1");
}

function idValidoShaper(id) {
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

function idParaApiShaper(id) {
    return /^\d+$/.test(String(id)) ? Number(id) : id;
}

function avisoShaper(titulo, dados) {
    // Aviso flutuante: aparece mesmo com a página rolada para baixo
    if (typeof window.showToast === "function") {
        window.showToast(titulo, { erro: dados.erro || dados.mensagem || titulo });
    }
    const alerta = document.getElementById("alerta");
    if (alerta) {
        alerta.className = "alert alert-danger";
        alerta.textContent = dados.erro || dados.mensagem || titulo;
        alerta.classList.remove("d-none");
        setTimeout(() => alerta.classList.add("d-none"), 4000);
    }
}

async function enviarShaper(id, body) {
    const res = await fetch(apiShaper(id), {
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

function mensagemErroShaper(r) {
    return (
        (r.result && (r.result.erro || r.result.mensagem)) ||
        `Erro (HTTP ${r.res.status})`
    );
}

window.listarShapers = async function () {
    try {
        const res = await fetch(apiShaper() + `?t=${Date.now()}`, {
            credentials: "same-origin",
        });
        const data = await res.json();
        const lista = document.getElementById("lista");
        if (!lista) return;

        if (!res.ok || !Array.isArray(data)) {
            console.error("Erro ao listar shapers:", res.status, data);
            return;
        }

        window.shaperCache = {};
        data.forEach((s) => {
            const idReal = s.id_shaper !== undefined && s.id_shaper !== null ? s.id_shaper : s.id;
            s.id = idReal;
            window.shaperCache[idReal] = s;
        });

        lista.innerHTML = "";
        data.forEach((s) => {
            const tr = document.createElement("tr");
            tr.setAttribute("data-id", s.id);

            tr.innerHTML = `
                <td>${escaparHtmlShaper(s.id)}</td>
                <td>${escaparHtmlShaper(s.nome)}</td>
                <td>${escaparHtmlShaper(s.telefone || "-")}</td>
                <td>${escaparHtmlShaper(s.email || "-")}</td>
                <td class="text-center">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input switch-ativo-shaper" type="checkbox" role="switch"
                               aria-label="Cliente ${escaparHtmlShaper(s.nome)} ativo"
                               data-id="${escaparHtmlShaper(s.id)}" ${shaperEstaAtivo(s) ? "checked" : ""}>
                    </div>
                </td>
                <td>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Ações do cliente ${escaparHtmlShaper(s.nome)}">
                        <button type="button" class="btn btn-outline-secondary btn-editar-shaper" data-id="${escaparHtmlShaper(s.id)}"
                                title="Editar" aria-label="Editar ${escaparHtmlShaper(s.nome)}">
                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-excluir-shaper" data-id="${escaparHtmlShaper(s.id)}"
                                title="Excluir" aria-label="Excluir ${escaparHtmlShaper(s.nome)}">
                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                </td>
            `;
            lista.appendChild(tr);
        });
        window.buscarShaper();
    } catch (error) {
        console.error("Erro ao listar shapers:", error);
    }
};

if (!window.__shaperEventosRegistrados) {
    window.__shaperEventosRegistrados = true;

    // Busca em tempo real (delegado: o campo só existe depois que a tela é carregada)
    document.addEventListener("input", function (e) {
        if (e.target && e.target.id === "buscarTexto") window.buscarShaper();
    });

    document.addEventListener("change", function (e) {
        const sw = e.target.closest && e.target.closest(".switch-ativo-shaper");
        if (!sw) return;
        window.alternarAtivoShaper(sw.getAttribute("data-id"), sw.checked, sw);
    });

    document.addEventListener("click", function (e) {
        const btnEditar = e.target.closest && e.target.closest(".btn-editar-shaper");
        if (btnEditar) {
            const s = window.shaperCache[btnEditar.getAttribute("data-id")];
            if (s) window.editarShaper(s);
            return;
        }

        const btnExcluir = e.target.closest && e.target.closest(".btn-excluir-shaper");
        if (btnExcluir) {
            window.abrirModalShaperExcluir(btnExcluir.getAttribute("data-id"));
        }
    });
}

window.abrirModalShaperExcluir = function (id) {
    if (!idValidoShaper(id)) {
        avisoShaper("Erro", { erro: "ID do Shaper inválido." });
        return;
    }

    const modalElement = document.getElementById("modalShaperExcluir");
    const btnConfirmar = document.getElementById("btnConfirmarExcluirShaper");

    if (!modalElement || !btnConfirmar) {
        if (confirm("Deseja realmente excluir este Shaper?")) {
            window.deletarShaper(id);
        }
        return;
    }

    btnConfirmar.disabled = false;
    btnConfirmar.onclick = async () => {
        btnConfirmar.disabled = true;
        await window.deletarShaper(id);
        btnConfirmar.disabled = false;
        btnConfirmar.blur();
        bootstrap.Modal.getOrCreateInstance(modalElement).hide();
    };

    bootstrap.Modal.getOrCreateInstance(modalElement).show();
};

window.deletarShaper = async (id) => {
    if (!idValidoShaper(id)) return;

    try {
        const r = await enviarShaper(id, { _method: "DELETE" });
        if (!r.falhou) {
            window.listarShapers();
        } else {
            avisoShaper("Atenção", { erro: mensagemErroShaper(r) });
        }
    } catch (error) {
        console.error("Erro na comunicação:", error);
        avisoShaper("Erro", { erro: "Falha de conexão ao excluir o shaper." });
    }
};

window.alternarAtivoShaper = async function (id, isChecked, checkbox) {
    const s = window.shaperCache[id];
    if (!s) return;

    const novoValor = isChecked ? 1 : 0;
    if (checkbox) checkbox.disabled = true;

    try {
        // Envia SÓ o campo ativo: com "nome" junto o servidor tratava como edição e ignorava o ativo
        const r = await enviarShaper(id, {
            _method: "PUT",
            id: idParaApiShaper(id),
            ativo: novoValor,
            somenteAtivo: true,
        });

        if (r.falhou) {
            throw new Error(mensagemErroShaper(r));
        }

        s.ativo = novoValor;
        window.listarShapers();
    } catch (error) {
        if (checkbox) checkbox.checked = !isChecked;
        avisoShaper("Erro", { erro: "Não foi possível alterar: " + error.message });
    } finally {
        if (checkbox) checkbox.disabled = false;
    }
};

window.cadastrarOuAtualizar = async function () {
    const nomeEl = document.getElementById("nome");
    const telEl = document.getElementById("telefone");
    const emailEl = document.getElementById("email");

    const nome = nomeEl ? nomeEl.value.trim() : "";
    const telefone = telEl ? telEl.value.trim() : "";
    const email = emailEl ? emailEl.value.trim() : "";

    if (!nome) {
        avisoShaper("Atenção", { erro: "O campo Nome é obrigatório." });
        if (nomeEl) nomeEl.focus();
        return;
    }

    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        avisoShaper("Atenção", { erro: "Informe um e-mail válido." });
        if (emailEl) emailEl.focus();
        return;
    }

    const body = { nome, telefone, email };

    if (window.editIdShaper) {
        body._method = "PUT";
        body.id = idParaApiShaper(window.editIdShaper);
        const atual = window.shaperCache[window.editIdShaper];
        if (atual && "ativo" in atual) body.ativo = shaperEstaAtivo(atual) ? 1 : 0;
    } else {
        body.ativo = 1;
    }

    try {
        const r = await enviarShaper(window.editIdShaper, body);
        if (!r.falhou) {
            window.resetForm();
            window.listarShapers();
        } else {
            avisoShaper("Erro", { erro: mensagemErroShaper(r) });
        }
    } catch (error) {
        console.error("Erro ao salvar shaper:", error);
        avisoShaper("Erro", { erro: "Falha de conexão ao salvar o shaper." });
    }
};

window.editarShaper = function (s) {
    window.editIdShaper = s.id;
    document.getElementById("nome").value = s.nome || "";
    document.getElementById("telefone").value = s.telefone || "";
    document.getElementById("email").value = s.email || "";

    const btnSubmit = document.getElementById("btnSubmit");
    btnSubmit.innerHTML = '<i class="fa-solid fa-save"></i> Atualizar';
    btnSubmit.classList.replace("btn-primary", "btn-success");
    document.getElementById("btnCancelarShaper").classList.remove("d-none");
    document.getElementById("nome").focus();
};

window.resetForm = function () {
    window.editIdShaper = null;
    document.getElementById("nome").value = "";
    document.getElementById("telefone").value = "";
    document.getElementById("email").value = "";

    const btnSubmit = document.getElementById("btnSubmit");
    btnSubmit.innerHTML = '<i class="fa-solid fa-plus"></i> Cadastrar';
    btnSubmit.classList.replace("btn-success", "btn-primary");
    document.getElementById("btnCancelarShaper").classList.add("d-none");
};

window.buscarShaper = function () {
    const inputBusca = document.getElementById("buscarTexto");
    const corpo = document.getElementById("lista");
    if (!inputBusca || !corpo) return;
    const termo = inputBusca.value.toLowerCase().trim();
    const antigo = corpo.querySelector("tr.sem-resultado");
    if (antigo) antigo.remove();
    const linhas = corpo.querySelectorAll("tr");
    let visiveis = 0;
    linhas.forEach((linha) => {
        const mostrar = linha.textContent.toLowerCase().includes(termo);
        linha.style.display = mostrar ? "" : "none";
        if (mostrar) visiveis++;
    });
    if (termo && linhas.length > 0 && visiveis === 0) {
        const tr = document.createElement("tr");
        tr.className = "sem-resultado";
        tr.innerHTML = '<td colspan="6" class="text-center text-muted">Nenhum cliente encontrado para essa busca.</td>';
        corpo.appendChild(tr);
    }
};
