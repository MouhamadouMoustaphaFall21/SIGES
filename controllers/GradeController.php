<?php

/**
 * Contrôleur de Gestion des Notes - SIGES
 * Traite l'enregistrement massif des notes envoyé par le professeur
 */
session_start();

// 1. Sécurité : Vérifier le rôle Enseignant
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Professeur') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';
require_once '../models/Grade.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['notes'])) {

    $database = new Database();
    $db = $database->getConnection();
    $gradeModel = new Grade($db);

    $id_evaluation = $_POST['id_evaluation'];
    $id_classe = $_POST['id_classe'];
    $notesArray = $_POST['notes']; // Tableau : [id_etudiant => note]

    try {
        // Début de la transaction pour garantir l'intégrité des données
        $db->beginTransaction();

        foreach ($notesArray as $id_etudiant => $note) {
            // On ne traite que si la note n'est pas vide (gestion du step 0.25)
            if ($note !== "") {
                $gradeModel->saveNote($id_etudiant, $id_evaluation, $note);
            }
        }

        // Si tout s'est bien passé, on valide
        $db->commit();

        // Redirection avec message de succès
        header("Location: ../views/professeur/grades_entry.php?id_classe=$id_classe&status=success");
        exit();
    } catch (Exception $e) {
        // En cas d'erreur (ex: contrainte de clé étrangère), on annule tout
        $db->rollBack();
        header("Location: ../views/professeur/grades_entry.php?id_classe=$id_classe&status=error");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}

// Dans /controllers/GradeController.php, ajoute cette condition :

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_reclamation') {
    require_once '../models/Student.php';
    $studentModel = new Student($db);
    $profile = $studentModel->getProfileByLogin($_SESSION['user_login']);
    
    $res = $gradeModel->createReclamation(
        $profile['id_Etudiant'], 
        $_POST['id_evaluation'], 
        $_POST['motif']
    );

    header("Location: ../views/etudiant/reclamation.php?status=" . ($res ? "sent" : "error"));
    exit();
}