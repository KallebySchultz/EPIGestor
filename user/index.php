<?php
/**
 * Dashboard do Usuário - Sistema de Gestão de EPIs Klarbyte
 * Painel principal para funcionários/usuários
 */

// Configurações da página
$page_title = "Dashboard do Usuário";
$panel_type = "Painel do Funcionário";
$is_admin = false;
$user_name = "Funcionário";

// Incluir conexão com banco de dados
require_once '../config/database.php';

// Simular funcionário logado (em uma implementação real, viria da sessão)
$funcionario_id = $_GET['funcionario_id'] ?? 1;

// Buscar dados do funcionário
$funcionario = executeQuery("
    SELECT f.*, e.nome as empresa_nome 
    FROM funcionarios f 
    LEFT JOIN empresas e ON f.empresa_id = e.id 
    WHERE f.id = ? AND f.ativo = 1
", [$funcionario_id]);

if (empty($funcionario)) {
    $funcionario = [
        'id' => $funcionario_id,
        'nome' => 'Funcionário Exemplo',
        'empresa_nome' => 'Empresa Exemplo'
    ];
} else {
    $funcionario = $funcionario[0];
    $user_name = $funcionario['nome'];
}

try {
    // EPIs disponíveis
    $epis_disponiveis = executeQuery("
        SELECT COUNT(*) as total 
        FROM epis 
        WHERE ativo = 1 AND quantidade_estoque > 0
    ")[0]['total'] ?? 0;
    
    // Minhas retiradas ativas (não devolvidas)
    $minhas_retiradas = executeQuery("
        SELECT COUNT(DISTINCT m1.epi_id) as total
        FROM movimentacoes m1
        WHERE m1.funcionario_id = ? 
        AND m1.tipo_movimentacao = 'retirada'
        AND NOT EXISTS (
            SELECT 1 FROM movimentacoes m2 
            WHERE m2.funcionario_id = m1.funcionario_id 
            AND m2.epi_id = m1.epi_id 
            AND m2.tipo_movimentacao = 'devolucao' 
            AND m2.data_movimentacao > m1.data_movimentacao
        )
    ", [$funcionario_id])[0]['total'] ?? 0;
    
    // Total de movimentações do funcionário
    $total_movimentacoes = executeQuery("
        SELECT COUNT(*) as total 
        FROM movimentacoes 
        WHERE funcionario_id = ?
    ", [$funcionario_id])[0]['total'] ?? 0;
    
    // EPIs próximos ao vencimento que tenho
    $epis_vencimento = executeQuery("
        SELECT COUNT(DISTINCT m1.epi_id) as total
        FROM movimentacoes m1
        JOIN epis e ON m1.epi_id = e.id
        WHERE m1.funcionario_id = ? 
        AND m1.tipo_movimentacao = 'retirada'
        AND e.validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND NOT EXISTS (
            SELECT 1 FROM movimentacoes m2 
            WHERE m2.funcionario_id = m1.funcionario_id 
            AND m2.epi_id = m1.epi_id 
            AND m2.tipo_movimentacao = 'devolucao' 
            AND m2.data_movimentacao > m1.data_movimentacao
        )
    ", [$funcionario_id])[0]['total'] ?? 0;
    
    // Últimas movimentações do funcionário
    $ultimas_movimentacoes = executeQuery("
        SELECT m.*, e.nome as epi_nome, 
               DATE_FORMAT(m.data_movimentacao, '%d/%m/%Y %H:%i') as data_formatada
        FROM movimentacoes m
        LEFT JOIN epis e ON m.epi_id = e.id
        WHERE m.funcionario_id = ?
        ORDER BY m.data_movimentacao DESC
        LIMIT 10
    ", [$funcionario_id]);
    
    // EPIs atualmente em minha posse
    $meus_epis = executeQuery("
        SELECT e.nome, e.categoria, e.validade, m1.data_movimentacao as data_retirada,
               DATEDIFF(e.validade, CURDATE()) as dias_para_vencer
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
        ORDER BY e.validade ASC
    ", [$funcionario_id]);
    
    // EPIs mais utilizados pelos funcionários (top 5)
    $epis_populares = executeQuery("
        SELECT e.nome, e.categoria, COUNT(m.id) as total_retiradas
        FROM epis e
        JOIN movimentacoes m ON e.id = m.epi_id
        WHERE m.tipo_movimentacao = 'retirada'
        AND m.data_movimentacao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY e.id, e.nome, e.categoria
        ORDER BY total_retiradas DESC
        LIMIT 5
    ");
    
} catch (Exception $e) {
    // Se houver erro na conexão, usar valores padrão
    $epis_disponiveis = 0;
    $minhas_retiradas = 0;
    $total_movimentacoes = 0;
    $epis_vencimento = 0;
    $ultimas_movimentacoes = [];
    $meus_epis = [];
    $epis_populares = [];
}

// Incluir header
include '../includes/header.php';
?>

<!-- Informações do Funcionário -->
<div class="card fade-in">
    <div class="card-header">
        <h3 class="card-title">Bem-vindo, <?php echo htmlspecialchars($funcionario['nome']); ?>!</h3>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-col">
                <strong>Funcionário:</strong> <?php echo htmlspecialchars($funcionario['nome']); ?><br>
                <strong>Empresa:</strong> <?php echo htmlspecialchars($funcionario['empresa_nome'] ?? 'Não informada'); ?><br>
                <?php if (!empty($funcionario['cargo'])): ?>
                    <strong>Cargo:</strong> <?php echo htmlspecialchars($funcionario['cargo']); ?><br>
                <?php endif; ?>
                <?php if (!empty($funcionario['setor'])): ?>
                    <strong>Setor:</strong> <?php echo htmlspecialchars($funcionario['setor']); ?>
                <?php endif; ?>
            </div>
            <div class="form-col">
                <div class="alert alert-info">
                    <strong>Como usar o sistema:</strong><br>
                    • Consulte EPIs disponíveis<br>
                    • Registre retiradas de equipamentos<br>
                    • Faça devoluções quando necessário<br>
                    • Consulte seu histórico de movimentações
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard com Estatísticas -->
<div class="dashboard-grid">
    <!-- EPIs Disponíveis -->
    <div class="stat-card" style="border-left-color: #28a745;">
        <div class="stat-number" style="color: #28a745;"><?php echo $epis_disponiveis; ?></div>
        <div class="stat-label">EPIs Disponíveis</div>
        <a href="epis.php" class="btn btn-success btn-sm mt-2">Ver EPIs</a>
    </div>
    
    <!-- Meus EPIs Ativos -->
    <div class="stat-card" style="border-left-color: #007bff;">
        <div class="stat-number" style="color: #007bff;"><?php echo $minhas_retiradas; ?></div>
        <div class="stat-label">EPIs em Minha Posse</div>
        <a href="movimentacoes.php" class="btn btn-primary btn-sm mt-2">Ver Detalhes</a>
    </div>
    
    <!-- Total de Movimentações -->
    <div class="stat-card" style="border-left-color: #17a2b8;">
        <div class="stat-number" style="color: #17a2b8;"><?php echo $total_movimentacoes; ?></div>
        <div class="stat-label">Minhas Movimentações</div>
        <a href="movimentacoes.php" class="btn btn-info btn-sm mt-2">Histórico</a>
    </div>
    
    <!-- Alertas de Vencimento -->
    <div class="stat-card" style="border-left-color: <?php echo $epis_vencimento > 0 ? '#ffc107' : '#28a745'; ?>;">
        <div class="stat-number" style="color: <?php echo $epis_vencimento > 0 ? '#ffc107' : '#28a745'; ?>;">
            <?php echo $epis_vencimento; ?>
        </div>
        <div class="stat-label">EPIs Vencendo</div>
        <?php if ($epis_vencimento > 0): ?>
            <span class="btn btn-warning btn-sm mt-2">Atenção!</span>
        <?php else: ?>
            <span class="btn btn-success btn-sm mt-2">Tudo OK</span>
        <?php endif; ?>
    </div>
</div>

<!-- Alertas Importantes -->
<?php if ($epis_vencimento > 0): ?>
<div class="alert alert-warning">
    <strong>Atenção!</strong> Você possui <?php echo $epis_vencimento; ?> EPI(s) com vencimento próximo. 
    Verifique a lista abaixo e procure o responsável para substituição.
</div>
<?php endif; ?>

<div class="form-row">
    <!-- Meus EPIs Atuais -->
    <div class="form-col">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">EPIs em Minha Posse</h3>
            </div>
            <div class="card-body">
                <?php if (empty($meus_epis)): ?>
                    <div class="alert alert-info">
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
                                    <th>Validade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($meus_epis as $epi): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($epi['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($epi['categoria'] ?? '-'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($epi['data_retirada'])); ?></td>
                                    <td class="expiration-date">
                                        <?php if ($epi['validade']): ?>
                                            <?php echo date('d/m/Y', strtotime($epi['validade'])); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($epi['validade']): ?>
                                            <?php if ($epi['dias_para_vencer'] < 0): ?>
                                                <span class="badge badge-danger">Vencido</span>
                                            <?php elseif ($epi['dias_para_vencer'] <= 30): ?>
                                                <span class="badge badge-warning">Vencendo</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Válido</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge badge-info">Sem validade</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center">
                        <a href="devolver.php" class="btn btn-warning">Devolver EPI</a>
                        <a href="retirar.php" class="btn btn-primary">Retirar Mais EPIs</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Últimas Movimentações -->
    <div class="form-col">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Minhas Últimas Movimentações</h3>
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
                                    <th>Qtd</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimas_movimentacoes as $mov): ?>
                                <tr>
                                    <td><?php echo $mov['data_formatada']; ?></td>
                                    <td>
                                        <?php
                                        $tipos = [
                                            'retirada' => '<span class="badge badge-info">Retirada</span>',
                                            'devolucao' => '<span class="badge badge-warning">Devolução</span>'
                                        ];
                                        echo $tipos[$mov['tipo_movimentacao']] ?? $mov['tipo_movimentacao'];
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($mov['epi_nome'] ?? 'N/A'); ?></td>
                                    <td><?php echo $mov['quantidade']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center">
                        <a href="movimentacoes.php" class="btn btn-primary btn-sm">Ver Histórico Completo</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- EPIs Mais Utilizados -->
<?php if (!empty($epis_populares)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">EPIs Mais Utilizados (Últimos 30 dias)</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>EPI</th>
                        <th>Categoria</th>
                        <th>Total de Retiradas</th>
                        <th>Popularidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($epis_populares as $epi): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($epi['nome']); ?></strong></td>
                        <td><?php echo htmlspecialchars($epi['categoria'] ?? '-'); ?></td>
                        <td class="text-center"><?php echo $epi['total_retiradas']; ?></td>
                        <td>
                            <div style="background: #e9ecef; border-radius: 10px; height: 20px; position: relative;">
                                <div style="background: #007bff; height: 100%; border-radius: 10px; width: <?php echo min(100, ($epi['total_retiradas'] / max(1, $epis_populares[0]['total_retiradas'])) * 100); ?>%;"></div>
                            </div>
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
            <a href="epis.php" class="btn btn-primary" style="padding: 1rem; text-align: center;">
                Ver EPIs Disponíveis
            </a>
            <a href="retirar.php" class="btn btn-success" style="padding: 1rem; text-align: center;">
                Retirar EPI
            </a>
            <a href="devolver.php" class="btn btn-warning" style="padding: 1rem; text-align: center;">
                Devolver EPI
            </a>
            <a href="movimentacoes.php" class="btn btn-info" style="padding: 1rem; text-align: center;">
                Meu Histórico
            </a>
        </div>
    </div>
</div>

<?php
// Script adicional para o dashboard
$additional_scripts = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animar cartões estatísticos
    const cards = document.querySelectorAll('.stat-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
        }, index * 200);
    });
    
    // Atualizar dados a cada 10 minutos
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            location.reload();
        }
    }, 600000);
});
</script>
";

// Incluir footer
include '../includes/footer.php';
?>