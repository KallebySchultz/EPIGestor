<?php
/**
 * Gestão de EPIs - Interface estilo planilha
 * Sistema de Gestão de EPIs Klarbyte - Versão Simplificada
 */

require_once '../config/database_simple.php';
require_once '../config/auth.php';

// Verificar se está logado
requireLogin();

$admin = getLoggedAdmin();
$message = '';
$message_type = '';

// Processar ações (adicionar, editar, excluir)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $quantidade_total = (int)($_POST['quantidade_total'] ?? 0);
        $quantidade_disponivel = (int)($_POST['quantidade_disponivel'] ?? 0);
        $validade = $_POST['validade'] ?? null;
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if (!empty($nome)) {
            $success = executeUpdate(
                "INSERT INTO epis (nome, descricao, categoria, quantidade_total, quantidade_disponivel, validade, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$nome, $descricao, $categoria, $quantidade_total, $quantidade_disponivel, $validade ?: null, $observacoes]
            );
            
            if ($success) {
                $message = 'EPI adicionado com sucesso!';
                $message_type = 'success';
            } else {
                $message = 'Erro ao adicionar EPI.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Nome do EPI é obrigatório.';
            $message_type = 'danger';
        }
    }
    
    elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $quantidade_total = (int)($_POST['quantidade_total'] ?? 0);
        $quantidade_disponivel = (int)($_POST['quantidade_disponivel'] ?? 0);
        $validade = $_POST['validade'] ?? null;
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if ($id > 0 && !empty($nome)) {
            $success = executeUpdate(
                "UPDATE epis SET nome = ?, descricao = ?, categoria = ?, quantidade_total = ?, quantidade_disponivel = ?, validade = ?, observacoes = ? WHERE id = ?",
                [$nome, $descricao, $categoria, $quantidade_total, $quantidade_disponivel, $validade ?: null, $observacoes, $id]
            );
            
            if ($success) {
                $message = 'EPI atualizado com sucesso!';
                $message_type = 'success';
            } else {
                $message = 'Erro ao atualizar EPI.';
                $message_type = 'danger';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id > 0) {
            $success = executeUpdate("UPDATE epis SET ativo = 0 WHERE id = ?", [$id]);
            
            if ($success) {
                $message = 'EPI removido com sucesso!';
                $message_type = 'success';
            } else {
                $message = 'Erro ao remover EPI.';
                $message_type = 'danger';
            }
        }
    }
}

// Buscar todos os EPIs ativos
$epis = executeQuery("SELECT * FROM epis WHERE ativo = 1 ORDER BY nome");

$page_title = "Gestão de EPIs";
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
            border: 2px dashed #007bff;
        }
        .add-form h3 {
            margin-bottom: 1rem;
            color: #007bff;
        }
        .quick-add {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 1fr 2fr 1fr;
            gap: 0.5rem;
            align-items: end;
        }
        .editable-cell input,
        .editable-cell textarea,
        .editable-cell select {
            border: 1px solid transparent;
            background: transparent;
            padding: 0.25rem;
            border-radius: 3px;
            width: 100%;
        }
        .editable-cell input:focus,
        .editable-cell textarea:focus,
        .editable-cell select:focus {
            border-color: #007bff;
            background: white;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        .actions-cell {
            text-align: center;
            white-space: nowrap;
        }
        .table-actions {
            display: flex;
            gap: 0.25rem;
            justify-content: center;
        }
        .btn-mini {
            padding: 0.125rem 0.25rem;
            font-size: 0.7rem;
        }
        .low-stock {
            background-color: #fff3cd !important;
        }
        .out-of-stock {
            background-color: #f8d7da !important;
        }
        .expired {
            color: #dc3545;
            font-weight: bold;
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
        <a href="epis.php" class="nav-link active">EPIs</a>
        <a href="funcionarios.php" class="nav-link">Funcionários</a>
        <a href="movimentacoes.php" class="nav-link">Movimentações</a>
    </nav>

    <!-- Container -->
    <div class="container">
        <h2>Gestão de EPIs</h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de Adição Rápida -->
        <div class="add-form">
            <h3>Adicionar Novo EPI</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="quick-add">
                    <input type="text" name="nome" placeholder="Nome do EPI" required class="form-control">
                    <input type="text" name="descricao" placeholder="Descrição" class="form-control">
                    <input type="text" name="categoria" placeholder="Categoria" class="form-control">
                    <input type="number" name="quantidade_total" placeholder="Qtd Total" min="0" class="form-control">
                    <input type="number" name="quantidade_disponivel" placeholder="Qtd Disponível" min="0" class="form-control">
                    <input type="date" name="validade" class="form-control">
                    <button type="submit" class="btn btn-success">Adicionar</button>
                </div>
                <div style="margin-top: 0.5rem;">
                    <textarea name="observacoes" placeholder="Observações (opcional)" rows="2" class="form-control"></textarea>
                </div>
            </form>
        </div>

        <!-- Tabela de EPIs (estilo planilha) -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 200px;">Nome</th>
                        <th style="width: 200px;">Descrição</th>
                        <th style="width: 120px;">Categoria</th>
                        <th style="width: 80px;">Total</th>
                        <th style="width: 80px;">Disponível</th>
                        <th style="width: 120px;">Validade</th>
                        <th>Observações</th>
                        <th style="width: 120px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($epis)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: #666;">
                                Nenhum EPI cadastrado ainda. Use o formulário acima para adicionar o primeiro EPI.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($epis as $epi): ?>
                            <?php
                            $is_low_stock = $epi['quantidade_disponivel'] <= 5;
                            $is_out_of_stock = $epi['quantidade_disponivel'] <= 0;
                            $is_expired = $epi['validade'] && $epi['validade'] < date('Y-m-d');
                            $row_class = '';
                            if ($is_out_of_stock) $row_class = 'out-of-stock';
                            elseif ($is_low_stock) $row_class = 'low-stock';
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <form method="POST" class="inline-form" data-id="<?php echo $epi['id']; ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?php echo $epi['id']; ?>">
                                    
                                    <td class="editable-cell">
                                        <input type="text" name="nome" value="<?php echo htmlspecialchars($epi['nome']); ?>" required>
                                    </td>
                                    <td class="editable-cell">
                                        <input type="text" name="descricao" value="<?php echo htmlspecialchars($epi['descricao']); ?>">
                                    </td>
                                    <td class="editable-cell">
                                        <input type="text" name="categoria" value="<?php echo htmlspecialchars($epi['categoria']); ?>">
                                    </td>
                                    <td class="editable-cell">
                                        <input type="number" name="quantidade_total" value="<?php echo $epi['quantidade_total']; ?>" min="0">
                                    </td>
                                    <td class="editable-cell">
                                        <input type="number" name="quantidade_disponivel" value="<?php echo $epi['quantidade_disponivel']; ?>" min="0">
                                    </td>
                                    <td class="editable-cell">
                                        <input type="date" name="validade" value="<?php echo $epi['validade']; ?>" <?php echo $is_expired ? 'class="expired"' : ''; ?>>
                                    </td>
                                    <td class="editable-cell">
                                        <textarea name="observacoes" rows="1"><?php echo htmlspecialchars($epi['observacoes']); ?></textarea>
                                    </td>
                                    <td class="actions-cell">
                                        <div class="table-actions">
                                            <button type="submit" class="btn btn-primary btn-mini" title="Salvar">💾</button>
                                        </div>
                                    </td>
                                </form>
                                <td style="padding: 0;">
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja remover este EPI?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $epi['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-mini" title="Remover">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Legenda -->
        <div style="margin-top: 1rem; padding: 1rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h4>Legenda:</h4>
            <div style="display: flex; gap: 2rem; margin-top: 0.5rem; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 20px; height: 20px; background: #f8d7da; border-radius: 3px;"></div>
                    <span>Estoque esgotado</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 20px; height: 20px; background: #fff3cd; border-radius: 3px;"></div>
                    <span>Estoque baixo (≤ 5)</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="color: #dc3545; font-weight: bold;">Texto vermelho</span>
                    <span>Vencido</span>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div style="margin-top: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #007bff;">
                    <?php echo count($epis); ?>
                </div>
                <div style="color: #666;">Total de EPIs</div>
            </div>
            <div style="background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">
                    <?php echo array_sum(array_column($epis, 'quantidade_total')); ?>
                </div>
                <div style="color: #666;">Quantidade Total</div>
            </div>
            <div style="background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #17a2b8;">
                    <?php echo array_sum(array_column($epis, 'quantidade_disponivel')); ?>
                </div>
                <div style="color: #666;">Disponível</div>
            </div>
        </div>
    </div>

    <script>
        // Auto-salvar quando o usuário sair do campo (estilo planilha)
        document.querySelectorAll('.editable-cell input, .editable-cell textarea').forEach(input => {
            let originalValue = input.value;
            
            input.addEventListener('focus', function() {
                originalValue = this.value;
            });
            
            input.addEventListener('blur', function() {
                if (this.value !== originalValue) {
                    // Auto-salvar quando valor mudou
                    const form = this.closest('form');
                    if (form) {
                        form.submit();
                    }
                }
            });
            
            // Salvar com Enter
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && this.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    this.blur(); // Vai trigger o auto-save
                }
            });
        });
        
        // Destacar campos editados
        document.querySelectorAll('.editable-cell input, .editable-cell textarea').forEach(input => {
            input.addEventListener('input', function() {
                this.style.backgroundColor = '#e3f2fd';
            });
        });
    </script>
</body>
</html>