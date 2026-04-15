<?php
/**
 * Fonctions de sécurité - SIGES
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_login']);
}

function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /SiGES2/index.php?error=session_expired");
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header("Location: /SiGES2/index.php?error=access_denied");
        exit();
    }
}
?>