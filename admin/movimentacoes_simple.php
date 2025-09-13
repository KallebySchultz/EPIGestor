<?php
/**
 * Gestão de Movimentações - Interface estilo planilha
 * Sistema de Gestão de EPIs Klarbyte - Versão Simplificada
 */

require_once '../config/database_simple.php';
require_once '../config/auth.php';

// Verificar se está logado
requireLogin();

$admin = getLoggedAdmin();
$message = '';
$message_type = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $epi_id = (int)($_POST['epi_id'] ?? 0);
        $funcionario_id = !empty($_POST['funcionario_id']) ? (int)$_POST['funcionario_id'] : null;
        $tipo = $_POST['tipo'] ?? '';
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if ($epi_id > 0 && in_array($tipo, ['retirada', 'devolucao', 'ajuste']) && $quantidade > 0) {
            // Buscar EPI atual
            $epi_atual = executeQuery("SELECT * FROM epis WHERE id = ?", [$epi_id]);
            
            if (!empty($epi_atual)) {
                $epi = $epi_atual[0];
                $quantidade_atual = $epi['quantidade_disponivel'];
                
                // Calcular nova quantidade baseada no tipo
                if ($tipo === 'retirada') {
                    $nova_quantidade = $quantidade_atual - $quantidade;
                } elseif ($tipo === 'devolucao') {
                    $nova_quantidade = $quantidade_atual + $quantidade;
                } else { // ajuste
                    $nova_quantidade = $quantidade; // quantidade é o valor final desejado
                }
                
                if ($nova_quantidade >= 0) {
                    // Iniciar transação
                    beginTransaction();
                    
                    try {
                        // Registrar movimentação
                        $mov_success = executeUpdate(
                            "INSERT INTO movimentacoes (epi_id, funcionario_id, tipo, quantidade, observacoes) VALUES (?, ?, ?, ?, ?)",
                            [$epi_id, $funcionario_id, $tipo, $quantidade, $observacoes]
                        );
                        
                        // Atualizar estoque
                        $estoque_success = executeUpdate(
                            "UPDATE epis SET quantidade_disponivel = ? WHERE id = ?",
                            [$nova_quantidade, $epi_id]
                        );
                        
                        if ($mov_success && $estoque_success) {
                            commit();
                            $message = 'Movimentação registrada com sucesso!';
                            $message_type = 'success';
                        } else {
                            rollback();
                            $message = 'Erro ao registrar movimentação.';
                            $message_type = 'danger';
                        }
                    } catch (Exception $e) {
                        rollback();
                        $message = 'Erro ao processar movimentação: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                } else {
                    $message = 'Quantidade insuficiente em estoque para esta operação.';
                    $message_type = 'danger';
                }
            } else {
                $message = 'EPI não encontrado.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Dados inválidos para a movimentação.';
            $message_type = 'danger';
        }
    }
}

// Buscar dados para formulário
$epis = executeQuery("SELECT id, nome, quantidade_disponivel FROM epis WHERE ativo = 1 ORDER BY nome");
$funcionarios = executeQuery("SELECT id, nome FROM funcionarios WHERE ativo = 1 ORDER BY nome");

// Buscar movimentações recentes
$movimentacoes = executeQuery("
    SELECT m.*, e.nome as epi_nome, f.nome as funcionario_nome
    FROM movimentacoes m
    LEFT JOIN epis e ON m.epi_id = e.id
    LEFT JOIN funcionarios f ON m.funcionario_id = f.id
    ORDER BY m.data_movimentacao DESC
    LIMIT 50
");

$page_title = "Gestão de Movimentações";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style_simple.css">
    <style>
        .add-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 2px dashed #17a2b8;
        }
        .add-form h3 {
            margin-bottom: 1rem;
            color: #17a2b8;
        }
        .quick-add {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 2fr 1fr;
            gap: 0.5rem;
            align-items: end;
        }
        .tipo-retirada { background-color: #fff3cd !important; }
        .tipo-devolucao { background-color: #d4edda !important; }
        .tipo-ajuste { background-color: #e2e3e5 !important; }
        .recent-movements {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <h1>Sistema de EPIs Klarbyte</h1>
            <div class="user-info">
                <span>Olá, <?php echo htmlspecialchars($admin['username']); ?></span>
                <a href="?logout=1" class="btn btn-sm btn-secondary">Sair</a>
            </div>
        </div>
    </header>

    <!-- Navegação -->
    <nav class="nav">
        <a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="epis_simple.php" class="nav-link">EPIs</a>
        <a href="funcionarios_simple.php" class="nav-link">Funcionários</a>
        <a href="movimentacoes.php" class="nav-link active">Movimentações</a>
    </nav>

    <!-- Container -->
    <div class="container">
        <h2>Gestão de Movimentações</h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de Nova Movimentação -->
        <div class="add-form">
            <h3>Registrar Nova Movimentação</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="quick-add">
                    <select name="epi_id" required class="form-control">
                        <option value="">Selecione o EPI</option>
                        <?php foreach ($epis as $epi): ?>
                            <option value="<?php echo $epi['id']; ?>">
                                <?php echo htmlspecialchars($epi['nome']); ?> (Disp: <?php echo $epi['quantidade_disponivel']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="funcionario_id" class="form-control">
                        <option value="">Funcionário (opcional)</option>
                        <?php foreach ($funcionarios as $func): ?>
                            <option value="<?php echo $func['id']; ?>">
                                <?php echo htmlspecialchars($func['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="tipo" required class="form-control">
                        <option value="">Tipo</option>
                        <option value="retirada">Retirada</option>
                        <option value="devolucao">Devolução</option>
                        <option value="ajuste">Ajuste</option>
                    </select>
                    
                    <input type="number" name="quantidade" placeholder="Quantidade" min="1" required class="form-control">
                    
                    <input type="text" name="observacoes" placeholder="Observações" class="form-control">
                    
                    <button type="submit" class="btn btn-info">Registrar</button>
                </div>
            </form>
        </div>

        <!-- Tipos de Movimentação -->
        <div style="background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem;">
            <h4>Tipos de Movimentação:</h4>
            <div style="display: flex; gap: 2rem; margin-top: 0.5rem; flex-wrap: wrap;">
                <div><strong>Retirada:</strong> Funcionário retira EPI do estoque</div>
                <div><strong>Devolução:</strong> Funcionário devolve EPI ao estoque</div>
                <div><strong>Ajuste:</strong> Correção manual do estoque</div>
            </div>
        </div>

        <!-- Histórico de Movimentações -->
        <div class="table-container">
            <h3 style="margin-bottom: 1rem;">Movimentações Recentes (50 últimas)</h3>
            <div class="recent-movements">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Data/Hora</th>
                            <th>EPI</th>
                            <th>Funcionário</th>
                            <th style="width: 100px;">Tipo</th>
                            <th style="width: 80px;">Qtd</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimentacoes)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: #666;">
                                    Nenhuma movimentação registrada ainda.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movimentacoes as $mov): ?>
                                <?php
                                $tipo_class = '';
                                $tipo_badge = '';
                                switch ($mov['tipo']) {
                                    case 'retirada':
                                        $tipo_class = 'tipo-retirada';
                                        $tipo_badge = '<span class="badge badge-warning">Retirada</span>';
                                        break;
                                    case 'devolucao':
                                        $tipo_class = 'tipo-devolucao';
                                        $tipo_badge = '<span class="badge badge-success">Devolução</span>';
                                        break;
                                    case 'ajuste':
                                        $tipo_class = 'tipo-ajuste';
                                        $tipo_badge = '<span class="badge badge-secondary">Ajuste</span>';
                                        break;
                                }
                                ?>
                                <tr class="<?php echo $tipo_class; ?>">
                                    <td style="font-size: 0.8rem;">
                                        <?php echo date('d/m/Y H:i', strtotime($mov['data_movimentacao'])); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($mov['epi_nome'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($mov['funcionario_nome'] ?? 'N/A'); ?></td>
                                    <td><?php echo $tipo_badge; ?></td>
                                    <td style="text-align: center; font-weight: bold;">
                                        <?php echo $mov['quantidade']; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($mov['observacoes']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Estatísticas Rápidas -->
        <div style="margin-top: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #17a2b8;">
                    <?php echo count($movimentacoes); ?>
                </div>
                <div style="color: #666;">Movimentações Recentes</div>
            </div>
            <div style="background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #ffc107;">
                    <?php 
                    $retiradas_hoje = executeQuery("
                        SELECT COUNT(*) as total 
                        FROM movimentacoes 
                        WHERE tipo = 'retirada' AND DATE(data_movimentacao) = CURDATE()
                    ")[0]['total'] ?? 0;
                    echo $retiradas_hoje;
                    ?>
                </div>
                <div style="color: #666;">Retiradas Hoje</div>
            </div>
            <div style="background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">
                    <?php 
                    $devolucoes_hoje = executeQuery("
                        SELECT COUNT(*) as total 
                        FROM movimentacoes 
                        WHERE tipo = 'devolucao' AND DATE(data_movimentacao) = CURDATE()
                    ")[0]['total'] ?? 0;
                    echo $devolucoes_hoje;
                    ?>
                </div>
                <div style="color: #666;">Devoluções Hoje</div>
            </div>
        </div>

        <!-- Informações -->
        <div style="margin-top: 2rem; background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h4 style="margin-bottom: 1rem; color: #333;">Como Usar</h4>
            <div style="color: #666; line-height: 1.6;">
                <p><strong>Dicas para registrar movimentações:</strong></p>
                <ul style="margin-left: 1rem;">
                    <li><strong>Retirada:</strong> Use quando um funcionário retirar EPI do estoque</li>
                    <li><strong>Devolução:</strong> Use quando um funcionário devolver EPI ao estoque</li>
                    <li><strong>Ajuste:</strong> Use para corrigir o estoque (informar quantidade final desejada)</li>
                    <li>O estoque é atualizado automaticamente após cada movimentação</li>
                    <li>As cores das linhas indicam o tipo de movimentação</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Validar quantidade baseada no tipo e estoque disponível
        document.addEventListener('DOMContentLoaded', function() {
            const epiSelect = document.querySelector('select[name="epi_id"]');
            const tipoSelect = document.querySelector('select[name="tipo"]');
            const quantidadeInput = document.querySelector('input[name="quantidade"]');
            
            function validarQuantidade() {
                const epiOption = epiSelect.selectedOptions[0];
                const tipo = tipoSelect.value;
                const quantidade = parseInt(quantidadeInput.value) || 0;
                
                if (epiOption && tipo === 'retirada') {
                    const disponivel = parseInt(epiOption.textContent.match(/Disp: (\d+)/)?.[1] || 0);
                    
                    if (quantidade > disponivel) {
                        alert(`Quantidade indisponível! Estoque disponível: ${disponivel}`);
                        quantidadeInput.value = disponivel;
                    }
                }
            }
            
            tipoSelect.addEventListener('change', validarQuantidade);
            quantidadeInput.addEventListener('blur', validarQuantidade);
            
            // Destacar tipo de movimentação no formulário
            tipoSelect.addEventListener('change', function() {
                const form = this.closest('.add-form');
                form.className = 'add-form';
                
                if (this.value === 'retirada') {
                    form.style.borderColor = '#ffc107';
                } else if (this.value === 'devolucao') {
                    form.style.borderColor = '#28a745';
                } else if (this.value === 'ajuste') {
                    form.style.borderColor = '#6c757d';
                } else {
                    form.style.borderColor = '#17a2b8';
                }
            });
        });
    </script>
</body>
</html>