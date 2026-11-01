<?php $base = defined('BASE_PATH') ? BASE_PATH : '/sistema'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Diagnóstico de erros com IA</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>
<body class="bg-light">
<a class="visually-hidden-focusable" href="#conteudo-principal">Ir para o conteúdo principal</a>
<main class="container py-4" id="conteudo-principal" style="max-width:900px">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0">Diagnóstico de erros com IA</h1>
    <a href="<?= htmlspecialchars($base) ?>/" class="btn btn-outline-secondary btn-sm">Voltar</a>
  </div>
  <div class="card shadow-sm mb-3"><div class="card-body">
    <p id="ajudaDiagnostico" class="small" style="color:#475569">Abra o DevTools (F12), tire prints das abas <b>Console</b> e <b>Network</b> (com a requisição clicada e a aba Response aberta) e envie aqui. Até 4 imagens.</p>
    <form id="f">
      <div class="mb-3"><label for="imagens" class="form-label">Imagens do erro (até 4)</label><input id="imagens" class="form-control" type="file" name="imagens[]" accept="image/png,image/jpeg,image/webp" multiple required aria-required="true" aria-describedby="ajudaDiagnostico"></div>
      <div class="mb-3"><label for="descricao" class="form-label">O que você estava fazendo?</label><textarea id="descricao" class="form-control" name="descricao" rows="3" aria-label="Descrição do que você estava fazendo quando o erro apareceu" placeholder="O que você estava fazendo quando o erro apareceu? (opcional)"></textarea></div>
      <button type="submit" class="btn btn-primary" id="btn">Analisar</button>
    </form>
  </div></div>
  <div id="out" role="status" aria-live="polite"></div>
</main>
<script>
document.getElementById('f').addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn = document.getElementById('btn'), out = document.getElementById('out');
  btn.disabled = true; btn.textContent = 'Analisando... (pode levar até 1 minuto)';
  out.innerHTML = '';
  try {
    const r = await fetch('<?= htmlspecialchars($base) ?>/diagnostico', { method: 'POST', body: new FormData(e.target) });
    const j = await r.json().catch(() => ({ erro: 'Resposta inválida do servidor.' }));
    out.innerHTML = j.diagnostico
      ? '<div class="card shadow-sm"><div class="card-body">' + marked.parse(j.diagnostico) + '</div></div>'
      : '<div class="alert alert-danger" role="alert"></div>';
    if (!j.diagnostico) out.firstChild.textContent = j.erro || 'Erro ao analisar.';
  } catch (err) {
    out.innerHTML = '<div class="alert alert-danger" role="alert">Falha de conexão.</div>';
  } finally { btn.disabled = false; btn.textContent = 'Analisar'; }
});
</script>
</body></html>
