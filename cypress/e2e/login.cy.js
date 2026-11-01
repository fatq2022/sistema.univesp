describe("Login", () => {
  beforeEach(() => cy.visit("/login"));

  it("mostra o formulário com rótulos e os dois botões de senha", () => {
    cy.get("label[for=usuario]").should("contain", "Usuário");
    cy.get("label[for=senha]").should("contain", "Senha");
    cy.contains("a", "Esqueci a senha").should("have.attr", "href").and("include", "/recuperar-senha");
    cy.contains("a", "Alterar a senha").should("have.attr", "href").and("include", "/alterar-senha");
  });

  it("recusa senha errada com mensagem", () => {
    cy.get("#usuario").type(Cypress.env("ADMIN_USUARIO"));
    cy.get("#senha").type("senha-errada");
    cy.get("#btnLogin").click();
    cy.get("#alerta").should("be.visible").and("contain", "Usuário ou senha inválidos");
    cy.url().should("include", "/login");
  });

  it("exige usuário e senha", () => {
    cy.get("#btnLogin").click();
    cy.get("#alerta").should("contain", "Informe o usuário e a senha");
  });

  it("entra com as credenciais corretas e abre o sistema", () => {
    cy.get("#usuario").type(Cypress.env("ADMIN_USUARIO"));
    cy.get("#senha").type(Cypress.env("ADMIN_SENHA"), { log: false });
    cy.get("#btnLogin").click();
    cy.url().should("not.include", "/login");
    cy.get('button[data-menu-target="#pedido"]').should("exist");
  });

  it("não deixa abrir a tela de pedidos sem login", () => {
    cy.clearCookies();
    cy.visit("/pedido", { failOnStatusCode: false });
    cy.url().should("include", "/login");
  });

  it("envia o formulário só com o teclado (Enter no campo de senha)", () => {
    cy.get("#usuario").type(Cypress.env("ADMIN_USUARIO"));
    cy.get("#senha").type(`${Cypress.env("ADMIN_SENHA")}{enter}`, { log: false });
    cy.url().should("not.include", "/login");
  });
});

describe("Alterar a senha (telas e validações, sem trocar a senha de verdade)", () => {
  beforeEach(() => cy.visit("/login"));

  it("o botão leva à tela e o Cancelar volta ao login sem alterar nada", () => {
    cy.contains("a", "Alterar a senha").click();
    cy.url().should("include", "/alterar-senha");
    cy.contains("h1", "Alterar a senha");
    cy.contains("a", "Cancelar").click();
    cy.url().should("include", "/login");
  });

  it("mostra o checklist de força da senha com texto, não só cor", () => {
    cy.visit("/alterar-senha");
    cy.get("#novaSenha").type("abc");
    cy.get("#listaRegras").should("contain", "1 letra maiúscula (pendente)");
    cy.get("#novaSenha").clear().type("Abc12!");
    cy.get("#listaRegras").should("not.contain", "(pendente)");
    cy.get("#listaRegras li").each(($li) => expect($li.text()).to.contain("(atendido)"));
  });

  it("recusa senha fraca, repetição diferente e senha igual à atual", () => {
    cy.visit("/alterar-senha");
    cy.get("#usuario").type(Cypress.env("ADMIN_USUARIO"));
    cy.get("#senhaAtual").type(Cypress.env("ADMIN_SENHA"), { log: false });

    cy.get("#novaSenha").type("fraca");
    cy.get("#confirmar").type("fraca");
    cy.get("#btnConfirmar").click();
    cy.get("#alerta").should("contain", "A senha precisa ter");

    cy.get("#novaSenha").clear().type("Forte#123");
    cy.get("#confirmar").clear().type("Forte#124");
    cy.get("#btnConfirmar").click();
    cy.get("#alerta").should("contain", "A confirmação não confere");
  });

  it("com a senha atual errada, mostra o erro e continua na tela", () => {
    cy.visit("/alterar-senha");
    cy.get("#usuario").type(Cypress.env("ADMIN_USUARIO"));
    cy.get("#senhaAtual").type("errada-errada");
    cy.get("#novaSenha").type("Forte#123");
    cy.get("#confirmar").type("Forte#123");
    cy.get("#btnConfirmar").click();
    cy.get("#alerta").should("contain", "inválidos");
    cy.url().should("include", "/alterar-senha");
  });
});
