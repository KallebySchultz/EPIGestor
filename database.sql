-- Banco de Dados para Sistema de Gestão de EPIs Klarbyte
-- Este script cria todas as tabelas necessárias para o sistema

CREATE DATABASE IF NOT EXISTS klarbyte_epi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE klarbyte_epi;

-- Tabela de empresas
CREATE TABLE IF NOT EXISTS empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cnpj VARCHAR(18) UNIQUE,
    endereco TEXT,
    telefone VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela de fornecedores
CREATE TABLE IF NOT EXISTS fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cnpj VARCHAR(18),
    endereco TEXT,
    telefone VARCHAR(20),
    email VARCHAR(100),
    contato VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela de usuários do sistema
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'usuario') DEFAULT 'usuario',
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela de funcionários
CREATE TABLE IF NOT EXISTS funcionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    empresa_id INT,
    cargo VARCHAR(100),
    setor VARCHAR(100),
    telefone VARCHAR(20),
    email VARCHAR(100),
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela de EPIs
CREATE TABLE IF NOT EXISTS epis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    categoria VARCHAR(100),
    numero_ca VARCHAR(20), -- Certificado de Aprovação
    fornecedor_id INT,
    quantidade_estoque INT DEFAULT 0,
    quantidade_minima INT DEFAULT 10,
    classificacao ENUM('novo', 'usado') DEFAULT 'novo',
    validade DATE,
    preco_unitario DECIMAL(10,2),
    observacoes TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabela de movimentações de EPIs
CREATE TABLE IF NOT EXISTS movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    epi_id INT NOT NULL,
    funcionario_id INT,
    tipo_movimentacao ENUM('entrada', 'retirada', 'devolucao', 'descarte') NOT NULL,
    quantidade INT NOT NULL,
    data_movimentacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observacoes TEXT,
    usuario_responsavel VARCHAR(50),
    saldo_anterior INT,
    saldo_atual INT,
    FOREIGN KEY (epi_id) REFERENCES epis(id) ON DELETE CASCADE,
    FOREIGN KEY (funcionario_id) REFERENCES funcionarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Inserir dados iniciais
-- Usuario administrador padrão (senha: admin123)
INSERT INTO usuarios (username, password, tipo) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Empresa exemplo
INSERT INTO empresas (nome, cnpj) VALUES 
('Klarbyte Sistemas', '12.345.678/0001-90');

-- Fornecedor exemplo
INSERT INTO fornecedores (nome, cnpj, contato) VALUES 
('EPIs & Segurança Ltda', '98.765.432/0001-10', 'João Silva');

-- Tabela de requests falhadas para retry
CREATE TABLE IF NOT EXISTS failed_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(50) UNIQUE NOT NULL,
    operation_type ENUM('epi_create', 'epi_update', 'epi_delete', 'funcionario_create', 'funcionario_update', 'funcionario_delete', 'movimentacao_create', 'movimentacao_update') NOT NULL,
    request_data JSON NOT NULL,
    error_message TEXT,
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    status ENUM('pending', 'retrying', 'success', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_retry_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL
) ENGINE=InnoDB;

-- Índices para otimização
CREATE INDEX idx_funcionarios_empresa ON funcionarios(empresa_id);
CREATE INDEX idx_funcionarios_ativo ON funcionarios(ativo);
CREATE INDEX idx_epis_fornecedor ON epis(fornecedor_id);
CREATE INDEX idx_epis_ativo ON epis(ativo);
CREATE INDEX idx_epis_quantidade ON epis(quantidade_estoque, quantidade_minima);
CREATE INDEX idx_movimentacoes_epi ON movimentacoes(epi_id);
CREATE INDEX idx_movimentacoes_funcionario ON movimentacoes(funcionario_id);
CREATE INDEX idx_movimentacoes_data ON movimentacoes(data_movimentacao);
CREATE INDEX idx_failed_requests_status ON failed_requests(status);
CREATE INDEX idx_failed_requests_created ON failed_requests(created_at);