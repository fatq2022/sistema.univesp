CREATE DATABASE IF NOT EXISTS sistema;
USE sistema;

-- 1. Tabela de Usuários (com campos para login e recuperação segura por token)
CREATE TABLE IF NOT EXISTS usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) DEFAULT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    token_expires_at DATETIME DEFAULT NULL
);

-- Usuário inicial: Admin / senha 123456
-- IMPORTANTE: para usar "Esqueci minha senha", cadastre o e-mail do Admin depois de importar:
--   UPDATE usuario SET email = 'seu-email@exemplo.com' WHERE usuario = 'Admin';
INSERT IGNORE INTO usuario (usuario, nome, senha) VALUES ('Admin', 'Administrador', '$2b$10$.MLU/I32JpovDjU0pCYZ9esEOLmkLD6roT9IAiqkpVDAIbG79bmSS');

-- 2. Tabela de Shapers (Clientes/Parceiros da oficina)
CREATE TABLE IF NOT EXISTS shaper (
    id_shaper INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefone VARCHAR(20),
    ativo TINYINT(1) NOT NULL DEFAULT 1
);

-- 3. Tabelas de Especificações e Componentes da Prancha
CREATE TABLE IF NOT EXISTS acabamento (
    id_acabamento INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(150) NOT NULL
);

CREATE TABLE IF NOT EXISTS composicao (
    id_composicao INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(150) NOT NULL,
    composicaocol VARCHAR(45)
);

CREATE TABLE IF NOT EXISTS variacao (
    id_variacao INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(150) NOT NULL
);

CREATE TABLE IF NOT EXISTS cor (
    id_cor INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(45) NOT NULL
);

CREATE TABLE IF NOT EXISTS sistemaquilha (
    id_sistemaquilha INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(150) NOT NULL
);

CREATE TABLE IF NOT EXISTS configuracaoquilha (
    id_configuracaoquilha INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(150) NOT NULL
);

CREATE TABLE IF NOT EXISTS tecido (
    id_tecido INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(150) NOT NULL
);

-- 4. Tabela de Status possíveis
CREATE TABLE IF NOT EXISTS status (
    id_status INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(45) NOT NULL,
    ordem INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1
);

-- 5. Tabela Principal de Pedidos
-- Inclui o campo 'tipo_bloco' para aplicar a regra (PU = 10 dias / EPS = 25 dias, contados do marco zero "Em produção")
CREATE TABLE IF NOT EXISTS pedido (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    data DATE NOT NULL,
    tipo_bloco ENUM('PU', 'EPS') NOT NULL DEFAULT 'PU', 
    data_prevista_entrega DATE DEFAULT NULL,
    data_inicio_producao DATE DEFAULT NULL,   -- marco zero: dia em que entrou em "Em produção"
    data_finalizacao DATE DEFAULT NULL,       -- dia em que entrou em "Finalizado" (encerra os alertas)
    observacao VARCHAR(800),
    id_shaper INT,
    id_composicao INT,
    id_variacao INT,
    id_acabamento INT,
    id_configuracaoquilha INT,
    id_sistemaquilha INT,
    FOREIGN KEY (id_shaper) REFERENCES shaper(id_shaper),
    FOREIGN KEY (id_composicao) REFERENCES composicao(id_composicao),
    FOREIGN KEY (id_variacao) REFERENCES variacao(id_variacao),
    FOREIGN KEY (id_acabamento) REFERENCES acabamento(id_acabamento),
    FOREIGN KEY (id_configuracaoquilha) REFERENCES configuracaoquilha(id_configuracaoquilha),
    FOREIGN KEY (id_sistemaquilha) REFERENCES sistemaquilha(id_sistemaquilha)
);

-- 6. Tabelas Relacionais e de Acompanhamento
CREATE TABLE IF NOT EXISTS pedido_status (
    id_pedido_status INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT,
    id_status INT,
    data DATETIME NOT NULL,
    id_usuario INT,
    observacao VARCHAR(255),
    FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido),
    FOREIGN KEY (id_status) REFERENCES status(id_status),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE IF NOT EXISTS pedido_cor (
    id_pedido_cor INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT,
    id_cor INT,
    FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido),
    FOREIGN KEY (id_cor) REFERENCES cor(id_cor)
);

CREATE TABLE IF NOT EXISTS pedido_tecido (
    id_tecido_cor INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT,
    id_tecido INT,
    FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido),
    FOREIGN KEY (id_tecido) REFERENCES tecido(id_tecido)
);