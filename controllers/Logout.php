<?php

/**
 * Contrôleur de Déconnexion - SIGES
 * Nettoie la session et sécurise la sortie de l'utilisateur
 */
session_start();

// 1. On vide toutes les variables de session
$_SESSION = array();

// 2. Si on veut détruire complètement la session, on efface aussi le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. On détruit la session côté serveur
session_destroy();

// 4. Redirection vers la page de login
header("Location: ../index.php");
exit();
