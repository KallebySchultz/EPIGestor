<?php
/**
 * Retry de Requests - Sistema de Gestão de EPIs Klarbyte
 * Página para visualizar e tentar novamente requests falhadas
 */

// Configurações da página
$page_title = "Retry de Requests";
$panel_type = "Painel Administrativo";
$is_admin = true;
$user_name = "Administrador";

// Incluir dependências
require_once '../config/database.php';
require_once '../includes/request_handler.php';

// Processar ações de retry
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'retry_request') {
        $request_id = $_POST['request_id'] ?? '';
        
        if (empty($request_id)) {
            $message = 'ID da request é obrigatório.';
            $message_type = 'danger';
        } else {
            $result = RequestHandler::retryRequest($request_id);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'danger';
        }
    } elseif ($action === 'retry_by_id') {
        $manual_request_id = trim($_POST['manual_request_id'] ?? '');
        
        if (empty($manual_request_id)) {
            $message = 'Por favor, informe o ID da request para retry.';
            $message_type = 'danger';
        } else {
            $result = RequestHandler::retryRequest($manual_request_id);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'danger';
        }
    } elseif ($action === 'cleanup') {
        RequestHandler::cleanupOldRequests();
        $message = 'Requests antigas limpas com sucesso.';
        $message_type = 'success';
    }
}

// Buscar requests falhadas
try {
    $failed_requests = RequestHandler::getFailedRequests();
    $pending_count = count(array_filter($failed_requests, function($r) { return $r['status'] === 'pending'; }));
    $failed_count = count(array_filter($failed_requests, function($r) { return $r['status'] === 'failed'; }));
    $success_count = count(array_filter($failed_requests, function($r) { return $r['status'] === 'success'; }));
} catch (Exception $e) {
    $failed_requests = [];
    $pending_count = $failed_count = $success_count = 0;
    if (empty($message)) {
        $message = 'Erro ao carregar requests: ' . $e->getMessage();
        $message_type = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Sistema Klarbyte</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <!-- Cabeçalho da página -->
        <div class="page-header">
            <h1><?php echo $page_title; ?></h1>
            <p>Gerencie e tente novamente requests que falharam no sistema</p>
        </div>

        <!-- Mensagens -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Retry Manual por ID -->
        <div class="card fade-in">
            <div class="card-header">
                <h3 class="card-title">Retry Manual por ID</h3>
            </div>
            <div class="card-body">
                <form method="POST" style="margin-bottom: 0;">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="manual_request_id">ID da Request:</label>
                            <input type="text" 
                                   id="manual_request_id" 
                                   name="manual_request_id" 
                                   class="form-control" 
                                   placeholder="Ex: 0818:AFC7F:5C03CF:877B93:68C4EC4F"
                                   value="<?php echo htmlspecialchars($_POST['manual_request_id'] ?? ''); ?>"
                                   required>
                            <small class="form-text">Informe o ID da request que deseja tentar novamente</small>
                        </div>
                        <div class="form-col" style="display: flex; align-items: end;">
                            <button type="submit" name="action" value="retry_by_id" class="btn btn-primary">
                                🔄 Tentar Novamente
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="dashboard-grid">
            <div class="stat-card" style="border-left-color: #ffc107;">
                <div class="stat-number" style="color: #ffc107;"><?php echo $pending_count; ?></div>
                <div class="stat-label">Pendentes</div>
            </div>
            <div class="stat-card" style="border-left-color: #dc3545;">
                <div class="stat-number" style="color: #dc3545;"><?php echo $failed_count; ?></div>
                <div class="stat-label">Falhadas</div>
            </div>
            <div class="stat-card" style="border-left-color: #28a745;">
                <div class="stat-number" style="color: #28a745;"><?php echo $success_count; ?></div>
                <div class="stat-label">Completadas</div>
            </div>
            <div class="stat-card" style="border-left-color: #007bff;">
                <div class="stat-number" style="color: #007bff;"><?php echo count($failed_requests); ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>

        <!-- Lista de Requests -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Histórico de Requests</h3>
                <div class="card-actions">
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="action" value="cleanup" class="btn btn-secondary btn-sm" 
                                onclick="return confirm('Confirma a limpeza de requests antigas completadas?')">
                            🧹 Limpar Antigas
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($failed_requests)): ?>
                    <div class="alert alert-info">
                        <p><strong>Nenhuma request encontrada.</strong></p>
                        <p>Não há requests falhadas no sistema no momento.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID da Request</th>
                                    <th>Operação</th>
                                    <th>Status</th>
                                    <th>Tentativas</th>
                                    <th>Criada em</th>
                                    <th>Último Erro</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($failed_requests as $request): ?>
                                    <tr>
                                        <td>
                                            <code style="font-size: 0.9em;"><?php echo htmlspecialchars($request['request_id']); ?></code>
                                        </td>
                                        <td>
                                            <?php
                                            $operation_labels = [
                                                'epi_create' => '➕ Criar EPI',
                                                'epi_update' => '✏️ Atualizar EPI',
                                                'epi_delete' => '🗑️ Excluir EPI',
                                                'funcionario_create' => '👤 Criar Funcionário',
                                                'funcionario_update' => '✏️ Atualizar Funcionário',
                                                'funcionario_delete' => '❌ Excluir Funcionário',
                                                'movimentacao_create' => '📦 Criar Movimentação',
                                                'movimentacao_update' => '✏️ Atualizar Movimentação'
                                            ];
                                            echo $operation_labels[$request['operation_type']] ?? $request['operation_type'];
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'pending' => 'warning',
                                                'retrying' => 'info',
                                                'success' => 'success',
                                                'failed' => 'danger'
                                            ];
                                            $status_labels = [
                                                'pending' => '⏳ Pendente',
                                                'retrying' => '🔄 Tentando',
                                                'success' => '✅ Sucesso',
                                                'failed' => '❌ Falhada'
                                            ];
                                            ?>
                                            <span class="badge badge-<?php echo $status_class[$request['status']]; ?>">
                                                <?php echo $status_labels[$request['status']]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $request['retry_count']; ?>/<?php echo $request['max_retries']; ?>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y H:i', strtotime($request['created_at'])); ?>
                                        </td>
                                        <td>
                                            <span title="<?php echo htmlspecialchars($request['error_message']); ?>">
                                                <?php echo htmlspecialchars(substr($request['error_message'], 0, 50)) . (strlen($request['error_message']) > 50 ? '...' : ''); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($request['status'] === 'pending' || ($request['status'] === 'failed' && $request['retry_count'] < $request['max_retries'])): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['request_id']); ?>">
                                                    <button type="submit" name="action" value="retry_request" class="btn btn-primary btn-sm">
                                                        🔄 Retry
                                                    </button>
                                                </form>
                                            <?php elseif ($request['status'] === 'success'): ?>
                                                <span class="text-success">✅ Concluída</span>
                                            <?php else: ?>
                                                <span class="text-danger">❌ Limite atingido</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Instruções de Uso -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Como Usar o Sistema de Retry</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-col">
                        <h4>🔄 Retry Manual</h4>
                        <p>Para tentar novamente uma request específica:</p>
                        <ol>
                            <li>Digite o ID da request no campo acima</li>
                            <li>Clique em "Tentar Novamente"</li>
                            <li>O sistema irá reprocessar a operação</li>
                        </ol>
                        <p><strong>Exemplo de ID:</strong> <code>0818:AFC7F:5C03CF:877B93:68C4EC4F</code></p>
                    </div>
                    <div class="form-col">
                        <h4>📊 Status das Requests</h4>
                        <ul>
                            <li><span class="badge badge-warning">⏳ Pendente</span> - Aguardando retry</li>
                            <li><span class="badge badge-info">🔄 Tentando</span> - Em processo de retry</li>
                            <li><span class="badge badge-success">✅ Sucesso</span> - Concluída com sucesso</li>
                            <li><span class="badge badge-danger">❌ Falhada</span> - Falhou após todas as tentativas</li>
                        </ul>
                    </div>
                </div>
                
                <div class="alert alert-info" style="margin-top: 1rem;">
                    <strong>💡 Dica:</strong> O sistema tentará automaticamente até 3 vezes antes de marcar uma request como falhada definitivamente.
                    Você pode tentar manualmente quantas vezes precisar usando o formulário acima.
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Atualizar página automaticamente a cada 30 segundos se houver requests pendentes
        <?php if ($pending_count > 0): ?>
        setTimeout(function() {
            if (document.querySelectorAll('.badge-warning, .badge-info').length > 0) {
                location.reload();
            }
        }, 30000);
        <?php endif; ?>

        // Validação do formato do ID da request
        document.getElementById('manual_request_id').addEventListener('input', function() {
            const value = this.value;
            const pattern = /^[0-9A-F]+:[0-9A-F]+:[0-9A-F]+:[0-9A-F]+:[0-9A-F]+$/i;
            
            if (value && !pattern.test(value)) {
                this.setCustomValidity('Formato inválido. Use o padrão: XXXX:XXXXX:XXXXXX:XXXXXX:XXXXXXXX');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>