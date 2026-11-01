// Alertas visuais de prazo (só para pedidos em produção; cessam em "Finalizado").
// PU:  normal 0-6 | crítico 7-10 | vencido 11+      EPS: normal 0-15 | crítico 16-25 | vencido 26+
// Cada alerta tem ÍCONE + TEXTO + atributo semântico (data-prazo): nunca depende só de cor.

const casos = [
  { tipo: "PU", dias: 3, nivel: "normal", texto: "Prazo normal", icone: "fa-circle-check" },
  { tipo: "PU", dias: 7, nivel: "critico", texto: "Prazo crítico", icone: "fa-triangle-exclamation" },
  { tipo: "PU", dias: 10, nivel: "critico", texto: "Prazo crítico", icone: "fa-triangle-exclamation" },
  { tipo: "PU", dias: 11, nivel: "vencido", texto: "Prazo vencido", icone: "fa-circle-xmark" },
  { tipo: "EPS", dias: 15, nivel: "normal", texto: "Prazo normal", icone: "fa-circle-check" },
  { tipo: "EPS", dias: 16, nivel: "critico", texto: "Prazo crítico", icone: "fa-triangle-exclamation" },
  { tipo: "EPS", dias: 25, nivel: "critico", texto: "Prazo crítico", icone: "fa-triangle-exclamation" },
  { tipo: "EPS", dias: 26, nivel: "vencido", texto: "Prazo vencido", icone: "fa-circle-xmark" },
];

describe("Alertas visuais de prazo dos pedidos", () => {
  const pedidos = {}; // "PU-3" -> id

  before(() => {
    cy.loginApi();
    cy.statusEspeciais().then(({ emProducao, finalizado }) => {
      casos.forEach(({ tipo, dias }) => {
        cy.criarPedido(tipo).then((id) => {
          pedidos[`${tipo}-${dias}`] = id;
          cy.dataHa(dias).then((data) => cy.registrarStatus(id, emProducao, data));
        });
      });
      // Pedido vencido que foi finalizado: o alerta tem de cessar
      cy.criarPedido("PU").then((id) => {
        pedidos.finalizado = id;
        cy.dataHa(20).then((d) => cy.registrarStatus(id, emProducao, d));
        cy.dataHa(1).then((d) => cy.registrarStatus(id, finalizado, d));
      });
      // Pedido que ainda não entrou em produção: sem alerta de prazo
      cy.criarPedido("EPS").then((id) => (pedidos.aguardando = id));
    });
  });

  beforeEach(() => {
    cy.loginApi();
    cy.abrirStatusDoPedido();
    cy.get("#accordionPanelsStayOpenExample .accordion-item").should("have.length.greaterThan", 0);
  });

  casos.forEach(({ tipo, dias, nivel, texto, icone }) => {
    it(`${tipo} no dia ${dias}: ${texto}`, () => {
      const item = `.accordion-item[data-pedido="${pedidos[`${tipo}-${dias}`]}"]`;
      cy.get(`${item} [data-prazo="${nivel}"]`).within(() => {
        cy.contains(texto); // texto explicativo
        cy.get(`i.${icone}`).should("exist"); // ícone
        cy.get(".badge-prazo-detalhe").invoke("text").should("match", /faltam|atrasad|hoje|prevista/); // detalhe
      });
    });
  });

  it("vencido mostra quantos dias de atraso", () => {
    const item = `.accordion-item[data-pedido="${pedidos["PU-11"]}"]`;
    cy.get(`${item} [data-prazo="vencido"]`).should("contain", "atrasado há 1 dia");
  });

  it("os níveis se distinguem sem cor: ícones e textos diferentes", () => {
    const rotulo = (nivel, id) => cy.get(`.accordion-item[data-pedido="${id}"] [data-prazo="${nivel}"] .badge-prazo-rotulo`);
    rotulo("normal", pedidos["PU-3"]).should("have.text", "Prazo normal");
    rotulo("critico", pedidos["PU-7"]).should("have.text", "Prazo crítico");
    rotulo("vencido", pedidos["PU-11"]).should("have.text", "Prazo vencido");
  });

  it("ao finalizar, o alerta cessa (mesmo estando fora do prazo)", () => {
    const item = `.accordion-item[data-pedido="${pedidos.finalizado}"]`;
    cy.get(`${item} [data-prazo="finalizado"]`).should("contain", "Finalizado");
    cy.get(`${item} [data-prazo="critico"], ${item} [data-prazo="vencido"]`).should("not.exist");
  });

  it("pedido que ainda não entrou em produção não tem alerta de prazo", () => {
    const item = `.accordion-item[data-pedido="${pedidos.aguardando}"]`;
    cy.get(`${item} [data-prazo="aguardando"]`).should("contain", "Aguardando produção");
    cy.get(`${item} [data-prazo="normal"], ${item} [data-prazo="critico"], ${item} [data-prazo="vencido"]`).should("not.exist");
  });
});
