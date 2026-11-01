// Comandos de apoio. Pré-requisito: banco com banco.sql + seed_teste.sql importados
// (o seed cria os cadastros usados para montar pedidos e o shaper de teste).

const api = (recurso, sufixo = "") => `/index.php?url=api/${recurso}${sufixo}`;

/** Login pela API (rápido): cria a sessão sem passar pela tela. */
Cypress.Commands.add("loginApi", (usuario, senha) => {
  cy.session(
    [usuario || Cypress.env("ADMIN_USUARIO"), senha || Cypress.env("ADMIN_SENHA")],
    () => {
      cy.request("POST", api("login"), {
        usuario: usuario || Cypress.env("ADMIN_USUARIO"),
        senha: senha || Cypress.env("ADMIN_SENHA"),
      })
        .its("body.sucesso")
        .should("eq", true);
    }
  );
});

const primeiroId = (lista) => {
  const itens = Array.isArray(lista) ? lista : [];
  expect(itens.length, "o seed_teste.sql precisa estar importado").to.be.greaterThan(0);
  const item = itens[0];
  const chave = "id" in item ? "id" : Object.keys(item).find((k) => k.startsWith("id_"));
  return Number(item[chave]);
};

/** Garante que existem os status "Em produção" e "Finalizado" e devolve os ids. */
Cypress.Commands.add("statusEspeciais", () => {
  const normaliza = (t) => String(t).normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim().toLowerCase();
  const garantir = (lista, nome, ordem) => {
    const achado = lista.find((s) => normaliza(s.descricao) === normaliza(nome));
    if (achado) return cy.wrap(Number(achado.id));
    return cy
      .request("POST", api("status"), { descricao: nome, ordem, ativo: 1 })
      .then(() => cy.request(api("status")))
      .then((r) => Number(r.body.find((s) => normaliza(s.descricao) === normaliza(nome)).id));
  };
  return cy.request(api("status")).then((r) =>
    garantir(r.body, "Em produção", 2).then((emProducao) =>
      garantir(r.body, "Finalizado", 6).then((finalizado) => ({ emProducao, finalizado }))
    )
  );
});

/** Cria um pedido PU/EPS pela API e devolve o id. */
Cypress.Commands.add("criarPedido", (tipo) => {
  const ids = {};
  const buscar = (campo, recurso) => cy.request(api(recurso)).then((r) => (ids[campo] = primeiroId(r.body)));
  buscar("id_shaper", "shapers");
  buscar("id_composicao", "composicoes");
  buscar("id_variacao", "variacoes");
  buscar("id_acabamento", "acabamentos");
  buscar("id_configuracaoquilha", "configuracaoquilhas");
  buscar("id_sistemaquilha", "sistemaquilhas");
  return cy.then(() =>
    cy
      .request("POST", api("pedidos"), {
        ...ids,
        data: new Date().toISOString().slice(0, 10),
        tipo_bloco: tipo,
        observacao: "Pedido criado pelo Cypress",
        tecidos: [],
        cores: [],
      })
      .then((r) => {
        expect(r.status).to.eq(201);
        return Number(r.body.id_pedido);
      })
  );
});

const doisDigitos = (n) => String(n).padStart(2, "0");
/** "agora menos N dias" no formato aceito pela API (Y-m-d H:i:s). */
Cypress.Commands.add("dataHa", (dias) => {
  const d = new Date();
  d.setDate(d.getDate() - dias);
  return cy.wrap(
    `${d.getFullYear()}-${doisDigitos(d.getMonth() + 1)}-${doisDigitos(d.getDate())} 10:00:00`
  );
});

/** Registra um status num pedido pela API. */
Cypress.Commands.add("registrarStatus", (idPedido, idStatus, data) =>
  cy
    .request("POST", api("pedidos-status"), { id_pedido: idPedido, id_status: idStatus, data, observacao: "" })
    .then((r) => {
      expect(r.status).to.eq(200);
      return r.body;
    })
);

/** Abre Pedidos > Status do Pedido na interface. */
Cypress.Commands.add("abrirStatusDoPedido", () => {
  cy.visit("/");
  cy.get('button[data-menu-target="#pedido"]').first().click();
  cy.get("#tabPedidoStatus").should("be.visible");
});
