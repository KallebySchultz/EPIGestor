<?php
/**
 * Teste de Geração de IDs - Validar formato similar ao problema
 */

require_once '../includes/request_handler.php';

echo "<h1>Teste de Geração de Request IDs</h1>";
echo "<p>Problema statement: <code>Tente de novo a request: 0818:AFC7F:5C03CF:877B93:68C4EC4F</code></p>";

echo "<h2>IDs gerados pelo sistema:</h2>";
for ($i = 1; $i <= 10; $i++) {
    $id = RequestHandler::generateRequestId();
    echo "<p>{$i}. <code>{$id}</code></p>";
}

echo "<h2>Validação do formato:</h2>";
$test_id = RequestHandler::generateRequestId();
echo "<p>ID gerado: <code>{$test_id}</code></p>";

// Validar formato
$parts = explode(':', $test_id);
echo "<p>Número de segmentos: " . count($parts) . " (esperado: 5)</p>";

foreach ($parts as $i => $part) {
    echo "<p>Segmento " . ($i + 1) . ": <code>{$part}</code> (tamanho: " . strlen($part) . " caracteres)</p>";
}

// Verificar se é hexadecimal válido
$all_hex = true;
foreach ($parts as $part) {
    if (!ctype_xdigit($part)) {
        $all_hex = false;
        break;
    }
}

echo "<p>Formato hexadecimal válido: " . ($all_hex ? "✅ SIM" : "❌ NÃO") . "</p>";

echo "<h2>Comparação com ID do problema:</h2>";
$problem_id = '0818:AFC7F:5C03CF:877B93:68C4EC4F';
$problem_parts = explode(':', $problem_id);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Segmento</th><th>Problema</th><th>Sistema</th><th>Tamanho Problema</th><th>Tamanho Sistema</th></tr>";

for ($i = 0; $i < 5; $i++) {
    $problem_segment = $problem_parts[$i] ?? 'N/A';
    $system_segment = $parts[$i] ?? 'N/A';
    
    echo "<tr>";
    echo "<td>" . ($i + 1) . "</td>";
    echo "<td><code>{$problem_segment}</code></td>";
    echo "<td><code>{$system_segment}</code></td>";
    echo "<td>" . strlen($problem_segment) . "</td>";
    echo "<td>" . strlen($system_segment) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Teste de Funcionalidade:</h2>";
echo "<p>Testando se o sistema aceita o ID do problema...</p>";

// Testar se nosso sistema pode processar o ID do problema
$pattern = '/^[0-9A-F]+:[0-9A-F]+:[0-9A-F]+:[0-9A-F]+:[0-9A-F]+$/i';
$matches_pattern = preg_match($pattern, $problem_id);

echo "<p>ID do problema segue o padrão: " . ($matches_pattern ? "✅ SIM" : "❌ NÃO") . "</p>";
echo "<p>Padrão usado: <code>{$pattern}</code></p>";

echo "<br><a href='test_retry.php'>👉 Testar retry com ID específico</a>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
code { background-color: #f8f9fa; padding: 2px 4px; border-radius: 2px; }
</style>