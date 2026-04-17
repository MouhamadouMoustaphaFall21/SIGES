<?php
require_once '../config/database.php';
require_once '../models/Schedule.php';
require_once '../config/auth.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$scheduleModel = new Schedule($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasRole('Admin')) {
    $action = $_POST['action'] ?? 'add_slot';

    if ($action === 'add_slot') {
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

    if ($action === 'update_slot') {
        $data = [
            'id' => $_POST['id_slot'],
            'jour' => $_POST['jour'],
            'h_debut' => $_POST['heure_debut'],
            'h_fin' => $_POST['heure_fin'],
            'id_c' => $_POST['id_classe'],
            'id_p' => $_POST['id_professeur'],
            'id_m' => $_POST['id_matiere']
        ];

        if ($scheduleModel->update($data)) {
            header("Location: ../views/admin/schedule.php?status=updated");
        } else {
            header("Location: ../views/admin/schedule.php?status=error");
        }
        exit();
    }

    if ($action === 'delete_slot') {
        $id = $_POST['id_slot'];
        if ($scheduleModel->delete($id)) {
            header("Location: ../views/admin/schedule.php?status=deleted");
        } else {
            header("Location: ../views/admin/schedule.php?status=error");
        }
        exit();
    }
}
?>