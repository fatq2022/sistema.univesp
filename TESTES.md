# Como testar o sistema

## 1. Preparar (uma vez)

1. Ligue **Apache** e **MySQL** no XAMPP e ative `mod_rewrite` (já vem ativo) e as extensões `pdo_mysql` e `curl` no `php.ini`.
2. Copie a pasta para `htdocs/sistema` (o nome da pasta precisa ser `sistema`, igual ao `base_path` de `config/config.json` e ao `RewriteBase` do `.htaccess`).
3. No phpMyAdmin (aba **Importar**), importe nesta ordem:
   - `banco.sql`
   - `seed_teste.sql` (opcional: cria status, cores, tecidos etc. de exemplo)
   - Se o banco **já existia** antes desta versão: `migracoes/001_recuperacao_senha.sql` (adiciona a coluna `email` em `usuario`; troque o e-mail de exemplo pelo seu).
   - Se o banco é novo: depois de importar, rode `UPDATE usuario SET email = 'seu-email@exemplo.com' WHERE usuario = 'Admin';` para poder testar a recuperação.
4. Confira a conexão: abra `http://localhost/sistema/teste-banco.php` (só funciona em localhost). Deve aparecer "Conexão com o MySQL funcionando" e "Tabela usuario com 1 usuário(s)".
   Se o MySQL tiver senha, ajuste `config/config.json`.

## 2. Testes automáticos

Na pasta do projeto (no XAMPP: `C:\xampp\php\php.exe`):

```
php tests/PedidoTest.php      # sem banco: regra de prazo, senha do Admin, colunas dos models
php tests/RecuperacaoTest.php # sem banco: token/hash, regras de senha, link do e-mail, rotas, segredos, textos dos JS
php tests/api_smoke.php       # com Apache+MySQL ligados: percorre a API inteira e limpa o que criou
php tests/api_smoke.php http://localhost/sistema   # se a URL for outra
```

Os dois terminam com `TODOS ... PASSARAM` ou listam cada `[FALHA]` com o HTTP e o corpo da resposta.
Se algo falhar sem explicação, veja o log de erros do PHP (`xampp/apache/logs/error.log`): os erros técnicos agora vão para lá, não para a tela.

## 2.1 PHPUnit (backend) e Cypress (interface)

**PHPUnit** (precisa de PHP 8.1+ e Composer; habilite `pdo_sqlite` no `php.ini`):

```
composer install
vendor/bin/phpunit                      # prazo PU/EPS, alertas, e-mail de "Em produção", token, senha
```

Os testes de **expiração de token** e **recuperação de senha completa** usam o MySQL e ficam *pulados* até você informar o banco
(usam uma tabela `TEMPORARY`, então não alteram seus dados):

```
set TEST_DB_DSN=mysql:host=localhost;dbname=sistema;charset=utf8mb4      (Windows)
export TEST_DB_DSN="mysql:host=localhost;dbname=sistema;charset=utf8mb4" (Linux/Mac)
vendor/bin/phpunit
```

**Cypress** (precisa de Node 18+; banco com `banco.sql` + `seed_teste.sql`; sistema rodando em `http://localhost/sistema`):

```
npm install
npx cypress open        # interface gráfica
npx cypress run         # linha de comando
```

Specs: `login`, `recuperacao-senha`, `pedido-producao` (muda o status para "Em produção" na tela e confere marco zero, previsão e notificação) e
`alertas-prazo` (todas as faixas PU/EPS, ícone + texto, e o alerta cessando em "Finalizado"). Ajuste usuário/senha e URL em `cypress.config.js`.
O teste completo do link de recuperação fica desligado (`FLUXO_COMPLETO_RECUPERACAO`) porque troca a senha do Admin.

> Os scripts antigos (`php tests/PedidoTest.php` e `php tests/RecuperacaoTest.php`) continuam funcionando e foram atualizados às novas regras.

## 3. Roteiro manual no navegador

Entre em `http://localhost/sistema/` com **Admin / 123456**.

| # | O que fazer | Resultado esperado |
|---|-------------|--------------------|
| 1 | Login com senha errada | Mensagem "Usuário ou senha inválidos" |
| 2 | Login correto | Vai para o Dashboard; no topo aparece `Admin \| Administrador` |
| 3 | Abrir `http://localhost/sistema/views/pedido/index.php` **deslogado** (janela anônima) | Página em branco (401), não mostra a tela |
| 4 | Menu **Shaper**: cadastrar, editar, desativar/ativar, excluir | Tudo funciona; excluir shaper com pedido dá aviso vermelho "vinculado a pedidos" |
| 5 | Menu **Status**: criar, editar, arrastar para reordenar, ativar/desativar | Ordem persiste ao recarregar (F5) |
| 6 | Menu **Pedido → Gerenciamento**: abrir o modal de cada cadastro (Acabamento, Tecido, Variação, Composição, Cor, Quilha) e **criar, editar e excluir** | Antes da correção, editar/excluir Acabamento, Tecido e Variação davam erro 500 |
| 7 | Criar pedido **PU** e outro **EPS** | Estimativa provisória (data do pedido + 10 PU / + 25 EPS); o prazo definitivo conta de "Em produção" (ver Rodada 6) |
| 8 | Editar o pedido trocando PU ↔ EPS | A previsão é recalculada |
| 9 | Aba **Status do Pedido**: avançar o status de um pedido | Aparece na timeline; o responsável é o usuário logado |
| 10 | Detalhe do pedido → **Abrir / PDF** | Abre janela de impressão (antes o botão não fazia nada) |
| 11 | Escrever `<b>teste</b>` na observação e abrir o detalhe | Aparece o texto literal, sem negrito (proteção contra XSS) |
| 12 | Dashboard | Totais de pedidos/shapers e contagem por status batem com as listas |
| 13 | Sair | Volta ao login; voltar com o botão "Voltar" do navegador e dar F5 → pede login |
| 14 | Login → **Esqueci minha senha** → informar o e-mail do Admin | Mensagem verde "Se este e-mail estiver cadastrado...". Com e-mail inexistente a mensagem é a mesma (não revela quem tem cadastro) |
| 15 | Abrir o link recebido (ou, sem Brevo, o link gravado em `logs/app.log` com `email.registrar_link_no_log = true`) | Aparece "Criar nova senha". Senha curta ou confirmação diferente é barrada |
| 16 | Salvar a nova senha e abrir **o mesmo link de novo** | "Este link expirou ou já foi utilizado" (uso único). Login com a senha nova funciona |
| 17 | Status → botões ↑ ↓ de cada linha (ou Tab até eles e Enter) | A ordem muda e persiste ao recarregar (alternativa ao arrastar, para teclado/leitor de tela) |
| 18 | Shaper: digitar no campo de busca; digitar e-mail inválido e dar Enter | Filtra enquanto digita; e-mail inválido mostra aviso e não grava |

## 3.1 Acessibilidade (conferência rápida)

- Navegar só com **Tab/Shift+Tab/Enter**: o primeiro Tab mostra "Ir para o conteúdo principal"; todos os botões (inclusive os de ícone) têm nome; o foco nunca some.
- Ligar um leitor de tela (NVDA no Windows, VoiceOver no Mac/iOS, TalkBack no Android): campos são lidos com o rótulo ("Nome", "E-mail"...), avisos de erro são anunciados (`role="alert"`), o item de menu atual é anunciado como "página atual".
- Ferramentas: extensão **axe DevTools** ou aba **Lighthouse → Accessibility** do Edge/Chrome (rode em cada tela: Dashboard, Pedidos, Status, Shaper, Login, Recuperar).

## 4. Itens de segurança para conferir (pelo navegador)

Estes endereços devem responder **403/404**, nunca mostrar conteúdo:

- `http://localhost/sistema/config/config.json`
- `http://localhost/sistema/banco.sql`
- `http://localhost/sistema/logs/app.log`
- `http://localhost/sistema/controllers/ShaperController.php`

## 5. Antes de colocar em produção

- Troque a senha do Admin (`123456` é só de teste) e o usuário do MySQL (`root` sem senha).
- Preencha `email.brevo_api_key` e `email.remetente_email` se quiser os e-mails de notificação.
- Preencha `diagnostico.token` se for usar a tela de Diagnóstico.
- Apague `teste-banco.php` e `seed_teste.sql` do servidor.
- Preencha `app.url_publica` (ex.: `https://seudominio.com.br`) para o link do e-mail de recuperação apontar sempre para o seu site, e não para o cabeçalho `Host` da requisição.
- `config/config.json` **não vai para o GitHub** (está no `.gitignore`). No servidor da Locaweb, copie `config/config.example.json` para `config/config.json` e preencha banco, chave da Brevo e remetente.
- Se o projeto não ficar em `/sistema` no servidor, ajuste `app.base_path` no `config.json` **e** o `RewriteBase` do `.htaccess`.
- Se `config/config.json` já tinha sido enviado ao GitHub antes, o `.gitignore` sozinho não resolve: rode `git rm --cached config/config.json`, faça o commit e **troque a senha do banco e a chave da Brevo** (elas ficaram no histórico).

---

# O que foi corrigido

**Erros que quebravam funcionalidades**
- `models/Acabamento.php`, `Tecido.php`, `Variacao.php`: usavam a coluna `id`, que não existe (as colunas são `id_acabamento`, `id_tecido`, `id_variacao`). Editar, excluir e buscar por id falhavam com erro 500.
- Os mesmos models, mais `Composicao`, `Configuracaoquilha`, `Cor` e `Sistemaquilha`: `deletar()` devolvia "sucesso" mesmo quando nenhuma linha era apagada. Agora confere as linhas afetadas.
- Excluir Acabamento, Composição, Configuração de quilha ou Tecido em uso por um pedido dava erro 500; agora responde 409 com mensagem clara.
- `CorController` e `VariacaoController`: o `id` devolvido na criação vinha sempre `null` (lia uma propriedade que não existe). Agora devolve o id criado.
- `SistemaquilhaController`: criar/editar sem descrição gerava erro/aviso do PHP; agora responde 400.
- `PedidoController::criar`: confirmava (commit) a transação antes de checar se o pedido tinha sido criado. Agora só confirma em caso de sucesso, e devolve `id_pedido` com status 201.
- `PedidoStatusController`: o responsável pelo status era qualquer `id_usuario` enviado pelo navegador (ou 1 por padrão). Agora é sempre o usuário logado.
- `app.js` (`showToast`): respostas de erro no formato `{sucesso:false, mensagem}` (Shaper, Status, Pedido) apareciam como toast **verde de sucesso**. Agora aparecem como erro.
- Menu lateral: cada clique chamava a tela duas vezes (`ativarAba` + `carregarX`), podendo montar selects duplicados. Removida a chamada duplicada.
- Botão **Abrir / PDF** do detalhe do pedido chamava `imprimirDetalhePedido()`, que não existia. Criada.
- Detalhe do pedido ficava "Carregando..." para sempre se a API falhasse. Agora mostra o erro.

**Segurança**
- `.htaccess` agora bloqueia `config/` (senha do banco e chaves de API estavam acessíveis pela URL), `controllers/`, `models/`, `routes/`, `logs/`, `tests/`, `docs/`, `banco.sql` e arquivos ocultos.
- Views `pedido`, `shaper` e `status` passaram a exigir login (como o dashboard já fazia).
- `teste-banco.php` só responde em localhost.
- Mensagens técnicas de exceção (`getMessage()`) não são mais enviadas ao navegador; vão para o log do PHP.
- Dados do banco exibidos no detalhe do pedido são escapados (evita XSS armazenado pelo campo observação).

**Testes**
- `tests/PedidoTest.php` antigo não testava o sistema (reimplementava a conta dentro do próprio teste e sempre passava). Foi substituído por testes que chamam o código real e conferem o `banco.sql`.
- Novo `tests/api_smoke.php` (teste de ponta a ponta da API) e `seed_teste.sql` (dados de exemplo).

---

# Rodada 6 (01/11/2026): prazo por "Em produção", e-mail, alertas, senha, testes

**Regra de prazo e notificação**
- O prazo agora conta a partir do **marco zero**: o dia em que o pedido entra na etapa **"Em produção"** (PU +10 dias corridos, EPS +25). Antes contava da data de criação do pedido.
- Ao registrar "Em produção" acontece, juntos: (1) grava `pedido.data_inicio_producao` (marco zero) e recalcula `data_prevista_entrega`; (2) envia o e-mail pela Brevo ao e-mail do shaper com o tipo (PU/EPS) e a previsão. O e-mail só sai na **primeira** vez que o pedido entra em produção. Se o e-mail falhar ou o shaper não tiver e-mail, o status é gravado do mesmo jeito e o aviso na tela informa.
- As etapas são reconhecidas pelo **nome** em "Status": `Em produção` e `Finalizado` (sem diferenciar maiúsculas/acentos). **Em bancos que já existiam**, renomeie na tela Status (ex.: "Em laminação" → "Em produção", "Entregue" → "Finalizado") ou crie os dois. Bancos novos já vêm assim pelo `seed_teste.sql`.
- Pedidos já em produção antes desta versão: rode `migracoes/002_prazo_producao.sql` (preenche o marco zero a partir do histórico). O sistema também cria as colunas sozinho se faltarem.
- Antes de entrar em produção o pedido mostra a estimativa antiga (data do pedido + prazo) e **sem alerta**.

**Alertas visuais (só para pedidos em produção)** - dias corridos desde o marco zero:

| Bloco | Normal | Crítico | Vencido |
|-------|--------|---------|---------|
| PU | 0 a 6 | 7 a 10 | 11 em diante |
| EPS | 0 a 15 | 16 a 25 | 26 em diante |

Cada nível tem ícone, texto ("Prazo crítico: faltam 3 dias...") e atributo `data-prazo`; as bordas também mudam (pontilhada, sólida, tracejada, dupla). Em "Finalizado" o alerta cessa.

**Senha**
- Nova tela **Alterar a senha** (botão no login): usuário, senha atual, nova, repetição; Confirmar leva ao login pedindo a nova senha; Cancelar volta sem alterar nada. Como a pessoa ainda não está logada, a tela pede também o **usuário**. Limite de 5 tentativas erradas por sessão (bloqueio de 5 minutos).
- **Força da senha**, em JavaScript (`public/js/senha.js`) **e** no PHP (`helpers/Senha.php`): mínimo 6 caracteres, 1 maiúscula, 1 minúscula, 1 número, 1 caractere especial. Vale para Alterar a senha e Redefinir. Antes o mínimo era 8. A senha de teste `123456` do Admin continua entrando, mas não serve como senha nova.
- O botão "Esqueci minha senha" virou "Esqueci a senha".

**Acessibilidade**: contraste reforçado (alertas, textos de apoio, placeholders), checklist da senha com texto "(atendido)/(pendente)", `aria-*` e rótulos nas telas de Diagnóstico e Dashboard, ícones decorativos escondidos de leitores de tela.

**Roteiro manual extra**: crie um pedido PU → Status do Pedido → avance até "Em produção" → o selo mostra "Prazo normal: faltam 10 dias" e a data final. Edite a data do status de "Em produção" para 8 dias atrás → "Prazo crítico". Para 12 dias atrás → "Prazo vencido". Avance até "Finalizado" → o selo vira "Finalizado".

---

# Rodada 5 (28/10/2026)

- **"Campos obrigatórios faltando" ao atualizar o status de um pedido.** Aparecia quando **não há nenhum status cadastrado** (tela "Status dos pedidos" vazia): a API devolvia "sem próximo status", o sistema mandava `id_status = 0` (que o PHP trata como vazio) e ainda fechava o modal como se tivesse salvo. Agora: se não houver próximo status, aparece um aviso explicando o que fazer e o modal nem abre; os campos são validados antes de enviar; e, se o servidor recusar, o modal **continua aberto** com a mensagem.
- **Tela Status vazia** agora explica o problema e traz o botão **Criar status padrão** (Aguardando, Em laminação, Secagem, Acabamento, Pronto, Entregue — os mesmos do `seed_teste.sql`).
- **Busca em Pedidos > Status do Pedido:** digitar um número (`11` ou `#11`) agora busca o **número do pedido**. Antes, `11` também achava qualquer pedido com data `/11/` (ex.: 07/11/2026) e parecia que o filtro não funcionava. Nome, status e ano (4+ dígitos) continuam funcionando.

**Roteiro para conferir:** 1) Status dos pedidos → se estiver vazia, clique em *Criar status padrão*. 2) Pedidos → Status do Pedido → abra um pedido → *Atualizar Status* → Salvar: o status entra na timeline. 3) Repita: o botão oferece o próximo da sequência. 4) Busque por `#12`, por nome do shaper e por nome de status.

---

# Rodada 4 (28/10/2026)

- **Pedidos ficava em branco ao sair e voltar.** `ativarAba` (views/home/index.php) escondia *todo* `.tab-content .tab-pane`, inclusive as abas internas "Status do Pedido" e "Gerenciamento", e nunca as reativava. Agora só mexe nos painéis do menu principal.
- **Status: busca por ID.** A tabela ganhou a coluna **ID** e digitar um número procura o ID exato (também continua buscando pela descrição). A coluna oculta "ordem" deixou de entrar na busca.
- Mensagem "Não é possível excluir: este shaper está vinculado a pedidos" **não é erro**: é a proteção do banco (chave estrangeira). Para tirar o cliente de uso, desative-o no botão de ativar/desativar.

---

# Rodada 3 (28/10/2026)

**Tela de Pedidos**
- `pedido.js` tinha cópias antigas de `initPedidoStatus` e `executarBuscaPedido` que, por carregarem depois de `pedido-status.js`, substituíam a **timeline** (acordeão com o botão "Atualizar Status") por uma lista simples, e a busca escondia os cards errados. Cópias removidas; a timeline voltou a ser a única.
- Timeline: descrição, observação e nome do shaper agora são escapados (XSS); erros ao salvar/excluir status aparecem na tela (antes só no console); acordeão com `aria-expanded`/`aria-controls`; botões de ícone com nome acessível (também na tabela de Gerenciamento).
- Buscas (Pedidos, Shaper, Status): mostram "Nenhum ... encontrado" e mantêm o filtro quando a lista é recarregada.

**Recuperação de senha**
- O sistema agora cria sozinho as colunas `email`, `reset_token` e `token_expires_at` se faltarem (não é mais obrigatório rodar a migração à mão).
- Erro real passa a ser gravado em `logs/app.log`.
- **Modo teste** (`"email": { "modo_teste": true }` no `config/config.json`): só funciona quando você acessa pelo próprio computador (localhost). A tela mostra o motivo se algo falhar (ex.: "nenhum usuário tem este e-mail", "falta a chave da Brevo") e um link clicável para criar a nova senha, sem precisar de e-mail. **Desligue em produção** (mesmo esquecido, não aparece para acessos de fora).
- Lembrete: o e-mail digitado precisa existir em `usuario.email`. Cadastre com `UPDATE usuario SET email = 'seu-email@gmail.com' WHERE usuario = 'Admin';`.

**Sobre o erro `Uncaught (in promise) {name: 'FetchError'}` do console:** a pilha aponta para `content.js:103` (extensão Mapify), não para o sistema. Todas as chamadas de rede do sistema respondem 200.

---

# Rodada 2 (outubro/2026)

**Erros do console / front-end**
- `status.js`: várias mensagens de erro, avisos e botões estavam em outro idioma (ilocano: "Baelna ti aglista", "Nasayaat"... e "Aktualisaren", "Rehistro"). Todos voltaram para português.
- `status.js` redefinia `window.carregarStatus` por cima da versão do `home.js` (que tem tratamento de erro), recarregando a tela a cada clique e deixando uma falha de rede sem tratamento (`Uncaught (in promise)`). Removida a duplicata.
- `dashboard.js` chamava `window.atualizarDashboard()` solto no arquivo (erro se algum dia fosse carregado) e `acabamento.js` não era usado: ambos removidos.
- `shaper.js`: a busca em tempo real era ligada no `DOMContentLoaded`, mas a tela só entra na página depois; nunca funcionava. Agora usa eventos delegados (também no `status.js`). O botão **X** da busca agora limpa o texto digitado.
- `home.js`: nome do usuário era colocado no HTML sem escape (XSS); agora é escapado.
- Bootstrap passou a ser servido pelos arquivos locais (`public/css/Bootstrap.css`, `public/js/Bootstrap.js`, versão 5.3.3) em vez do CDN: some o aviso "Tracking Prevention blocked access to storage" e o sistema não depende de CDN para o layout.
- Shaper: e-mail é validado no navegador e no servidor (`ShaperController`).
- Os erros de **MetaMask**, **Mapify** (`content.js`, `contentscript.js`, `inpage.js`) e `ObjectMultiplex` vistos no console vêm de extensões do navegador, não do sistema. Para confirmar, abra o site numa janela **InPrivate** (extensões desligadas) e o console fica limpo.

**Recuperação de acesso (estava no tema, mas não existia)**
- Novo fluxo completo: `/recuperar-senha` → e-mail (API Brevo) → `/redefinir-senha?token=...` → nova senha. Arquivos: `controllers/RecuperacaoController.php`, `helpers/Mailer.php`, `models/Usuario.php` (novos métodos), `views/auth/recuperar.php`, `views/auth/redefinir.php`, `public/js/recuperacao.js`, rotas em `routes/api.php` e `routes/web.php`.
- Segurança: token aleatório de 256 bits; no banco fica só o **hash SHA-256**; validade de 60 min; uso único (a troca de senha limpa o token no mesmo `UPDATE`); resposta idêntica para e-mail existente ou não (não revela cadastros); limite de 1 e-mail por minuto por usuário; link montado a partir de `app.url_publica` (não confia no cabeçalho `Host`); página de redefinição com `Referrer-Policy: no-referrer`.
- Banco: coluna `usuario.email` (em `banco.sql` e em `migracoes/001_recuperacao_senha.sql`).

**Acessibilidade**
- Link "Ir para o conteúdo principal", `<main role="main">`, menus com `aria-current` (antes `aria-selected` em `<button>`, que é inválido), painéis como regiões nomeadas, abas de Pedido com `tablist/tab/tabpanel`.
- Todos os campos com `<label for>`; botões só com ícone com `aria-label`; ícones decorativos com `aria-hidden`; tabelas com `<caption>` e `scope`; cabeçalhos ordenáveis viraram botões (funcionam por teclado); modais com `aria-labelledby`; "Close" → "Fechar".
- Formulários de Shaper e Status agora são `<form>` reais (Enter envia).
- Status: botões ↑ ↓ como alternativa ao arrastar (reordenar só com mouse excluía quem usa teclado/leitor de tela). Aba "Gerenciamento" tinha um ícone sem classe (não aparecia).

**GitHub / segredos**
- `.gitignore` apontava para `config/database.php` (arquivo que não existe), então `config/config.json` (senha do banco, chave da Brevo, token do diagnóstico) seria enviado ao repositório. Corrigido, e criado `config/config.example.json` (sem segredos) para ir no lugar.
- `.htaccess` também bloqueia `migracoes/` e `TESTES.md`.

**Testes**
- Novo `tests/RecuperacaoTest.php` (sem banco) e novas verificações em `tests/api_smoke.php` para as rotas públicas de recuperação. Inclui teste de regressão que falha se aparecerem textos em idioma estranho nos JS.

**Não alterado, mas vale saber**
- Em `StatusController`, a tela mostra `ordem` e `ativo`; o model `models/Status.php` não é usado pelo controller (ele consulta direto o banco).
