const { defineConfig } = require("cypress");

module.exports = defineConfig({
  e2e: {
    // URL do sistema (XAMPP). Mude com: npx cypress run --config baseUrl=https://seudominio.com.br/sistema
    baseUrl: "http://localhost/sistema",
    specPattern: "cypress/e2e/**/*.cy.js",
    supportFile: "cypress/support/e2e.js",
    video: false,
    env: {
      ADMIN_USUARIO: "Admin",
      ADMIN_SENHA: "123456",
      // Só ligue se config.json tiver "email.modo_teste": true e o Admin tiver e-mail cadastrado.
      // ATENÇÃO: o fluxo completo TROCA a senha do Admin para ADMIN_SENHA_NOVA.
      FLUXO_COMPLETO_RECUPERACAO: false,
      ADMIN_EMAIL: "seu-email@exemplo.com",
      ADMIN_SENHA_NOVA: "Nova#12345",
    },
  },
});
