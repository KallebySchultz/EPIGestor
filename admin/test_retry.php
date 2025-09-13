<?php
/**
 * Test Script - Simular Request com ID específico do problema
 * Teste para "Tente de novo a request: 0818:AFC7F:5C03CF:877B93:68C4EC4F"
 */

require_once '../config/database.php';
require_once '../includes/request_handler.php';

// ID da request mencionada no problema
$specific_request_id = '0818:AFC7F:5C03CF:877B93:68C4EC4F';

echo "<h1>Teste do Sistema de Retry</h1>";
echo "<h2>Request ID: {$specific_request_id}</h2>";

// Verificar se a request já existe
$existing_request = RequestHandler::getRequestById($specific_request_id);

if ($existing_request) {
    echo "<h3>✅ Request encontrada no sistema:</h3>";
    echo "<pre>";
    print_r($existing_request);
    echo "</pre>";
    
    // Tentar retry da request
    echo "<h3>🔄 Tentando retry da request...</h3>";
    $result = RequestHandler::retryRequest($specific_request_id);
    
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
} else {
    echo "<h3>❌ Request não encontrada. Criando uma simulada...</h3>";
    
    // Criar uma request falhada simulada com o ID específico
    $test_data = [
        'nome' => 'EPI Teste Retry',
        'descricao' => 'EPI criado para testar o sistema de retry',
        'categoria' => 'Proteção',
        'numero_ca' => '12345',
        'fornecedor_id' => null,
        'quantidade_estoque' => 10,
        'quantidade_minima' => 5,
        'classificacao' => 'novo',
        'validade' => '2025-12-31',
        'preco_unitario' => 25.50,
        'observacoes' => 'Teste do sistema de retry'
    ];
    
    // Inserir request falhada simulada diretamente no banco
    $json_data = json_encode($test_data);
    $success = executeUpdate(
        "INSERT INTO failed_requests (request_id, operation_type, request_data, error_message, status) VALUES (?, ?, ?, ?, ?)",
        [$specific_request_id, 'epi_create', $json_data, 'Simulação de falha para teste', 'pending']
    );
    
    if ($success) {
        echo "✅ Request simulada criada com sucesso!<br>";
        echo "<strong>Agora você pode tentar o retry usando:</strong><br>";
        echo "<code>RequestHandler::retryRequest('{$specific_request_id}')</code><br><br>";
        
        // Tentar o retry automaticamente
        echo "<h3>🔄 Executando retry automaticamente...</h3>";
        $result = RequestHandler::retryRequest($specific_request_id);
        
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        
        if ($result['success']) {
            echo "<div style='color: green; font-weight: bold;'>✅ SUCESSO! A request foi executada com sucesso no retry.</div>";
        } else {
            echo "<div style='color: red; font-weight: bold;'>❌ FALHOU: {$result['message']}</div>";
        }
        
    } else {
        echo "❌ Erro ao criar request simulada.";
    }
}

// Mostrar status atual da request
echo "<h3>📊 Status atual da request:</h3>";
$current_status = RequestHandler::getRequestById($specific_request_id);
if ($current_status) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    foreach ($current_status as $key => $value) {
        echo "<tr><td>{$key}</td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "Request não encontrada no sistema.";
}

echo "<br><br><a href='../admin/retry.php'>👉 Ir para interface de retry</a>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
pre { background-color: #f8f9fa; padding: 10px; border-radius: 4px; }
</style>