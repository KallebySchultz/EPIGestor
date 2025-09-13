<?php
/**
 * Retirar EPI - Sistema de Gestão de EPIs Klarbyte
 * Página para funcionários retirarem EPIs
 */

// Configurações da página
$page_title = "Retirar EPI";
$panel_type = "Painel do Funcionário";
$is_admin = false;
$user_name = "Funcionário";

// Incluir conexão com banco de dados
require_once '../config/database.php';

// Simular funcionário logado (em implementação real, viria da sessão)
$funcionario_id = $_GET['funcionario_id'] ?? 1;
$epi_pre_selecionado = $_GET['epi_id'] ?? '';

// Processar retirada
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $epi_id = (int)($_POST['epi_id'] ?? 0);
    $quantidade = (int)($_POST['quantidade'] ?? 1);
    $observacoes = trim($_POST['observacoes'] ?? '');
    
    if ($epi_id <= 0 || $quantidade <= 0) {
        $message = 'Selecione um EPI e quantidade válida.';
        $message_type = 'danger';
    } else {
        // Verificar disponibilidade do EPI
        $epi_result = executeQuery("
            SELECT nome, quantidade_estoque, validade 
            FROM epis 
            WHERE id = ? AND ativo = 1
        ", [$epi_id]);
        
        if (empty($epi_result)) {
            $message = 'EPI não encontrado.';
            $message_type = 'danger';
        } else {
            $epi = $epi_result[0];
            
            if ($epi['quantidade_estoque'] < $quantidade) {
                $message = 'Quantidade solicitada não disponível. Estoque atual: ' . $epi['quantidade_estoque'];
                $message_type = 'danger';
            } elseif ($epi['validade'] && strtotime($epi['validade']) < time()) {
                $message = 'Este EPI está vencido e não pode ser retirado.';
                $message_type = 'danger';
            } else {
                try {
                    // Iniciar transação
                    $pdo = getConnection();
                    $pdo->beginTransaction();
                    
                    $saldo_anterior = $epi['quantidade_estoque'];
                    $novo_saldo = $saldo_anterior - $quantidade;
                    
                    // Registrar movimentação
                    $success = executeUpdate(
                        "INSERT INTO movimentacoes (epi_id, funcionario_id, tipo_movimentacao, quantidade, observacoes, usuario_responsavel, saldo_anterior, saldo_atual) VALUES (?, ?, 'retirada', ?, ?, 'funcionario', ?, ?)",
                        [$epi_id, $funcionario_id, $quantidade, $observacoes, $saldo_anterior, $novo_saldo]
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
                        $message = 'EPI retirado com sucesso! Lembre-se de utilizá-lo adequadamente.';
                        $message_type = 'success';
                        
                        // Limpar formulário após sucesso
                        $epi_pre_selecionado = '';
                        $_POST = [];
                    } else {
                        $pdo->rollBack();
                        $message = 'Erro ao registrar retirada. Tente novamente.';
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
}

try {
    // Buscar EPIs disponíveis para retirada
    $epis_disponiveis = executeQuery("
        SELECT e.*, f.nome as fornecedor_nome,
               CASE 
                   WHEN e.validade < CURDATE() THEN 'Vencido'
                   WHEN e.validade <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Vencimento Próximo'
                   ELSE 'Válido'
               END as status_validade
        FROM epis e
        LEFT JOIN fornecedores f ON e.fornecedor_id = f.id
        WHERE e.ativo = 1 AND e.quantidade_estoque > 0 AND (e.validade IS NULL OR e.validade >= CURDATE())
        ORDER BY e.nome
    ");
    
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
    
    // Buscar categorias para filtro
    $categorias = executeQuery("
        SELECT DISTINCT categoria 
        FROM epis 
        WHERE categoria IS NOT NULL AND categoria != '' AND ativo = 1 AND quantidade_estoque > 0
        ORDER BY categoria
    ");
    
} catch (Exception $e) {
    $epis_disponiveis = [];
    $funcionario = ['nome' => 'Funcionário'];
    $categorias = [];
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
        <h3 class="card-title">Retirada de EPI</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>Funcionário:</strong> <?php echo htmlspecialchars($funcionario['nome'] ?? 'Funcionário'); ?><br>
            <strong>Empresa:</strong> <?php echo htmlspecialchars($funcionario['empresa_nome'] ?? 'Não informada'); ?><br>
            <strong>Data/Hora:</strong> <?php echo date('d/m/Y H:i'); ?>
        </div>
    </div>
</div>

<!-- Formulário de Retirada -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Selecionar EPI para Retirada</h3>
    </div>
    <div class="card-body">
        <?php if (empty($epis_disponiveis)): ?>
            <div class="alert alert-warning">
                <strong>Nenhum EPI disponível no momento.</strong><br>
                Não há EPIs em estoque ou todos estão vencidos. Entre em contato com o responsável.
            </div>
        <?php else: ?>
            <!-- Filtro rápido -->
            <div class="form-group">
                <label class="form-label" for="filtro_categoria">Filtrar por categoria:</label>
                <select id="filtro_categoria" class="form-control" onchange="filtrarEPIs()">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['categoria']); ?>">
                            <?php echo htmlspecialchars($cat['categoria']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <form method="POST" action="" id="form-retirada">
                <div class="form-group">
                    <label class="form-label" for="epi_id">EPI *</label>
                    <select id="epi_id" name="epi_id" class="form-control" required onchange="atualizarInfoEPI()">
                        <option value="">Selecione um EPI</option>
                        <?php foreach ($epis_disponiveis as $epi): ?>
                            <option value="<?php echo $epi['id']; ?>" 
                                    data-estoque="<?php echo $epi['quantidade_estoque']; ?>"
                                    data-categoria="<?php echo htmlspecialchars($epi['categoria']); ?>"
                                    data-validade="<?php echo $epi['validade']; ?>"
                                    data-status="<?php echo $epi['status_validade']; ?>"
                                    data-descricao="<?php echo htmlspecialchars($epi['descricao']); ?>"
                                    data-ca="<?php echo htmlspecialchars($epi['numero_ca']); ?>"
                                    <?php echo $epi_pre_selecionado == $epi['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($epi['nome']); ?> 
                                (Estoque: <?php echo $epi['quantidade_estoque']; ?>)
                                <?php if ($epi['status_validade'] != 'Válido'): ?>
                                    - <?php echo $epi['status_validade']; ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Informações do EPI Selecionado -->
                <div id="info-epi" style="display: none;" class="card" style="background: #f8f9fa; margin-bottom: 1rem;">
                    <div class="card-body">
                        <h5>Informações do EPI Selecionado</h5>
                        <div id="detalhes-epi"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="quantidade">Quantidade *</label>
                            <input type="number" id="quantidade" name="quantidade" class="form-control" 
                                   min="1" max="1" value="1" required>
                            <small id="quantidade-help" class="text-muted">Máximo disponível: -</small>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label" for="observacoes">Motivo da Retirada</label>
                            <select id="observacoes" name="observacoes" class="form-control">
                                <option value="">Selecione o motivo</option>
                                <option value="Uso rotineiro de trabalho">Uso rotineiro de trabalho</option>
                                <option value="Substituição de EPI danificado">Substituição de EPI danificado</option>
                                <option value="EPI perdido">EPI perdido</option>
                                <option value="Trabalho em área específica">Trabalho em área específica</option>
                                <option value="Treinamento">Treinamento</option>
                                <option value="Outro">Outro motivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="observacoes_extras">Observações Adicionais</label>
                    <textarea id="observacoes_extras" name="observacoes_extras" class="form-control" rows="3" 
                             placeholder="Informações adicionais sobre a retirada (opcional)"></textarea>
                </div>
                
                <!-- Termos de Responsabilidade -->
                <div class="form-group">
                    <div style="background: #e7f3ff; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                        <h6 style="color: #004085;">Termo de Responsabilidade</h6>
                        <p style="margin-bottom: 0.5rem; font-size: 14px;">
                            Ao retirar este EPI, declaro estar ciente de que:
                        </p>
                        <ul style="font-size: 14px; margin-bottom: 1rem;">
                            <li>Sou responsável pelo uso adequado e conservação do equipamento</li>
                            <li>Devo devolver o EPI quando solicitado ou ao final do uso</li>
                            <li>Em caso de dano ou perda, devo comunicar imediatamente</li>
                            <li>O EPI deve ser usado conforme as instruções de segurança</li>
                        </ul>
                        <label style="display: flex; align-items: center; font-size: 14px;">
                            <input type="checkbox" id="aceito_termos" required style="margin-right: 0.5rem;">
                            Declaro estar ciente e aceito os termos de responsabilidade
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" id="btn-retirar" disabled>
                        Confirmar Retirada
                    </button>
                    <a href="epis.php" class="btn btn-secondary">Cancelar</a>
                    <a href="index.php" class="btn btn-info">Voltar ao Dashboard</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Lista de EPIs Disponíveis -->
<?php if (!empty($epis_disponiveis)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">EPIs Disponíveis para Retirada</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>EPI</th>
                        <th>Categoria</th>
                        <th>Estoque</th>
                        <th>Validade</th>
                        <th>Status</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody id="tabela-epis">
                    <?php foreach ($epis_disponiveis as $epi): ?>
                    <tr data-categoria="<?php echo htmlspecialchars($epi['categoria']); ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($epi['nome']); ?></strong>
                            <?php if (!empty($epi['descricao'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($epi['descricao'], 0, 50)); ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($epi['categoria'] ?? '-'); ?></td>
                        <td class="text-center">
                            <span class="badge badge-<?php echo $epi['quantidade_estoque'] <= $epi['quantidade_minima'] ? 'warning' : 'success'; ?>">
                                <?php echo $epi['quantidade_estoque']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($epi['validade']): ?>
                                <?php echo date('d/m/Y', strtotime($epi['validade'])); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $epi['status_validade'] == 'Válido' ? 'success' : 'warning'; ?>">
                                <?php echo $epi['status_validade']; ?>
                            </span>
                        </td>
                        <td>
                            <button onclick="selecionarEPI(<?php echo $epi['id']; ?>)" class="btn btn-primary btn-sm">
                                Selecionar
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

<?php
// Script adicional para a página
$additional_scripts = "
<script>
function atualizarInfoEPI() {
    const select = document.getElementById('epi_id');
    const option = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('info-epi');
    const detalhesDiv = document.getElementById('detalhes-epi');
    const quantidadeInput = document.getElementById('quantidade');
    const quantidadeHelp = document.getElementById('quantidade-help');
    
    if (option.value) {
        const estoque = option.dataset.estoque;
        const categoria = option.dataset.categoria;
        const validade = option.dataset.validade;
        const status = option.dataset.status;
        const descricao = option.dataset.descricao;
        const ca = option.dataset.ca;
        
        quantidadeInput.max = estoque;
        quantidadeInput.value = Math.min(1, estoque);
        quantidadeHelp.textContent = 'Máximo disponível: ' + estoque;
        
        let detalhes = '<div class=\"form-row\">';
        detalhes += '<div class=\"form-col\">';
        if (categoria) detalhes += '<p><strong>Categoria:</strong> ' + categoria + '</p>';
        if (ca) detalhes += '<p><strong>CA:</strong> ' + ca + '</p>';
        detalhes += '<p><strong>Estoque:</strong> ' + estoque + ' unidades</p>';
        detalhes += '</div>';
        detalhes += '<div class=\"form-col\">';
        if (validade) detalhes += '<p><strong>Validade:</strong> ' + new Date(validade).toLocaleDateString('pt-BR') + '</p>';
        detalhes += '<p><strong>Status:</strong> <span class=\"badge badge-' + (status === 'Válido' ? 'success' : 'warning') + '\">' + status + '</span></p>';
        detalhes += '</div>';
        detalhes += '</div>';
        if (descricao) detalhes += '<p><strong>Descrição:</strong> ' + descricao + '</p>';
        
        detalhesDiv.innerHTML = detalhes;
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
        quantidadeInput.max = 1;
        quantidadeInput.value = 1;
        quantidadeHelp.textContent = 'Máximo disponível: -';
    }
}

function filtrarEPIs() {
    const categoria = document.getElementById('filtro_categoria').value.toLowerCase();
    const rows = document.querySelectorAll('#tabela-epis tr');
    const select = document.getElementById('epi_id');
    const options = select.querySelectorAll('option');
    
    // Filtrar tabela
    rows.forEach(row => {
        const rowCategoria = row.dataset.categoria.toLowerCase();
        row.style.display = categoria === '' || rowCategoria.includes(categoria) ? '' : 'none';
    });
    
    // Filtrar select
    options.forEach(option => {
        if (option.value === '') return; // Manter opção vazia
        const optionCategoria = option.dataset.categoria.toLowerCase();
        option.style.display = categoria === '' || optionCategoria.includes(categoria) ? '' : 'none';
    });
    
    // Resetar seleção se não estiver visível
    if (select.selectedIndex > 0 && select.options[select.selectedIndex].style.display === 'none') {
        select.selectedIndex = 0;
        atualizarInfoEPI();
    }
}

function selecionarEPI(epiId) {
    const select = document.getElementById('epi_id');
    select.value = epiId;
    atualizarInfoEPI();
    
    // Scroll para o formulário
    document.getElementById('form-retirada').scrollIntoView({ behavior: 'smooth' });
}

// Habilitar botão de retirada apenas quando termos forem aceitos
document.getElementById('aceito_termos').addEventListener('change', function() {
    document.getElementById('btn-retirar').disabled = !this.checked;
});

// Combinar observações
document.getElementById('form-retirada').addEventListener('submit', function(e) {
    const motivo = document.getElementById('observacoes').value;
    const extras = document.getElementById('observacoes_extras').value;
    let observacaoFinal = motivo;
    
    if (extras) {
        observacaoFinal += (motivo ? ' - ' : '') + extras;
    }
    
    // Criar campo hidden com observação final
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'observacoes';
    hiddenInput.value = observacaoFinal;
    this.appendChild(hiddenInput);
    
    // Remover name dos campos originais para evitar conflito
    document.getElementById('observacoes').name = '';
    document.getElementById('observacoes_extras').name = '';
});

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    atualizarInfoEPI();
});
</script>
";

// Incluir footer
include '../includes/footer.php';
?>