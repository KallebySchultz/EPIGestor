<?php
/**
 * Verificação de Autenticação Simples
 * Sistema de Gestão de EPIs Klarbyte
 */

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se o usuário está logado
 * Redireciona para login se não estiver
 */
function requireLogin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: ../login.php');
        exit;
    }
}

/**
 * Retorna informações do admin logado
 * @return array
 */
function getLoggedAdmin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'] ?? 0,
        'username' => $_SESSION['admin_username'] ?? 'Admin'
    ];
}

/**
 * Faz logout do sistema
 */
function logout() {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

/**
 * Processa logout se solicitado
 */
if (isset($_GET['logout'])) {
    logout();
}
?>