-- Banco de Dados Simplificado para Sistema de Gestão de EPIs Klarbyte
-- Sistema muito mais simples, sem complexidades de usuários múltiplos
-- Tudo é administrado pelo administrador

CREATE DATABASE IF NOT EXISTS klarbyte_epi_simple CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE klarbyte_epi_simple;

-- Tabela simples de login (apenas administradores)
CREATE TABLE IF NOT EXISTS admin_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela simplificada de EPIs (lista editável estilo planilha)
CREATE TABLE IF NOT EXISTS epis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    categoria VARCHAR(100),
    quantidade_total INT DEFAULT 0,
    quantidade_disponivel INT DEFAULT 0,
    validade DATE,
    observacoes TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela simplificada de funcionários (lista editável estilo planilha)
CREATE TABLE IF NOT EXISTS funcionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    setor VARCHAR(100),
    cargo VARCHAR(100),
    telefone VARCHAR(20),
    email VARCHAR(100),
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela simplificada de movimentações (histórico simples)
CREATE TABLE IF NOT EXISTS movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    epi_id INT NOT NULL,
    funcionario_id INT,
    tipo ENUM('retirada', 'devolucao', 'ajuste') NOT NULL,
    quantidade INT NOT NULL,
    data_movimentacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    observacoes TEXT,
    FOREIGN KEY (epi_id) REFERENCES epis(id) ON DELETE CASCADE,
    FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Inserir admin padrão (senha: admin123)
INSERT INTO admin_login (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Dados exemplo para teste
INSERT INTO epis (nome, descricao, categoria, quantidade_total, quantidade_disponivel, validade) VALUES 
('Capacete de Segurança', 'Capacete branco com jugular', 'Proteção da Cabeça', 50, 45, '2025-12-31'),
('Luva de Segurança', 'Luva de látex tamanho M', 'Proteção das Mãos', 100, 80, '2024-06-30'),
('Óculos de Proteção', 'Óculos transparente anti-embaçante', 'Proteção dos Olhos', 30, 25, '2026-01-15'),
('Botina de Segurança', 'Botina de couro com bico de aço', 'Proteção dos Pés', 40, 35, '2025-08-20');

INSERT INTO funcionarios (nome, setor, cargo, telefone, email) VALUES 
('João Silva', 'Produção', 'Operador', '(11) 99999-1111', 'joao@empresa.com'),
('Maria Santos', 'Manutenção', 'Técnica', '(11) 99999-2222', 'maria@empresa.com'),
('Carlos Oliveira', 'Almoxarifado', 'Auxiliar', '(11) 99999-3333', 'carlos@empresa.com'),
('Ana Costa', 'Produção', 'Supervisora', '(11) 99999-4444', 'ana@empresa.com');

-- Índices básicos para performance
CREATE INDEX idx_epis_ativo ON epis(ativo);
CREATE INDEX idx_funcionarios_ativo ON funcionarios(ativo);
CREATE INDEX idx_movimentacoes_data ON movimentacoes(data_movimentacao);