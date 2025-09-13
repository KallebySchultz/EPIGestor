<?php
/**
 * Sistema de Login Simples - Sistema de Gestão de EPIs Klarbyte
 * Login apenas para administradores
 */

session_start();

// Incluir conexão com banco
require_once 'config/database_simple.php';

// Verificar se já está logado
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin/dashboard.php');
    exit;
}

$error_message = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Por favor, preencha todos os campos.';
    } else {
        // Verificar credenciais
        $admin = executeQuery("SELECT * FROM admin_login WHERE username = ?", [$username]);
        
        if (!empty($admin) && password_verify($password, $admin[0]['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_id'] = $admin[0]['id'];
            
            header('Location: admin/dashboard.php');
            exit;
        } else {
            $error_message = 'Usuário ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gestão de EPIs Klarbyte</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 5rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            color: #007bff;
            margin-bottom: 0.5rem;
        }
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .login-form label {
            font-weight: bold;
            color: #333;
        }
        .login-form input[type="text"],
        .login-form input[type="password"] {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        .login-form input[type="text"]:focus,
        .login-form input[type="password"]:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        .login-btn {
            padding: 0.75rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .login-btn:hover {
            background: #0056b3;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
            margin-bottom: 1rem;
            text-align: center;
        }
        .demo-info {
            margin-top: 2rem;
            padding: 1rem;
            background: #e7f3ff;
            border-radius: 5px;
            text-align: center;
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    
    <div class="login-container">
        <div class="login-header">
            <h1>Sistema de EPIs</h1>
            <p>Klarbyte - Acesso Administrativo</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <label for="username">Usuário:</label>
            <input 
                type="text" 
                id="username" 
                name="username" 
                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                required
                autocomplete="username"
            >
            
            <label for="password">Senha:</label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                required
                autocomplete="current-password"
            >
            
            <button type="submit" class="login-btn">Entrar</button>
        </form>
        
        <div class="demo-info">
            <strong>Acesso de Demonstração:</strong><br>
            Usuário: <code>admin</code><br>
            Senha: <code>admin123</code>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="index.html" style="color: #007bff; text-decoration: none;">
                ← Voltar ao início
            </a>
        </div>
    </div>
    
    <script>
        // Focar no campo usuário quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Permitir Enter no campo senha para submeter form
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    </script>
</body>
</html>