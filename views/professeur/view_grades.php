<?php
require_once '../../config/auth.php';
requireRole('Professeur');
require_once '../../config/database.php';
require_once '../../models/Grade.php';
require_once '../../models/Teacher.php';

$database = new Database();
$db = $database->getConnection();

$gradeModel = new Grade($db);
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
        header("Location: view_grades.php?id_classe=" . $assignedClasses[0]['Id_Classe']);
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$ranking = $gradeModel->getRankingByClasse($selected_classe);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Classement des Élèves - SIGES</title>
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
                <a href="grades_entry.php?id_classe=<?= $id_classe ?>"><i class='bx bx-edit'></i>Saisir notes</a>
                <a href="view_students.php?id_classe=<?= $id_classe ?>"><i class='bx bx-group'></i>Mes élèves</a>
                <a href="view_grades.php?id_classe=<?= $id_classe ?>" class="active"><i class='bx bx-bar-chart-alt-2'></i>Classement</a>
            </nav>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">
            <section class="page-header page-header-schedule">
                <div>
                    <p class="eyebrow">Classement</p>
                    <h1><?= htmlspecialchars($selectedClassLabel) ?></h1>
                    <p>Consultez le classement des étudiants de votre classe avec les moyennes générales.</p>
                </div>
                <div class="header-user-card">
                    <strong><?= htmlspecialchars($profData['prenom'] . ' ' . $profData['nom']) ?></strong>
                    <span><?= htmlspecialchars($profData['nom_matiere']) ?></span>
                </div>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Classement des Élèves</h2>
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
                                <th>Rang</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Moyenne Générale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; foreach ($ranking as $r): ?>
                                <tr>
                                    <td><?= $rank++ ?></td>
                                    <td><?= htmlspecialchars($r['nom']) ?></td>
                                    <td><?= htmlspecialchars($r['prenom']) ?></td>
                                    <td><?= number_format($r['moyenne_gen'], 2) ?> / 20</td>
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