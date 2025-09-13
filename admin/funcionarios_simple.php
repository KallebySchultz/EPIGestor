<?php
/**
 * Gestão de Funcionários - Interface estilo planilha
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
        $setor = trim($_POST['setor'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (!empty($nome)) {
            $success = executeUpdate(
                "INSERT INTO funcionarios (nome, setor, cargo, telefone, email) VALUES (?, ?, ?, ?, ?)",
                [$nome, $setor, $cargo, $telefone, $email]
            );
            
            if ($success) {
                $message = 'Funcionário adicionado com sucesso!';
                $message_type = 'success';
            } else {
                $message = 'Erro ao adicionar funcionário.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Nome do funcionário é obrigatório.';
            $message_type = 'danger';
        }
    }
    
    elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $setor = trim($_POST['setor'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if ($id > 0 && !empty($nome)) {
            $success = executeUpdate(
                "UPDATE funcionarios SET nome = ?, setor = ?, cargo = ?, telefone = ?, email = ? WHERE id = ?",
                [$nome, $setor, $cargo, $telefone, $email, $id]
            );
            
            if ($success) {
                $message = 'Funcionário atualizado com sucesso!';
                $message_type = 'success';
            } else {
                $message = 'Erro ao atualizar funcionário.';
                $message_type = 'danger';
            }
        }
    }
    
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id > 0) {
            $success = executeUpdate("UPDATE funcionarios SET ativo = 0 WHERE id = ?", [$id]);
            
            if ($success) {
                $message = 'Funcionário removido com sucesso!';
                $message_type = 'success';
            } else {
                $message = 'Erro ao remover funcionário.';
                $message_type = 'danger';
            }
        }
    }
}

// Buscar todos os funcionários ativos
$funcionarios = executeQuery("SELECT * FROM funcionarios WHERE ativo = 1 ORDER BY nome");

$page_title = "Gestão de Funcionários";
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
            border: 2px dashed #28a745;
        }
        .add-form h3 {
            margin-bottom: 1rem;
            color: #28a745;
        }
        .quick-add {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 2fr 1fr;
            gap: 0.5rem;
            align-items: end;
        }
        .editable-cell input {
            border: 1px solid transparent;
            background: transparent;
            padding: 0.25rem;
            border-radius: 3px;
            width: 100%;
        }
        .editable-cell input:focus {
            border-color: #28a745;
            background: white;
            box-shadow: 0 0 0 2px rgba(40,167,69,0.25);
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
        <a href="funcionarios.php" class="nav-link active">Funcionários</a>
        <a href="movimentacoes.php" class="nav-link">Movimentações</a>
    </nav>

    <!-- Container -->
    <div class="container">
        <h2>Gestão de Funcionários</h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de Adição Rápida -->
        <div class="add-form">
            <h3>Adicionar Novo Funcionário</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="quick-add">
                    <input type="text" name="nome" placeholder="Nome completo" required class="form-control">
                    <input type="text" name="setor" placeholder="Setor" class="form-control">
                    <input type="text" name="cargo" placeholder="Cargo" class="form-control">
                    <input type="text" name="telefone" placeholder="Telefone" class="form-control">
                    <input type="email" name="email" placeholder="E-mail" class="form-control">
                    <button type="submit" class="btn btn-success">Adicionar</button>
                </div>
            </form>
        </div>

        <!-- Tabela de Funcionários (estilo planilha) -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 250px;">Nome</th>
                        <th style="width: 150px;">Setor</th>
                        <th style="width: 150px;">Cargo</th>
                        <th style="width: 150px;">Telefone</th>
                        <th style="width: 200px;">E-mail</th>
                        <th style="width: 150px;">Cadastro</th>
                        <th style="width: 100px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($funcionarios)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #666;">
                                Nenhum funcionário cadastrado ainda. Use o formulário acima para adicionar o primeiro funcionário.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($funcionarios as $funcionario): ?>
                            <tr>
                                <form method="POST" class="inline-form" data-id="<?php echo $funcionario['id']; ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?php echo $funcionario['id']; ?>">
                                    
                                    <td class="editable-cell">
                                        <input type="text" name="nome" value="<?php echo htmlspecialchars($funcionario['nome']); ?>" required>
                                    </td>
                                    <td class="editable-cell">
                                        <input type="text" name="setor" value="<?php echo htmlspecialchars($funcionario['setor']); ?>">
                                    </td>
                                    <td class="editable-cell">
                                        <input type="text" name="cargo" value="<?php echo htmlspecialchars($funcionario['cargo']); ?>">
                                    </td>
                                    <td class="editable-cell">
                                        <input type="text" name="telefone" value="<?php echo htmlspecialchars($funcionario['telefone']); ?>">
                                    </td>
                                    <td class="editable-cell">
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($funcionario['email']); ?>">
                                    </td>
                                    <td style="text-align: center; color: #666; font-size: 0.8rem;">
                                        <?php echo date('d/m/Y', strtotime($funcionario['created_at'])); ?>
                                    </td>
                                    <td class="actions-cell">
                                        <div class="table-actions">
                                            <button type="submit" class="btn btn-success btn-mini" title="Salvar">💾</button>
                                        </div>
                                    </td>
                                </form>
                                <td style="padding: 0;">
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja remover este funcionário?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $funcionario['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-mini" title="Remover">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Estatísticas -->
        <div style="margin-top: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">
                    <?php echo count($funcionarios); ?>
                </div>
                <div style="color: #666;">Total de Funcionários</div>
            </div>
            <div style="background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #007bff;">
                    <?php 
                    $setores = array_unique(array_filter(array_column($funcionarios, 'setor')));
                    echo count($setores); 
                    ?>
                </div>
                <div style="color: #666;">Setores Diferentes</div>
            </div>
            <div style="background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; color: #17a2b8;">
                    <?php 
                    $cargos = array_unique(array_filter(array_column($funcionarios, 'cargo')));
                    echo count($cargos); 
                    ?>
                </div>
                <div style="color: #666;">Cargos Diferentes</div>
            </div>
        </div>

        <!-- Informações Úteis -->
        <div style="margin-top: 2rem; background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h4 style="margin-bottom: 1rem; color: #333;">Informações</h4>
            <div style="color: #666; line-height: 1.6;">
                <p><strong>Como usar:</strong></p>
                <ul style="margin-left: 1rem;">
                    <li>Clique em qualquer campo para editar diretamente na tabela</li>
                    <li>As alterações são salvas automaticamente quando você sai do campo</li>
                    <li>Use o botão 💾 para forçar o salvamento</li>
                    <li>Use o botão 🗑️ para remover um funcionário</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Auto-salvar quando o usuário sair do campo (estilo planilha)
        document.querySelectorAll('.editable-cell input').forEach(input => {
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
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.blur(); // Vai trigger o auto-save
                }
            });
        });
        
        // Destacar campos editados
        document.querySelectorAll('.editable-cell input').forEach(input => {
            input.addEventListener('input', function() {
                this.style.backgroundColor = '#e8f5e8';
            });
        });

        // Validação de email em tempo real
        document.querySelectorAll('input[type="email"]').forEach(input => {
            input.addEventListener('blur', function() {
                const email = this.value.trim();
                if (email && !email.includes('@')) {
                    alert('Por favor, insira um e-mail válido.');
                    this.focus();
                }
            });
        });
        
        // Formatação automática de telefone
        document.querySelectorAll('input[name="telefone"]').forEach(input => {
            input.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length <= 11) {
                    if (value.length <= 10) {
                        value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                    } else {
                        value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                    }
                    this.value = value;
                }
            });
        });
    </script>
</body>
</html>