async function login(event) {
    if (event) event.preventDefault();

    const usuarioEl = document.getElementById("usuario");
    const senhaEl = document.getElementById("senha");
    const btn = document.getElementById("btnLogin");
    const alerta = document.getElementById("alerta");

    const usuario = usuarioEl.value.trim();
    const senha = senhaEl.value;

    alerta.className = "alert alert-danger d-none";
    alerta.textContent = "";

    if (!usuario || !senha) {
        alerta.textContent = "Informe o usuário e a senha.";
        alerta.classList.remove("d-none");
        return;
    }

    btn.disabled = true;
    btn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Entrando...';

    try {
        const base = String(window.BASE_URL || "").replace(/\/+$/, "");
        const url = `${base}/index.php?url=api/login`;

        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify({ usuario, senha })
        });

        const texto = await response.text();
        console.log("URL:", url, "| Status HTTP:", response.status);

        let data;
        try {
            data = JSON.parse(texto);
        } catch {
            console.error("Resposta não JSON:", texto);
            throw new Error(
                `O servidor retornou uma resposta inválida (HTTP ${response.status}). Verifique o log do PHP.`
            );
        }

        if (!response.ok || data.sucesso !== true) {
            throw new Error(data.mensagem || `Falha no login (HTTP ${response.status}).`);
        }

        window.location.href = `${base}/`;
    } catch (error) {
        console.error("Erro no login:", error);
        alerta.textContent = error.message || "Não foi possível conectar ao servidor.";
        alerta.classList.remove("d-none");
    } finally {
        btn.disabled = false;
        btn.textContent = "Entrar";
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formLogin");
    if (!form) {
        console.error("Formulário #formLogin não encontrado.");
        return;
    }
    form.addEventListener("submit", login);

    // Veio de "Alterar a senha": pede para entrar com a nova senha
    if (new URLSearchParams(window.location.search).get("senha_alterada") === "1") {
        const alerta = document.getElementById("alerta");
        alerta.className = "alert alert-success";
        alerta.textContent = "Senha alterada com sucesso. Entre com a nova senha.";
    }
});
