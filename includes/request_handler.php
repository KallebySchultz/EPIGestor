<?php
/**
 * Request Handler - Sistema de Retry para Requests Falhadas
 * Sistema de Gestão de EPIs Klarbyte
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Classe para gerenciar requests e retries
 */
class RequestHandler {
    
    /**
     * Gera um ID único para request
     * @return string
     */
    public static function generateRequestId() {
        // Gera um ID no formato similar ao do problema: 0818:AFC7F:5C03CF:877B93:68C4EC4F
        $segments = [];
        $segments[] = strtoupper(dechex(rand(0x0000, 0xFFFF)));
        $segments[] = strtoupper(dechex(rand(0x00000, 0xFFFFF)));
        $segments[] = strtoupper(dechex(rand(0x000000, 0xFFFFFF)));
        $segments[] = strtoupper(dechex(rand(0x000000, 0xFFFFFF)));
        $segments[] = strtoupper(dechex(rand(0x00000000, 0xFFFFFFFF)));
        
        return implode(':', $segments);
    }
    
    /**
     * Executa uma operação com retry automático
     * @param string $operation_type
     * @param array $data
     * @param callable $operation
     * @return array
     */
    public static function executeWithRetry($operation_type, $data, $operation) {
        $request_id = self::generateRequestId();
        
        try {
            // Tenta executar a operação
            $result = $operation($data);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'request_id' => $request_id,
                    'data' => $result['data'] ?? null,
                    'message' => $result['message'] ?? 'Operação executada com sucesso'
                ];
            } else {
                // Se falhou, salva para retry
                self::saveFailedRequest($request_id, $operation_type, $data, $result['error'] ?? 'Erro desconhecido');
                
                return [
                    'success' => false,
                    'request_id' => $request_id,
                    'error' => $result['error'] ?? 'Erro na operação',
                    'message' => 'Operação falhou e foi salva para retry. ID: ' . $request_id
                ];
            }
        } catch (Exception $e) {
            // Em caso de exceção, salva para retry
            self::saveFailedRequest($request_id, $operation_type, $data, $e->getMessage());
            
            return [
                'success' => false,
                'request_id' => $request_id,
                'error' => $e->getMessage(),
                'message' => 'Erro interno. Request salva para retry. ID: ' . $request_id
            ];
        }
    }
    
    /**
     * Salva uma request falhada no banco
     * @param string $request_id
     * @param string $operation_type
     * @param array $data
     * @param string $error_message
     */
    private static function saveFailedRequest($request_id, $operation_type, $data, $error_message) {
        try {
            $json_data = json_encode($data);
            executeUpdate(
                "INSERT INTO failed_requests (request_id, operation_type, request_data, error_message) VALUES (?, ?, ?, ?)",
                [$request_id, $operation_type, $json_data, $error_message]
            );
        } catch (Exception $e) {
            error_log("Erro ao salvar request falhada: " . $e->getMessage());
        }
    }
    
    /**
     * Retry de uma request específica
     * @param string $request_id
     * @return array
     */
    public static function retryRequest($request_id) {
        try {
            // Busca a request falhada
            $failed_requests = executeQuery(
                "SELECT * FROM failed_requests WHERE request_id = ? AND status IN ('pending', 'failed')",
                [$request_id]
            );
            
            if (empty($failed_requests)) {
                return [
                    'success' => false,
                    'error' => 'Request não encontrada ou já processada',
                    'message' => 'ID de request inválido: ' . $request_id
                ];
            }
            
            $failed_request = $failed_requests[0];
            
            // Verifica limite de retries
            if ($failed_request['retry_count'] >= $failed_request['max_retries']) {
                return [
                    'success' => false,
                    'error' => 'Limite de retries excedido',
                    'message' => 'Request atingiu o máximo de tentativas permitidas'
                ];
            }
            
            // Atualiza status para retrying
            executeUpdate(
                "UPDATE failed_requests SET status = 'retrying', retry_count = retry_count + 1, last_retry_at = CURRENT_TIMESTAMP WHERE request_id = ?",
                [$request_id]
            );
            
            // Decodifica os dados
            $data = json_decode($failed_request['request_data'], true);
            $operation_type = $failed_request['operation_type'];
            
            // Executa a operação baseada no tipo
            $result = self::executeOperation($operation_type, $data);
            
            if ($result['success']) {
                // Sucesso - marca como completa
                executeUpdate(
                    "UPDATE failed_requests SET status = 'success', completed_at = CURRENT_TIMESTAMP WHERE request_id = ?",
                    [$request_id]
                );
                
                return [
                    'success' => true,
                    'request_id' => $request_id,
                    'message' => 'Request executada com sucesso no retry',
                    'data' => $result['data'] ?? null
                ];
            } else {
                // Falhou novamente
                executeUpdate(
                    "UPDATE failed_requests SET status = 'failed', error_message = ? WHERE request_id = ?",
                    [$result['error'] ?? 'Erro no retry', $request_id]
                );
                
                return [
                    'success' => false,
                    'request_id' => $request_id,
                    'error' => $result['error'] ?? 'Erro no retry',
                    'message' => 'Retry falhou. Tentativa ' . ($failed_request['retry_count'] + 1) . ' de ' . $failed_request['max_retries']
                ];
            }
            
        } catch (Exception $e) {
            // Erro no retry
            executeUpdate(
                "UPDATE failed_requests SET status = 'failed', error_message = ? WHERE request_id = ?",
                ['Erro interno no retry: ' . $e->getMessage(), $request_id]
            );
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erro interno durante o retry'
            ];
        }
    }
    
    /**
     * Executa operação baseada no tipo
     * @param string $operation_type
     * @param array $data
     * @return array
     */
    private static function executeOperation($operation_type, $data) {
        switch ($operation_type) {
            case 'epi_create':
                return self::createEPI($data);
            case 'epi_update':
                return self::updateEPI($data);
            case 'epi_delete':
                return self::deleteEPI($data);
            case 'funcionario_create':
                return self::createFuncionario($data);
            case 'funcionario_update':
                return self::updateFuncionario($data);
            case 'funcionario_delete':
                return self::deleteFuncionario($data);
            case 'movimentacao_create':
                return self::createMovimentacao($data);
            case 'movimentacao_update':
                return self::updateMovimentacao($data);
            default:
                return ['success' => false, 'error' => 'Tipo de operação não suportado'];
        }
    }
    
    /**
     * Operação: Criar EPI
     */
    private static function createEPI($data) {
        try {
            $success = executeUpdate(
                "INSERT INTO epis (nome, descricao, categoria, numero_ca, fornecedor_id, quantidade_estoque, quantidade_minima, classificacao, validade, preco_unitario, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['nome'], $data['descricao'], $data['categoria'], $data['numero_ca'],
                    $data['fornecedor_id'], $data['quantidade_estoque'], $data['quantidade_minima'],
                    $data['classificacao'], $data['validade'], $data['preco_unitario'], $data['observacoes']
                ]
            );
            
            return $success ? 
                ['success' => true, 'message' => 'EPI criado com sucesso', 'data' => ['id' => getLastInsertId()]] :
                ['success' => false, 'error' => 'Falha ao inserir EPI no banco de dados'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao criar EPI: ' . $e->getMessage()];
        }
    }
    
    /**
     * Operação: Atualizar EPI
     */
    private static function updateEPI($data) {
        try {
            $success = executeUpdate(
                "UPDATE epis SET nome=?, descricao=?, categoria=?, numero_ca=?, fornecedor_id=?, quantidade_estoque=?, quantidade_minima=?, classificacao=?, validade=?, preco_unitario=?, observacoes=?, updated_at=CURRENT_TIMESTAMP WHERE id=?",
                [
                    $data['nome'], $data['descricao'], $data['categoria'], $data['numero_ca'],
                    $data['fornecedor_id'], $data['quantidade_estoque'], $data['quantidade_minima'],
                    $data['classificacao'], $data['validade'], $data['preco_unitario'], $data['observacoes'],
                    $data['id']
                ]
            );
            
            return $success ? 
                ['success' => true, 'message' => 'EPI atualizado com sucesso'] :
                ['success' => false, 'error' => 'Falha ao atualizar EPI no banco de dados'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao atualizar EPI: ' . $e->getMessage()];
        }
    }
    
    /**
     * Operação: Deletar EPI
     */
    private static function deleteEPI($data) {
        try {
            $success = executeUpdate(
                "UPDATE epis SET ativo = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$data['id']]
            );
            
            return $success ? 
                ['success' => true, 'message' => 'EPI excluído com sucesso'] :
                ['success' => false, 'error' => 'Falha ao excluir EPI'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao excluir EPI: ' . $e->getMessage()];
        }
    }
    
    /**
     * Operação: Criar Funcionário
     */
    private static function createFuncionario($data) {
        try {
            $success = executeUpdate(
                "INSERT INTO funcionarios (nome, cpf, empresa_id, cargo, setor, telefone, email) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['nome'], $data['cpf'], $data['empresa_id'],
                    $data['cargo'], $data['setor'], $data['telefone'], $data['email']
                ]
            );
            
            return $success ? 
                ['success' => true, 'message' => 'Funcionário criado com sucesso', 'data' => ['id' => getLastInsertId()]] :
                ['success' => false, 'error' => 'Falha ao inserir funcionário no banco de dados'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao criar funcionário: ' . $e->getMessage()];
        }
    }
    
    /**
     * Operação: Atualizar Funcionário
     */
    private static function updateFuncionario($data) {
        try {
            $success = executeUpdate(
                "UPDATE funcionarios SET nome=?, cpf=?, empresa_id=?, cargo=?, setor=?, telefone=?, email=?, updated_at=CURRENT_TIMESTAMP WHERE id=?",
                [
                    $data['nome'], $data['cpf'], $data['empresa_id'],
                    $data['cargo'], $data['setor'], $data['telefone'], $data['email'], $data['id']
                ]
            );
            
            return $success ? 
                ['success' => true, 'message' => 'Funcionário atualizado com sucesso'] :
                ['success' => false, 'error' => 'Falha ao atualizar funcionário no banco de dados'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao atualizar funcionário: ' . $e->getMessage()];
        }
    }
    
    /**
     * Operação: Deletar Funcionário
     */
    private static function deleteFuncionario($data) {
        try {
            $success = executeUpdate(
                "UPDATE funcionarios SET ativo = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$data['id']]
            );
            
            return $success ? 
                ['success' => true, 'message' => 'Funcionário desativado com sucesso'] :
                ['success' => false, 'error' => 'Falha ao desativar funcionário'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao desativar funcionário: ' . $e->getMessage()];
        }
    }
    
    /**
     * Operação: Criar Movimentação
     */
    private static function createMovimentacao($data) {
        try {
            $success = executeUpdate(
                "INSERT INTO movimentacoes (epi_id, funcionario_id, tipo_movimentacao, quantidade, observacoes, usuario_responsavel, saldo_anterior, saldo_atual) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['epi_id'], $data['funcionario_id'], $data['tipo_movimentacao'],
                    $data['quantidade'], $data['observacoes'], $data['usuario_responsavel'],
                    $data['saldo_anterior'], $data['saldo_atual']
                ]
            );
            
            return $success ? 
                ['success' => true, 'message' => 'Movimentação criada com sucesso', 'data' => ['id' => getLastInsertId()]] :
                ['success' => false, 'error' => 'Falha ao inserir movimentação no banco de dados'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao criar movimentação: ' . $e->getMessage()];
        }
    }
    
    /**
     * Operação: Atualizar Movimentação
     */
    private static function updateMovimentacao($data) {
        try {
            $success = executeUpdate(
                "UPDATE movimentacoes SET epi_id=?, funcionario_id=?, tipo_movimentacao=?, quantidade=?, observacoes=?, usuario_responsavel=?, saldo_anterior=?, saldo_atual=? WHERE id=?",
                [
                    $data['epi_id'], $data['funcionario_id'], $data['tipo_movimentacao'],
                    $data['quantidade'], $data['observacoes'], $data['usuario_responsavel'],
                    $data['saldo_anterior'], $data['saldo_atual'], $data['id']
                ]
            );
            
            return $success ? 
                ['success' => true, 'message' => 'Movimentação atualizada com sucesso'] :
                ['success' => false, 'error' => 'Falha ao atualizar movimentação no banco de dados'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao atualizar movimentação: ' . $e->getMessage()];
        }
    }
    
    /**
     * Lista requests falhadas
     * @param int $limit
     * @return array
     */
    public static function getFailedRequests($limit = 50) {
        return executeQuery(
            "SELECT * FROM failed_requests ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * Busca request específica
     * @param string $request_id
     * @return array|null
     */
    public static function getRequestById($request_id) {
        $requests = executeQuery(
            "SELECT * FROM failed_requests WHERE request_id = ?",
            [$request_id]
        );
        
        return !empty($requests) ? $requests[0] : null;
    }
    
    /**
     * Limpa requests antigas e completas
     * @param int $days_old
     */
    public static function cleanupOldRequests($days_old = 30) {
        executeUpdate(
            "DELETE FROM failed_requests WHERE status = 'success' AND completed_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days_old]
        );
    }
}
?>