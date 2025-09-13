<?php
/**
 * Controle de Estoque - Sistema de Gestão de EPIs Klarbyte
 * Página para visualizar estoque, alertas e análises
 */

// Configurações da página
$page_title = "Controle de Estoque";
$panel_type = "Painel Administrativo";
$is_admin = true;
$user_name = "Administrador";

// Incluir conexão com banco de dados
require_once '../config/database.php';

// Filtros
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Base query
$base_where = "e.ativo = 1";
$params = [];

if (!empty($search)) {
    $base_where .= " AND (e.nome LIKE ? OR e.descricao LIKE ? OR e.categoria LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

// Aplicar filtros específicos
switch ($filter) {
    case 'low_stock':
        $where_clause = $base_where . " AND e.quantidade_estoque <= e.quantidade_minima";
        break;
    case 'out_of_stock':
        $where_clause = $base_where . " AND e.quantidade_estoque = 0";
        break;
    case 'expiring':
        $where_clause = $base_where . " AND e.validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND e.validade >= CURDATE()";
        break;
    case 'expired':
        $where_clause = $base_where . " AND e.validade < CURDATE()";
        break;
    case 'high_value':
        $where_clause = $base_where . " AND (e.quantidade_estoque * e.preco_unitario) > 1000";
        break;
    default:
        $where_clause = $base_where;
}

// Buscar dados de estoque
$estoque = executeQuery("
    SELECT e.*, f.nome as fornecedor_nome,
           (e.quantidade_estoque * COALESCE(e.preco_unitario, 0)) as valor_total_estoque,
           CASE 
               WHEN e.quantidade_estoque = 0 THEN 'out_of_stock'
               WHEN e.quantidade_estoque <= e.quantidade_minima THEN 'low_stock'
               WHEN e.validade < CURDATE() THEN 'expired'
               WHEN e.validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring'
               ELSE 'ok'
           END as status_estoque
    FROM epis e
    LEFT JOIN fornecedores f ON e.fornecedor_id = f.id
    WHERE $where_clause
    ORDER BY 
        CASE 
            WHEN e.quantidade_estoque = 0 THEN 1
            WHEN e.quantidade_estoque <= e.quantidade_minima THEN 2
            WHEN e.validade < CURDATE() THEN 3
            WHEN e.validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 4
            ELSE 5
        END,
        e.nome
", $params);

// Estatísticas gerais
$stats = executeQuery("
    SELECT 
        COUNT(*) as total_epis,
        SUM(CASE WHEN quantidade_estoque = 0 THEN 1 ELSE 0 END) as esgotados,
        SUM(CASE WHEN quantidade_estoque <= quantidade_minima THEN 1 ELSE 0 END) as estoque_baixo,
        SUM(CASE WHEN validade < CURDATE() THEN 1 ELSE 0 END) as vencidos,
        SUM(CASE WHEN validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND validade >= CURDATE() THEN 1 ELSE 0 END) as vencimento_proximo,
        SUM(quantidade_estoque) as total_itens,
        SUM(quantidade_estoque * COALESCE(preco_unitario, 0)) as valor_total
    FROM epis 
    WHERE ativo = 1
")[0];

// EPIs mais movimentados (últimos 30 dias)
$mais_movimentados = executeQuery("
    SELECT e.nome, e.quantidade_estoque, e.quantidade_minima,
           COUNT(m.id) as total_movimentacoes,
           SUM(CASE WHEN m.tipo_movimentacao = 'retirada' THEN m.quantidade ELSE 0 END) as total_retiradas
    FROM epis e
    LEFT JOIN movimentacoes m ON e.id = m.epi_id AND m.data_movimentacao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    WHERE e.ativo = 1
    GROUP BY e.id, e.nome, e.quantidade_estoque, e.quantidade_minima
    HAVING total_movimentacoes > 0
    ORDER BY total_movimentacoes DESC
    LIMIT 10
");

// Previsão de reposição (baseada na média de uso dos últimos 30 dias)
$previsao_reposicao = executeQuery("
    SELECT e.nome, e.quantidade_estoque, e.quantidade_minima,
           AVG(CASE WHEN m.tipo_movimentacao IN ('retirada', 'descarte') THEN m.quantidade ELSE 0 END) as media_saida_diaria,
           CASE 
               WHEN AVG(CASE WHEN m.tipo_movimentacao IN ('retirada', 'descarte') THEN m.quantidade ELSE 0 END) > 0 
               THEN FLOOR(e.quantidade_estoque / AVG(CASE WHEN m.tipo_movimentacao IN ('retirada', 'descarte') THEN m.quantidade ELSE 0 END))
               ELSE NULL
           END as dias_restantes
    FROM epis e
    LEFT JOIN movimentacoes m ON e.id = m.epi_id AND m.data_movimentacao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    WHERE e.ativo = 1 AND e.quantidade_estoque > 0
    GROUP BY e.id, e.nome, e.quantidade_estoque, e.quantidade_minima
    HAVING media_saida_diaria > 0 AND dias_restantes <= 30
    ORDER BY dias_restantes ASC
    LIMIT 10
");

// Incluir header
include '../includes/header.php';
?>

<!-- Estatísticas Gerais -->
<div class="dashboard-grid">
    <div class="stat-card" style="border-left-color: #007bff;">
        <div class="stat-number" style="color: #007bff;"><?php echo $stats['total_epis']; ?></div>
        <div class="stat-label">Total de EPIs</div>
        <small style="color: #6c757d;"><?php echo number_format($stats['total_itens']); ?> itens em estoque</small>
    </div>
    
    <div class="stat-card" style="border-left-color: <?php echo $stats['esgotados'] > 0 ? '#dc3545' : '#28a745'; ?>;">
        <div class="stat-number" style="color: <?php echo $stats['esgotados'] > 0 ? '#dc3545' : '#28a745'; ?>;">
            <?php echo $stats['esgotados']; ?>
        </div>
        <div class="stat-label">Esgotados</div>
        <a href="?filter=out_of_stock" class="btn btn-danger btn-sm mt-1">Ver Lista</a>
    </div>
    
    <div class="stat-card" style="border-left-color: <?php echo $stats['estoque_baixo'] > 0 ? '#ffc107' : '#28a745'; ?>;">
        <div class="stat-number" style="color: <?php echo $stats['estoque_baixo'] > 0 ? '#ffc107' : '#28a745'; ?>;">
            <?php echo $stats['estoque_baixo']; ?>
        </div>
        <div class="stat-label">Estoque Baixo</div>
        <a href="?filter=low_stock" class="btn btn-warning btn-sm mt-1">Ver Lista</a>
    </div>
    
    <div class="stat-card" style="border-left-color: <?php echo $stats['vencidos'] > 0 ? '#dc3545' : '#28a745'; ?>;">
        <div class="stat-number" style="color: <?php echo $stats['vencidos'] > 0 ? '#dc3545' : '#28a745'; ?>;">
            <?php echo $stats['vencidos']; ?>
        </div>
        <div class="stat-label">Vencidos</div>
        <a href="?filter=expired" class="btn btn-danger btn-sm mt-1">Ver Lista</a>
    </div>
    
    <div class="stat-card" style="border-left-color: <?php echo $stats['vencimento_proximo'] > 0 ? '#fd7e14' : '#28a745'; ?>;">
        <div class="stat-number" style="color: <?php echo $stats['vencimento_proximo'] > 0 ? '#fd7e14' : '#28a745'; ?>;">
            <?php echo $stats['vencimento_proximo']; ?>
        </div>
        <div class="stat-label">Vencimento Próximo</div>
        <a href="?filter=expiring" class="btn btn-warning btn-sm mt-1">Ver Lista</a>
    </div>
    
    <div class="stat-card" style="border-left-color: #17a2b8;">
        <div class="stat-number" style="color: #17a2b8;">
            R$ <?php echo number_format($stats['valor_total'], 2, ',', '.'); ?>
        </div>
        <div class="stat-label">Valor Total Estoque</div>
        <a href="?filter=high_value" class="btn btn-info btn-sm mt-1">Itens de Alto Valor</a>
    </div>
</div>

<!-- Alertas Críticos -->
<?php if ($stats['esgotados'] > 0 || $stats['vencidos'] > 0): ?>
<div class="alert alert-danger">
    <strong>Atenção Crítica!</strong>
    <?php if ($stats['esgotados'] > 0): ?>
        Há <?php echo $stats['esgotados']; ?> EPI(s) esgotado(s).
    <?php endif; ?>
    <?php if ($stats['vencidos'] > 0): ?>
        <?php echo $stats['esgotados'] > 0 ? ' Além disso, há' : 'Há'; ?> <?php echo $stats['vencidos']; ?> EPI(s) vencido(s).
    <?php endif; ?>
    Ação imediata necessária!
</div>
<?php elseif ($stats['estoque_baixo'] > 0 || $stats['vencimento_proximo'] > 0): ?>
<div class="alert alert-warning">
    <strong>Atenção!</strong>
    <?php if ($stats['estoque_baixo'] > 0): ?>
        Há <?php echo $stats['estoque_baixo']; ?> EPI(s) com estoque baixo.
    <?php endif; ?>
    <?php if ($stats['vencimento_proximo'] > 0): ?>
        <?php echo $stats['estoque_baixo'] > 0 ? ' Há também' : 'Há'; ?> <?php echo $stats['vencimento_proximo']; ?> EPI(s) com vencimento próximo.
    <?php endif; ?>
</div>
<?php else: ?>
<div class="alert alert-success">
    <strong>Tudo sob controle!</strong> Todos os EPIs estão com estoque adequado e dentro da validade.
</div>
<?php endif; ?>

<div class="form-row">
    <!-- Previsão de Reposição -->
    <?php if (!empty($previsao_reposicao)): ?>
    <div class="form-col">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Previsão de Reposição</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>EPI</th>
                                <th>Estoque</th>
                                <th>Dias Restantes</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($previsao_reposicao as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['nome']); ?></td>
                                <td><?php echo $item['quantidade_estoque']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $item['dias_restantes'] <= 7 ? 'danger' : ($item['dias_restantes'] <= 15 ? 'warning' : 'info'); ?>">
                                        <?php echo $item['dias_restantes']; ?> dias
                                    </span>
                                </td>
                                <td>
                                    <?php if ($item['dias_restantes'] <= 7): ?>
                                        <span class="badge badge-danger">Urgente</span>
                                    <?php elseif ($item['dias_restantes'] <= 15): ?>
                                        <span class="badge badge-warning">Atenção</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Monitorar</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- EPIs Mais Movimentados -->
    <?php if (!empty($mais_movimentados)): ?>
    <div class="form-col">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Mais Movimentados (30 dias)</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>EPI</th>
                                <th>Estoque</th>
                                <th>Movimentações</th>
                                <th>Retiradas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mais_movimentados as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['nome']); ?></td>
                                <td class="stock-quantity" data-minimum="<?php echo $item['quantidade_minima']; ?>">
                                    <?php echo $item['quantidade_estoque']; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info"><?php echo $item['total_movimentacoes']; ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-primary"><?php echo $item['total_retiradas']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Filtros e Lista de Estoque -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Controle de Estoque</h3>
    </div>
    <div class="card-body">
        <!-- Filtros -->
        <div class="filters">
            <div class="search-box">
                <label class="form-label" for="search">Pesquisar</label>
                <input type="text" id="search" class="form-control search-input" placeholder="Nome, descrição ou categoria..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="filter_select">Filtro</label>
                <select id="filter_select" class="form-control">
                    <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>Todos os EPIs</option>
                    <option value="low_stock" <?php echo $filter == 'low_stock' ? 'selected' : ''; ?>>Estoque Baixo</option>
                    <option value="out_of_stock" <?php echo $filter == 'out_of_stock' ? 'selected' : ''; ?>>Esgotados</option>
                    <option value="expiring" <?php echo $filter == 'expiring' ? 'selected' : ''; ?>>Vencimento Próximo</option>
                    <option value="expired" <?php echo $filter == 'expired' ? 'selected' : ''; ?>>Vencidos</option>
                    <option value="high_value" <?php echo $filter == 'high_value' ? 'selected' : ''; ?>>Alto Valor (>R$ 1.000)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-primary" onclick="applyFilters()">Aplicar</button>
                <a href="estoque.php" class="btn btn-secondary">Limpar</a>
            </div>
        </div>
        
        <!-- Tabela de Estoque -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>EPI</th>
                        <th>Categoria</th>
                        <th>Fornecedor</th>
                        <th>Estoque</th>
                        <th>Mínimo</th>
                        <th>Valor Unit.</th>
                        <th>Valor Total</th>
                        <th>Validade</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($estoque)): ?>
                        <tr>
                            <td colspan="10" class="text-center">Nenhum EPI encontrado com os filtros aplicados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($estoque as $item): ?>
                        <tr class="<?php echo $item['status_estoque'] == 'out_of_stock' ? 'table-danger' : ($item['status_estoque'] == 'low_stock' ? 'table-warning' : ''); ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($item['nome']); ?></strong>
                                <?php if (!empty($item['descricao'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($item['descricao'], 0, 40)); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['categoria'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['fornecedor_nome'] ?? '-'); ?></td>
                            <td class="stock-quantity text-center" data-minimum="<?php echo $item['quantidade_minima']; ?>">
                                <strong><?php echo $item['quantidade_estoque']; ?></strong>
                            </td>
                            <td class="text-center"><?php echo $item['quantidade_minima']; ?></td>
                            <td class="text-right">
                                <?php echo $item['preco_unitario'] ? 'R$ ' . number_format($item['preco_unitario'], 2, ',', '.') : '-'; ?>
                            </td>
                            <td class="text-right">
                                <strong>
                                    <?php echo $item['valor_total_estoque'] > 0 ? 'R$ ' . number_format($item['valor_total_estoque'], 2, ',', '.') : '-'; ?>
                                </strong>
                            </td>
                            <td class="expiration-date">
                                <?php if ($item['validade']): ?>
                                    <?php echo date('d/m/Y', strtotime($item['validade'])); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_badges = [
                                    'out_of_stock' => '<span class="badge badge-danger">Esgotado</span>',
                                    'low_stock' => '<span class="badge badge-warning">Baixo</span>',
                                    'expired' => '<span class="badge badge-danger">Vencido</span>',
                                    'expiring' => '<span class="badge badge-warning">Vencendo</span>',
                                    'ok' => '<span class="badge badge-success">OK</span>'
                                ];
                                echo $status_badges[$item['status_estoque']] ?? '<span class="badge badge-secondary">-</span>';
                                ?>
                            </td>
                            <td>
                                <a href="epis.php?edit=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                <a href="movimentacoes.php?epi=<?php echo $item['id']; ?>" class="btn btn-info btn-sm">Histórico</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Ações Rápidas</h3>
    </div>
    <div class="card-body">
        <div class="dashboard-grid">
            <a href="movimentacoes.php?action=new&tipo=entrada" class="btn btn-success" style="padding: 1rem; text-align: center;">
                Registrar Entrada
            </a>
            <a href="epis.php?action=new" class="btn btn-primary" style="padding: 1rem; text-align: center;">
                Cadastrar Novo EPI
            </a>
            <a href="relatorios.php?tipo=estoque" class="btn btn-info" style="padding: 1rem; text-align: center;">
                Relatório de Estoque
            </a>
            <button onclick="window.print()" class="btn btn-secondary" style="padding: 1rem;">
                Imprimir Lista
            </button>
        </div>
    </div>
</div>

<?php
// Script adicional para a página
$additional_scripts = "
<script>
function applyFilters() {
    const search = document.getElementById('search').value;
    const filter = document.getElementById('filter_select').value;
    
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (filter && filter !== 'all') params.set('filter', filter);
    
    window.location.href = 'estoque.php?' + params.toString();
}

// Atualizar dados a cada 5 minutos
setInterval(function() {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 300000);

// Filtro em tempo real na tabela
document.getElementById('search').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Destacar itens críticos
document.addEventListener('DOMContentLoaded', function() {
    // Aplicar cores baseadas no status
    const statusCells = document.querySelectorAll('.stock-quantity');
    statusCells.forEach(cell => {
        const current = parseInt(cell.textContent);
        const minimum = parseInt(cell.dataset.minimum);
        
        if (current === 0) {
            cell.closest('tr').classList.add('table-danger');
        } else if (current <= minimum) {
            cell.closest('tr').classList.add('table-warning');
        }
    });
});
</script>

<style>
@media print {
    .btn, .filters, .alert, .nav, .header { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #000 !important; }
    .table { font-size: 12px; }
    .table th, .table td { padding: 4px !important; }
    body { background: white !important; }
}
</style>
";

// Incluir footer
include '../includes/footer.php';
?>