-- Migração: recuperação de acesso por e-mail
-- Rode UMA vez em bancos que já existiam antes desta versão (banco novo já vem com a coluna).
-- Se aparecer "Duplicate column name 'email'", a coluna já existe: pode ignorar.
USE sistema;

ALTER TABLE usuario
    ADD COLUMN email VARCHAR(150) DEFAULT NULL AFTER nome,
    ADD UNIQUE KEY uq_usuario_email (email);

-- Troque pelo e-mail real do administrador:
UPDATE usuario SET email = 'seu-email@exemplo.com' WHERE usuario = 'Admin';
