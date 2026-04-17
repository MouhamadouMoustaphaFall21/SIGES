<?php

/**
 * Interface de saisie des notes - SIGES
 * Permet au professeur de saisir les notes pour une classe et une évaluation précise
 */
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Professeur') {
    header("Location: ../../index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../models/Teacher.php';
require_once '../../models/Student.php';
require_once '../../models/Grade.php';

$database = new Database();
$db = $database->getConnection();

$teacherModel = new Teacher($db);
$studentModel = new Student($db);
$gradeModel = new Grade($db);

// 1. Récupérer les infos du prof et l'ID de la classe depuis l'URL
$profData = $teacherModel->getProfileByLogin($_SESSION['user_login']);
$assignedClasses = $teacherModel->getAssignedClasses($profData['Id_Professeur'])->fetchAll(PDO::FETCH_ASSOC);
$selected_classe = isset($_GET['id_classe']) ? intval($_GET['id_classe']) : 0;
if (!$selected_classe && count($assignedClasses) > 0) {
    $selected_classe = $assignedClasses[0]['Id_Classe'];
}

$allowed = false;
$selectedClassLabel = 'Classe';
foreach ($assignedClasses as $c) {
    if ($c['Id_Classe'] == $selected_classe) {
        $allowed = true;
        $selectedClassLabel = $c['libelle'] . ' ' . $c['niveau'];
        break;
    }
}
if (!$allowed) {
    if (count($assignedClasses) > 0) {
        header('Location: grades_entry.php?id_classe=' . $assignedClasses[0]['Id_Classe']);
        exit();
    }
    header('Location: dashboard.php');
    exit();
}

// 2. Récupérer les évaluations du professeur
$allEvaluations = $gradeModel->getTeacherEvaluations($profData['Id_Professeur']);
$id_evaluation = isset($_GET['id_evaluation']) ? intval($_GET['id_evaluation']) : null;
if (!$id_evaluation && count($allEvaluations) > 0) {
    $id_evaluation = $allEvaluations[0]['Id_Evaluation'];
}

// 3. Vérifier qu'il y a une évaluation sélectionnée
if (!$id_evaluation) {
    $students = [];
} else {
    $queryStudents = "SELECT e.id_Etudiant, e.nom, e.prenom, eff.note 
                      FROM etudiant e
                      LEFT JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant AND eff.Id_Evaluation = :id_ev
                      WHERE e.Id_Classe = :id_c
                      ORDER BY e.nom ASC";
    $stmtStudents = $db->prepare($queryStudents);
    $stmtStudents->execute(['id_ev' => $id_evaluation, 'id_c' => $selected_classe]);
    $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Saisie des notes - SIGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
</head>

<body>
    <div class="student-shell">
        <aside class="student-sidebar">
            <div class="sidebar-brand">
                <img src="../../assets/img/logo_simple-SAP.png" alt="SIGES logo">
                <div class="brand-title">
                    <strong>SIGES</strong>
                    <span>Espace Enseignant</span>
                </div>
            </div>

            <div class="profile-box">
                <div class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($profData['prenom'], 0, 1) . substr($profData['nom'], 0, 1))) ?></div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($profData['prenom'] . ' ' . $profData['nom']) ?></h2>
                    <p><?= htmlspecialchars($profData['nom_matiere']) ?></p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php"><i class='bx bx-grid-alt'></i>Dashboard</a>
                <a href="grades_entry.php?id_classe=<?= $selected_classe ?>" class="active"><i class='bx bx-edit'></i>Saisir notes</a>
                <a href="view_students.php?id_classe=<?= $selected_classe ?>"><i class='bx bx-group'></i>Mes élèves</a>
                <a href="view_grades.php?id_classe=<?= $selected_classe ?>"><i class='bx bx-bar-chart-alt-2'></i>Classement</a>
            </nav>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">
            <section class="page-header page-header-reclamation">
                <div>
                    <p class="eyebrow">Saisie des notes</p>
                    <h1><?= htmlspecialchars($selectedClassLabel) ?></h1>
                    <p>Choisissez l'évaluation et saisissez les notes des étudiants.</p>
                </div>
                <div class="header-user-card">
                    <strong><?= htmlspecialchars($profData['prenom'] . ' ' . $profData['nom']) ?></strong>
                    <span><?= htmlspecialchars($profData['nom_matiere']) ?></span>
                </div>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Sélection de l'évaluation</h2>
                </div>

                <div class="form-box" style="max-width: 600px;">
                    <form method="GET">
                        <div class="form-group">
                            <label>Classe</label>
                            <select name="id_classe" onchange="this.form.submit()" style="max-width: none;">
                                <?php foreach ($assignedClasses as $c): ?>
                                    <option value="<?= $c['Id_Classe'] ?>" <?= $selected_classe == $c['Id_Classe'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['libelle'] . ' ' . $c['niveau']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Évaluation à saisir</label>
                            <select name="id_evaluation" onchange="this.form.submit()" style="max-width: none;">
                                <option value="">-- Choisir une évaluation --</option>
                                <?php foreach ($allEvaluations as $evalItem): ?>
                                    <option value="<?= $evalItem['Id_Evaluation'] ?>" <?= $id_evaluation == $evalItem['Id_Evaluation'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($evalItem['matiere']) ?> - S<?= htmlspecialchars($evalItem['semestre']) ?> - <?= htmlspecialchars($evalItem['date_eval']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                    <?php if (!$id_evaluation): ?>
                        <div class="form-hint" style="margin-top:16px;">Aucune évaluation disponible. Créez-en une depuis votre tableau de bord.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Liste des étudiants</h2>
                </div>

                <?php if ($id_evaluation): ?>
                    <form action="../../controllers/GradeController.php" method="POST">
                        <input type="hidden" name="id_evaluation" value="<?= $id_evaluation ?>">
                        <input type="hidden" name="id_classe" value="<?= $selected_classe ?>">

                        <div class="table-card">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Étudiant</th>
                                        <th>Note / 20</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $s): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($s['nom'] . ' ' . $s['prenom']) ?></td>
                                            <td>
                                                <input type="number"
                                                    name="notes[<?= $s['id_Etudiant'] ?>]"
                                                    value="<?= $s['note'] ?>"
                                                    step="0.25" min="0" max="20" required>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="display:flex; justify-content:flex-end; margin-top:20px;">
                            <button type="submit" class="button-success">Enregistrer toutes les notes</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="form-hint" style="margin-top: 16px;">Aucune évaluation sélectionnée pour cette classe. Veuillez créer une évaluation depuis le tableau de bord.</div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>