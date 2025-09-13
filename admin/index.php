<?php
/**
 * Dashboard Administrativo - Sistema de Gestão de EPIs Klarbyte
 * Painel principal para administradores
 */

// Configurações da página
$page_title = "Dashboard Administrativo";
$panel_type = "Painel Administrativo";
$is_admin = true;
$user_name = "Administrador";

// Incluir conexão com banco de dados
require_once '../config/database.php';

// Buscar dados para o dashboard
try {
    // Total de EPIs
    $total_epis = executeQuery("SELECT COUNT(*) as total FROM epis WHERE ativo = 1")[0]['total'] ?? 0;
    
    // Total de funcionários
    $total_funcionarios = executeQuery("SELECT COUNT(*) as total FROM funcionarios WHERE ativo = 1")[0]['total'] ?? 0;
    
    // EPIs com estoque baixo
    $estoque_baixo = executeQuery("SELECT COUNT(*) as total FROM epis WHERE quantidade_estoque <= quantidade_minima AND ativo = 1")[0]['total'] ?? 0;
    
    // EPIs próximos ao vencimento (30 dias)
    $vencimento_proximo = executeQuery("SELECT COUNT(*) as total FROM epis WHERE validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND validade >= CURDATE() AND ativo = 1")[0]['total'] ?? 0;
    
    // Total de movimentações hoje
    $movimentacoes_hoje = executeQuery("SELECT COUNT(*) as total FROM movimentacoes WHERE DATE(data_movimentacao) = CURDATE()")[0]['total'] ?? 0;
    
    // EPIs mais retirados (últimos 30 dias)
    $epis_mais_retirados = executeQuery("
        SELECT e.nome, COUNT(m.id) as total_retiradas
        FROM epis e 
        LEFT JOIN movimentacoes m ON e.id = m.epi_id 
        WHERE m.tipo_movimentacao = 'retirada' 
        AND m.data_movimentacao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY e.id, e.nome 
        ORDER BY total_retiradas DESC 
        LIMIT 5
    ");
    
    // Últimas movimentações
    $ultimas_movimentacoes = executeQuery("
        SELECT m.*, e.nome as epi_nome, f.nome as funcionario_nome, m.data_movimentacao
        FROM movimentacoes m
        LEFT JOIN epis e ON m.epi_id = e.id
        LEFT JOIN funcionarios f ON m.funcionario_id = f.id
        ORDER BY m.data_movimentacao DESC
        LIMIT 10
    ");
    
    // EPIs com estoque crítico
    $epis_criticos = executeQuery("
        SELECT nome, quantidade_estoque, quantidade_minima, validade
        FROM epis 
        WHERE quantidade_estoque <= quantidade_minima 
        AND ativo = 1
        ORDER BY quantidade_estoque ASC
        LIMIT 10
    ");
    
} catch (Exception $e) {
    // Se houver erro na conexão, usar valores padrão
    $total_epis = 0;
    $total_funcionarios = 0;
    $estoque_baixo = 0;
    $vencimento_proximo = 0;
    $movimentacoes_hoje = 0;
    $epis_mais_retirados = [];
    $ultimas_movimentacoes = [];
    $epis_criticos = [];
}

// Incluir header
include '../includes/header.php';
?>

<!-- Dashboard Principal -->
<div class="dashboard-grid">
    <!-- Cartão: Total de EPIs -->
    <div class="stat-card" style="border-left-color: #007bff;">
        <div class="stat-number"><?php echo $total_epis; ?></div>
        <div class="stat-label">Total de EPIs</div>
        <a href="epis.php" class="btn btn-primary btn-sm mt-2">Ver Todos</a>
    </div>
    
    <!-- Cartão: Total de Funcionários -->
    <div class="stat-card" style="border-left-color: #28a745;">
        <div class="stat-number"><?php echo $total_funcionarios; ?></div>
        <div class="stat-label">Funcionários Ativos</div>
        <a href="funcionarios.php" class="btn btn-success btn-sm mt-2">Gerenciar</a>
    </div>
    
    <!-- Cartão: Estoque Baixo -->
    <div class="stat-card" style="border-left-color: <?php echo $estoque_baixo > 0 ? '#dc3545' : '#ffc107'; ?>;">
        <div class="stat-number" style="color: <?php echo $estoque_baixo > 0 ? '#dc3545' : '#ffc107'; ?>;">
            <?php echo $estoque_baixo; ?>
        </div>
        <div class="stat-label">Estoque Baixo</div>
        <a href="estoque.php" class="btn btn-warning btn-sm mt-2">Verificar</a>
    </div>
    
    <!-- Cartão: Vencimento Próximo -->
    <div class="stat-card" style="border-left-color: <?php echo $vencimento_proximo > 0 ? '#fd7e14' : '#17a2b8'; ?>;">
        <div class="stat-number" style="color: <?php echo $vencimento_proximo > 0 ? '#fd7e14' : '#17a2b8'; ?>;">
            <?php echo $vencimento_proximo; ?>
        </div>
        <div class="stat-label">Vencimento em 30 dias</div>
        <a href="estoque.php?filter=expiring" class="btn btn-info btn-sm mt-2">Ver Detalhes</a>
    </div>
</div>

<!-- Alertas Importantes -->
<?php if ($estoque_baixo > 0 || $vencimento_proximo > 0): ?>
<div class="alert alert-warning">
    <strong>Atenção!</strong>
    <?php if ($estoque_baixo > 0): ?>
        Há <?php echo $estoque_baixo; ?> EPI(s) com estoque baixo.
    <?php endif; ?>
    <?php if ($vencimento_proximo > 0): ?>
        <?php echo $estoque_baixo > 0 ? ' Além disso, há' : 'Há'; ?> <?php echo $vencimento_proximo; ?> EPI(s) com vencimento próximo.
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="form-row">
    <!-- EPIs com Estoque Crítico -->
    <div class="form-col">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">EPIs com Estoque Crítico</h3>
            </div>
            <div class="card-body">
                <?php if (empty($epis_criticos)): ?>
                    <div class="alert alert-success">
                        <strong>Parabéns!</strong> Todos os EPIs estão com estoque adequado.
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>EPI</th>
                                    <th>Atual</th>
                                    <th>Mínimo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($epis_criticos as $epi): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($epi['nome']); ?></td>
                                    <td class="stock-quantity" data-minimum="<?php echo $epi['quantidade_minima']; ?>">
                                        <?php echo $epi['quantidade_estoque']; ?>
                                    </td>
                                    <td><?php echo $epi['quantidade_minima']; ?></td>
                                    <td>
                                        <?php if ($epi['quantidade_estoque'] == 0): ?>
                                            <span class="badge badge-danger">Esgotado</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Baixo</span>
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
    </div>
    
    <!-- Últimas Movimentações -->
    <div class="form-col">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Últimas Movimentações</h3>
            </div>
            <div class="card-body">
                <?php if (empty($ultimas_movimentacoes)): ?>
                    <p>Nenhuma movimentação registrada ainda.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>EPI</th>
                                    <th>Funcionário</th>
                                    <th>Qtd</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimas_movimentacoes as $mov): ?>
                                <tr>
                                    <td><?php echo date('d/m H:i', strtotime($mov['data_movimentacao'])); ?></td>
                                    <td>
                                        <?php
                                        $tipos = [
                                            'entrada' => '<span class="badge badge-success">Entrada</span>',
                                            'retirada' => '<span class="badge badge-info">Retirada</span>',
                                            'devolucao' => '<span class="badge badge-warning">Devolução</span>',
                                            'descarte' => '<span class="badge badge-danger">Descarte</span>'
                                        ];
                                        echo $tipos[$mov['tipo_movimentacao']] ?? $mov['tipo_movimentacao'];
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($mov['epi_nome'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($mov['funcionario_nome'] ?? 'N/A'); ?></td>
                                    <td><?php echo $mov['quantidade']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center">
                        <a href="movimentacoes.php" class="btn btn-primary btn-sm">Ver Todas as Movimentações</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- EPIs Mais Utilizados -->
<?php if (!empty($epis_mais_retirados)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">EPIs Mais Retirados (Últimos 30 dias)</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>EPI</th>
                        <th>Total de Retiradas</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($epis_mais_retirados as $epi): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($epi['nome']); ?></td>
                        <td><?php echo $epi['total_retiradas']; ?></td>
                        <td>
                            <a href="epis.php?search=<?php echo urlencode($epi['nome']); ?>" class="btn btn-primary btn-sm">
                                Ver Detalhes
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Ações Rápidas -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Ações Rápidas</h3>
    </div>
    <div class="card-body">
        <div class="dashboard-grid">
            <a href="epis.php?action=new" class="btn btn-primary" style="padding: 1rem; text-align: center;">
                Cadastrar Novo EPI
            </a>
            <a href="funcionarios.php?action=new" class="btn btn-success" style="padding: 1rem; text-align: center;">
                Cadastrar Funcionário
            </a>
            <a href="movimentacoes.php?action=new" class="btn btn-info" style="padding: 1rem; text-align: center;">
                Nova Movimentação
            </a>
            <a href="relatorios.php" class="btn btn-secondary" style="padding: 1rem; text-align: center;">
                Gerar Relatório
            </a>
        </div>
    </div>
</div>

<?php
// Script adicional para o dashboard
$additional_scripts = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar dados a cada 5 minutos
    setInterval(function() {
        location.reload();
    }, 300000);
    
    // Mostrar alerta se houver problemas críticos
    const estoqueBaixo = " . $estoque_baixo . ";
    const vencimentoProximo = " . $vencimento_proximo . ";
    
    if (estoqueBaixo > 0 || vencimentoProximo > 0) {
        console.log('Alertas detectados no sistema');
    }
});
</script>
";

// Incluir footer
include '../includes/footer.php';
?>