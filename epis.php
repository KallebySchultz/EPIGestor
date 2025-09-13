<?php
require_once 'config.php';
verificarSessao();

$database = new Database();
$db = $database->getConnection();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'cadastrar':
                $nome = sanitizar($_POST['nome']);
                $descricao = sanitizar($_POST['descricao']);
                $validade = $_POST['validade'];
                $quantidade_minima = (int)$_POST['quantidade_minima'];
                $saldo_estoque = (int)$_POST['saldo_estoque'];
                
                $query = "INSERT INTO epis (nome, descricao, validade, quantidade_minima, saldo_estoque) VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$nome, $descricao, $validade, $quantidade_minima, $saldo_estoque])) {
                    $sucesso = "EPI cadastrado com sucesso!";
                } else {
                    $erro = "Erro ao cadastrar EPI.";
                }
                break;
                
            case 'editar':
                $id = (int)$_POST['id'];
                $nome = sanitizar($_POST['nome']);
                $descricao = sanitizar($_POST['descricao']);
                $validade = $_POST['validade'];
                $quantidade_minima = (int)$_POST['quantidade_minima'];
                
                $query = "UPDATE epis SET nome = ?, descricao = ?, validade = ?, quantidade_minima = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$nome, $descricao, $validade, $quantidade_minima, $id])) {
                    $sucesso = "EPI atualizado com sucesso!";
                } else {
                    $erro = "Erro ao atualizar EPI.";
                }
                break;
                
            case 'excluir':
                $id = (int)$_POST['id'];
                
                $query = "UPDATE epis SET ativo = 0 WHERE id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$id])) {
                    $sucesso = "EPI excluído com sucesso!";
                } else {
                    $erro = "Erro ao excluir EPI.";
                }
                break;
        }
    }
}

// Buscar EPIs
$search = isset($_GET['search']) ? sanitizar($_GET['search']) : '';
$query = "SELECT * FROM epis WHERE ativo = 1";
if ($search) {
    $query .= " AND (nome LIKE ? OR descricao LIKE ?)";
}
$query .= " ORDER BY nome";

$stmt = $db->prepare($query);
if ($search) {
    $searchParam = "%$search%";
    $stmt->execute([$searchParam, $searchParam]);
} else {
    $stmt->execute();
}
$epis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar EPI para edição
$epi_editando = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $query = "SELECT * FROM epis WHERE id = ? AND ativo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $epi_editando = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPIs - Gestão de EPIs Klarbyte</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">Gestão de EPIs Klarbyte</div>
            <div class="user-info">
                <span>Bem-vindo, <?php echo $_SESSION['usuario_nome']; ?></span>
                <a href="logout.php" class="btn btn-sm btn-danger">Sair</a>
            </div>
        </div>
    </header>

    <nav class="nav">
        <div class="nav-content">
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="nav-item active"><a href="epis.php">EPIs</a></li>
                <li class="nav-item"><a href="movimentacoes.php">Movimentações</a></li>
                <li class="nav-item"><a href="usuarios.php">Usuários</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($sucesso)): ?>
            <div class="alert alert-success"><?php echo $sucesso; ?></div>
        <?php endif; ?>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>

        <!-- Formulário de cadastro/edição -->
        <div class="card">
            <div class="card-header">
                <?php echo $epi_editando ? 'Editar EPI' : 'Cadastrar Novo EPI'; ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="<?php echo $epi_editando ? 'editar' : 'cadastrar'; ?>">
                    <?php if ($epi_editando): ?>
                        <input type="hidden" name="id" value="<?php echo $epi_editando['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome">Nome do EPI:</label>
                            <input type="text" id="nome" name="nome" class="form-control" 
                                   value="<?php echo $epi_editando ? $epi_editando['nome'] : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="validade">Data de Validade:</label>
                            <input type="date" id="validade" name="validade" class="form-control" 
                                   value="<?php echo $epi_editando ? $epi_editando['validade'] : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição:</label>
                        <textarea id="descricao" name="descricao" class="form-control" rows="3"><?php echo $epi_editando ? $epi_editando['descricao'] : ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantidade_minima">Quantidade Mínima:</label>
                            <input type="number" id="quantidade_minima" name="quantidade_minima" class="form-control" 
                                   value="<?php echo $epi_editando ? $epi_editando['quantidade_minima'] : '0'; ?>" min="0" required>
                        </div>
                        
                        <?php if (!$epi_editando): ?>
                        <div class="form-group">
                            <label for="saldo_estoque">Saldo Inicial em Estoque:</label>
                            <input type="number" id="saldo_estoque" name="saldo_estoque" class="form-control" 
                                   value="0" min="0" required>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $epi_editando ? 'Atualizar' : 'Cadastrar'; ?>
                        </button>
                        
                        <?php if ($epi_editando): ?>
                            <a href="epis.php" class="btn btn-warning">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Busca e listagem -->
        <div class="card">
            <div class="card-header">EPIs Cadastrados</div>
            <div class="card-body">
                <!-- Formulário de busca -->
                <form method="GET" style="margin-bottom: 1rem;">
                    <div style="display: flex; gap: 1rem; align-items: end;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label for="search">Buscar EPI:</label>
                            <input type="text" id="search" name="search" class="form-control" 
                                   value="<?php echo $search; ?>" placeholder="Nome ou descrição">
                        </div>
                        <button type="submit" class="btn btn-primary">Buscar</button>
                        <?php if ($search): ?>
                            <a href="epis.php" class="btn btn-warning">Limpar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Tabela de EPIs -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Validade</th>
                                <th>Estoque</th>
                                <th>Mínimo</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($epis as $epi): ?>
                            <tr>
                                <td><?php echo $epi['nome']; ?></td>
                                <td><?php echo substr($epi['descricao'], 0, 50) . (strlen($epi['descricao']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo formatarData($epi['validade']); ?></td>
                                <td><?php echo $epi['saldo_estoque']; ?></td>
                                <td><?php echo $epi['quantidade_minima']; ?></td>
                                <td>
                                    <?php if (validadeVencida($epi['validade'])): ?>
                                        <span class="status-badge status-danger">Vencido</span>
                                    <?php elseif (estoqueMinimo($epi['saldo_estoque'], $epi['quantidade_minima'])): ?>
                                        <span class="status-badge status-warning">Estoque Baixo</span>
                                    <?php else: ?>
                                        <span class="status-badge status-ok">OK</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="epis.php?editar=<?php echo $epi['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este EPI?')">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?php echo $epi['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($epis) == 0): ?>
                    <p>Nenhum EPI encontrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>