// Mudança de status para "Em produção" pela interface: grava o marco zero, calcula a previsão
// (PU +10 dias, EPS +25) e dispara a notificação, tudo sem erro em tela.

const api = (recurso) => `/index.php?url=api/${recurso}`;
const ymd = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
const maisDias = (n) => {
  const d = new Date();
  d.setDate(d.getDate() + n);
  return ymd(d);
};
const normaliza = (t) => String(t).normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim().toLowerCase();

/** Deixa o pedido uma etapa antes de "Em produção" (registrando a etapa anterior pela API). */
const prepararParaProducao = (idPedido) =>
  cy.request(api("status")).then((r) => {
    const ativos = r.body.filter((s) => Number(s.ativo) === 1);
    const i = ativos.findIndex((s) => normaliza(s.descricao) === "em producao");
    expect(i, 'o status "Em produção" precisa existir e estar ativo').to.be.greaterThan(-1);
    if (i > 0) {
      cy.dataHa(1).then((data) => cy.registrarStatus(idPedido, Number(ativos[i - 1].id), data));
    }
  });

const colocarEmProducaoPelaTela = (idPedido) => {
  cy.intercept("POST", "**/api/pedidos-status*").as("salvarStatus");
  cy.abrirStatusDoPedido();
  cy.get("#inputBuscaPedido").clear().type(`#${idPedido}`);
  const item = `.accordion-item[data-pedido="${idPedido}"]`;
  cy.get(`${item} .accordion-button`).click();
  cy.contains(`${item} button`, "Atualizar Status").click();

  cy.get("#modalStatus").should("be.visible");
  cy.get("#statusDescricao").should(($i) => expect(normaliza($i.val())).to.eq("em producao"));
  cy.get("#btnSalvarStatusPedido").click();
  return cy.wait("@salvarStatus");
};

describe("Pedido: status 'Em produção' encadeia marco zero, previsão e notificação", () => {
  beforeEach(() => {
    cy.loginApi();
    cy.statusEspeciais();
  });

  [
    { tipo: "PU", dias: 10, rotulo: "10 dias" },
    { tipo: "EPS", dias: 25, rotulo: "25 dias" },
  ].forEach(({ tipo, dias }) => {
    it(`bloco ${tipo}: previsão = hoje + ${dias} dias, gravada e visível`, () => {
      cy.criarPedido(tipo).then((idPedido) => {
        prepararParaProducao(idPedido);

        colocarEmProducaoPelaTela(idPedido).then(({ response }) => {
          expect(response.statusCode).to.eq(200);
          // 1) marco zero + previsão gravados na mesma operação
          expect(response.body.marco_zero).to.eq(ymd(new Date()));
          expect(response.body.previsao).to.eq(maisDias(dias));
          // 2) a notificação foi acionada (enviada, ou falhou/sem e-mail se a Brevo não estiver configurada)
          expect(["enviada", "falhou", "sem_email"]).to.include(response.body.notificacao);
          if (Cypress.env("EMAIL_CONFIGURADO")) {
            expect(response.body.notificacao).to.eq("enviada");
          }
        });

        // Sem erro em tela: o aviso não é vermelho e o modal fecha
        cy.get("#alertToast").should("have.class", "bg-success").and("not.have.class", "bg-danger");
        cy.get("#modalStatus").should("not.be.visible");

        // Previsão registrada no banco
        cy.request(`${api("pedidos")}`).then((r) => {
          const p = r.body.find((x) => Number(x.id_pedido) === idPedido);
          expect(p.data_inicio_producao).to.eq(ymd(new Date()));
          expect(p.data_prevista_entrega).to.eq(maisDias(dias));
        });

        // Visualização da data final prevista, com o alerta "normal"
        const item = `.accordion-item[data-pedido="${idPedido}"]`;
        cy.get(`${item} [data-prazo="normal"]`)
          .should("contain", "Prazo normal")
          .and("contain", `faltam ${dias} dias`);
        const [a, m, d] = maisDias(dias).split("-");
        cy.get(`${item} [data-prazo]`).should("contain", `${d}/${m}/${a}`);
      });
    });
  });
});
