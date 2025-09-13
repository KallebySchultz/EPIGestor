<?php
/**
 * EPIs Disponíveis - Sistema de Gestão de EPIs Klarbyte
 * Página para funcionários visualizarem EPIs disponíveis
 */

// Configurações da página
$page_title = "EPIs Disponíveis";
$panel_type = "Painel do Funcionário";
$is_admin = false;
$user_name = "Funcionário";

// Incluir conexão com banco de dados
require_once '../config/database.php';

// Parâmetros de filtro
$search = $_GET['search'] ?? '';
$filter_categoria = $_GET['categoria'] ?? '';
$filter_disponivel = $_GET['disponivel'] ?? '1';

// Construir query de busca
$where_conditions = ["e.ativo = 1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(e.nome LIKE ? OR e.descricao LIKE ? OR e.categoria LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if (!empty($filter_categoria)) {
    $where_conditions[] = "e.categoria = ?";
    $params[] = $filter_categoria;
}

if ($filter_disponivel == '1') {
    $where_conditions[] = "e.quantidade_estoque > 0";
}

$where_clause = implode(' AND ', $where_conditions);

try {
    // Buscar EPIs
    $epis = executeQuery("
        SELECT e.*, f.nome as fornecedor_nome,
               CASE 
                   WHEN e.quantidade_estoque = 0 THEN 'Esgotado'
                   WHEN e.quantidade_estoque <= e.quantidade_minima THEN 'Estoque Baixo'
                   WHEN e.validade < CURDATE() THEN 'Vencido'
                   WHEN e.validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Vencimento Próximo'
                   ELSE 'Disponível'
               END as status_disponibilidade,
               DATEDIFF(e.validade, CURDATE()) as dias_para_vencer
        FROM epis e
        LEFT JOIN fornecedores f ON e.fornecedor_id = f.id
        WHERE $where_clause
        ORDER BY e.quantidade_estoque DESC, e.nome
    ", $params);
    
    // Buscar categorias para filtro
    $categorias = executeQuery("
        SELECT DISTINCT categoria 
        FROM epis 
        WHERE categoria IS NOT NULL AND categoria != '' AND ativo = 1
        ORDER BY categoria
    ");
    
    // Estatísticas
    $stats = executeQuery("
        SELECT 
            COUNT(*) as total_epis,
            SUM(CASE WHEN quantidade_estoque > 0 THEN 1 ELSE 0 END) as disponiveis,
            SUM(CASE WHEN quantidade_estoque = 0 THEN 1 ELSE 0 END) as esgotados,
            SUM(CASE WHEN validade < CURDATE() THEN 1 ELSE 0 END) as vencidos
        FROM epis 
        WHERE ativo = 1
    ")[0];
    
} catch (Exception $e) {
    $epis = [];
    $categorias = [];
    $stats = ['total_epis' => 0, 'disponiveis' => 0, 'esgotados' => 0, 'vencidos' => 0];
}

// Incluir header
include '../includes/header.php';
?>

<!-- Estatísticas de EPIs -->
<div class="dashboard-grid">
    <div class="stat-card" style="border-left-color: #007bff;">
        <div class="stat-number" style="color: #007bff;"><?php echo $stats['total_epis']; ?></div>
        <div class="stat-label">Total de EPIs</div>
    </div>
    
    <div class="stat-card" style="border-left-color: #28a745;">
        <div class="stat-number" style="color: #28a745;"><?php echo $stats['disponiveis']; ?></div>
        <div class="stat-label">Disponíveis</div>
    </div>
    
    <div class="stat-card" style="border-left-color: #dc3545;">
        <div class="stat-number" style="color: #dc3545;"><?php echo $stats['esgotados']; ?></div>
        <div class="stat-label">Esgotados</div>
    </div>
    
    <div class="stat-card" style="border-left-color: #ffc107;">
        <div class="stat-number" style="color: #ffc107;"><?php echo $stats['vencidos']; ?></div>
        <div class="stat-label">Vencidos</div>
    </div>
</div>

<!-- Filtros e Pesquisa -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">EPIs Disponíveis para Retirada</h3>
    </div>
    <div class="card-body">
        <div class="filters">
            <div class="search-box">
                <label class="form-label" for="search">Pesquisar EPI</label>
                <input type="text" id="search" class="form-control search-input" 
                       placeholder="Nome, descrição ou categoria..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="filter_categoria">Categoria</label>
                <select id="filter_categoria" class="form-control">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['categoria']); ?>"
                                <?php echo $filter_categoria == $cat['categoria'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['categoria']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="filter_disponivel">Mostrar</label>
                <select id="filter_disponivel" class="form-control">
                    <option value="1" <?php echo $filter_disponivel == '1' ? 'selected' : ''; ?>>
                        Apenas Disponíveis
                    </option>
                    <option value="0" <?php echo $filter_disponivel == '0' ? 'selected' : ''; ?>>
                        Todos os EPIs
                    </option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-primary" onclick="applyFilters()">Filtrar</button>
                <a href="epis.php" class="btn btn-secondary">Limpar</a>
            </div>
        </div>
        
        <!-- Grid de EPIs -->
        <div id="epis-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; margin-top: 1rem;">
            <?php if (empty($epis)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
                    <div class="alert alert-info">
                        <strong>Nenhum EPI encontrado</strong><br>
                        Não há EPIs disponíveis com os filtros selecionados.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($epis as $epi): ?>
                <div class="card epi-card" style="margin-bottom: 0;">
                    <div class="card-body">
                        <!-- Header do EPI -->
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <h5 style="margin: 0; color: #007bff;">
                                <?php echo htmlspecialchars($epi['nome']); ?>
                            </h5>
                            <div>
                                <?php if ($epi['status_disponibilidade'] == 'Disponível'): ?>
                                    <span class="badge badge-success">Disponível</span>
                                <?php elseif ($epi['status_disponibilidade'] == 'Estoque Baixo'): ?>
                                    <span class="badge badge-warning">Estoque Baixo</span>
                                <?php elseif ($epi['status_disponibilidade'] == 'Esgotado'): ?>
                                    <span class="badge badge-danger">Esgotado</span>
                                <?php elseif ($epi['status_disponibilidade'] == 'Vencido'): ?>
                                    <span class="badge badge-danger">Vencido</span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><?php echo $epi['status_disponibilidade']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Informações do EPI -->
                        <div style="margin-bottom: 1rem;">
                            <?php if (!empty($epi['categoria'])): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong>Categoria:</strong> 
                                    <span class="badge badge-info"><?php echo htmlspecialchars($epi['categoria']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($epi['descricao'])): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong>Descrição:</strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($epi['descricao']); ?></small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($epi['numero_ca'])): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong>CA:</strong> <?php echo htmlspecialchars($epi['numero_ca']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div style="margin-bottom: 0.5rem;">
                                <strong>Estoque:</strong> 
                                <span class="stock-quantity" data-minimum="<?php echo $epi['quantidade_minima']; ?>">
                                    <?php echo $epi['quantidade_estoque']; ?>
                                </span>
                                <small class="text-muted">(mín: <?php echo $epi['quantidade_minima']; ?>)</small>
                            </div>
                            
                            <?php if (!empty($epi['validade'])): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong>Validade:</strong> 
                                    <span class="expiration-date">
                                        <?php echo date('d/m/Y', strtotime($epi['validade'])); ?>
                                    </span>
                                    <?php if ($epi['dias_para_vencer'] <= 30): ?>
                                        <small class="text-warning">
                                            (<?php echo $epi['dias_para_vencer']; ?> dias)
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div style="margin-bottom: 0.5rem;">
                                <strong>Classificação:</strong> 
                                <span class="badge <?php echo $epi['classificacao'] == 'novo' ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo ucfirst($epi['classificacao']); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($epi['fornecedor_nome'])): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong>Fornecedor:</strong> 
                                    <small><?php echo htmlspecialchars($epi['fornecedor_nome']); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Ações -->
                        <div style="border-top: 1px solid #dee2e6; padding-top: 1rem;">
                            <?php if ($epi['quantidade_estoque'] > 0 && $epi['status_disponibilidade'] != 'Vencido'): ?>
                                <a href="retirar.php?epi_id=<?php echo $epi['id']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    Retirar EPI
                                </a>
                                <button onclick="showEpiDetails(<?php echo $epi['id']; ?>)" 
                                        class="btn btn-info btn-sm">
                                    Ver Detalhes
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>
                                    Indisponível
                                </button>
                                <button onclick="showEpiDetails(<?php echo $epi['id']; ?>)" 
                                        class="btn btn-info btn-sm">
                                    Ver Detalhes
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
            <a href="retirar.php" class="btn btn-primary" style="padding: 1rem; text-align: center;">
                Retirar EPI
            </a>
            <a href="devolver.php" class="btn btn-warning" style="padding: 1rem; text-align: center;">
                Devolver EPI
            </a>
            <a href="movimentacoes.php" class="btn btn-info" style="padding: 1rem; text-align: center;">
                Meu Histórico
            </a>
            <a href="index.php" class="btn btn-secondary" style="padding: 1rem; text-align: center;">
                Voltar ao Dashboard
            </a>
        </div>
    </div>
</div>

<?php
// Script adicional para a página
$additional_scripts = "
<script>
function applyFilters() {
    const search = document.getElementById('search').value;
    const categoria = document.getElementById('filter_categoria').value;
    const disponivel = document.getElementById('filter_disponivel').value;
    
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (categoria) params.set('categoria', categoria);
    if (disponivel) params.set('disponivel', disponivel);
    
    window.location.href = 'epis.php?' + params.toString();
}

function showEpiDetails(epiId) {
    // Buscar dados do EPI via API ou mostrar modal com informações
    const cards = document.querySelectorAll('.epi-card');
    let epiData = null;
    
    cards.forEach(card => {
        const retirarLink = card.querySelector('a[href*=\"epi_id=' + epiId + '\"]');
        if (retirarLink) {
            const cardBody = card.querySelector('.card-body');
            const titulo = cardBody.querySelector('h5').textContent;
            const conteudo = cardBody.innerHTML;
            
            showModal('Detalhes do EPI: ' + titulo, conteudo);
        }
    });
}

// Filtro em tempo real
document.getElementById('search').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const cards = document.querySelectorAll('.epi-card');
    
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(searchTerm) ? 'block' : 'none';
    });
});

// Animação de entrada para os cartões
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.epi-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.3s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Responsividade do grid
function adjustGrid() {
    const grid = document.getElementById('epis-grid');
    const width = window.innerWidth;
    
    if (width < 768) {
        grid.style.gridTemplateColumns = '1fr';
    } else if (width < 1024) {
        grid.style.gridTemplateColumns = 'repeat(2, 1fr)';
    } else {
        grid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(300px, 1fr))';
    }
}

window.addEventListener('resize', adjustGrid);
adjustGrid();
</script>

<style>
.epi-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.epi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

@media (max-width: 768px) {
    #epis-grid {
        grid-template-columns: 1fr !important;
    }
    
    .filters {
        flex-direction: column;
    }
    
    .filters .form-group {
        margin-bottom: 1rem;
    }
}
</style>
";

// Incluir footer
include '../includes/footer.php';
?>