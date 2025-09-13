<?php
/**
 * Gerenciamento de EPIs - Sistema de Gestão de EPIs Klarbyte
 * Página para cadastro, edição e listagem de EPIs
 */

// Configurações da página
$page_title = "Gerenciamento de EPIs";
$panel_type = "Painel Administrativo";
$is_admin = true;
$user_name = "Administrador";

// Incluir conexão com banco de dados
require_once '../config/database.php';
require_once '../includes/request_handler.php';

// Processar ações (cadastro, edição, exclusão)
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $id = $_POST['id'] ?? null;
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $numero_ca = trim($_POST['numero_ca'] ?? '');
        $fornecedor_id = !empty($_POST['fornecedor_id']) ? (int)$_POST['fornecedor_id'] : null;
        $quantidade_estoque = (int)($_POST['quantidade_estoque'] ?? 0);
        $quantidade_minima = (int)($_POST['quantidade_minima'] ?? 10);
        $classificacao = $_POST['classificacao'] ?? 'novo';
        $validade = !empty($_POST['validade']) ? $_POST['validade'] : null;
        $preco_unitario = !empty($_POST['preco_unitario']) ? (float)$_POST['preco_unitario'] : null;
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if (empty($nome)) {
            $message = 'Nome do EPI é obrigatório.';
            $message_type = 'danger';
        } else {
            // Preparar dados para o request handler
            $data = [
                'nome' => $nome,
                'descricao' => $descricao,
                'categoria' => $categoria,
                'numero_ca' => $numero_ca,
                'fornecedor_id' => $fornecedor_id,
                'quantidade_estoque' => $quantidade_estoque,
                'quantidade_minima' => $quantidade_minima,
                'classificacao' => $classificacao,
                'validade' => $validade,
                'preco_unitario' => $preco_unitario,
                'observacoes' => $observacoes
            ];
            
            if ($action === 'create') {
                $operation = function($data) {
                    $success = executeUpdate(
                        "INSERT INTO epis (nome, descricao, categoria, numero_ca, fornecedor_id, quantidade_estoque, quantidade_minima, classificacao, validade, preco_unitario, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$data['nome'], $data['descricao'], $data['categoria'], $data['numero_ca'], $data['fornecedor_id'], $data['quantidade_estoque'], $data['quantidade_minima'], $data['classificacao'], $data['validade'], $data['preco_unitario'], $data['observacoes']]
                    );
                    return $success ? 
                        ['success' => true, 'message' => 'EPI cadastrado com sucesso!', 'data' => ['id' => getLastInsertId()]] :
                        ['success' => false, 'error' => 'Erro ao cadastrar EPI no banco de dados'];
                };
                
                $result = RequestHandler::executeWithRetry('epi_create', $data, $operation);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'danger';
                
                if (!$result['success']) {
                    $message .= ' (Request ID: ' . $result['request_id'] . ')';
                }
            } else {
                $data['id'] = $id;
                $operation = function($data) {
                    $success = executeUpdate(
                        "UPDATE epis SET nome=?, descricao=?, categoria=?, numero_ca=?, fornecedor_id=?, quantidade_estoque=?, quantidade_minima=?, classificacao=?, validade=?, preco_unitario=?, observacoes=?, updated_at=CURRENT_TIMESTAMP WHERE id=?",
                        [$data['nome'], $data['descricao'], $data['categoria'], $data['numero_ca'], $data['fornecedor_id'], $data['quantidade_estoque'], $data['quantidade_minima'], $data['classificacao'], $data['validade'], $data['preco_unitario'], $data['observacoes'], $data['id']]
                    );
                    return $success ? 
                        ['success' => true, 'message' => 'EPI atualizado com sucesso!'] :
                        ['success' => false, 'error' => 'Erro ao atualizar EPI no banco de dados'];
                };
                
                $result = RequestHandler::executeWithRetry('epi_update', $data, $operation);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'danger';
                
                if (!$result['success']) {
                    $message .= ' (Request ID: ' . $result['request_id'] . ')';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $operation = function($data) {
                $success = executeUpdate(
                    "UPDATE epis SET ativo = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                    [$data['id']]
                );
                return $success ? 
                    ['success' => true, 'message' => 'EPI excluído com sucesso!'] :
                    ['success' => false, 'error' => 'Erro ao excluir EPI'];
            };
            
            $result = RequestHandler::executeWithRetry('epi_delete', ['id' => $id], $operation);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'danger';
            
            if (!$result['success']) {
                $message .= ' (Request ID: ' . $result['request_id'] . ')';
            }
        } else {
            $message = 'ID inválido para exclusão.';
            $message_type = 'danger';
        }
    }
}

// Buscar dados para exibição
$search = $_GET['search'] ?? '';
$filter_categoria = $_GET['categoria'] ?? '';
$filter_classificacao = $_GET['classificacao'] ?? '';
$order = $_GET['order'] ?? 'nome';

// Construir query de busca
$where_conditions = ["ativo = 1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(nome LIKE ? OR descricao LIKE ? OR numero_ca LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if (!empty($filter_categoria)) {
    $where_conditions[] = "categoria = ?";
    $params[] = $filter_categoria;
}

if (!empty($filter_classificacao)) {
    $where_conditions[] = "classificacao = ?";
    $params[] = $filter_classificacao;
}

$where_clause = implode(' AND ', $where_conditions);

// Buscar EPIs
$epis = executeQuery("
    SELECT e.*, f.nome as fornecedor_nome 
    FROM epis e 
    LEFT JOIN fornecedores f ON e.fornecedor_id = f.id 
    WHERE $where_clause 
    ORDER BY $order
", $params);

// Buscar fornecedores para o formulário
$fornecedores = executeQuery("SELECT id, nome FROM fornecedores ORDER BY nome");

// Buscar categorias existentes
$categorias = executeQuery("SELECT DISTINCT categoria FROM epis WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria");

// EPI para edição
$editing_epi = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $editing_result = executeQuery("SELECT * FROM epis WHERE id = ? AND ativo = 1", [$edit_id]);
    $editing_epi = !empty($editing_result) ? $editing_result[0] : null;
}

// Incluir header
include '../includes/header.php';
?>

<!-- Mensagem de feedback -->
<?php if (!empty($message)): ?>
<div class="alert alert-<?php echo $message_type; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<!-- Formulário de Cadastro/Edição -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <?php echo $editing_epi ? 'Editar EPI' : 'Cadastrar Novo EPI'; ?>
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="<?php echo $editing_epi ? 'update' : 'create'; ?>">
            <?php if ($editing_epi): ?>
                <input type="hidden" name="id" value="<?php echo $editing_epi['id']; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="nome">Nome do EPI *</label>
                        <input type="text" id="nome" name="nome" class="form-control" required
                               value="<?php echo htmlspecialchars($editing_epi['nome'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="categoria">Categoria</label>
                        <input type="text" id="categoria" name="categoria" class="form-control" list="categorias"
                               value="<?php echo htmlspecialchars($editing_epi['categoria'] ?? ''); ?>">
                        <datalist id="categorias">
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['categoria']); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao" class="form-control" rows="3"><?php echo htmlspecialchars($editing_epi['descricao'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="numero_ca">Número CA</label>
                        <input type="text" id="numero_ca" name="numero_ca" class="form-control"
                               value="<?php echo htmlspecialchars($editing_epi['numero_ca'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="fornecedor_id">Fornecedor</label>
                        <select id="fornecedor_id" name="fornecedor_id" class="form-control">
                            <option value="">Selecione um fornecedor</option>
                            <?php foreach ($fornecedores as $fornecedor): ?>
                                <option value="<?php echo $fornecedor['id']; ?>" 
                                        <?php echo ($editing_epi['fornecedor_id'] ?? '') == $fornecedor['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($fornecedor['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="quantidade_estoque">Quantidade em Estoque</label>
                        <input type="number" id="quantidade_estoque" name="quantidade_estoque" class="form-control" min="0"
                               value="<?php echo $editing_epi['quantidade_estoque'] ?? 0; ?>">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="quantidade_minima">Quantidade Mínima</label>
                        <input type="number" id="quantidade_minima" name="quantidade_minima" class="form-control" min="1"
                               value="<?php echo $editing_epi['quantidade_minima'] ?? 10; ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="classificacao">Classificação</label>
                        <select id="classificacao" name="classificacao" class="form-control">
                            <option value="novo" <?php echo ($editing_epi['classificacao'] ?? 'novo') == 'novo' ? 'selected' : ''; ?>>Novo</option>
                            <option value="usado" <?php echo ($editing_epi['classificacao'] ?? '') == 'usado' ? 'selected' : ''; ?>>Usado</option>
                        </select>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="validade">Data de Validade</label>
                        <input type="date" id="validade" name="validade" class="form-control"
                               value="<?php echo $editing_epi['validade'] ?? ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="preco_unitario">Preço Unitário (R$)</label>
                        <input type="number" id="preco_unitario" name="preco_unitario" class="form-control" min="0" step="0.01"
                               value="<?php echo $editing_epi['preco_unitario'] ?? ''; ?>">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="observacoes">Observações</label>
                        <textarea id="observacoes" name="observacoes" class="form-control" rows="3"><?php echo htmlspecialchars($editing_epi['observacoes'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <?php echo $editing_epi ? 'Atualizar EPI' : 'Cadastrar EPI'; ?>
                </button>
                <?php if ($editing_epi): ?>
                    <a href="epis.php" class="btn btn-secondary">Cancelar Edição</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Filtros e Pesquisa -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de EPIs</h3>
    </div>
    <div class="card-body">
        <div class="filters">
            <div class="search-box">
                <label class="form-label" for="search">Pesquisar</label>
                <input type="text" id="search" class="form-control search-input" placeholder="Nome, descrição ou CA..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="filter_categoria">Categoria</label>
                <select id="filter_categoria" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['categoria']); ?>"
                                <?php echo $filter_categoria == $cat['categoria'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['categoria']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="filter_classificacao">Classificação</label>
                <select id="filter_classificacao" class="form-control">
                    <option value="">Todas</option>
                    <option value="novo" <?php echo $filter_classificacao == 'novo' ? 'selected' : ''; ?>>Novo</option>
                    <option value="usado" <?php echo $filter_classificacao == 'usado' ? 'selected' : ''; ?>>Usado</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-primary" onclick="applyFilters()">Filtrar</button>
            </div>
        </div>
        
        <!-- Tabela de EPIs -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><a href="?order=nome&<?php echo http_build_query($_GET); ?>">Nome</a></th>
                        <th>Categoria</th>
                        <th>CA</th>
                        <th>Estoque</th>
                        <th>Mínimo</th>
                        <th>Classificação</th>
                        <th>Validade</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($epis)): ?>
                        <tr>
                            <td colspan="9" class="text-center">Nenhum EPI encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($epis as $epi): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($epi['nome']); ?></strong>
                                <?php if (!empty($epi['descricao'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($epi['descricao'], 0, 50)); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($epi['categoria'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($epi['numero_ca'] ?? '-'); ?></td>
                            <td class="stock-quantity" data-minimum="<?php echo $epi['quantidade_minima']; ?>">
                                <?php echo $epi['quantidade_estoque']; ?>
                            </td>
                            <td><?php echo $epi['quantidade_minima']; ?></td>
                            <td>
                                <span class="badge <?php echo $epi['classificacao'] == 'novo' ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo ucfirst($epi['classificacao']); ?>
                                </span>
                            </td>
                            <td class="expiration-date">
                                <?php if ($epi['validade']): ?>
                                    <?php echo date('d/m/Y', strtotime($epi['validade'])); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status = 'success';
                                $status_text = 'OK';
                                
                                if ($epi['quantidade_estoque'] <= $epi['quantidade_minima']) {
                                    $status = 'danger';
                                    $status_text = 'Estoque Baixo';
                                }
                                
                                if ($epi['validade'] && strtotime($epi['validade']) < time()) {
                                    $status = 'danger';
                                    $status_text = 'Vencido';
                                } elseif ($epi['validade'] && strtotime($epi['validade']) < strtotime('+30 days')) {
                                    $status = 'warning';
                                    $status_text = 'Vencimento Próximo';
                                }
                                ?>
                                <span class="badge badge-<?php echo $status; ?>"><?php echo $status_text; ?></span>
                            </td>
                            <td>
                                <a href="?edit=<?php echo $epi['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                <button onclick="confirmDelete(<?php echo $epi['id']; ?>, '<?php echo htmlspecialchars($epi['nome']); ?>')" 
                                        class="btn btn-danger btn-sm">Excluir</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Form oculto para exclusão -->
<form id="delete-form" method="POST" action="" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete-id">
</form>

<?php
// Script adicional para a página
$additional_scripts = "
<script>
function applyFilters() {
    const search = document.getElementById('search').value;
    const categoria = document.getElementById('filter_categoria').value;
    const classificacao = document.getElementById('filter_classificacao').value;
    
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (categoria) params.set('categoria', categoria);
    if (classificacao) params.set('classificacao', classificacao);
    
    window.location.href = 'epis.php?' + params.toString();
}

function confirmDelete(id, nome) {
    if (confirm('Tem certeza que deseja excluir o EPI \"' + nome + '\"?')) {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-form').submit();
    }
}

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