<?php
require_once '../../config/auth.php';
requireRole('Professeur');
require_once '../../config/database.php';
require_once '../../models/Student.php';
require_once '../../models/Teacher.php';

$database = new Database();
$db = $database->getConnection();

$studentModel = new Student($db);
$teacherModel = new Teacher($db);

// Vérifier que la classe est affectée au prof
$profData = $teacherModel->getProfileByLogin($_SESSION['user_login']);
$id_classe = isset($_GET['id_classe']) ? intval($_GET['id_classe']) : 0;

$assignedClasses = $teacherModel->getAssignedClasses($profData['Id_Professeur'])->fetchAll(PDO::FETCH_ASSOC);
$selected_classe = isset($_GET['id_classe']) ? intval($_GET['id_classe']) : 0;
if (!$selected_classe && count($assignedClasses) > 0) {
    $selected_classe = $assignedClasses[0]['Id_Classe'];
}

$allowed = false;
$selectedClassLabel = 'Classe';
foreach ($assignedClasses as $ac) {
    if ($ac['Id_Classe'] == $selected_classe) {
        $allowed = true;
        $selectedClassLabel = $ac['libelle'] . ' ' . $ac['niveau'];
        break;
    }
}

if (!$allowed) {
    if (count($assignedClasses) > 0) {
        header("Location: view_students.php?id_classe=" . $assignedClasses[0]['Id_Classe']);
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$students = $studentModel->getByClasse($selected_classe)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Élèves de la Classe - SIGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
</head>
<body>
    <div class="student-shell">
        <?php
$active_page = 'view_students';
include '_sidebar.php';
?>

        <main class="student-main">
            <section class="page-header page-header-schedule">
                <div>
                    <p class="eyebrow">Liste des élèves</p>
                    <h1><?= htmlspecialchars($selectedClassLabel) ?></h1>
                    <p>Retrouvez ici les élèves inscrits dans votre classe.</p>
                </div>
                <div class="header-user-card">
                    <strong><?= htmlspecialchars($profData['prenom'] . ' ' . $profData['nom']) ?></strong>
                    <span><?= htmlspecialchars($profData['nom_matiere']) ?></span>
                </div>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Élèves de la classe</h2>
                    <form method="GET" style="display:inline-flex; gap:12px; align-items:center;">
                        <label>Choisir la classe :</label>
                        <select name="id_classe" onchange="this.form.submit()">
                            <?php foreach ($assignedClasses as $c): ?>
                                <option value="<?= $c['Id_Classe'] ?>" <?= $selected_classe == $c['Id_Classe'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['libelle'] . ' ' . $c['niveau']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['nom']) ?></td>
                                    <td><?= htmlspecialchars($s['prenom']) ?></td>
                                    <td><?= htmlspecialchars($s['login']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>