<?php
/**
 * Gerenciamento de Movimentações - Sistema de Gestão de EPIs Klarbyte
 * Página para registrar e listar movimentações de EPIs
 */

// Configurações da página
$page_title = "Gerenciamento de Movimentações";
$panel_type = "Painel Administrativo";
$is_admin = true;
$user_name = "Administrador";

// Incluir conexão com banco de dados
require_once '../config/database.php';

// Processar ações
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $epi_id = (int)($_POST['epi_id'] ?? 0);
        $funcionario_id = !empty($_POST['funcionario_id']) ? (int)$_POST['funcionario_id'] : null;
        $tipo_movimentacao = $_POST['tipo_movimentacao'] ?? '';
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if ($epi_id <= 0 || $quantidade <= 0 || empty($tipo_movimentacao)) {
            $message = 'Todos os campos obrigatórios devem ser preenchidos.';
            $message_type = 'danger';
        } else {
            // Buscar estoque atual do EPI
            $epi_result = executeQuery("SELECT quantidade_estoque FROM epis WHERE id = ? AND ativo = 1", [$epi_id]);
            
            if (empty($epi_result)) {
                $message = 'EPI não encontrado.';
                $message_type = 'danger';
            } else {
                $estoque_atual = (int)$epi_result[0]['quantidade_estoque'];
                $saldo_anterior = $estoque_atual;
                
                // Calcular novo saldo baseado no tipo de movimentação
                switch ($tipo_movimentacao) {
                    case 'entrada':
                        $novo_saldo = $estoque_atual + $quantidade;
                        break;
                    case 'retirada':
                    case 'descarte':
                        if ($estoque_atual < $quantidade) {
                            $message = 'Quantidade insuficiente em estoque.';
                            $message_type = 'danger';
                        } else {
                            $novo_saldo = $estoque_atual - $quantidade;
                        }
                        break;
                    case 'devolucao':
                        $novo_saldo = $estoque_atual + $quantidade;
                        break;
                    default:
                        $message = 'Tipo de movimentação inválido.';
                        $message_type = 'danger';
                }
                
                if (empty($message)) {
                    try {
                        // Iniciar transação
                        $pdo = getConnection();
                        $pdo->beginTransaction();
                        
                        // Registrar movimentação
                        $success = executeUpdate(
                            "INSERT INTO movimentacoes (epi_id, funcionario_id, tipo_movimentacao, quantidade, observacoes, usuario_responsavel, saldo_anterior, saldo_atual) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                            [$epi_id, $funcionario_id, $tipo_movimentacao, $quantidade, $observacoes, 'admin', $saldo_anterior, $novo_saldo]
                        );
                        
                        if ($success) {
                            // Atualizar estoque do EPI
                            $success = executeUpdate(
                                "UPDATE epis SET quantidade_estoque = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                                [$novo_saldo, $epi_id]
                            );
                        }
                        
                        if ($success) {
                            $pdo->commit();
                            $message = 'Movimentação registrada com sucesso!';
                            $message_type = 'success';
                        } else {
                            $pdo->rollBack();
                            $message = 'Erro ao registrar movimentação.';
                            $message_type = 'danger';
                        }
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $message = 'Erro no banco de dados: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                }
            }
        }
    }
}

// Buscar dados para filtros
$search = $_GET['search'] ?? '';
$filter_tipo = $_GET['tipo'] ?? '';
$filter_funcionario = $_GET['funcionario'] ?? '';
$filter_epi = $_GET['epi'] ?? '';
$filter_data_inicio = $_GET['data_inicio'] ?? '';
$filter_data_fim = $_GET['data_fim'] ?? '';

// Construir query de busca
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(e.nome LIKE ? OR f.nome LIKE ? OR m.observacoes LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if (!empty($filter_tipo)) {
    $where_conditions[] = "m.tipo_movimentacao = ?";
    $params[] = $filter_tipo;
}

if (!empty($filter_funcionario)) {
    $where_conditions[] = "m.funcionario_id = ?";
    $params[] = $filter_funcionario;
}

if (!empty($filter_epi)) {
    $where_conditions[] = "m.epi_id = ?";
    $params[] = $filter_epi;
}

if (!empty($filter_data_inicio)) {
    $where_conditions[] = "DATE(m.data_movimentacao) >= ?";
    $params[] = $filter_data_inicio;
}

if (!empty($filter_data_fim)) {
    $where_conditions[] = "DATE(m.data_movimentacao) <= ?";
    $params[] = $filter_data_fim;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Buscar movimentações
$movimentacoes = executeQuery("
    SELECT m.*, e.nome as epi_nome, f.nome as funcionario_nome, 
           DATE_FORMAT(m.data_movimentacao, '%d/%m/%Y %H:%i') as data_formatada
    FROM movimentacoes m
    LEFT JOIN epis e ON m.epi_id = e.id
    LEFT JOIN funcionarios f ON m.funcionario_id = f.id
    $where_clause
    ORDER BY m.data_movimentacao DESC
    LIMIT 100
", $params);

// Buscar EPIs ativos para o formulário
$epis = executeQuery("SELECT id, nome, quantidade_estoque FROM epis WHERE ativo = 1 ORDER BY nome");

// Buscar funcionários ativos para o formulário
$funcionarios = executeQuery("SELECT id, nome FROM funcionarios WHERE ativo = 1 ORDER BY nome");

// Estatísticas do dia
$stats_hoje = executeQuery("
    SELECT 
        tipo_movimentacao,
        COUNT(*) as total,
        SUM(quantidade) as quantidade_total
    FROM movimentacoes 
    WHERE DATE(data_movimentacao) = CURDATE()
    GROUP BY tipo_movimentacao
");

// Incluir header
include '../includes/header.php';
?>

<!-- Mensagem de feedback -->
<?php if (!empty($message)): ?>
<div class="alert alert-<?php echo $message_type; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<!-- Estatísticas do Dia -->
<div class="dashboard-grid">
    <?php
    $tipos_cores = [
        'entrada' => ['#28a745', 'Entradas'],
        'retirada' => ['#007bff', 'Retiradas'],
        'devolucao' => ['#ffc107', 'Devoluções'],
        'descarte' => ['#dc3545', 'Descartes']
    ];
    
    foreach ($tipos_cores as $tipo => [$cor, $label]):
        $stat = array_filter($stats_hoje, fn($s) => $s['tipo_movimentacao'] == $tipo);
        $total = !empty($stat) ? array_values($stat)[0]['total'] : 0;
        $quantidade = !empty($stat) ? array_values($stat)[0]['quantidade_total'] : 0;
    ?>
    <div class="stat-card" style="border-left-color: <?php echo $cor; ?>;">
        <div class="stat-number" style="color: <?php echo $cor; ?>;"><?php echo $total; ?></div>
        <div class="stat-label"><?php echo $label; ?> Hoje</div>
        <small style="color: #6c757d;">Total: <?php echo $quantidade; ?> itens</small>
    </div>
    <?php endforeach; ?>
</div>

<!-- Formulário de Nova Movimentação -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Registrar Nova Movimentação</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="epi_id">EPI *</label>
                        <select id="epi_id" name="epi_id" class="form-control" required>
                            <option value="">Selecione um EPI</option>
                            <?php foreach ($epis as $epi): ?>
                                <option value="<?php echo $epi['id']; ?>" data-estoque="<?php echo $epi['quantidade_estoque']; ?>">
                                    <?php echo htmlspecialchars($epi['nome']); ?> (Estoque: <?php echo $epi['quantidade_estoque']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small id="estoque-info" class="text-muted"></small>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="tipo_movimentacao">Tipo de Movimentação *</label>
                        <select id="tipo_movimentacao" name="tipo_movimentacao" class="form-control" required>
                            <option value="">Selecione o tipo</option>
                            <option value="entrada">Entrada (Compra/Recebimento)</option>
                            <option value="retirada">Retirada (Funcionário)</option>
                            <option value="devolucao">Devolução</option>
                            <option value="descarte">Descarte</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="funcionario_id">Funcionário</label>
                        <select id="funcionario_id" name="funcionario_id" class="form-control">
                            <option value="">Selecione um funcionário (opcional)</option>
                            <?php foreach ($funcionarios as $funcionario): ?>
                                <option value="<?php echo $funcionario['id']; ?>">
                                    <?php echo htmlspecialchars($funcionario['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="quantidade">Quantidade *</label>
                        <input type="number" id="quantidade" name="quantidade" class="form-control" min="1" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="observacoes">Observações</label>
                <textarea id="observacoes" name="observacoes" class="form-control" rows="3" 
                         placeholder="Detalhes adicionais sobre a movimentação..."></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Registrar Movimentação</button>
                <button type="reset" class="btn btn-secondary">Limpar Formulário</button>
            </div>
        </form>
    </div>
</div>

<!-- Filtros e Pesquisa -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Histórico de Movimentações</h3>
    </div>
    <div class="card-body">
        <div class="filters">
            <div class="search-box">
                <label class="form-label" for="search">Pesquisar</label>
                <input type="text" id="search" class="form-control search-input" placeholder="EPI, funcionário ou observações..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="filter_tipo">Tipo</label>
                <select id="filter_tipo" class="form-control">
                    <option value="">Todos</option>
                    <option value="entrada" <?php echo $filter_tipo == 'entrada' ? 'selected' : ''; ?>>Entrada</option>
                    <option value="retirada" <?php echo $filter_tipo == 'retirada' ? 'selected' : ''; ?>>Retirada</option>
                    <option value="devolucao" <?php echo $filter_tipo == 'devolucao' ? 'selected' : ''; ?>>Devolução</option>
                    <option value="descarte" <?php echo $filter_tipo == 'descarte' ? 'selected' : ''; ?>>Descarte</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="filter_data_inicio">Data Início</label>
                <input type="date" id="filter_data_inicio" class="form-control" value="<?php echo $filter_data_inicio; ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="filter_data_fim">Data Fim</label>
                <input type="date" id="filter_data_fim" class="form-control" value="<?php echo $filter_data_fim; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-primary" onclick="applyFilters()">Filtrar</button>
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
                        <th>Funcionário</th>
                        <th>Quantidade</th>
                        <th>Saldo Anterior</th>
                        <th>Saldo Atual</th>
                        <th>Responsável</th>
                        <th>Observações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimentacoes)): ?>
                        <tr>
                            <td colspan="9" class="text-center">Nenhuma movimentação encontrada.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movimentacoes as $mov): ?>
                        <tr>
                            <td><?php echo $mov['data_formatada']; ?></td>
                            <td>
                                <?php
                                $tipos_badges = [
                                    'entrada' => '<span class="badge badge-success">Entrada</span>',
                                    'retirada' => '<span class="badge badge-info">Retirada</span>',
                                    'devolucao' => '<span class="badge badge-warning">Devolução</span>',
                                    'descarte' => '<span class="badge badge-danger">Descarte</span>'
                                ];
                                echo $tipos_badges[$mov['tipo_movimentacao']] ?? $mov['tipo_movimentacao'];
                                ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($mov['epi_nome'] ?? 'EPI Removido'); ?></strong>
                                <br><small class="text-muted">ID: <?php echo $mov['epi_id']; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($mov['funcionario_nome'] ?? '-'); ?></td>
                            <td class="text-center">
                                <span class="badge badge-<?php echo in_array($mov['tipo_movimentacao'], ['entrada', 'devolucao']) ? 'success' : 'danger'; ?>">
                                    <?php echo in_array($mov['tipo_movimentacao'], ['entrada', 'devolucao']) ? '+' : '-'; ?><?php echo $mov['quantidade']; ?>
                                </span>
                            </td>
                            <td class="text-center"><?php echo $mov['saldo_anterior']; ?></td>
                            <td class="text-center">
                                <strong><?php echo $mov['saldo_atual']; ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($mov['usuario_responsavel']); ?></td>
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

<?php
// Script adicional para a página
$additional_scripts = "
<script>
function applyFilters() {
    const search = document.getElementById('search').value;
    const tipo = document.getElementById('filter_tipo').value;
    const dataInicio = document.getElementById('filter_data_inicio').value;
    const dataFim = document.getElementById('filter_data_fim').value;
    
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (tipo) params.set('tipo', tipo);
    if (dataInicio) params.set('data_inicio', dataInicio);
    if (dataFim) params.set('data_fim', dataFim);
    
    window.location.href = 'movimentacoes.php?' + params.toString();
}

// Mostrar informações de estoque ao selecionar EPI
document.getElementById('epi_id').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const infoDiv = document.getElementById('estoque-info');
    
    if (selected.value) {
        const estoque = selected.dataset.estoque;
        infoDiv.textContent = 'Estoque atual: ' + estoque + ' unidades';
        infoDiv.className = 'text-info';
    } else {
        infoDiv.textContent = '';
    }
});

// Validar quantidade baseada no tipo de movimentação e estoque
document.getElementById('quantidade').addEventListener('input', function() {
    const tipo = document.getElementById('tipo_movimentacao').value;
    const epiSelect = document.getElementById('epi_id');
    const selected = epiSelect.options[epiSelect.selectedIndex];
    
    if (selected.value && (tipo === 'retirada' || tipo === 'descarte')) {
        const estoque = parseInt(selected.dataset.estoque);
        const quantidade = parseInt(this.value);
        
        if (quantidade > estoque) {
            this.setCustomValidity('Quantidade maior que o estoque disponível (' + estoque + ')');
        } else {
            this.setCustomValidity('');
        }
    } else {
        this.setCustomValidity('');
    }
});

// Atualizar validação quando tipo de movimentação mudar
document.getElementById('tipo_movimentacao').addEventListener('change', function() {
    document.getElementById('quantidade').dispatchEvent(new Event('input'));
    
    // Tornar funcionário obrigatório para retiradas e devoluções
    const funcionarioSelect = document.getElementById('funcionario_id');
    if (this.value === 'retirada' || this.value === 'devolucao') {
        funcionarioSelect.required = true;
        funcionarioSelect.parentNode.querySelector('.form-label').innerHTML = 'Funcionário *';
    } else {
        funcionarioSelect.required = false;
        funcionarioSelect.parentNode.querySelector('.form-label').innerHTML = 'Funcionário';
    }
});

// Filtro em tempo real na tabela
document.getElementById('search').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>
";

// Incluir footer
include '../includes/footer.php';
?>