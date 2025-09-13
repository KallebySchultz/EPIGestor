<?php
require_once 'config.php';
verificarSessao();

$database = new Database();
$db = $database->getConnection();

// Processar movimentação
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'movimentar') {
    $epi_id = (int)$_POST['epi_id'];
    $tipo = $_POST['tipo_movimentacao'];
    $quantidade = (int)$_POST['quantidade'];
    $responsavel = sanitizar($_POST['responsavel']);
    $empresa = sanitizar($_POST['empresa']);
    $observacoes = sanitizar($_POST['observacoes']);
    
    // Buscar EPI atual
    $query = "SELECT saldo_estoque FROM epis WHERE id = ? AND ativo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$epi_id]);
    $epi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($epi) {
        $saldo_atual = $epi['saldo_estoque'];
        $novo_saldo = $saldo_atual;
        
        // Calcular novo saldo baseado no tipo de movimentação
        switch ($tipo) {
            case 'entrada':
            case 'devolucao':
                $novo_saldo = $saldo_atual + $quantidade;
                break;
            case 'retirada':
            case 'descarte':
                if ($quantidade > $saldo_atual) {
                    $erro = "Quantidade indisponível em estoque. Saldo atual: $saldo_atual";
                } else {
                    $novo_saldo = $saldo_atual - $quantidade;
                }
                break;
        }
        
        if (!isset($erro)) {
            try {
                $db->beginTransaction();
                
                // Inserir movimentação
                $query = "INSERT INTO movimentacoes (epi_id, tipo_movimentacao, quantidade, responsavel, empresa, observacoes, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$epi_id, $tipo, $quantidade, $responsavel, $empresa, $observacoes, $_SESSION['usuario_id']]);
                
                // Atualizar estoque
                $query = "UPDATE epis SET saldo_estoque = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$novo_saldo, $epi_id]);
                
                $db->commit();
                $sucesso = "Movimentação registrada com sucesso!";
            } catch (Exception $e) {
                $db->rollBack();
                $erro = "Erro ao registrar movimentação: " . $e->getMessage();
            }
        }
    } else {
        $erro = "EPI não encontrado.";
    }
}

// Buscar EPIs para o formulário
$query = "SELECT id, nome, saldo_estoque FROM epis WHERE ativo = 1 ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$epis_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar movimentações com pesquisa
$search = isset($_GET['search']) ? sanitizar($_GET['search']) : '';

$query = "SELECT m.*, e.nome as epi_nome, u.nome as usuario_nome 
          FROM movimentacoes m 
          JOIN epis e ON m.epi_id = e.id 
          JOIN usuarios u ON m.usuario_id = u.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (e.nome LIKE ? OR m.tipo_movimentacao LIKE ? OR m.responsavel LIKE ? OR m.empresa LIKE ? OR m.observacoes LIKE ?)";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
}

$query .= " ORDER BY m.data_movimentacao DESC LIMIT 100";

$stmt = $db->prepare($query);
$stmt->execute($params);
$movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimentações - Gestão de EPIs Klarbyte</title>
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
                <li class="nav-item"><a href="epis.php">EPIs</a></li>
                <li class="nav-item active"><a href="movimentacoes.php">Movimentações</a></li>
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

        <!-- Formulário de movimentação -->
        <div class="card">
            <div class="card-header">Nova Movimentação</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="movimentar">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="epi_id">EPI:</label>
                            <select id="epi_id" name="epi_id" class="form-control" required onchange="atualizarEstoque()">
                                <option value="">Selecione um EPI</option>
                                <?php foreach ($epis_lista as $epi): ?>
                                    <option value="<?php echo $epi['id']; ?>" data-estoque="<?php echo $epi['saldo_estoque']; ?>">
                                        <?php echo $epi['nome']; ?> (Estoque: <?php echo $epi['saldo_estoque']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo_movimentacao">Tipo de Movimentação:</label>
                            <select id="tipo_movimentacao" name="tipo_movimentacao" class="form-control" required onchange="toggleCampos()">
                                <option value="">Selecione</option>
                                <option value="entrada">Entrada</option>
                                <option value="retirada">Retirada</option>
                                <option value="devolucao">Devolução</option>
                                <option value="descarte">Descarte</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantidade">Quantidade:</label>
                            <input type="number" id="quantidade" name="quantidade" class="form-control" min="1" required>
                            <small id="estoqueInfo" class="form-text"></small>
                        </div>
                    </div>
                    
                    <div class="form-row" id="camposRetirada" style="display: none;">
                        <div class="form-group">
                            <label for="responsavel">Responsável:</label>
                            <input type="text" id="responsavel" name="responsavel" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="empresa">Empresa:</label>
                            <input type="text" id="empresa" name="empresa" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="observacoes">Observações:</label>
                        <textarea id="observacoes" name="observacoes" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Registrar Movimentação</button>
                </form>
            </div>
        </div>

        <!-- Filtros e histórico -->
        <div class="card">
            <div class="card-header">Histórico de Movimentações</div>
            <div class="card-body">
                <!-- Formulário de busca -->
                <form method="GET" style="margin-bottom: 1rem;">
                    <div style="display: flex; gap: 1rem; align-items: end;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label for="search">Buscar Movimentação:</label>
                            <input type="text" id="search" name="search" class="form-control" 
                                   value="<?php echo $search; ?>" placeholder="EPI, tipo, responsável, empresa ou observações">
                        </div>
                        <button type="submit" class="btn btn-primary">Buscar</button>
                        <?php if ($search): ?>
                            <a href="movimentacoes.php" class="btn btn-warning">Limpar</a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Tabela de movimentações -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>EPI</th>
                                <th>Tipo</th>
                                <th>Quantidade</th>
                                <th>Responsável</th>
                                <th>Empresa</th>
                                <th>Usuário</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimentacoes as $mov): ?>
                            <tr>
                                <td><?php echo formatarDataHora($mov['data_movimentacao']); ?></td>
                                <td><?php echo $mov['epi_nome']; ?></td>
                                <td>
                                    <?php
                                    $tipos = [
                                        'entrada' => '<span class="status-badge status-ok">Entrada</span>',
                                        'retirada' => '<span class="status-badge status-warning">Retirada</span>',
                                        'devolucao' => '<span class="status-badge status-ok">Devolução</span>',
                                        'descarte' => '<span class="status-badge status-danger">Descarte</span>'
                                    ];
                                    echo $tipos[$mov['tipo_movimentacao']];
                                    ?>
                                </td>
                                <td><?php echo $mov['quantidade']; ?></td>
                                <td><?php echo $mov['responsavel'] ?: '-'; ?></td>
                                <td><?php echo $mov['empresa'] ?: '-'; ?></td>
                                <td><?php echo $mov['usuario_nome']; ?></td>
                                <td><?php echo $mov['observacoes'] ?: '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($movimentacoes) == 0): ?>
                    <p>Nenhuma movimentação encontrada.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleCampos() {
            const tipo = document.getElementById('tipo_movimentacao').value;
            const camposRetirada = document.getElementById('camposRetirada');
            const responsavel = document.getElementById('responsavel');
            const empresa = document.getElementById('empresa');
            
            if (tipo === 'retirada') {
                camposRetirada.style.display = 'block';
                responsavel.required = true;
                empresa.required = true;
            } else {
                camposRetirada.style.display = 'none';
                responsavel.required = false;
                empresa.required = false;
            }
        }
        
        function atualizarEstoque() {
            const select = document.getElementById('epi_id');
            const info = document.getElementById('estoqueInfo');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                const estoque = option.dataset.estoque;
                info.textContent = `Estoque disponível: ${estoque}`;
                document.getElementById('quantidade').max = estoque;
            } else {
                info.textContent = '';
                document.getElementById('quantidade').removeAttribute('max');
            }
        }
    </script>
</body>
</html>