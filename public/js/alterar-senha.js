// alterar-senha.js - tela "Alterar a senha" (aberta a partir do login)

function mostrarAlerta(texto, tipo) {
    const alerta = document.getElementById("alerta");
    alerta.className = "alert alert-" + tipo;
    alerta.textContent = texto;
}

async function alterarSenha(event) {
    event.preventDefault();

    const campo = (id) => document.getElementById(id);
    const usuario = campo("usuario").value.trim();
    const senhaAtual = campo("senhaAtual").value;
    const nova = campo("novaSenha").value;
    const confirmar = campo("confirmar").value;
    const botao = campo("btnConfirmar");

    const invalido = (el, msg) => {
        mostrarAlerta(msg, "danger");
        el.setAttribute("aria-invalid", "true");
        el.focus();
    };
    ["usuario", "senhaAtual", "novaSenha", "confirmar"].forEach((id) => campo(id).setAttribute("aria-invalid", "false"));

    if (!usuario) return invalido(campo("usuario"), "Informe o usuário.");
    if (!senhaAtual) return invalido(campo("senhaAtual"), "Informe a senha atual.");
    const erroForca = window.mensagemForcaSenha(nova);
    if (erroForca) return invalido(campo("novaSenha"), erroForca);
    if (nova === senhaAtual) return invalido(campo("novaSenha"), "A nova senha precisa ser diferente da senha atual.");
    if (nova !== confirmar) return invalido(campo("confirmar"), "A confirmação não confere com a nova senha.");

    botao.disabled = true;
    botao.textContent = "Salvando...";
    try {
        const base = String(window.BASE_URL || "").replace(/\/+$/, "");
        const resposta = await fetch(`${base}/index.php?url=api/alterar-senha`, {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json", Accept: "application/json" },
            body: JSON.stringify({ usuario, senha_atual: senhaAtual, nova_senha: nova, confirmar }),
        });
        const dados = await resposta.json().catch(() => null);
        if (!dados) throw new Error(`Resposta inválida do servidor (HTTP ${resposta.status}).`);

        if (resposta.ok && dados.sucesso === true) {
            // Pede novo login com a nova senha
            window.location.href = `${base}/login?senha_alterada=1`;
            return;
        }
        mostrarAlerta(dados.mensagem || "Não foi possível alterar a senha.", "danger");
    } catch (erro) {
        console.error("Erro ao alterar a senha:", erro);
        mostrarAlerta("Não foi possível conectar ao servidor. Tente novamente.", "danger");
    } finally {
        botao.disabled = false;
        botao.textContent = "Confirmar";
    }
}

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("formAlterar").addEventListener("submit", alterarSenha);
    window.ligarChecklistSenha("novaSenha", "listaRegras");
});
