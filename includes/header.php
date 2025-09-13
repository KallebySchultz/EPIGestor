<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Gestão de EPIs Klarbyte</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>Klarbyte EPIs</h1>
                <small><?php echo isset($panel_type) ? $panel_type : 'Sistema de Gestão'; ?></small>
            </div>
            <div class="user-info">
                <?php if(isset($user_name)): ?>
                    <span>Olá, <?php echo htmlspecialchars($user_name); ?></span>
                <?php endif; ?>
                <a href="../index.html" class="btn btn-secondary btn-sm">Voltar ao Início</a>
            </div>
        </div>
    </header>

    <!-- Navegação -->
    <nav class="nav">
        <div class="nav-content">
            <ul class="nav-menu">
                <?php if(isset($is_admin) && $is_admin): ?>
                    <!-- Menu Administrador -->
                    <li class="nav-item">
                        <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="epis.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'epis.php' ? 'active' : ''; ?>">
                            EPIs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="funcionarios.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'funcionarios.php' ? 'active' : ''; ?>">
                            Funcionários
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="movimentacoes.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'movimentacoes.php' ? 'active' : ''; ?>">
                            Movimentações
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="estoque.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'estoque.php' ? 'active' : ''; ?>">
                            Estoque
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="relatorios.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'relatorios.php' ? 'active' : ''; ?>">
                            Relatórios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="retry.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'retry.php' ? 'active' : ''; ?>">
                            🔄 Retry Requests
                        </a>
                    </li>
                <?php else: ?>
                    <!-- Menu Usuário -->
                    <li class="nav-item">
                        <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            Início
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="epis.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'epis.php' ? 'active' : ''; ?>">
                            EPIs Disponíveis
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="movimentacoes.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'movimentacoes.php' ? 'active' : ''; ?>">
                            Minhas Movimentações
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="retirar.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'retirar.php' ? 'active' : ''; ?>">
                            Retirar EPI
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="devolver.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'devolver.php' ? 'active' : ''; ?>">
                            Devolver EPI
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Container Principal -->
    <div class="container">