describe("Recuperação de senha", () => {
  it("o botão 'Esqueci a senha' abre a tela de recuperação", () => {
    cy.visit("/login");
    cy.contains("a", "Esqueci a senha").click();
    cy.url().should("include", "/recuperar-senha");
    cy.get("label[for=email]").should("exist");
  });

  it("recusa e-mail inválido", () => {
    cy.visit("/recuperar-senha");
    cy.get("#email").type("isso-nao-e-email");
    cy.get("#btnRecuperar").click();
    cy.get("#alerta").should("contain", "e-mail válido");
    cy.get("#email").should("have.attr", "aria-invalid", "true");
  });

  it("e-mail cadastrado ou não: mesma mensagem (não revela quem tem conta)", () => {
    const mensagens = [];
    ["desconhecido-cypress@example.com", Cypress.env("ADMIN_EMAIL")].forEach((email) => {
      cy.visit("/recuperar-senha");
      cy.get("#email").type(email);
      cy.get("#btnRecuperar").click();
      cy.get("#alerta.alert-success")
        .invoke("text")
        .then((t) => mensagens.push(t.split("Modo teste")[0].trim()));
    });
    cy.then(() => expect(mensagens[0]).to.eq(mensagens[1]));
  });

  it("link com token inválido mostra aviso e oferece pedir outro", () => {
    cy.visit("/redefinir-senha?token=token-invalido");
    cy.get("#alerta").should("be.visible").and("contain", "inválido");
    cy.contains("a", "Solicitar novo link").should("be.visible");
    cy.get("#formRedefinir").should("not.be.visible");
  });

  it("link sem token também é recusado", () => {
    cy.visit("/redefinir-senha");
    cy.get("#alerta").should("contain", "inválido");
  });

  // Fluxo completo: precisa de "email.modo_teste": true (o servidor devolve o link na resposta)
  // e do e-mail do Admin cadastrado. TROCA a senha do Admin para ADMIN_SENHA_NOVA.
  const fluxo = Cypress.env("FLUXO_COMPLETO_RECUPERACAO") ? it : it.skip;
  fluxo("pede o link, recusa senha fraca, redefine, e o link não funciona de novo", () => {
    cy.intercept("POST", "**/api/recuperar-senha*").as("pedir");
    cy.visit("/recuperar-senha");
    cy.get("#email").type(Cypress.env("ADMIN_EMAIL"));
    cy.get("#btnRecuperar").click();

    cy.wait("@pedir").then(({ response }) => {
      const link = response.body.link_teste;
      expect(link, "ative email.modo_teste no config.json").to.be.a("string");
      cy.visit(link);
    });

    cy.get("#formRedefinir").should("be.visible");
    cy.get("#listaRegras").should("contain", "(pendente)");

    // senha fraca: barrada no navegador
    cy.get("#senha").type("fraca");
    cy.get("#confirmar").type("fraca");
    cy.get("#btnRedefinir").click();
    cy.get("#alerta").should("contain", "A senha precisa ter");

    // senha forte: aceita
    cy.get("#senha").clear().type(Cypress.env("ADMIN_SENHA_NOVA"));
    cy.get("#confirmar").clear().type(Cypress.env("ADMIN_SENHA_NOVA"));
    cy.get("#btnRedefinir").click();
    cy.get("#alerta.alert-success").should("contain", "Senha alterada");

    // mesmo link de novo: expirado/usado
    cy.url().then((url) => {
      cy.visit(url);
      cy.get("#alerta").should("contain", "expirou");
    });

    // e o login já funciona com a nova senha
    cy.visit("/login");
    cy.get("#usuario").type(Cypress.env("ADMIN_USUARIO"));
    cy.get("#senha").type(Cypress.env("ADMIN_SENHA_NOVA"), { log: false });
    cy.get("#btnLogin").click();
    cy.url().should("not.include", "/login");
  });
});
