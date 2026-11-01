-- Marco zero ("Em produção") e finalização do pedido.
-- O sistema também cria estas colunas sozinho se faltarem; rodar este arquivo é opcional.
ALTER TABLE pedido ADD COLUMN data_inicio_producao DATE DEFAULT NULL;
ALTER TABLE pedido ADD COLUMN data_finalizacao DATE DEFAULT NULL;

-- Pedidos que já têm "Em produção" / "Finalizado" no histórico passam a ter o marco zero preenchido.
UPDATE pedido p
   SET data_inicio_producao = (
        SELECT DATE(MIN(ps.data)) FROM pedido_status ps
          JOIN status s ON s.id_status = ps.id_status
         WHERE ps.id_pedido = p.id_pedido AND LOWER(TRIM(s.descricao)) IN ('em produção', 'em producao')),
       data_finalizacao = (
        SELECT DATE(MIN(ps.data)) FROM pedido_status ps
          JOIN status s ON s.id_status = ps.id_status
         WHERE ps.id_pedido = p.id_pedido AND LOWER(TRIM(s.descricao)) = 'finalizado');
UPDATE pedido
   SET data_prevista_entrega = DATE_ADD(data_inicio_producao, INTERVAL IF(tipo_bloco = 'EPS', 25, 10) DAY)
 WHERE data_inicio_producao IS NOT NULL;
