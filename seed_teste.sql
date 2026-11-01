-- Dados de TESTE (seguro rodar mais de uma vez: não duplica).
-- Execute DEPOIS do banco.sql:  mysql -u root sistema < seed_teste.sql
USE sistema;

INSERT INTO acabamento (descricao) SELECT 'Polido' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM acabamento WHERE descricao = 'Polido');
INSERT INTO acabamento (descricao) SELECT 'Fosco' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM acabamento WHERE descricao = 'Fosco');
INSERT INTO acabamento (descricao) SELECT 'Brilho com lixa' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM acabamento WHERE descricao = 'Brilho com lixa');

INSERT INTO composicao (descricao) SELECT 'Epóxi' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM composicao WHERE descricao = 'Epóxi');
INSERT INTO composicao (descricao) SELECT 'Poliéster' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM composicao WHERE descricao = 'Poliéster');
INSERT INTO composicao (descricao) SELECT 'Híbrida' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM composicao WHERE descricao = 'Híbrida');

INSERT INTO variacao (descricao) SELECT 'Padrão' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM variacao WHERE descricao = 'Padrão');
INSERT INTO variacao (descricao) SELECT 'Reforçada' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM variacao WHERE descricao = 'Reforçada');
INSERT INTO variacao (descricao) SELECT 'Leve' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM variacao WHERE descricao = 'Leve');

INSERT INTO cor (descricao) SELECT 'Branco' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cor WHERE descricao = 'Branco');
INSERT INTO cor (descricao) SELECT 'Preto' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cor WHERE descricao = 'Preto');
INSERT INTO cor (descricao) SELECT 'Azul' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cor WHERE descricao = 'Azul');
INSERT INTO cor (descricao) SELECT 'Verde' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cor WHERE descricao = 'Verde');

INSERT INTO sistemaquilha (descricao) SELECT 'FCS II' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sistemaquilha WHERE descricao = 'FCS II');
INSERT INTO sistemaquilha (descricao) SELECT 'Futures' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sistemaquilha WHERE descricao = 'Futures');
INSERT INTO sistemaquilha (descricao) SELECT 'Quilha fixa' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM sistemaquilha WHERE descricao = 'Quilha fixa');

INSERT INTO configuracaoquilha (descricao) SELECT 'Thruster' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM configuracaoquilha WHERE descricao = 'Thruster');
INSERT INTO configuracaoquilha (descricao) SELECT 'Quad' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM configuracaoquilha WHERE descricao = 'Quad');
INSERT INTO configuracaoquilha (descricao) SELECT 'Twin' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM configuracaoquilha WHERE descricao = 'Twin');
INSERT INTO configuracaoquilha (descricao) SELECT 'Single' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM configuracaoquilha WHERE descricao = 'Single');

INSERT INTO tecido (descricao) SELECT 'Fibra de vidro 4oz' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tecido WHERE descricao = 'Fibra de vidro 4oz');
INSERT INTO tecido (descricao) SELECT 'Fibra de vidro 6oz' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tecido WHERE descricao = 'Fibra de vidro 6oz');
INSERT INTO tecido (descricao) SELECT 'Carbono' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tecido WHERE descricao = 'Carbono');

INSERT INTO status (descricao, ordem, ativo) SELECT 'Aguardando', 1, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM status WHERE descricao = 'Aguardando');
INSERT INTO status (descricao, ordem, ativo) SELECT 'Em produção', 2, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM status WHERE descricao = 'Em produção');
INSERT INTO status (descricao, ordem, ativo) SELECT 'Secagem', 3, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM status WHERE descricao = 'Secagem');
INSERT INTO status (descricao, ordem, ativo) SELECT 'Acabamento', 4, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM status WHERE descricao = 'Acabamento');
INSERT INTO status (descricao, ordem, ativo) SELECT 'Pronto', 5, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM status WHERE descricao = 'Pronto');
INSERT INTO status (descricao, ordem, ativo) SELECT 'Finalizado', 6, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM status WHERE descricao = 'Finalizado');

INSERT INTO shaper (nome, email, telefone, ativo) SELECT 'Shaper Teste', 'shaper.teste@example.com', '(11) 99999-0000', 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM shaper WHERE nome = 'Shaper Teste');
