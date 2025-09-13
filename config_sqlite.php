<?php
// Configuração de conexão com o banco de dados SQLite para teste
class Database {
    private $db_file = 'klarbyte_epi.db';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("sqlite:" . $this->db_file);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Criar tabelas se não existirem
            $this->createTables();
            
        } catch(PDOException $exception) {
            echo "Erro de conexão: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
    
    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(100) NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ativo BOOLEAN DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS epis (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(100) NOT NULL,
            descricao TEXT,
            valor_unitario DECIMAL(10,2) DEFAULT 0.00,
            quantidade_minima INTEGER DEFAULT 0,
            saldo_estoque INTEGER DEFAULT 0,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ativo BOOLEAN DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS movimentacoes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            epi_id INTEGER NOT NULL,
            tipo_movimentacao TEXT NOT NULL CHECK(tipo_movimentacao IN ('entrada', 'retirada', 'devolucao', 'descarte')),
            quantidade INTEGER NOT NULL,
            responsavel VARCHAR(100),
            empresa VARCHAR(100),
            observacoes TEXT,
            data_movimentacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            usuario_id INTEGER NOT NULL,
            FOREIGN KEY (epi_id) REFERENCES epis(id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        );
        ";
        
        $this->conn->exec($sql);
        
        // Inserir usuário padrão se não existir
        $query = "SELECT COUNT(*) FROM usuarios WHERE username = 'Kalleby Schultz'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $query = "INSERT INTO usuarios (nome, username, senha) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['Kalleby Schultz', 'Kalleby Schultz', '$2y$10$LrM.AVy5jfD47yWM0gHhM.kn1uBHOI4ptA9owLVOLSdro76LIOhpC']);
        }
    }
}

// Funções auxiliares para o sistema
function verificarSessao() {
    session_start();
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit();
    }
}

function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}

function formatarDataHora($data) {
    return date('d/m/Y H:i', strtotime($data));
}

function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function estoqueMinimo($saldo, $minimo) {
    return $saldo <= $minimo;
}

function sanitizar($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}
?>