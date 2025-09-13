<?php
/**
 * Conexão Simplificada com Banco de Dados
 * Sistema de Gestão de EPIs Klarbyte - Versão Simplificada
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'klarbyte_epi_simple');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Função para obter conexão com banco
 * @return PDO|null
 */
function getConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch(PDOException $e) {
            error_log("Erro de conexão: " . $e->getMessage());
            die("Erro na conexão com o banco de dados. Verifique as configurações.");
        }
    }
    
    return $pdo;
}

/**
 * Executa uma consulta SELECT e retorna os resultados
 * @param string $query
 * @param array $params
 * @return array
 */
function executeQuery($query, $params = []) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Erro na consulta: " . $e->getMessage());
        return [];
    }
}

/**
 * Executa uma consulta INSERT, UPDATE ou DELETE
 * @param string $query
 * @param array $params
 * @return bool
 */
function executeUpdate($query, $params = []) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare($query);
        return $stmt->execute($params);
    } catch(PDOException $e) {
        error_log("Erro na execução: " . $e->getMessage());
        return false;
    }
}

/**
 * Retorna o último ID inserido
 * @return int
 */
function getLastInsertId() {
    try {
        $pdo = getConnection();
        return (int) $pdo->lastInsertId();
    } catch(PDOException $e) {
        error_log("Erro ao obter último ID: " . $e->getMessage());
        return 0;
    }
}

/**
 * Inicia uma transação
 */
function beginTransaction() {
    try {
        $pdo = getConnection();
        return $pdo->beginTransaction();
    } catch(PDOException $e) {
        error_log("Erro ao iniciar transação: " . $e->getMessage());
        return false;
    }
}

/**
 * Confirma uma transação
 */
function commit() {
    try {
        $pdo = getConnection();
        return $pdo->commit();
    } catch(PDOException $e) {
        error_log("Erro ao confirmar transação: " . $e->getMessage());
        return false;
    }
}

/**
 * Cancela uma transação
 */
function rollback() {
    try {
        $pdo = getConnection();
        return $pdo->rollback();
    } catch(PDOException $e) {
        error_log("Erro ao cancelar transação: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se a conexão está funcionando
 * @return bool
 */
function testConnection() {
    try {
        $pdo = getConnection();
        $result = $pdo->query("SELECT 1");
        return $result !== false;
    } catch(Exception $e) {
        return false;
    }
}
?>