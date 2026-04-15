<?php
/**
 * Contrôleur d'Authentification - SIGES
 * Gère les sessions et les redirections par rôle
 */
session_start();
require_once '../config/database.php';
require_once '../models/User.php';

// On vérifie que le formulaire a bien été soumis
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login']) && isset($_POST['password'])) {
    
    // 1. Initialisation de la connexion et du modèle
    $database = new Database();
    $db = $database->getConnection();
    $userModel = new User($db);

    // 2. Récupération et assignation des données
    $userModel->login = $_POST['login'];
    $userModel->password = $_POST['password'];

    // 3. Tentative de connexion
    $userData = $userModel->login();

    if ($userData) {
        // Authentification réussie
        $_SESSION['user_login'] = $userData['login'];
        $_SESSION['user_role'] = $userData['role'];

        // 4. Redirection selon le rôle (Admin, Professeur, Etudiant)
        // On utilise strtolower() pour correspondre aux noms de dossiers en minuscules
        $roleDir = strtolower($userData['role']);
        
        header("Location: ../views/" . $roleDir . "/dashboard.php");
        exit();
    } else {
        // Échec de l'authentification
        header("Location: ../index.php?error=login_failed");
        exit();
    }
} else {
    // Accès direct au fichier interdit sans POST
    header("Location: ../index.php");
    exit();
}
?>