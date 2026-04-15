<?php
require_once '../config/database.php';
require_once '../models/Schedule.php';
require_once '../config/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$scheduleModel = new Schedule($db);

// Si l'admin ajoute un créneau
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasRole('Admin')) {
    $data = [
        'jour' => $_POST['jour'],
        'h_debut' => $_POST['heure_debut'],
        'h_fin' => $_POST['heure_fin'],
        'id_c' => $_POST['id_classe'],
        'id_p' => $_POST['id_professeur'],
        'id_m' => $_POST['id_matiere']
    ];

    if ($scheduleModel->create($data)) {
        header("Location: ../views/admin/schedule.php?status=success");
    } else {
        header("Location: ../views/admin/schedule.php?status=error");
    }
    exit();
}
?>