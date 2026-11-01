// senha.js - validação da força da senha (mesmas regras de helpers/Senha.php)
// mínimo 6 caracteres, 1 maiúscula, 1 minúscula, 1 número, 1 caractere especial

window.REGRAS_SENHA = [
  { id: "tamanho", texto: "Pelo menos 6 caracteres", testar: (s) => s.length >= 6 },
  { id: "maiuscula", texto: "1 letra maiúscula", testar: (s) => /\p{Lu}/u.test(s) },
  { id: "minuscula", texto: "1 letra minúscula", testar: (s) => /\p{Ll}/u.test(s) },
  { id: "numero", texto: "1 número", testar: (s) => /\d/.test(s) },
  { id: "especial", texto: "1 caractere especial (ex.: ! @ # $ %)", testar: (s) => /[^\p{L}\p{N}\s]/u.test(s) },
];

window.validarForcaSenha = function (senha) {
  const valor = String(senha || "");
  const regras = window.REGRAS_SENHA.map((r) => ({ id: r.id, texto: r.texto, ok: r.testar(valor) }));
  return {
    ok: regras.every((r) => r.ok) && valor.length <= 72,
    regras,
    faltam: regras.filter((r) => !r.ok).map((r) => r.texto),
  };
};

window.mensagemForcaSenha = function (senha) {
  const r = window.validarForcaSenha(senha);
  if (String(senha || "").length > 72) return "A senha pode ter no máximo 72 caracteres.";
  return r.ok ? null : "A senha precisa ter: " + r.faltam.join("; ").toLowerCase() + ".";
};

// Checklist ao vivo: ícone + texto (não depende só de cor) e anunciado por leitores de tela.
window.ligarChecklistSenha = function (inputId, listaId) {
  const input = document.getElementById(inputId);
  const lista = document.getElementById(listaId);
  if (!input || !lista) return;

  const desenhar = () => {
    const r = window.validarForcaSenha(input.value);
    lista.innerHTML = "";
    r.regras.forEach((regra) => {
      const li = document.createElement("li");
      li.className = regra.ok ? "senha-regra senha-regra-ok" : "senha-regra";
      const icone = document.createElement("i");
      icone.className = regra.ok ? "fa-solid fa-circle-check" : "fa-regular fa-circle";
      icone.setAttribute("aria-hidden", "true");
      const texto = document.createElement("span");
      texto.textContent = " " + regra.texto + (regra.ok ? " (atendido)" : " (pendente)");
      li.append(icone, texto);
      lista.appendChild(li);
    });
  };
  input.addEventListener("input", desenhar);
  desenhar();
};
