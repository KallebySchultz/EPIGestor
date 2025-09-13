<?php
/**
 * Dashboard Principal Simplificado - Sistema de Gestão de EPIs Klarbyte
 * Interface estilo planilha, simples e funcional
 */

require_once '../config/database_simple.php';
require_once '../config/auth.php';

// Verificar se está logado
requireLogin();

$admin = getLoggedAdmin();
$admin_name = $admin['username'] ?? 'Admin';

// Buscar dados para o dashboard
$total_epis = executeQuery("SELECT COUNT(*) as total FROM epis WHERE ativo = 1")[0]['total'] ?? 0;
$total_funcionarios = executeQuery("SELECT COUNT(*) as total FROM funcionarios WHERE ativo = 1")[0]['total'] ?? 0;
$epis_baixo_estoque = executeQuery("SELECT COUNT(*) as total FROM epis WHERE quantidade_disponivel <= 5 AND ativo = 1")[0]['total'] ?? 0;

$page_title = "Sistema de EPIs - Dashboard";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style_simple.css">
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #007bff;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .action-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .action-card h3 {
            margin-bottom: 1rem;
            color: #333;
        }
        .action-card p {
            color: #666;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .btn-large {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            width: 100%;
        }
    </style>
</head>
<body>
    <!-- Header Simples -->
    <header class="header">
        <div class="header-content">
            <h1>Sistema de EPIs Klarbyte</h1>
            <div class="user-info">
                <span>Olá, <?php echo htmlspecialchars($admin_name); ?></span>
                <a href="?logout=1" class="btn btn-sm btn-secondary">Sair</a>
            </div>
        </div>
    </header>

    <!-- Navegação Simples -->
    <nav class="nav">
        <a href="dashboard.php" class="nav-link active">Dashboard</a>
        <a href="epis.php" class="nav-link">EPIs</a>
        <a href="funcionarios.php" class="nav-link">Funcionários</a>
        <a href="movimentacoes.php" class="nav-link">Movimentações</a>
    </nav>

    <!-- Container Principal -->
    <div class="container">
        <!-- Estatísticas -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_epis; ?></div>
                <div class="stat-label">Total de EPIs</div>
            </div>
            <div class="stat-card" style="border-left-color: #28a745;">
                <div class="stat-number" style="color: #28a745;"><?php echo $total_funcionarios; ?></div>
                <div class="stat-label">Funcionários</div>
            </div>
            <div class="stat-card" style="border-left-color: <?php echo $epis_baixo_estoque > 0 ? '#dc3545' : '#ffc107'; ?>;">
                <div class="stat-number" style="color: <?php echo $epis_baixo_estoque > 0 ? '#dc3545' : '#ffc107'; ?>;">
                    <?php echo $epis_baixo_estoque; ?>
                </div>
                <div class="stat-label">EPIs com Estoque Baixo</div>
            </div>
        </div>

        <!-- Alerta de Estoque -->
        <?php if ($epis_baixo_estoque > 0): ?>
        <div class="alert alert-warning">
            <strong>Atenção!</strong> Há <?php echo $epis_baixo_estoque; ?> EPI(s) com estoque baixo (≤ 5 unidades).
        </div>
        <?php endif; ?>

        <!-- Ações Rápidas -->
        <div class="quick-actions">
            <div class="action-card">
                <h3>Gerenciar EPIs</h3>
                <p>Visualize, adicione, edite ou remova EPIs. Interface estilo planilha para facilitar a gestão.</p>
                <a href="epis_simple.php" class="btn btn-primary btn-large">Abrir Lista de EPIs</a>
            </div>
            
            <div class="action-card">
                <h3>Gerenciar Funcionários</h3>
                <p>Cadastre e gerencie funcionários. Lista simples e editável para controle de pessoal.</p>
                <a href="funcionarios_simple.php" class="btn btn-success btn-large">Abrir Lista de Funcionários</a>
            </div>
            
            <div class="action-card">
                <h3>Movimentações</h3>
                <p>Registre retiradas, devoluções e ajustes de estoque. Histórico completo de movimentações.</p>
                <a href="movimentacoes_simple.php" class="btn btn-info btn-large">Ver Movimentações</a>
            </div>
        </div>

        <!-- Informações do Sistema -->
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 2rem;">
            <h3 style="margin-bottom: 1rem; color: #333;">Sobre o Sistema</h3>
            <p style="color: #666; line-height: 1.6;">
                Este é um sistema simplificado para gestão de EPIs. Foi projetado para ser simples e funcional, 
                com interfaces estilo planilha para facilitar o uso. Todos os dados são gerenciados por administradores.
            </p>
            <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 5px; font-size: 0.9rem; color: #666;">
                <strong>Características:</strong><br>
                • Interface simples estilo planilha<br>
                • Controle centralizado por administradores<br>
                • Listas editáveis em tempo real<br>
                • Sem complexidade desnecessária
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer style="text-align: center; padding: 2rem; color: #666; border-top: 1px solid #ddd; margin-top: 2rem;">
        <p>&copy; 2024 Klarbyte Sistemas - Sistema Simplificado de Gestão de EPIs</p>
    </footer>
</body>
</html>