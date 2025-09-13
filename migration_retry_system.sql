-- Migration Script para adicionar sistema de retry
-- Execute este script se a tabela failed_requests ainda não existir

-- Criar tabela failed_requests se não existir
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

-- Adicionar índices se não existirem
CREATE INDEX IF NOT EXISTS idx_failed_requests_status ON failed_requests(status);
CREATE INDEX IF NOT EXISTS idx_failed_requests_created ON failed_requests(created_at);

-- Inserir request de exemplo para teste (opcional)
INSERT IGNORE INTO failed_requests (request_id, operation_type, request_data, error_message, status) VALUES 
('0818:AFC7F:5C03CF:877B93:68C4EC4F', 'epi_create', 
 '{"nome":"EPI Teste","descricao":"EPI para teste do sistema","categoria":"Proteção","numero_ca":"12345","fornecedor_id":null,"quantidade_estoque":10,"quantidade_minima":5,"classificacao":"novo","validade":"2025-12-31","preco_unitario":25.50,"observacoes":"Request de exemplo do problema statement"}',
 'Exemplo de request falhada para demonstração do sistema de retry',
 'pending');

-- Verificar se a tabela foi criada corretamente
SELECT 'Tabela failed_requests criada com sucesso!' as status;