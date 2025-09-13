-- Banco de Dados para Sistema de Gestão de EPIs Klarbyte
-- MySQL Schema

CREATE DATABASE IF NOT EXISTS klarbyte_epi;
USE klarbyte_epi;

-- Tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE
);

-- Tabela de EPIs
CREATE TABLE epis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    validade DATE,
    quantidade_minima INT DEFAULT 0,
    saldo_estoque INT DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE
);

-- Tabela de movimentações de estoque
CREATE TABLE movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    epi_id INT NOT NULL,
    tipo_movimentacao ENUM('entrada', 'retirada', 'devolucao', 'descarte') NOT NULL,
    quantidade INT NOT NULL,
    responsavel VARCHAR(100),
    empresa VARCHAR(100),
    observacoes TEXT,
    data_movimentacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT NOT NULL,
    FOREIGN KEY (epi_id) REFERENCES epis(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Inserir usuário administrador padrão
INSERT INTO usuarios (nome, email, senha) VALUES 
('Administrador', 'admin@klarbyte.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- Senha padrão: password (deve ser alterada no primeiro login)

-- Índices para melhor performance
CREATE INDEX idx_epi_nome ON epis(nome);
CREATE INDEX idx_movimentacao_epi ON movimentacoes(epi_id);
CREATE INDEX idx_movimentacao_data ON movimentacoes(data_movimentacao);
CREATE INDEX idx_movimentacao_tipo ON movimentacoes(tipo_movimentacao);