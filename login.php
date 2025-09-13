<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestão de EPIs Klarbyte</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Gestão de EPIs</h1>
                <p>Sistema Klarbyte</p>
            </div>
            
            <?php
            require_once 'config.php';
            
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $email = sanitizar($_POST['email']);
                $senha = $_POST['senha'];
                
                $database = new Database();
                $db = $database->getConnection();
                
                $query = "SELECT id, nome, senha FROM usuarios WHERE email = ? AND ativo = 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $email);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (password_verify($senha, $row['senha'])) {
                        session_start();
                        $_SESSION['usuario_id'] = $row['id'];
                        $_SESSION['usuario_nome'] = $row['nome'];
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        $erro = "Email ou senha incorretos.";
                    }
                } else {
                    $erro = "Email ou senha incorretos.";
                }
            }
            ?>
            
            <?php if (isset($erro)): ?>
                <div class="alert alert-danger"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Entrar</button>
            </form>
            
            <div style="margin-top: 1rem; text-align: center; font-size: 0.875rem; color: #666;">
                <p>Usuário padrão: admin@klarbyte.com</p>
                <p>Senha padrão: password</p>
            </div>
        </div>
    </div>
</body>
</html>