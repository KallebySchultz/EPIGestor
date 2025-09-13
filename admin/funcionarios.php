<?php
/**
 * Gerenciamento de Funcionários - Sistema de Gestão de EPIs Klarbyte
 * Página para cadastro, edição e listagem de funcionários
 */

// Configurações da página
$page_title = "Gerenciamento de Funcionários";
$panel_type = "Painel Administrativo";
$is_admin = true;
$user_name = "Administrador";

// Incluir conexão com banco de dados
require_once '../config/database.php';

// Processar ações (cadastro, edição, exclusão)
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $id = $_POST['id'] ?? null;
        $nome = trim($_POST['nome'] ?? '');
        $cpf = trim($_POST['cpf'] ?? '');
        $empresa_id = !empty($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : null;
        $cargo = trim($_POST['cargo'] ?? '');
        $setor = trim($_POST['setor'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($nome)) {
            $message = 'Nome do funcionário é obrigatório.';
            $message_type = 'danger';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Email inválido.';
            $message_type = 'danger';
        } else {
            if ($action === 'create') {
                $success = executeUpdate(
                    "INSERT INTO funcionarios (nome, cpf, empresa_id, cargo, setor, telefone, email) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$nome, $cpf, $empresa_id, $cargo, $setor, $telefone, $email]
                );
                $message = $success ? 'Funcionário cadastrado com sucesso!' : 'Erro ao cadastrar funcionário.';
                $message_type = $success ? 'success' : 'danger';
            } else {
                $success = executeUpdate(
                    "UPDATE funcionarios SET nome=?, cpf=?, empresa_id=?, cargo=?, setor=?, telefone=?, email=?, updated_at=CURRENT_TIMESTAMP WHERE id=?",
                    [$nome, $cpf, $empresa_id, $cargo, $setor, $telefone, $email, $id]
                );
                $message = $success ? 'Funcionário atualizado com sucesso!' : 'Erro ao atualizar funcionário.';
                $message_type = $success ? 'success' : 'danger';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $success = executeUpdate("UPDATE funcionarios SET ativo = 0 WHERE id = ?", [$id]);
            $message = $success ? 'Funcionário removido com sucesso!' : 'Erro ao remover funcionário.';
            $message_type = $success ? 'success' : 'danger';
        }
    }
}

// Buscar dados para exibição
$search = $_GET['search'] ?? '';
$filter_empresa = $_GET['empresa'] ?? '';
$filter_ativo = $_GET['ativo'] ?? '1';

// Construir query de busca
$where_conditions = [];
$params = [];

if ($filter_ativo !== '') {
    $where_conditions[] = "f.ativo = ?";
    $params[] = $filter_ativo;
}

if (!empty($search)) {
    $where_conditions[] = "(f.nome LIKE ? OR f.cpf LIKE ? OR f.cargo LIKE ? OR f.setor LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($filter_empresa)) {
    $where_conditions[] = "f.empresa_id = ?";
    $params[] = $filter_empresa;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Buscar funcionários
$funcionarios = executeQuery("
    SELECT f.*, e.nome as empresa_nome,
           (SELECT COUNT(*) FROM movimentacoes m WHERE m.funcionario_id = f.id) as total_movimentacoes
    FROM funcionarios f 
    LEFT JOIN empresas e ON f.empresa_id = e.id 
    $where_clause 
    ORDER BY f.nome
", $params);

// Buscar empresas para o formulário
$empresas = executeQuery("SELECT id, nome FROM empresas ORDER BY nome");

// Funcionário para edição
$editing_funcionario = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $editing_result = executeQuery("SELECT * FROM funcionarios WHERE id = ?", [$edit_id]);
    $editing_funcionario = !empty($editing_result) ? $editing_result[0] : null;
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
            <?php echo $editing_funcionario ? 'Editar Funcionário' : 'Cadastrar Novo Funcionário'; ?>
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="<?php echo $editing_funcionario ? 'update' : 'create'; ?>">
            <?php if ($editing_funcionario): ?>
                <input type="hidden" name="id" value="<?php echo $editing_funcionario['id']; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="nome">Nome Completo *</label>
                        <input type="text" id="nome" name="nome" class="form-control" required
                               value="<?php echo htmlspecialchars($editing_funcionario['nome'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="cpf">CPF</label>
                        <input type="text" id="cpf" name="cpf" class="form-control" maxlength="14"
                               value="<?php echo htmlspecialchars($editing_funcionario['cpf'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="empresa_id">Empresa</label>
                        <select id="empresa_id" name="empresa_id" class="form-control">
                            <option value="">Selecione uma empresa</option>
                            <?php foreach ($empresas as $empresa): ?>
                                <option value="<?php echo $empresa['id']; ?>" 
                                        <?php echo ($editing_funcionario['empresa_id'] ?? '') == $empresa['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($empresa['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="cargo">Cargo</label>
                        <input type="text" id="cargo" name="cargo" class="form-control"
                               value="<?php echo htmlspecialchars($editing_funcionario['cargo'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="setor">Setor</label>
                        <input type="text" id="setor" name="setor" class="form-control"
                               value="<?php echo htmlspecialchars($editing_funcionario['setor'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="telefone">Telefone</label>
                        <input type="tel" id="telefone" name="telefone" class="form-control"
                               value="<?php echo htmlspecialchars($editing_funcionario['telefone'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($editing_funcionario['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <?php echo $editing_funcionario ? 'Atualizar Funcionário' : 'Cadastrar Funcionário'; ?>
                </button>
                <?php if ($editing_funcionario): ?>
                    <a href="funcionarios.php" class="btn btn-secondary">Cancelar Edição</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Filtros e Pesquisa -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Funcionários</h3>
    </div>
    <div class="card-body">
        <div class="filters">
            <div class="search-box">
                <label class="form-label" for="search">Pesquisar</label>
                <input type="text" id="search" class="form-control search-input" placeholder="Nome, CPF, cargo ou setor..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="filter_empresa">Empresa</label>
                <select id="filter_empresa" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($empresas as $empresa): ?>
                        <option value="<?php echo $empresa['id']; ?>"
                                <?php echo $filter_empresa == $empresa['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($empresa['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="filter_ativo">Status</label>
                <select id="filter_ativo" class="form-control">
                    <option value="1" <?php echo $filter_ativo == '1' ? 'selected' : ''; ?>>Ativos</option>
                    <option value="0" <?php echo $filter_ativo == '0' ? 'selected' : ''; ?>>Inativos</option>
                    <option value="" <?php echo $filter_ativo == '' ? 'selected' : ''; ?>>Todos</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-primary" onclick="applyFilters()">Filtrar</button>
            </div>
        </div>
        
        <!-- Estatísticas -->
        <div class="dashboard-grid" style="margin-bottom: 1rem;">
            <div class="stat-card" style="border-left-color: #28a745;">
                <div class="stat-number" style="color: #28a745;"><?php echo count(array_filter($funcionarios, fn($f) => $f['ativo'] == 1)); ?></div>
                <div class="stat-label">Funcionários Ativos</div>
            </div>
            <div class="stat-card" style="border-left-color: #007bff;">
                <div class="stat-number" style="color: #007bff;"><?php echo count(array_unique(array_column($funcionarios, 'empresa_id'))); ?></div>
                <div class="stat-label">Empresas</div>
            </div>
            <div class="stat-card" style="border-left-color: #17a2b8;">
                <div class="stat-number" style="color: #17a2b8;"><?php echo array_sum(array_column($funcionarios, 'total_movimentacoes')); ?></div>
                <div class="stat-label">Total Movimentações</div>
            </div>
        </div>
        
        <!-- Tabela de Funcionários -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Empresa</th>
                        <th>Cargo</th>
                        <th>Setor</th>
                        <th>Contato</th>
                        <th>Movimentações</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($funcionarios)): ?>
                        <tr>
                            <td colspan="9" class="text-center">Nenhum funcionário encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($funcionarios as $funcionario): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($funcionario['nome']); ?></strong>
                                <br><small class="text-muted">ID: <?php echo $funcionario['id']; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($funcionario['cpf'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($funcionario['empresa_nome'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($funcionario['cargo'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($funcionario['setor'] ?? '-'); ?></td>
                            <td>
                                <?php if (!empty($funcionario['telefone'])): ?>
                                    <div><?php echo htmlspecialchars($funcionario['telefone']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($funcionario['email'])): ?>
                                    <div><small><?php echo htmlspecialchars($funcionario['email']); ?></small></div>
                                <?php endif; ?>
                                <?php if (empty($funcionario['telefone']) && empty($funcionario['email'])): ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info"><?php echo $funcionario['total_movimentacoes']; ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $funcionario['ativo'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $funcionario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="?edit=<?php echo $funcionario['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                <a href="movimentacoes.php?funcionario=<?php echo $funcionario['id']; ?>" class="btn btn-info btn-sm">Histórico</a>
                                <?php if ($funcionario['ativo']): ?>
                                    <button onclick="confirmDelete(<?php echo $funcionario['id']; ?>, '<?php echo htmlspecialchars($funcionario['nome']); ?>')" 
                                            class="btn btn-danger btn-sm">Desativar</button>
                                <?php endif; ?>
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
    const empresa = document.getElementById('filter_empresa').value;
    const ativo = document.getElementById('filter_ativo').value;
    
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (empresa) params.set('empresa', empresa);
    if (ativo !== '') params.set('ativo', ativo);
    
    window.location.href = 'funcionarios.php?' + params.toString();
}

function confirmDelete(id, nome) {
    if (confirm('Tem certeza que deseja desativar o funcionário \"' + nome + '\"?')) {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-form').submit();
    }
}

// Aplicar máscara de CPF
document.getElementById('cpf').addEventListener('input', function() {
    let value = this.value.replace(/\D/g, '');
    value = value.replace(/(\d{3})(\d)/, '\$1.\$2');
    value = value.replace(/(\d{3})(\d)/, '\$1.\$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '\$1-\$2');
    this.value = value;
});

// Aplicar máscara de telefone
document.getElementById('telefone').addEventListener('input', function() {
    let value = this.value.replace(/\D/g, '');
    if (value.length <= 10) {
        value = value.replace(/(\d{2})(\d)/, '(\$1) \$2');
        value = value.replace(/(\d{4})(\d)/, '\$1-\$2');
    } else {
        value = value.replace(/(\d{2})(\d)/, '(\$1) \$2');
        value = value.replace(/(\d{5})(\d)/, '\$1-\$2');
    }
    this.value = value;
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