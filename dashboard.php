<?php
require_once 'config.php';
verificarSessao();

$database = new Database();
$db = $database->getConnection();

// Estatísticas do dashboard
$query = "SELECT COUNT(*) as total_epis FROM epis WHERE ativo = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$total_epis = $stmt->fetch(PDO::FETCH_ASSOC)['total_epis'];

$query = "SELECT COUNT(*) as epis_vencidos FROM epis WHERE ativo = 1 AND validade < CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$epis_vencidos = $stmt->fetch(PDO::FETCH_ASSOC)['epis_vencidos'];

$query = "SELECT COUNT(*) as estoque_baixo FROM epis WHERE ativo = 1 AND saldo_estoque <= quantidade_minima";
$stmt = $db->prepare($query);
$stmt->execute();
$estoque_baixo = $stmt->fetch(PDO::FETCH_ASSOC)['estoque_baixo'];

$query = "SELECT COUNT(*) as movimentacoes_hoje FROM movimentacoes WHERE DATE(data_movimentacao) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$movimentacoes_hoje = $stmt->fetch(PDO::FETCH_ASSOC)['movimentacoes_hoje'];

// EPIs com problemas (vencidos ou estoque baixo)
$query = "SELECT nome, validade, saldo_estoque, quantidade_minima FROM epis 
          WHERE ativo = 1 AND (validade < CURDATE() OR saldo_estoque <= quantidade_minima) 
          ORDER BY validade ASC, saldo_estoque ASC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$epis_problemas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Últimas movimentações
$query = "SELECT m.*, e.nome as epi_nome FROM movimentacoes m 
          JOIN epis e ON m.epi_id = e.id 
          ORDER BY m.data_movimentacao DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$ultimas_movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestão de EPIs Klarbyte</title>
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
                <li class="nav-item active"><a href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a href="epis.php">EPIs</a></li>
                <li class="nav-item"><a href="movimentacoes.php">Movimentações</a></li>
                <li class="nav-item"><a href="usuarios.php">Usuários</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <h3>Total de EPIs</h3>
                <div class="number"><?php echo $total_epis; ?></div>
            </div>
            <div class="dashboard-card">
                <h3>EPIs Vencidos</h3>
                <div class="number" style="color: #dc3545;"><?php echo $epis_vencidos; ?></div>
            </div>
            <div class="dashboard-card">
                <h3>Estoque Baixo</h3>
                <div class="number" style="color: #ffc107;"><?php echo $estoque_baixo; ?></div>
            </div>
            <div class="dashboard-card">
                <h3>Movimentações Hoje</h3>
                <div class="number"><?php echo $movimentacoes_hoje; ?></div>
            </div>
        </div>

        <?php if ($epis_vencidos > 0 || $estoque_baixo > 0): ?>
        <div class="alert alert-warning">
            <strong>Atenção!</strong> 
            <?php if ($epis_vencidos > 0): ?>
                Há <?php echo $epis_vencidos; ?> EPI(s) com validade vencida.
            <?php endif; ?>
            <?php if ($estoque_baixo > 0): ?>
                Há <?php echo $estoque_baixo; ?> EPI(s) com estoque abaixo do mínimo.
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- EPIs com problemas -->
            <div class="card">
                <div class="card-header">EPIs Requerendo Atenção</div>
                <div class="card-body">
                    <?php if (count($epis_problemas) > 0): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>EPI</th>
                                    <th>Validade</th>
                                    <th>Estoque</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($epis_problemas as $epi): ?>
                                <tr>
                                    <td><?php echo $epi['nome']; ?></td>
                                    <td><?php echo formatarData($epi['validade']); ?></td>
                                    <td><?php echo $epi['saldo_estoque'] . '/' . $epi['quantidade_minima']; ?></td>
                                    <td>
                                        <?php if (validadeVencida($epi['validade'])): ?>
                                            <span class="status-badge status-danger">Vencido</span>
                                        <?php elseif (estoqueMinimo($epi['saldo_estoque'], $epi['quantidade_minima'])): ?>
                                            <span class="status-badge status-warning">Estoque Baixo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p>Nenhum problema detectado.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Últimas movimentações -->
            <div class="card">
                <div class="card-header">Últimas Movimentações</div>
                <div class="card-body">
                    <?php if (count($ultimas_movimentacoes) > 0): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>EPI</th>
                                    <th>Tipo</th>
                                    <th>Qtd</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimas_movimentacoes as $mov): ?>
                                <tr>
                                    <td><?php echo $mov['epi_nome']; ?></td>
                                    <td>
                                        <?php
                                        $tipos = [
                                            'entrada' => 'Entrada',
                                            'retirada' => 'Retirada',
                                            'devolucao' => 'Devolução',
                                            'descarte' => 'Descarte'
                                        ];
                                        echo $tipos[$mov['tipo_movimentacao']];
                                        ?>
                                    </td>
                                    <td><?php echo $mov['quantidade']; ?></td>
                                    <td><?php echo formatarDataHora($mov['data_movimentacao']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p>Nenhuma movimentação registrada.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>