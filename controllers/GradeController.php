<?php

session_start();

require_once '../config/database.php';
require_once '../models/Grade.php';

// ── Soumission d'une réclamation (depuis vue étudiant) ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'submit_reclamation'
) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Etudiant') {
        header("Location: ../index.php");
        exit();
    }

    require_once '../models/Student.php';

    $database    = new Database();
    $db          = $database->getConnection();
    $gradeModel  = new Grade($db);
    $studentModel = new Student($db);

    $profile = $studentModel->getProfileByLogin($_SESSION['user_login']);
    $res = $gradeModel->createReclamation(
        $profile['id_Etudiant'],
        intval($_POST['id_evaluation']),
        trim($_POST['type_reclamation'] ?? ''),
        strip_tags($_POST['motif'] ?? '')
    );

    header("Location: ../views/etudiant/reclamation.php?status=" . ($res ? 'sent' : 'error'));
    exit();
}

// ── Enregistrement des notes ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notes'])) {

    $database = new Database();
    $db       = $database->getConnection();
    $gradeModel = new Grade($db);

    $id_evaluation = intval($_POST['id_evaluation']);
    $id_classe     = intval($_POST['id_classe']);
    $notesArray    = $_POST['notes'];

    try {
        $db->beginTransaction();

        foreach ($notesArray as $id_etudiant => $note) {
            if ($note !== '') {
                $gradeModel->saveNote(intval($id_etudiant), $id_evaluation, $note);
            }
        }

        $db->commit();

        // ✅ Redirection avec message de succès vers la saisie de notes
        header("Location: ../views/professeur/grades_entry.php?id_classe=$id_classe&id_evaluation=$id_evaluation&status=success");
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        header("Location: ../views/professeur/grades_entry.php?id_classe=$id_classe&status=error");
        exit();
    }
}

// ── Mise à jour du statut d'une réclamation (enseignant) ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'update_reclamation_status'
) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Professeur') {
        header("Location: ../index.php");
        exit();
    }

    require_once '../models/Teacher.php';

    $database   = new Database();
    $db         = $database->getConnection();
    $gradeModel = new Grade($db);
    $teacherModel = new Teacher($db);

    $profData = $teacherModel->getProfileByLogin($_SESSION['user_login']);
    $idReclamation = intval($_POST['id_reclamation'] ?? 0);
    $newStatus     = trim($_POST['new_status'] ?? '');
    $commentaire   = trim($_POST['commentaire_prof'] ?? '');
    $allowedStatuses = ['Corrigé', 'Décliné'];

    if ($idReclamation > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $res = $gradeModel->updateReclamationStatus($idReclamation, $newStatus, $commentaire);
        header("Location: ../views/professeur/reclamations.php?status=" . ($res ? 'updated' : 'error'));
        exit();
    }

    header("Location: ../views/professeur/reclamations.php?status=error");
    exit();
}

// Accès direct interdit
header("Location: ../index.php");
exit();
