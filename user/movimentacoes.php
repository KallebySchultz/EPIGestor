<?php
/**
 * Minhas Movimentações - Sistema de Gestão de EPIs Klarbyte
 * Página para funcionários consultarem seu histórico
 */

// Configurações da página
$page_title = "Minhas Movimentações";
$panel_type = "Painel do Funcionário";
$is_admin = false;
$user_name = "Funcionário";

// Incluir conexão com banco de dados
require_once '../config/database.php';

// Simular funcionário logado (em implementação real, viria da sessão)
$funcionario_id = $_GET['funcionario_id'] ?? 1;

// Parâmetros de filtro
$filter_tipo = $_GET['tipo'] ?? '';
$filter_data_inicio = $_GET['data_inicio'] ?? '';
$filter_data_fim = $_GET['data_fim'] ?? '';
$filter_epi = $_GET['epi'] ?? '';

try {
    // Buscar dados do funcionário
    $funcionario = executeQuery("
        SELECT f.*, e.nome as empresa_nome 
        FROM funcionarios f 
        LEFT JOIN empresas e ON f.empresa_id = e.id 
        WHERE f.id = ? AND f.ativo = 1
    ", [$funcionario_id]);
    
    if (!empty($funcionario)) {
        $funcionario = $funcionario[0];
        $user_name = $funcionario['nome'];
    }
    
    // Construir query de busca
    $where_conditions = ["m.funcionario_id = ?"];
    $params = [$funcionario_id];
    
    if (!empty($filter_tipo)) {
        $where_conditions[] = "m.tipo_movimentacao = ?";
        $params[] = $filter_tipo;
    }
    
    if (!empty($filter_data_inicio)) {
        $where_conditions[] = "DATE(m.data_movimentacao) >= ?";
        $params[] = $filter_data_inicio;
    }
    
    if (!empty($filter_data_fim)) {
        $where_conditions[] = "DATE(m.data_movimentacao) <= ?";
        $params[] = $filter_data_fim;
    }
    
    if (!empty($filter_epi)) {
        $where_conditions[] = "m.epi_id = ?";
        $params[] = $filter_epi;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Buscar movimentações do funcionário
    $movimentacoes = executeQuery("
        SELECT m.*, e.nome as epi_nome, e.categoria,
               DATE_FORMAT(m.data_movimentacao, '%d/%m/%Y %H:%i') as data_formatada,
               DATE_FORMAT(m.data_movimentacao, '%Y-%m-%d') as data_ymd
        FROM movimentacoes m
        LEFT JOIN epis e ON m.epi_id = e.id
        WHERE $where_clause
        ORDER BY m.data_movimentacao DESC
        LIMIT 100
    ", $params);
    
    // Buscar EPIs que o funcionário já utilizou para filtro
    $epis_utilizados = executeQuery("
        SELECT DISTINCT e.id, e.nome 
        FROM movimentacoes m
        JOIN epis e ON m.epi_id = e.id
        WHERE m.funcionario_id = ?
        ORDER BY e.nome
    ", [$funcionario_id]);
    
    // Estatísticas do funcionário
    $estatisticas = executeQuery("
        SELECT 
            COUNT(*) as total_movimentacoes,
            SUM(CASE WHEN tipo_movimentacao = 'retirada' THEN quantidade ELSE 0 END) as total_retiradas,
            SUM(CASE WHEN tipo_movimentacao = 'devolucao' THEN quantidade ELSE 0 END) as total_devolucoes,
            COUNT(DISTINCT epi_id) as epis_diferentes,
            MIN(data_movimentacao) as primeira_movimentacao,
            MAX(data_movimentacao) as ultima_movimentacao
        FROM movimentacoes 
        WHERE funcionario_id = ?
    ", [$funcionario_id])[0];
    
    // EPIs atualmente em posse
    $epis_em_posse = executeQuery("
        SELECT e.nome, e.categoria, m1.data_movimentacao as data_retirada,
               DATEDIFF(CURDATE(), m1.data_movimentacao) as dias_posse
        FROM movimentacoes m1
        JOIN epis e ON m1.epi_id = e.id
        WHERE m1.funcionario_id = ? 
        AND m1.tipo_movimentacao = 'retirada'
        AND NOT EXISTS (
            SELECT 1 FROM movimentacoes m2 
            WHERE m2.funcionario_id = m1.funcionario_id 
            AND m2.epi_id = m1.epi_id 
            AND m2.tipo_movimentacao = 'devolucao' 
            AND m2.data_movimentacao > m1.data_movimentacao
        )
        ORDER BY m1.data_movimentacao DESC
    ", [$funcionario_id]);
    
    // Movimentações por mês (últimos 12 meses)
    $movimentacoes_mes = executeQuery("
        SELECT 
            DATE_FORMAT(data_movimentacao, '%Y-%m') as mes,
            DATE_FORMAT(data_movimentacao, '%m/%Y') as mes_formatado,
            COUNT(*) as total,
            SUM(CASE WHEN tipo_movimentacao = 'retirada' THEN 1 ELSE 0 END) as retiradas,
            SUM(CASE WHEN tipo_movimentacao = 'devolucao' THEN 1 ELSE 0 END) as devolucoes
        FROM movimentacoes 
        WHERE funcionario_id = ? 
        AND data_movimentacao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(data_movimentacao, '%Y-%m')
        ORDER BY mes DESC
    ", [$funcionario_id]);
    
} catch (Exception $e) {
    $funcionario = ['nome' => 'Funcionário'];
    $movimentacoes = [];
    $epis_utilizados = [];
    $estatisticas = [
        'total_movimentacoes' => 0,
        'total_retiradas' => 0,
        'total_devolucoes' => 0,
        'epis_diferentes' => 0,
        'primeira_movimentacao' => null,
        'ultima_movimentacao' => null
    ];
    $epis_em_posse = [];
    $movimentacoes_mes = [];
}

// Incluir header
include '../includes/header.php';
?>

<!-- Informações do Funcionário -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Meu Histórico de Movimentações</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>Funcionário:</strong> <?php echo htmlspecialchars($funcionario['nome'] ?? 'Funcionário'); ?><br>
            <strong>Empresa:</strong> <?php echo htmlspecialchars($funcionario['empresa_nome'] ?? 'Não informada'); ?><br>
            <?php if ($estatisticas['primeira_movimentacao']): ?>
                <strong>Primeira movimentação:</strong> <?php echo date('d/m/Y', strtotime($estatisticas['primeira_movimentacao'])); ?><br>
            <?php endif; ?>
            <?php if ($estatisticas['ultima_movimentacao']): ?>
                <strong>Última movimentação:</strong> <?php echo date('d/m/Y H:i', strtotime($estatisticas['ultima_movimentacao'])); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Estatísticas Pessoais -->
<div class="dashboard-grid">
    <div class="stat-card" style="border-left-color: #007bff;">
        <div class="stat-number" style="color: #007bff;"><?php echo $estatisticas['total_movimentacoes']; ?></div>
        <div class="stat-label">Total de Movimentações</div>
    </div>
    
    <div class="stat-card" style="border-left-color: #17a2b8;">
        <div class="stat-number" style="color: #17a2b8;"><?php echo $estatisticas['total_retiradas']; ?></div>
        <div class="stat-label">Total de Retiradas</div>
    </div>
    
    <div class="stat-card" style="border-left-color: #ffc107;">
        <div class="stat-number" style="color: #ffc107;"><?php echo $estatisticas['total_devolucoes']; ?></div>
        <div class="stat-label">Total de Devoluções</div>
    </div>
    
    <div class="stat-card" style="border-left-color: #28a745;">
        <div class="stat-number" style="color: #28a745;"><?php echo count($epis_em_posse); ?></div>
        <div class="stat-label">EPIs em Minha Posse</div>
    </div>
    
    <div class="stat-card" style="border-left-color: #6f42c1;">
        <div class="stat-number" style="color: #6f42c1;"><?php echo $estatisticas['epis_diferentes']; ?></div>
        <div class="stat-label">EPIs Diferentes Utilizados</div>
    </div>
    
    <div class="stat-card" style="border-left-color: #fd7e14;">
        <div class="stat-number" style="color: #fd7e14;">
            <?php echo $estatisticas['total_retiradas'] - $estatisticas['total_devolucoes']; ?>
        </div>
        <div class="stat-label">Saldo de Retiradas</div>
    </div>
</div>

<div class="form-row">
    <!-- EPIs Atualmente em Posse -->
    <div class="form-col">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">EPIs em Minha Posse</h3>
            </div>
            <div class="card-body">
                <?php if (empty($epis_em_posse)): ?>
                    <div class="alert alert-success">
                        Você não possui nenhum EPI retirado no momento.
                    </div>
                    <a href="retirar.php" class="btn btn-primary">Retirar EPI</a>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>EPI</th>
                                    <th>Categoria</th>
                                    <th>Data Retirada</th>
                                    <th>Dias</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($epis_em_posse as $epi): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($epi['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($epi['categoria'] ?? '-'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($epi['data_retirada'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $epi['dias_posse'] > 30 ? 'warning' : 'info'; ?>">
                                            <?php echo $epi['dias_posse']; ?> dias
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center">
                        <a href="devolver.php" class="btn btn-warning">Devolver EPI</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Atividade por Mês -->
    <?php if (!empty($movimentacoes_mes)): ?>
    <div class="form-col">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Atividade Mensal</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mês</th>
                                <th>Total</th>
                                <th>Retiradas</th>
                                <th>Devoluções</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($movimentacoes_mes, 0, 6) as $mes): ?>
                            <tr>
                                <td><?php echo $mes['mes_formatado']; ?></td>
                                <td class="text-center">
                                    <span class="badge badge-primary"><?php echo $mes['total']; ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info"><?php echo $mes['retiradas']; ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-warning"><?php echo $mes['devolucoes']; ?></span>
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

<!-- Filtros e Histórico -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Histórico Completo de Movimentações</h3>
    </div>
    <div class="card-body">
        <!-- Filtros -->
        <div class="filters">
            <div class="form-group">
                <label class="form-label" for="filter_tipo">Tipo</label>
                <select id="filter_tipo" class="form-control">
                    <option value="">Todos os tipos</option>
                    <option value="retirada" <?php echo $filter_tipo == 'retirada' ? 'selected' : ''; ?>>Retiradas</option>
                    <option value="devolucao" <?php echo $filter_tipo == 'devolucao' ? 'selected' : ''; ?>>Devoluções</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="filter_epi">EPI</label>
                <select id="filter_epi" class="form-control">
                    <option value="">Todos os EPIs</option>
                    <?php foreach ($epis_utilizados as $epi): ?>
                        <option value="<?php echo $epi['id']; ?>"
                                <?php echo $filter_epi == $epi['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($epi['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="filter_data_inicio">Data Início</label>
                <input type="date" id="filter_data_inicio" class="form-control" 
                       value="<?php echo $filter_data_inicio; ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="filter_data_fim">Data Fim</label>
                <input type="date" id="filter_data_fim" class="form-control" 
                       value="<?php echo $filter_data_fim; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-primary" onclick="applyFilters()">Filtrar</button>
                <a href="movimentacoes.php" class="btn btn-secondary">Limpar</a>
            </div>
        </div>
        
        <!-- Tabela de Movimentações -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Tipo</th>
                        <th>EPI</th>
                        <th>Categoria</th>
                        <th>Quantidade</th>
                        <th>Observações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimentacoes)): ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                Nenhuma movimentação encontrada com os filtros selecionados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movimentacoes as $mov): ?>
                        <tr>
                            <td><?php echo $mov['data_formatada']; ?></td>
                            <td>
                                <?php if ($mov['tipo_movimentacao'] == 'retirada'): ?>
                                    <span class="badge badge-info">Retirada</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Devolução</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($mov['epi_nome'] ?? 'EPI Removido'); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($mov['categoria'] ?? '-'); ?></td>
                            <td class="text-center">
                                <span class="badge badge-<?php echo $mov['tipo_movimentacao'] == 'retirada' ? 'danger' : 'success'; ?>">
                                    <?php echo $mov['tipo_movimentacao'] == 'retirada' ? '-' : '+'; ?><?php echo $mov['quantidade']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($mov['observacoes'])): ?>
                                    <small><?php echo htmlspecialchars(substr($mov['observacoes'], 0, 50)); ?><?php echo strlen($mov['observacoes']) > 50 ? '...' : ''; ?></small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (count($movimentacoes) >= 100): ?>
        <div class="alert alert-info">
            <strong>Nota:</strong> Mostrando apenas as 100 movimentações mais recentes. Use os filtros para refinar a busca.
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Ações Rápidas</h3>
    </div>
    <div class="card-body">
        <div class="dashboard-grid">
            <a href="retirar.php" class="btn btn-primary" style="padding: 1rem; text-align: center;">
                Retirar EPI
            </a>
            <a href="devolver.php" class="btn btn-warning" style="padding: 1rem; text-align: center;">
                Devolver EPI
            </a>
            <a href="epis.php" class="btn btn-success" style="padding: 1rem; text-align: center;">
                Ver EPIs Disponíveis
            </a>
            <a href="index.php" class="btn btn-secondary" style="padding: 1rem; text-align: center;">
                Voltar ao Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Resumo de Responsabilidades -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lembrete de Responsabilidades</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <h6>Suas Responsabilidades com os EPIs:</h6>
            <ul style="margin-bottom: 0;">
                <li>Usar os EPIs adequadamente conforme treinamento recebido</li>
                <li>Manter os equipamentos limpos e em bom estado</li>
                <li>Devolver os EPIs quando solicitado ou ao final do uso</li>
                <li>Comunicar imediatamente qualquer dano ou problema</li>
                <li>Não emprestar EPIs para outras pessoas</li>
            </ul>
        </div>
    </div>
</div>

<?php
// Script adicional para a página
$additional_scripts = "
<script>
function applyFilters() {
    const tipo = document.getElementById('filter_tipo').value;
    const epi = document.getElementById('filter_epi').value;
    const dataInicio = document.getElementById('filter_data_inicio').value;
    const dataFim = document.getElementById('filter_data_fim').value;
    
    const params = new URLSearchParams();
    if (tipo) params.set('tipo', tipo);
    if (epi) params.set('epi', epi);
    if (dataInicio) params.set('data_inicio', dataInicio);
    if (dataFim) params.set('data_fim', dataFim);
    
    window.location.href = 'movimentacoes.php?' + params.toString();
}

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey) {
        switch(e.key) {
            case 'r':
                e.preventDefault();
                window.location.href = 'retirar.php';
                break;
            case 'd':
                e.preventDefault();
                window.location.href = 'devolver.php';
                break;
            case 'h':
                e.preventDefault();
                window.location.href = 'index.php';
                break;
        }
    }
});

// Tooltip para atalhos
document.addEventListener('DOMContentLoaded', function() {
    const actionButtons = document.querySelectorAll('.dashboard-grid a');
    actionButtons.forEach((button, index) => {
        const shortcuts = ['Ctrl+R', 'Ctrl+D', '', 'Ctrl+H'];
        if (shortcuts[index]) {
            button.title = button.textContent.trim() + ' (' + shortcuts[index] + ')';
        }
    });
});
</script>
";

// Incluir footer
include '../includes/footer.php';
?>