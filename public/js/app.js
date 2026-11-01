// app.js

// Em seguida, vem o resto das tuas funções (abrirModal, listarPedidos, etc.)

// ===============================
// GESTÃO DE ATUALIZAÇÃO DE TABS
// ===============================
window.vincularEventosTabs = function () {
  const tabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
  tabs.forEach((tab) => {
    // Remove para não duplicar se carregar a página várias vezes
    tab.removeEventListener("shown.bs.tab", gerirTrocaDeAba);
    tab.addEventListener("shown.bs.tab", gerirTrocaDeAba);
  });
};

document.addEventListener('DOMContentLoaded', function () {
    const botaoSair = document.getElementById('btnLogout');

    if (!botaoSair) return;

    botaoSair.addEventListener('click', function () {
        if (typeof window.logout === 'function') {
            window.logout();
        } else {
            console.error(
                'Função logout() não encontrada. Verifique public/js/app.js.'
            );
            alert('A função de sair não foi carregada.');
        }
    });
});

function gerirTrocaDeAba(event) {
  const targetId = event.target.getAttribute("data-bs-target");

  // Se clicar na Tab de Timeline/Status
  if (targetId === "#tabPedidoStatus") {
    if (typeof window.listarPedidosStatus === "function") {
      window.listarPedidosStatus();
    }
  }

  // Se clicar na Tab de Gerenciamento (Listagem de Pedidos)
  if (targetId === "#tabGerenciamentoPedido") {
    if (typeof window.listarPedidos === "function") {
      window.listarPedidos();
    }

    // 🔥 ATUALIZAÇÃO DOS SELECTS (Inclui Shapers, Cores, etc.)
    if (typeof window.carregarSelectsPedido === "function") {
      window.carregarSelectsPedido();
    }

    // 🔥 Garante que a lista de Shapers ativos seja renovada
    if (typeof window.carregarShapersPedido === "function") {
      window.carregarShapersPedido();
    }
  }
}

window.executarScripts = async function (container) {
  const scripts = container.querySelectorAll("script");
  for (const oldScript of scripts) {
    const newScript = document.createElement("script");
    if (oldScript.src) {
      newScript.src = oldScript.src;
      await new Promise((resolve, reject) => {
        newScript.onload = resolve;
        newScript.onerror = reject;
        document.body.appendChild(newScript);
      });
    } else {
      newScript.textContent = oldScript.textContent;
      document.body.appendChild(newScript);
    }
  }
};

// ===============================
// SHOW TOAST GLOBAL
// ===============================
window.showToast = function (title, result) {
  const toastEl = document.getElementById("alertToast");
  if (!toastEl) return;

  const textTitle = toastEl.querySelector("#toast-title");
  const textToast = toastEl.querySelector("#toast-text");

  // Reset de cores
  toastEl.classList.remove(
    "bg-danger",
    "text-danger",
    "bg-success",
    "text-success",
    "text-light",
  );

  result = result || {};
  const isErro = !!result.erro || result.sucesso === false;
  const msg = result.erro || result.mensagem || "Sem resposta do servidor";

  toastEl.classList.add(isErro ? "bg-danger" : "bg-success", "text-light");

  textTitle.textContent = title;
  textToast.textContent = msg;

  bootstrap.Toast.getOrCreateInstance(toastEl).show();
};

// ===============================
// LOGOUT
// ===============================
window.logout = async function () {
  try {
    await fetch(`${window.BASE_URL}/api/logout`, {
      method: "POST",
    });
  } catch (err) {
    console.error("Erro ao deslogar no servidor:", err);
  }
  // Redireciona sempre para o login usando a base configurada
  window.location.href = `${window.BASE_URL}/login`;
};

window.setarHoraAtual = function () {
  const agora = new Date();
  agora.setMinutes(agora.getMinutes() - agora.getTimezoneOffset());
  document.getElementById("statusData").value = agora
    .toISOString()
    .slice(0, 16);
};


// ===============================
// ESCAPE DE HTML (usar sempre que um dado do banco entrar em innerHTML)
// ===============================
window.escHtml = function (valor) {
  return String(valor ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
};

// ===============================
// IMPRIMIR / SALVAR EM PDF O DETALHE DO PEDIDO
// ===============================
window.imprimirDetalhePedido = function () {
  const modal = document.getElementById("modalPedidoDetalhe");
  if (!modal) return;
  const titulo = modal.querySelector(".modal-title");
  const corpo = modal.querySelector(".modal-body");
  if (!corpo || !corpo.innerHTML.trim()) return;

  const janela = window.open("", "_blank", "width=800,height=900");
  if (!janela) {
    alert("O navegador bloqueou a janela. Permita pop-ups para este site.");
    return;
  }
  janela.document.write(`<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">
    <title>${window.escHtml(titulo ? titulo.textContent.trim() : "Pedido")}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    </head><body class="p-4">
    <h4 class="mb-3">${window.escHtml(titulo ? titulo.textContent.trim() : "Pedido")}</h4>
    ${corpo.innerHTML}
    <script>window.onload = function () { window.print(); };<\/script>
    </body></html>`);
  janela.document.close();
};
