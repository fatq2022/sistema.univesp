// recuperacao.js - telas "Esqueci minha senha" e "Criar nova senha"

function mostrarAlerta(texto, tipo) {
    const alerta = document.getElementById("alerta");
    if (!alerta) return;
    alerta.className = "alert alert-" + tipo;
    alerta.textContent = texto;
}

// Modo teste (só em localhost): mostra o diagnóstico e o link devolvidos pelo servidor
function mostrarDadosDeTeste(dados) {
    const alerta = document.getElementById("alerta");
    if (!alerta || !dados) return;

    if (dados.detalhe_teste) {
        const p = document.createElement("p");
        p.className = "small mb-0 mt-2";
        p.textContent = "Modo teste: " + dados.detalhe_teste;
        alerta.appendChild(p);
    }
    if (dados.link_teste && /^https?:\/\//.test(dados.link_teste)) {
        const p = document.createElement("p");
        p.className = "small mb-0 mt-2";
        const a = document.createElement("a");
        a.href = dados.link_teste;
        a.textContent = "Abrir link de recuperação (modo teste)";
        p.appendChild(a);
        alerta.appendChild(p);
    }
}

function urlApi(recurso, extra) {
    const base = String(window.BASE_URL || "").replace(/\/+$/, "");
    return `${base}/index.php?url=api/${recurso}${extra || ""}`;
}

async function chamarApi(url, opcoes) {
    const resposta = await fetch(url, Object.assign({ credentials: "same-origin" }, opcoes));
    const texto = await resposta.text();
    let dados = null;
    try {
        dados = JSON.parse(texto);
    } catch (_) {
        // resposta que não é JSON: tratada abaixo
    }
    if (!dados) {
        throw new Error(`O servidor retornou uma resposta inválida (HTTP ${resposta.status}).`);
    }
    return { ok: resposta.ok && dados.sucesso === true, status: resposta.status, dados };
}

/* ---------- tela: pedir o link ---------- */
async function enviarPedido(event) {
    event.preventDefault();

    const campo = document.getElementById("email");
    const botao = document.getElementById("btnRecuperar");
    const email = campo.value.trim();

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        mostrarAlerta("Informe um e-mail válido.", "danger");
        campo.setAttribute("aria-invalid", "true");
        campo.focus();
        return;
    }
    campo.setAttribute("aria-invalid", "false");

    botao.disabled = true;
    botao.textContent = "Enviando...";

    try {
        const r = await chamarApi(urlApi("recuperar-senha"), {
            method: "POST",
            headers: { "Content-Type": "application/json", "Accept": "application/json" },
            body: JSON.stringify({ email }),
        });
        mostrarAlerta(r.dados.mensagem || "Pedido enviado.", r.ok ? "success" : "danger");
        mostrarDadosDeTeste(r.dados);
        if (r.ok) campo.value = "";
    } catch (erro) {
        console.error("Erro ao pedir recuperação:", erro);
        mostrarAlerta("Não foi possível conectar ao servidor. Tente novamente.", "danger");
    } finally {
        botao.disabled = false;
        botao.textContent = "Enviar link de recuperação";
    }
}

/* ---------- tela: criar a nova senha ---------- */
function tokenDaUrl() {
    return new URLSearchParams(window.location.search).get("token") || "";
}

async function conferirLink() {
    const form = document.getElementById("formRedefinir");
    const carregando = document.getElementById("carregandoLink");
    const token = tokenDaUrl();

    const falhar = (mensagem) => {
        carregando.classList.add("d-none");
        mostrarAlerta(mensagem, "danger");
        document.getElementById("linkNovoPedido").classList.remove("d-none");
    };

    if (!token) {
        falhar("Link inválido. Solicite uma nova recuperação de senha.");
        return;
    }

    try {
        const r = await chamarApi(urlApi("redefinir-senha", "&token=" + encodeURIComponent(token)));
        if (!r.ok) {
            falhar(r.dados.mensagem || "Link inválido ou expirado.");
            return;
        }
        carregando.classList.add("d-none");
        form.classList.remove("d-none");
        document.getElementById("senha").focus();
    } catch (erro) {
        console.error("Erro ao conferir o link:", erro);
        falhar("Não foi possível verificar o link agora. Tente novamente.");
    }
}

async function salvarNovaSenha(event) {
    event.preventDefault();

    const senhaEl = document.getElementById("senha");
    const confirmarEl = document.getElementById("confirmar");
    const botao = document.getElementById("btnRedefinir");
    const senha = senhaEl.value;
    const confirmar = confirmarEl.value;

    const erroForca = window.mensagemForcaSenha ? window.mensagemForcaSenha(senha) : null;
    if (erroForca) {
        mostrarAlerta(erroForca, "danger");
        senhaEl.setAttribute("aria-invalid", "true");
        senhaEl.focus();
        return;
    }
    if (senha !== confirmar) {
        mostrarAlerta("A confirmação não confere com a senha.", "danger");
        confirmarEl.setAttribute("aria-invalid", "true");
        confirmarEl.focus();
        return;
    }
    senhaEl.setAttribute("aria-invalid", "false");
    confirmarEl.setAttribute("aria-invalid", "false");

    botao.disabled = true;
    botao.textContent = "Salvando...";

    try {
        const r = await chamarApi(urlApi("redefinir-senha"), {
            method: "POST",
            headers: { "Content-Type": "application/json", "Accept": "application/json" },
            body: JSON.stringify({ token: tokenDaUrl(), senha, confirmar }),
        });

        if (r.ok) {
            document.getElementById("formRedefinir").classList.add("d-none");
            mostrarAlerta(r.dados.mensagem, "success");
            document.getElementById("linkLogin").focus();
            return;
        }

        mostrarAlerta(r.dados.mensagem || "Não foi possível salvar a nova senha.", "danger");
        if (r.status === 410) {
            document.getElementById("formRedefinir").classList.add("d-none");
            document.getElementById("linkNovoPedido").classList.remove("d-none");
        }
    } catch (erro) {
        console.error("Erro ao salvar a nova senha:", erro);
        mostrarAlerta("Não foi possível conectar ao servidor. Tente novamente.", "danger");
    } finally {
        botao.disabled = false;
        botao.textContent = "Salvar nova senha";
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const formRecuperar = document.getElementById("formRecuperar");
    if (formRecuperar) {
        formRecuperar.addEventListener("submit", enviarPedido);
    }

    const formRedefinir = document.getElementById("formRedefinir");
    if (formRedefinir) {
        formRedefinir.addEventListener("submit", salvarNovaSenha);
        if (window.ligarChecklistSenha) window.ligarChecklistSenha("senha", "listaRegras");
        conferirLink();
    }
});
