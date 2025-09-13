<?php
/**
 * Devolver EPI - Sistema de Gestão de EPIs Klarbyte
 * Página para funcionários devolverem EPIs
 */

// Configurações da página
$page_title = "Devolver EPI";
$panel_type = "Painel do Funcionário";
$is_admin = false;
$user_name = "Funcionário";

// Incluir conexão com banco de dados
require_once '../config/database.php';

// Simular funcionário logado (em implementação real, viria da sessão)
$funcionario_id = $_GET['funcionario_id'] ?? 1;

// Processar devolução
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $epi_id = (int)($_POST['epi_id'] ?? 0);
    $quantidade = (int)($_POST['quantidade'] ?? 1);
    $condicao = $_POST['condicao'] ?? '';
    $observacoes = trim($_POST['observacoes'] ?? '');
    
    if ($epi_id <= 0 || $quantidade <= 0 || empty($condicao)) {
        $message = 'Todos os campos obrigatórios devem ser preenchidos.';
        $message_type = 'danger';
    } else {
        // Verificar se o funcionário tem esse EPI retirado
        $retirada = executeQuery("
            SELECT m1.*, e.nome as epi_nome, e.quantidade_estoque
            FROM movimentacoes m1
            JOIN epis e ON m1.epi_id = e.id
            WHERE m1.funcionario_id = ? 
            AND m1.epi_id = ? 
            AND m1.tipo_movimentacao = 'retirada'
            AND NOT EXISTS (
                SELECT 1 FROM movimentacoes m2 
                WHERE m2.funcionario_id = m1.funcionario_id 
                AND m2.epi_id = m1.epi_id 
                AND m2.tipo_movimentacao = 'devolucao' 
                AND m2.data_movimentacao > m1.data_movimentacao
            )
            ORDER BY m1.data_movimentacao DESC
            LIMIT 1
        ", [$funcionario_id, $epi_id]);
        
        if (empty($retirada)) {
            $message = 'Você não possui este EPI retirado.';
            $message_type = 'danger';
        } else {
            $epi_info = $retirada[0];
            
            try {
                // Iniciar transação
                $pdo = getConnection();
                $pdo->beginTransaction();
                
                $saldo_anterior = $epi_info['quantidade_estoque'];
                $novo_saldo = $saldo_anterior + $quantidade;
                
                // Preparar observações da devolução
                $observacao_completa = "Condição: $condicao";
                if (!empty($observacoes)) {
                    $observacao_completa .= " - $observacoes";
                }
                
                // Registrar movimentação de devolução
                $success = executeUpdate(
                    "INSERT INTO movimentacoes (epi_id, funcionario_id, tipo_movimentacao, quantidade, observacoes, usuario_responsavel, saldo_anterior, saldo_atual) VALUES (?, ?, 'devolucao', ?, ?, 'funcionario', ?, ?)",
                    [$epi_id, $funcionario_id, $quantidade, $observacao_completa, $saldo_anterior, $novo_saldo]
                );
                
                if ($success) {
                    // Atualizar estoque
                    $success = executeUpdate(
                        "UPDATE epis SET quantidade_estoque = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                        [$novo_saldo, $epi_id]
                    );
                }
                
                if ($success) {
                    $pdo->commit();
                    $message = 'EPI devolvido com sucesso! Obrigado por manter os equipamentos em ordem.';
                    $message_type = 'success';
                    
                    // Limpar formulário após sucesso
                    $_POST = [];
                } else {
                    $pdo->rollBack();
                    $message = 'Erro ao registrar devolução. Tente novamente.';
                    $message_type = 'danger';
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = 'Erro no sistema: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

try {
    // Buscar EPIs que o funcionário tem retirado e ainda não devolveu
    $epis_para_devolucao = executeQuery("
        SELECT DISTINCT e.id, e.nome, e.categoria, e.descricao, 
               m1.data_movimentacao as data_retirada,
               COUNT(m1.id) as quantidade_retirada,
               DATEDIFF(CURDATE(), m1.data_movimentacao) as dias_uso
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
        GROUP BY e.id, e.nome, e.categoria, e.descricao, m1.data_movimentacao
        ORDER BY m1.data_movimentacao DESC
    ", [$funcionario_id]);
    
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
    
} catch (Exception $e) {
    $epis_para_devolucao = [];
    $funcionario = ['nome' => 'Funcionário'];
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

<!-- Informações do Funcionário -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Devolução de EPI</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>Funcionário:</strong> <?php echo htmlspecialchars($funcionario['nome'] ?? 'Funcionário'); ?><br>
            <strong>Empresa:</strong> <?php echo htmlspecialchars($funcionario['empresa_nome'] ?? 'Não informada'); ?><br>
            <strong>Data/Hora:</strong> <?php echo date('d/m/Y H:i'); ?>
        </div>
    </div>
</div>

<?php if (empty($epis_para_devolucao)): ?>
<!-- Nenhum EPI para devolver -->
<div class="card">
    <div class="card-body">
        <div class="alert alert-info">
            <h5>Nenhum EPI para devolver</h5>
            <p>Você não possui EPIs retirados que precisem ser devolvidos no momento.</p>
            <a href="retirar.php" class="btn btn-primary">Retirar EPI</a>
            <a href="index.php" class="btn btn-secondary">Voltar ao Dashboard</a>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Formulário de Devolução -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Selecionar EPI para Devolução</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="" id="form-devolucao">
            <div class="form-group">
                <label class="form-label" for="epi_id">EPI a Devolver *</label>
                <select id="epi_id" name="epi_id" class="form-control" required onchange="atualizarInfoEPI()">
                    <option value="">Selecione um EPI</option>
                    <?php foreach ($epis_para_devolucao as $epi): ?>
                        <option value="<?php echo $epi['id']; ?>" 
                                data-nome="<?php echo htmlspecialchars($epi['nome']); ?>"
                                data-categoria="<?php echo htmlspecialchars($epi['categoria']); ?>"
                                data-descricao="<?php echo htmlspecialchars($epi['descricao']); ?>"
                                data-retirada="<?php echo date('d/m/Y', strtotime($epi['data_retirada'])); ?>"
                                data-dias="<?php echo $epi['dias_uso']; ?>">
                            <?php echo htmlspecialchars($epi['nome']); ?> 
                            (Retirado em <?php echo date('d/m/Y', strtotime($epi['data_retirada'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Informações do EPI Selecionado -->
            <div id="info-epi" style="display: none;" class="card" style="background: #f8f9fa; margin-bottom: 1rem;">
                <div class="card-body">
                    <h5>Informações do EPI</h5>
                    <div id="detalhes-epi"></div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="quantidade">Quantidade a Devolver *</label>
                        <input type="number" id="quantidade" name="quantidade" class="form-control" 
                               min="1" value="1" required>
                        <small class="text-muted">Normalmente deve ser 1 unidade por devolução</small>
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label class="form-label" for="condicao">Condição do EPI *</label>
                        <select id="condicao" name="condicao" class="form-control" required>
                            <option value="">Selecione a condição</option>
                            <option value="Bom estado">Bom estado - EPI em perfeitas condições</option>
                            <option value="Desgaste normal">Desgaste normal - Uso adequado, pequenos sinais</option>
                            <option value="Desgaste acentuado">Desgaste acentuado - Muito usado mas funcional</option>
                            <option value="Danificado">Danificado - EPI com defeitos que impedem uso</option>
                            <option value="Quebrado">Quebrado - EPI inutilizável</option>
                            <option value="Perdido">Perdido - EPI extraviado</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="observacoes">Observações sobre a Devolução</label>
                <textarea id="observacoes" name="observacoes" class="form-control" rows="4" 
                         placeholder="Descreva detalhadamente o estado do EPI, problemas encontrados, sugestões, etc."></textarea>
                <small class="text-muted">
                    Seja específico sobre o estado do EPI. Isso ajuda na manutenção e controle de qualidade.
                </small>
            </div>
            
            <!-- Checklist de Devolução -->
            <div style="background: #e7f3ff; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <h6 style="color: #004085;">Checklist de Devolução</h6>
                <div style="font-size: 14px;">
                    <label style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                        <input type="checkbox" id="check_limpeza" style="margin-right: 0.5rem;">
                        EPI foi limpo e higienizado antes da devolução
                    </label>
                    <label style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                        <input type="checkbox" id="check_completo" style="margin-right: 0.5rem;">
                        EPI está completo (todas as partes e acessórios)
                    </label>
                    <label style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                        <input type="checkbox" id="check_condicao" style="margin-right: 0.5rem;">
                        Condição do EPI foi avaliada corretamente
                    </label>
                    <label style="display: flex; align-items: center;">
                        <input type="checkbox" id="check_responsabilidade" style="margin-right: 0.5rem;">
                        Estou ciente da minha responsabilidade sobre o estado do EPI
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-warning" id="btn-devolver" disabled>
                    Confirmar Devolução
                </button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <a href="movimentacoes.php" class="btn btn-info">Ver Histórico</a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de EPIs para Devolução -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Meus EPIs Retirados</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>EPI</th>
                        <th>Categoria</th>
                        <th>Data Retirada</th>
                        <th>Dias de Uso</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($epis_para_devolucao as $epi): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($epi['nome']); ?></strong>
                            <?php if (!empty($epi['descricao'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($epi['descricao'], 0, 50)); ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($epi['categoria'] ?? '-'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($epi['data_retirada'])); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $epi['dias_uso'] > 30 ? 'warning' : 'info'; ?>">
                                <?php echo $epi['dias_uso']; ?> dias
                            </span>
                        </td>
                        <td>
                            <button onclick="selecionarEPI(<?php echo $epi['id']; ?>)" class="btn btn-warning btn-sm">
                                Devolver
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Instruções de Devolução -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Instruções para Devolução</h3>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-col">
                <h5>Antes de Devolver:</h5>
                <ul>
                    <li>Limpe o EPI adequadamente</li>
                    <li>Verifique se todas as partes estão presentes</li>
                    <li>Avalie honestamente o estado do equipamento</li>
                    <li>Anote problemas ou defeitos encontrados</li>
                </ul>
            </div>
            <div class="form-col">
                <h5>Condições dos EPIs:</h5>
                <ul>
                    <li><strong>Bom estado:</strong> EPI funcional sem defeitos</li>
                    <li><strong>Desgaste normal:</strong> Sinais de uso mas funcional</li>
                    <li><strong>Danificado:</strong> Com defeitos que prejudicam o uso</li>
                    <li><strong>Quebrado:</strong> Completamente inutilizável</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
// Script adicional para a página
$additional_scripts = "
<script>
function atualizarInfoEPI() {
    const select = document.getElementById('epi_id');
    const option = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('info-epi');
    const detalhesDiv = document.getElementById('detalhes-epi');
    
    if (option.value) {
        const nome = option.dataset.nome;
        const categoria = option.dataset.categoria;
        const descricao = option.dataset.descricao;
        const retirada = option.dataset.retirada;
        const dias = option.dataset.dias;
        
        let detalhes = '<div class=\"form-row\">';
        detalhes += '<div class=\"form-col\">';
        detalhes += '<p><strong>Nome:</strong> ' + nome + '</p>';
        if (categoria) detalhes += '<p><strong>Categoria:</strong> ' + categoria + '</p>';
        detalhes += '<p><strong>Data da Retirada:</strong> ' + retirada + '</p>';
        detalhes += '</div>';
        detalhes += '<div class=\"form-col\">';
        detalhes += '<p><strong>Tempo de Uso:</strong> ' + dias + ' dias</p>';
        const alertaTempo = parseInt(dias) > 30 ? 'warning' : 'info';
        detalhes += '<p><strong>Status:</strong> <span class=\"badge badge-' + alertaTempo + '\">';
        detalhes += parseInt(dias) > 30 ? 'Uso prolongado' : 'Uso normal';
        detalhes += '</span></p>';
        detalhes += '</div>';
        detalhes += '</div>';
        if (descricao) detalhes += '<p><strong>Descrição:</strong> ' + descricao + '</p>';
        
        detalhesDiv.innerHTML = detalhes;
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}

function selecionarEPI(epiId) {
    const select = document.getElementById('epi_id');
    select.value = epiId;
    atualizarInfoEPI();
    
    // Scroll para o formulário
    document.getElementById('form-devolucao').scrollIntoView({ behavior: 'smooth' });
}

// Habilitar botão de devolução apenas quando todos os checks estiverem marcados
function verificarChecklist() {
    const checks = ['check_limpeza', 'check_completo', 'check_condicao', 'check_responsabilidade'];
    const allChecked = checks.every(id => document.getElementById(id).checked);
    document.getElementById('btn-devolver').disabled = !allChecked;
}

// Adicionar event listeners aos checkboxes
document.addEventListener('DOMContentLoaded', function() {
    const checks = ['check_limpeza', 'check_completo', 'check_condicao', 'check_responsabilidade'];
    checks.forEach(id => {
        document.getElementById(id).addEventListener('change', verificarChecklist);
    });
    
    // Sugestões baseadas na condição
    document.getElementById('condicao').addEventListener('change', function() {
        const observacoes = document.getElementById('observacoes');
        const condicao = this.value;
        
        if (condicao && !observacoes.value) {
            let sugestao = '';
            switch(condicao) {
                case 'Danificado':
                    sugestao = 'Descreva especificamente quais partes estão danificadas e como isso afeta o uso do EPI.';
                    break;
                case 'Quebrado':
                    sugestao = 'Explique como o EPI quebrou e se foi devido ao uso normal ou acidente.';
                    break;
                case 'Perdido':
                    sugestao = 'Informe quando e onde o EPI foi perdido, e se há possibilidade de recuperação.';
                    break;
                case 'Desgaste acentuado':
                    sugestao = 'Descreva as áreas mais desgastadas e se ainda oferece proteção adequada.';
                    break;
            }
            if (sugestao) {
                observacoes.placeholder = sugestao;
            }
        }
    });
});
</script>
";

// Incluir footer
include '../includes/footer.php';
?>