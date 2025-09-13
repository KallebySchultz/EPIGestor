<?php
/**
 * Relatórios - Sistema de Gestão de EPIs Klarbyte
 * Página para geração de relatórios e análises
 */

// Configurações da página
$page_title = "Relatórios e Análises";
$panel_type = "Painel Administrativo";
$is_admin = true;
$user_name = "Administrador";

// Incluir conexão com banco de dados
require_once '../config/database.php';

// Parâmetros do relatório
$tipo_relatorio = $_GET['tipo'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01'); // Primeiro dia do mês
$data_fim = $_GET['data_fim'] ?? date('Y-m-d'); // Hoje
$funcionario_id = $_GET['funcionario_id'] ?? '';
$epi_id = $_GET['epi_id'] ?? '';

// Dados para os selects
$funcionarios = executeQuery("SELECT id, nome FROM funcionarios WHERE ativo = 1 ORDER BY nome");
$epis = executeQuery("SELECT id, nome FROM epis WHERE ativo = 1 ORDER BY nome");

// Gerar relatório baseado no tipo
$relatorio_dados = [];
$relatorio_titulo = '';

switch ($tipo_relatorio) {
    case 'movimentacoes':
        $relatorio_titulo = 'Relatório de Movimentações';
        $where_conditions = ["DATE(m.data_movimentacao) BETWEEN ? AND ?"];
        $params = [$data_inicio, $data_fim];
        
        if (!empty($funcionario_id)) {
            $where_conditions[] = "m.funcionario_id = ?";
            $params[] = $funcionario_id;
        }
        
        if (!empty($epi_id)) {
            $where_conditions[] = "m.epi_id = ?";
            $params[] = $epi_id;
        }
        
        $relatorio_dados = executeQuery("
            SELECT m.*, e.nome as epi_nome, f.nome as funcionario_nome,
                   DATE_FORMAT(m.data_movimentacao, '%d/%m/%Y %H:%i') as data_formatada
            FROM movimentacoes m
            LEFT JOIN epis e ON m.epi_id = e.id
            LEFT JOIN funcionarios f ON m.funcionario_id = f.id
            WHERE " . implode(' AND ', $where_conditions) . "
            ORDER BY m.data_movimentacao DESC
        ", $params);
        break;
        
    case 'estoque':
        $relatorio_titulo = 'Relatório de Estoque Atual';
        $relatorio_dados = executeQuery("
            SELECT e.*, f.nome as fornecedor_nome,
                   (e.quantidade_estoque * COALESCE(e.preco_unitario, 0)) as valor_total,
                   CASE 
                       WHEN e.quantidade_estoque = 0 THEN 'Esgotado'
                       WHEN e.quantidade_estoque <= e.quantidade_minima THEN 'Estoque Baixo'
                       WHEN e.validade < CURDATE() THEN 'Vencido'
                       WHEN e.validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Vencimento Próximo'
                       ELSE 'OK'
                   END as status_item
            FROM epis e
            LEFT JOIN fornecedores f ON e.fornecedor_id = f.id
            WHERE e.ativo = 1
            ORDER BY e.nome
        ");
        break;
        
    case 'funcionarios':
        $relatorio_titulo = 'Relatório de Funcionários e Movimentações';
        $relatorio_dados = executeQuery("
            SELECT f.*, e.nome as empresa_nome,
                   COUNT(m.id) as total_movimentacoes,
                   SUM(CASE WHEN m.tipo_movimentacao = 'retirada' THEN m.quantidade ELSE 0 END) as total_retiradas,
                   SUM(CASE WHEN m.tipo_movimentacao = 'devolucao' THEN m.quantidade ELSE 0 END) as total_devolucoes,
                   MAX(m.data_movimentacao) as ultima_movimentacao
            FROM funcionarios f
            LEFT JOIN empresas e ON f.empresa_id = e.id
            LEFT JOIN movimentacoes m ON f.id = m.funcionario_id 
                AND DATE(m.data_movimentacao) BETWEEN ? AND ?
            WHERE f.ativo = 1
            GROUP BY f.id, f.nome, f.cpf, f.cargo, f.setor, e.nome
            ORDER BY total_movimentacoes DESC, f.nome
        ", [$data_inicio, $data_fim]);
        break;
        
    case 'analise_uso':
        $relatorio_titulo = 'Análise de Uso de EPIs';
        $relatorio_dados = executeQuery("
            SELECT e.nome as epi_nome, e.categoria,
                   COUNT(m.id) as total_movimentacoes,
                   SUM(CASE WHEN m.tipo_movimentacao = 'retirada' THEN m.quantidade ELSE 0 END) as total_retiradas,
                   SUM(CASE WHEN m.tipo_movimentacao = 'devolucao' THEN m.quantidade ELSE 0 END) as total_devolucoes,
                   SUM(CASE WHEN m.tipo_movimentacao = 'descarte' THEN m.quantidade ELSE 0 END) as total_descartes,
                   COUNT(DISTINCT m.funcionario_id) as funcionarios_utilizaram,
                   e.quantidade_estoque as estoque_atual
            FROM epis e
            LEFT JOIN movimentacoes m ON e.id = m.epi_id 
                AND DATE(m.data_movimentacao) BETWEEN ? AND ?
            WHERE e.ativo = 1
            GROUP BY e.id, e.nome, e.categoria, e.quantidade_estoque
            HAVING total_movimentacoes > 0
            ORDER BY total_retiradas DESC
        ", [$data_inicio, $data_fim]);
        break;
        
    case 'vencimentos':
        $relatorio_titulo = 'Relatório de Vencimentos';
        $relatorio_dados = executeQuery("
            SELECT e.*, f.nome as fornecedor_nome,
                   DATEDIFF(e.validade, CURDATE()) as dias_para_vencer,
                   (e.quantidade_estoque * COALESCE(e.preco_unitario, 0)) as valor_perdido
            FROM epis e
            LEFT JOIN fornecedores f ON e.fornecedor_id = f.id
            WHERE e.ativo = 1 AND e.validade IS NOT NULL
                AND e.validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
            ORDER BY e.validade ASC
        ");
        break;
}

// Incluir header
include '../includes/header.php';
?>

<!-- Seleção do Tipo de Relatório -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Gerador de Relatórios</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="tipo">Tipo de Relatório</label>
                        <select id="tipo" name="tipo" class="form-control" required>
                            <option value="">Selecione um tipo de relatório</option>
                            <option value="movimentacoes" <?php echo $tipo_relatorio == 'movimentacoes' ? 'selected' : ''; ?>>
                                Movimentações por Período
                            </option>
                            <option value="estoque" <?php echo $tipo_relatorio == 'estoque' ? 'selected' : ''; ?>>
                                Estoque Atual
                            </option>
                            <option value="funcionarios" <?php echo $tipo_relatorio == 'funcionarios' ? 'selected' : ''; ?>>
                                Funcionários e Atividades
                            </option>
                            <option value="analise_uso" <?php echo $tipo_relatorio == 'analise_uso' ? 'selected' : ''; ?>>
                                Análise de Uso de EPIs
                            </option>
                            <option value="vencimentos" <?php echo $tipo_relatorio == 'vencimentos' ? 'selected' : ''; ?>>
                                Vencimentos (Próximos 90 dias)
                            </option>
                        </select>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="data_inicio">Data Início</label>
                        <input type="date" id="data_inicio" name="data_inicio" class="form-control" 
                               value="<?php echo $data_inicio; ?>">
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="data_fim">Data Fim</label>
                        <input type="date" id="data_fim" name="data_fim" class="form-control" 
                               value="<?php echo $data_fim; ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="funcionario_id">Funcionário (Opcional)</label>
                        <select id="funcionario_id" name="funcionario_id" class="form-control">
                            <option value="">Todos os funcionários</option>
                            <?php foreach ($funcionarios as $func): ?>
                                <option value="<?php echo $func['id']; ?>" 
                                        <?php echo $funcionario_id == $func['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($func['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="epi_id">EPI (Opcional)</label>
                        <select id="epi_id" name="epi_id" class="form-control">
                            <option value="">Todos os EPIs</option>
                            <?php foreach ($epis as $epi): ?>
                                <option value="<?php echo $epi['id']; ?>" 
                                        <?php echo $epi_id == $epi['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($epi['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Gerar Relatório</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Exibição do Relatório -->
<?php if (!empty($tipo_relatorio) && !empty($relatorio_dados)): ?>
<div class="card" id="relatorio-content">
    <div class="card-header">
        <h3 class="card-title"><?php echo $relatorio_titulo; ?></h3>
        <div style="float: right;">
            <button onclick="window.print()" class="btn btn-secondary btn-sm">Imprimir</button>
            <button onclick="exportarCSV()" class="btn btn-info btn-sm">Exportar CSV</button>
        </div>
    </div>
    <div class="card-body">
        <!-- Informações do Relatório -->
        <div class="alert alert-info">
            <strong>Período:</strong> <?php echo date('d/m/Y', strtotime($data_inicio)); ?> a <?php echo date('d/m/Y', strtotime($data_fim)); ?><br>
            <strong>Gerado em:</strong> <?php echo date('d/m/Y H:i'); ?><br>
            <strong>Total de registros:</strong> <?php echo count($relatorio_dados); ?>
        </div>
        
        <!-- Tabela do Relatório -->
        <div class="table-container">
            <table class="table" id="tabela-relatorio">
                <thead>
                    <tr>
                        <?php if ($tipo_relatorio == 'movimentacoes'): ?>
                            <th>Data/Hora</th>
                            <th>Tipo</th>
                            <th>EPI</th>
                            <th>Funcionário</th>
                            <th>Quantidade</th>
                            <th>Saldo Anterior</th>
                            <th>Saldo Atual</th>
                            <th>Observações</th>
                        <?php elseif ($tipo_relatorio == 'estoque'): ?>
                            <th>EPI</th>
                            <th>Categoria</th>
                            <th>Fornecedor</th>
                            <th>Estoque</th>
                            <th>Mínimo</th>
                            <th>Preço Unit.</th>
                            <th>Valor Total</th>
                            <th>Validade</th>
                            <th>Status</th>
                        <?php elseif ($tipo_relatorio == 'funcionarios'): ?>
                            <th>Funcionário</th>
                            <th>CPF</th>
                            <th>Empresa</th>
                            <th>Cargo</th>
                            <th>Total Mov.</th>
                            <th>Retiradas</th>
                            <th>Devoluções</th>
                            <th>Última Mov.</th>
                        <?php elseif ($tipo_relatorio == 'analise_uso'): ?>
                            <th>EPI</th>
                            <th>Categoria</th>
                            <th>Total Mov.</th>
                            <th>Retiradas</th>
                            <th>Devoluções</th>
                            <th>Descartes</th>
                            <th>Funcionários</th>
                            <th>Estoque Atual</th>
                        <?php elseif ($tipo_relatorio == 'vencimentos'): ?>
                            <th>EPI</th>
                            <th>Categoria</th>
                            <th>Fornecedor</th>
                            <th>Estoque</th>
                            <th>Validade</th>
                            <th>Dias p/ Vencer</th>
                            <th>Valor em Risco</th>
                            <th>Status</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($relatorio_dados as $item): ?>
                    <tr>
                        <?php if ($tipo_relatorio == 'movimentacoes'): ?>
                            <td><?php echo $item['data_formatada']; ?></td>
                            <td><?php echo ucfirst($item['tipo_movimentacao']); ?></td>
                            <td><?php echo htmlspecialchars($item['epi_nome'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($item['funcionario_nome'] ?? '-'); ?></td>
                            <td class="text-center"><?php echo $item['quantidade']; ?></td>
                            <td class="text-center"><?php echo $item['saldo_anterior']; ?></td>
                            <td class="text-center"><?php echo $item['saldo_atual']; ?></td>
                            <td><?php echo htmlspecialchars($item['observacoes'] ?? '-'); ?></td>
                        <?php elseif ($tipo_relatorio == 'estoque'): ?>
                            <td><?php echo htmlspecialchars($item['nome']); ?></td>
                            <td><?php echo htmlspecialchars($item['categoria'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['fornecedor_nome'] ?? '-'); ?></td>
                            <td class="text-center"><?php echo $item['quantidade_estoque']; ?></td>
                            <td class="text-center"><?php echo $item['quantidade_minima']; ?></td>
                            <td class="text-right">
                                <?php echo $item['preco_unitario'] ? 'R$ ' . number_format($item['preco_unitario'], 2, ',', '.') : '-'; ?>
                            </td>
                            <td class="text-right">
                                <?php echo $item['valor_total'] > 0 ? 'R$ ' . number_format($item['valor_total'], 2, ',', '.') : '-'; ?>
                            </td>
                            <td><?php echo $item['validade'] ? date('d/m/Y', strtotime($item['validade'])) : '-'; ?></td>
                            <td><?php echo $item['status_item']; ?></td>
                        <?php elseif ($tipo_relatorio == 'funcionarios'): ?>
                            <td><?php echo htmlspecialchars($item['nome']); ?></td>
                            <td><?php echo htmlspecialchars($item['cpf'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['empresa_nome'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['cargo'] ?? '-'); ?></td>
                            <td class="text-center"><?php echo $item['total_movimentacoes']; ?></td>
                            <td class="text-center"><?php echo $item['total_retiradas']; ?></td>
                            <td class="text-center"><?php echo $item['total_devolucoes']; ?></td>
                            <td><?php echo $item['ultima_movimentacao'] ? date('d/m/Y', strtotime($item['ultima_movimentacao'])) : '-'; ?></td>
                        <?php elseif ($tipo_relatorio == 'analise_uso'): ?>
                            <td><?php echo htmlspecialchars($item['epi_nome']); ?></td>
                            <td><?php echo htmlspecialchars($item['categoria'] ?? '-'); ?></td>
                            <td class="text-center"><?php echo $item['total_movimentacoes']; ?></td>
                            <td class="text-center"><?php echo $item['total_retiradas']; ?></td>
                            <td class="text-center"><?php echo $item['total_devolucoes']; ?></td>
                            <td class="text-center"><?php echo $item['total_descartes']; ?></td>
                            <td class="text-center"><?php echo $item['funcionarios_utilizaram']; ?></td>
                            <td class="text-center"><?php echo $item['estoque_atual']; ?></td>
                        <?php elseif ($tipo_relatorio == 'vencimentos'): ?>
                            <td><?php echo htmlspecialchars($item['nome']); ?></td>
                            <td><?php echo htmlspecialchars($item['categoria'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['fornecedor_nome'] ?? '-'); ?></td>
                            <td class="text-center"><?php echo $item['quantidade_estoque']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($item['validade'])); ?></td>
                            <td class="text-center">
                                <span class="badge badge-<?php echo $item['dias_para_vencer'] <= 0 ? 'danger' : ($item['dias_para_vencer'] <= 30 ? 'warning' : 'info'); ?>">
                                    <?php echo $item['dias_para_vencer']; ?> dias
                                </span>
                            </td>
                            <td class="text-right">
                                <?php echo $item['valor_perdido'] > 0 ? 'R$ ' . number_format($item['valor_perdido'], 2, ',', '.') : '-'; ?>
                            </td>
                            <td>
                                <?php if ($item['dias_para_vencer'] <= 0): ?>
                                    <span class="badge badge-danger">Vencido</span>
                                <?php elseif ($item['dias_para_vencer'] <= 30): ?>
                                    <span class="badge badge-warning">Crítico</span>
                                <?php else: ?>
                                    <span class="badge badge-info">Atenção</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Resumo Estatístico -->
        <?php if ($tipo_relatorio == 'movimentacoes'): ?>
        <div class="alert alert-secondary">
            <strong>Resumo:</strong><br>
            Total de Entradas: <?php echo count(array_filter($relatorio_dados, fn($r) => $r['tipo_movimentacao'] == 'entrada')); ?><br>
            Total de Retiradas: <?php echo count(array_filter($relatorio_dados, fn($r) => $r['tipo_movimentacao'] == 'retirada')); ?><br>
            Total de Devoluções: <?php echo count(array_filter($relatorio_dados, fn($r) => $r['tipo_movimentacao'] == 'devolucao')); ?><br>
            Total de Descartes: <?php echo count(array_filter($relatorio_dados, fn($r) => $r['tipo_movimentacao'] == 'descarte')); ?>
        </div>
        <?php elseif ($tipo_relatorio == 'estoque'): ?>
        <div class="alert alert-secondary">
            <strong>Resumo:</strong><br>
            Total de EPIs: <?php echo count($relatorio_dados); ?><br>
            Valor Total em Estoque: R$ <?php echo number_format(array_sum(array_column($relatorio_dados, 'valor_total')), 2, ',', '.'); ?><br>
            Itens com Estoque Baixo: <?php echo count(array_filter($relatorio_dados, fn($r) => $r['status_item'] == 'Estoque Baixo')); ?><br>
            Itens Vencidos: <?php echo count(array_filter($relatorio_dados, fn($r) => $r['status_item'] == 'Vencido')); ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif (!empty($tipo_relatorio) && empty($relatorio_dados)): ?>
<div class="alert alert-warning">
    <strong>Nenhum dado encontrado!</strong> Não há registros para os filtros selecionados no período especificado.
</div>
<?php endif; ?>

<!-- Relatórios Rápidos -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Relatórios Rápidos</h3>
    </div>
    <div class="card-body">
        <div class="dashboard-grid">
            <a href="?tipo=estoque" class="btn btn-primary" style="padding: 1rem; text-align: center;">
                Estoque Atual
            </a>
            <a href="?tipo=movimentacoes&data_inicio=<?php echo date('Y-m-01'); ?>&data_fim=<?php echo date('Y-m-d'); ?>" 
               class="btn btn-info" style="padding: 1rem; text-align: center;">
                Movimentações do Mês
            </a>
            <a href="?tipo=vencimentos" class="btn btn-warning" style="padding: 1rem; text-align: center;">
                Vencimentos Próximos
            </a>
            <a href="?tipo=analise_uso&data_inicio=<?php echo date('Y-m-01', strtotime('-1 month')); ?>&data_fim=<?php echo date('Y-m-d'); ?>" 
               class="btn btn-success" style="padding: 1rem; text-align: center;">
                Análise de Uso
            </a>
        </div>
    </div>
</div>

<?php
// Script adicional para a página
$additional_scripts = "
<script>
function exportarCSV() {
    const tabela = document.getElementById('tabela-relatorio');
    if (!tabela) return;
    
    let csv = '';
    const rows = tabela.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('th, td');
        const rowData = Array.from(cols).map(col => {
            return '\"' + col.textContent.replace(/\"/g, '\"\"') + '\"';
        });
        csv += rowData.join(',') + '\\n';
    });
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'relatorio_epis_' + new Date().getTime() + '.csv';
    link.click();
}

// Atualizar campos baseado no tipo de relatório
document.getElementById('tipo').addEventListener('change', function() {
    const tipo = this.value;
    const dataInicio = document.getElementById('data_inicio');
    const dataFim = document.getElementById('data_fim');
    const funcionarioField = document.getElementById('funcionario_id').closest('.form-col');
    const epiField = document.getElementById('epi_id').closest('.form-col');
    
    // Mostrar/ocultar campos baseado no tipo
    if (tipo === 'estoque' || tipo === 'vencimentos') {
        funcionarioField.style.display = 'none';
        epiField.style.display = 'none';
        dataInicio.closest('.form-col').style.display = 'none';
        dataFim.closest('.form-col').style.display = 'none';
    } else {
        funcionarioField.style.display = 'block';
        epiField.style.display = 'block';
        dataInicio.closest('.form-col').style.display = 'block';
        dataFim.closest('.form-col').style.display = 'block';
    }
});

// Inicializar campos
document.getElementById('tipo').dispatchEvent(new Event('change'));
</script>

<style>
@media print {
    .btn, .form-row, .alert:not(.alert-info):not(.alert-secondary), .nav, .header, .card-header .btn { 
        display: none !important; 
    }
    .card { 
        box-shadow: none !important; 
        border: 1px solid #000 !important; 
        page-break-inside: avoid;
    }
    .table { 
        font-size: 10px; 
    }
    .table th, .table td { 
        padding: 2px !important; 
        border: 1px solid #000 !important;
    }
    body { 
        background: white !important; 
        font-size: 12px;
    }
    .card-title {
        font-size: 16px !important;
        font-weight: bold !important;
    }
}
</style>
";

// Incluir footer
include '../includes/footer.php';
?>