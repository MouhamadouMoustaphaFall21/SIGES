<?php
session_start();

require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Teacher.php';
require_once '../models/Grade.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Professeur') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    header("Location: ../views/professeur/dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);
$teacherModel = new Teacher($db);
$gradeModel = new Grade($db);

$profData = $teacherModel->getProfileByLogin($_SESSION['user_login']);
$id_prof = $profData['Id_Professeur'];
$classStmt = $teacherModel->getAssignedClasses($id_prof);
$assignedClasses = $classStmt->fetchAll(PDO::FETCH_ASSOC);
$allowedClassIds = array_column($assignedClasses, 'Id_Classe');

if ($_POST['action'] === 'create_evaluation') {
    $date_eval = $_POST['date_eval'] ?? date('Y-m-d');
    $semestre  = intval($_POST['semestre'] ?? 1);
    $matiereId = $profData['Id_Matiere'];
    $id_classe_eval = intval($_POST['id_classe'] ?? 0);

    // Vérifier que le prof est bien affecté à cette classe
    if ($id_classe_eval && !in_array($id_classe_eval, $allowedClassIds, true)) {
        header("Location: ../views/professeur/dashboard.php?status=error");
        exit();
    }

    $newId = $gradeModel->createEvaluation($date_eval, $semestre, $matiereId, $id_prof, $id_classe_eval);
    $redirect = $newId ? 'eval_created' : 'error';
    $classeParam = $id_classe_eval ? '&id_classe='.$id_classe_eval : '';
    header("Location: ../views/professeur/dashboard.php?status=$redirect$classeParam");
    exit();
}

if ($_POST['action'] === 'add_student') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $id_classe = intval($_POST['id_classe'] ?? 0);

    if (!$login || !$password || !$nom || !$prenom || !in_array($id_classe, $allowedClassIds, true)) {
        header("Location: ../views/professeur/dashboard.php?status=error");
        exit();
    }

    $created = $userModel->createStudent($login, $password, $nom, $prenom, $id_classe);
    header("Location: ../views/professeur/dashboard.php?status=" . ($created ? 'student_added' : 'error'));
    exit();
}

header("Location: ../views/professeur/dashboard.php");
exit();
