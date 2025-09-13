-- Banco de Dados para Sistema de Gestão de EPIs Klarbyte
-- MySQL Schema

CREATE DATABASE IF NOT EXISTS klarbyte_epi;
USE klarbyte_epi;

-- Tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE
);

-- Tabela de EPIs
CREATE TABLE epis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    valor_unitario DECIMAL(10,2) DEFAULT 0.00,
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
INSERT INTO usuarios (nome, username, senha) VALUES 
('Kalleby Schultz', 'Kalleby Schultz', 'admin123');
-- Senha padrão: admin123 (sem hash para desenvolvimento local)

-- Índices para melhor performance
CREATE INDEX idx_epi_nome ON epis(nome);
CREATE INDEX idx_movimentacao_epi ON movimentacoes(epi_id);
CREATE INDEX idx_movimentacao_data ON movimentacoes(data_movimentacao);
CREATE INDEX idx_movimentacao_tipo ON movimentacoes(tipo_movimentacao);