<?php
/**
 * Arquivo de conexão com o banco de dados MySQL
 * Sistema de Gestão de EPIs Klarbyte
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'klarbyte_epi');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Classe para gerenciar conexão com banco de dados
 */
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    private $pdo;

    /**
     * Conecta ao banco de dados
     * @return PDO|null
     */
    public function connect() {
        $this->pdo = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            echo "Erro de conexão: " . $e->getMessage();
        }
        
        return $this->pdo;
    }
}

/**
 * Função auxiliar para obter conexão com banco
 * @return PDO|null
 */
function getConnection() {
    $database = new Database();
    return $database->connect();
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
        return $pdo->lastInsertId();
    } catch(PDOException $e) {
        error_log("Erro ao obter último ID: " . $e->getMessage());
        return 0;
    }
}

/**
 * Verifica se a conexão está funcionando
 * @return bool
 */
function testConnection() {
    try {
        $pdo = getConnection();
        return $pdo !== null;
    } catch(Exception $e) {
        return false;
    }
}
?>