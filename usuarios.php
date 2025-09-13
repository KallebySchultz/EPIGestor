<?php
require_once 'config.php';
verificarSessao();

$database = new Database();
$db = $database->getConnection();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'criar_usuario':
                $nome = sanitizar($_POST['nome']);
                $email = sanitizar($_POST['email']);
                $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                
                // Verificar se email já existe
                $query = "SELECT id FROM usuarios WHERE email = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $erro = "Este email já está em uso.";
                } else {
                    $query = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$nome, $email, $senha])) {
                        $sucesso = "Usuário criado com sucesso!";
                    } else {
                        $erro = "Erro ao criar usuário.";
                    }
                }
                break;
                
            case 'editar_usuario':
                $id = (int)$_POST['id'];
                $nome = sanitizar($_POST['nome']);
                $email = sanitizar($_POST['email']);
                
                // Verificar se email já existe (exceto para o próprio usuário)
                $query = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$email, $id]);
                
                if ($stmt->rowCount() > 0) {
                    $erro = "Este email já está em uso por outro usuário.";
                } else {
                    if (!empty($_POST['senha'])) {
                        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                        $query = "UPDATE usuarios SET nome = ?, email = ?, senha = ? WHERE id = ?";
                        $params = [$nome, $email, $senha, $id];
                    } else {
                        $query = "UPDATE usuarios SET nome = ?, email = ? WHERE id = ?";
                        $params = [$nome, $email, $id];
                    }
                    
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute($params)) {
                        $sucesso = "Usuário atualizado com sucesso!";
                        
                        // Se o usuário editou seus próprios dados, atualizar a sessão
                        if ($id == $_SESSION['usuario_id']) {
                            $_SESSION['usuario_nome'] = $nome;
                        }
                    } else {
                        $erro = "Erro ao atualizar usuário.";
                    }
                }
                break;
                
            case 'desativar_usuario':
                $id = (int)$_POST['id'];
                
                // Não permitir desativar o próprio usuário
                if ($id == $_SESSION['usuario_id']) {
                    $erro = "Você não pode desativar sua própria conta.";
                } else {
                    $query = "UPDATE usuarios SET ativo = 0 WHERE id = ?";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$id])) {
                        $sucesso = "Usuário desativado com sucesso!";
                    } else {
                        $erro = "Erro ao desativar usuário.";
                    }
                }
                break;
                
            case 'ativar_usuario':
                $id = (int)$_POST['id'];
                
                $query = "UPDATE usuarios SET ativo = 1 WHERE id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$id])) {
                    $sucesso = "Usuário ativado com sucesso!";
                } else {
                    $erro = "Erro ao ativar usuário.";
                }
                break;
        }
    }
}

// Buscar usuários
$query = "SELECT * FROM usuarios ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar usuário para edição
$usuario_editando = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $query = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $usuario_editando = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - Gestão de EPIs Klarbyte</title>
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
                <li class="nav-item"><a href="movimentacoes.php">Movimentações</a></li>
                <li class="nav-item active"><a href="usuarios.php">Usuários</a></li>
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
                <?php echo $usuario_editando ? 'Editar Usuário' : 'Criar Novo Usuário'; ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="<?php echo $usuario_editando ? 'editar_usuario' : 'criar_usuario'; ?>">
                    <?php if ($usuario_editando): ?>
                        <input type="hidden" name="id" value="<?php echo $usuario_editando['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome">Nome Completo:</label>
                            <input type="text" id="nome" name="nome" class="form-control" 
                                   value="<?php echo $usuario_editando ? $usuario_editando['nome'] : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo $usuario_editando ? $usuario_editando['email'] : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="senha">
                            <?php echo $usuario_editando ? 'Nova Senha (deixe em branco para manter a atual):' : 'Senha:'; ?>
                        </label>
                        <input type="password" id="senha" name="senha" class="form-control" 
                               <?php echo $usuario_editando ? '' : 'required'; ?> minlength="6">
                        <small class="form-text">A senha deve ter pelo menos 6 caracteres.</small>
                    </div>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $usuario_editando ? 'Atualizar' : 'Criar Usuário'; ?>
                        </button>
                        
                        <?php if ($usuario_editando): ?>
                            <a href="usuarios.php" class="btn btn-warning">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de usuários -->
        <div class="card">
            <div class="card-header">Usuários do Sistema</div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Data de Criação</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td>
                                    <?php echo $usuario['nome']; ?>
                                    <?php if ($usuario['id'] == $_SESSION['usuario_id']): ?>
                                        <span class="status-badge status-info" style="margin-left: 0.5rem;">Você</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $usuario['email']; ?></td>
                                <td><?php echo formatarDataHora($usuario['data_criacao']); ?></td>
                                <td>
                                    <?php if ($usuario['ativo']): ?>
                                        <span class="status-badge status-ok">Ativo</span>
                                    <?php else: ?>
                                        <span class="status-badge status-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="usuarios.php?editar=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                    
                                    <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                        <?php if ($usuario['ativo']): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja desativar este usuário?')">
                                                <input type="hidden" name="acao" value="desativar_usuario">
                                                <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Desativar</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="acao" value="ativar_usuario">
                                                <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Ativar</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Informações importantes -->
        <div class="alert alert-info">
            <strong>Importante:</strong>
            <ul style="margin: 0.5rem 0 0 1rem;">
                <li>Todos os usuários têm acesso total ao sistema (não há níveis de acesso diferentes).</li>
                <li>Você não pode desativar sua própria conta.</li>
                <li>Usuários desativados não conseguem fazer login no sistema.</li>
                <li>A senha padrão para novos usuários deve ser alterada no primeiro login.</li>
            </ul>
        </div>
    </div>
</body>
</html>