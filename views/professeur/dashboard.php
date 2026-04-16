<?php

/**
 * Dashboard Enseignant - SIGES
 * Gestion des classes affectées, statistiques et saisie des notes
 */
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Professeur') {
    header("Location: ../../index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../models/Teacher.php';
require_once '../../models/Grade.php';

$database = new Database();
$db = $database->getConnection();
$teacherModel = new Teacher($db);
$gradeModel = new Grade($db);

$profData = $teacherModel->getProfileByLogin($_SESSION['user_login']);
$id_prof = $profData['Id_Professeur'];

$classesStmt = $teacherModel->getAssignedClasses($id_prof);
$classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

$selected_classe = isset($_GET['id_classe']) ? intval($_GET['id_classe']) : ($classes[0]['Id_Classe'] ?? null);
$teacherSummary = $gradeModel->getTeacherClassSummary($id_prof);
$distribution = $selected_classe ? $gradeModel->getClassDistribution($selected_classe) : ['0-7' => 0, '8-9.99' => 0, '10-12.99' => 0, '13-15.99' => 0, '16-20' => 0];
$evaluations = $gradeModel->getTeacherEvaluations($id_prof);

$querySchedule = "SELECT cr.*, cl.libelle as classe_nom 
                  FROM creneau cr
                  JOIN classe cl ON cr.Id_Classe = cl.Id_Classe
                  WHERE cr.Id_Professeur = :id_prof
                  ORDER BY FIELD(cr.jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'), cr.heure_debut";
$stmtSchedule = $db->prepare($querySchedule);
$stmtSchedule->execute(['id_prof' => $id_prof]);
$schedule = $stmtSchedule->fetchAll(PDO::FETCH_ASSOC);

$statusMessage = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'eval_created':
            $statusMessage = 'Évaluation créée avec succès.';
            break;
        case 'student_added':
            $statusMessage = 'Étudiant ajouté avec succès.';
            break;
        case 'error':
            $statusMessage = 'Une erreur est survenue. Vérifiez les champs.';
            break;
    }
}

$chartLabels = [];
$chartAverages = [];
$chartSuccess = [];
foreach ($teacherSummary as $item) {
    $chartLabels[] = htmlspecialchars($item['libelle'] . ' ' . $item['niveau']);
    $chartAverages[] = round(floatval($item['moyenne_classe']), 2);
    $chartSuccess[] = round(floatval($item['taux_reussite']), 2);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Espace Enseignant - SIGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="dashboard.php" class="active"><i class='bx bx-grid-alt'></i>Dashboard</a>
                <a href="grades_entry.php?id_classe=<?= $selected_classe ?>"><i class='bx bx-edit'></i>Saisir notes</a>
                <a href="view_students.php?id_classe=<?= $selected_classe ?>"><i class='bx bx-group'></i>Mes élèves</a>
                <a href="view_grades.php?id_classe=<?= $selected_classe ?>"><i class='bx bx-bar-chart-alt-2'></i>Classement</a>
            </nav>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">
            <section class="page-header page-header-dashboard">
                <div>
                    <p class="eyebrow">Tableau de bord</p>
                    <h1>Bonjour <?= htmlspecialchars($profData['prenom']) ?>,</h1>
                    <p>Retrouvez vos classes affectées, vos évaluations et des statistiques de réussite pour chaque promotion.</p>
                    <?php if ($statusMessage): ?>
                        <div class="form-hint" style="margin-top: 16px;"><?= htmlspecialchars($statusMessage) ?></div>
                    <?php endif; ?>
                </div>

                <div class="header-user-card">
                    <strong>Prof. <?= htmlspecialchars($profData['prenom'] . ' ' . $profData['nom']) ?></strong>
                    <span><?= htmlspecialchars($profData['nom_matiere']) ?></span>
                </div>
            </section>

            <section class="stats-grid">
                <article class="stat-card">
                    <h3>Classes affectées</h3>
                    <p class="stat-value"><?= count($classes) ?></p>
                    <span class="badge badge-soft">Total</span>
                </article>
                <article class="stat-card">
                    <h3>Évaluations créées</h3>
                    <p class="stat-value"><?= count($evaluations) ?></p>
                    <span class="badge badge-soft">Dernières</span>
                </article>
                <article class="stat-card">
                    <h3>Taux de réussite</h3>
                    <p class="stat-value"><?= count($chartSuccess) ? round(array_sum($chartSuccess) / count($chartSuccess), 1) : 0 ?>%</p>
                    <span class="badge badge-success">Moyenne</span>
                </article>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Performances de mes classes</h2>
                    <form method="GET" style="display:inline-flex; gap:12px; align-items:center;">
                        <label>Classe active :</label>
                        <select name="id_classe" onchange="this.form.submit()">
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['Id_Classe'] ?>" <?= $selected_classe == $c['Id_Classe'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['libelle'] . ' ' . $c['niveau']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="chart-grid">
                    <div class="chart-card">
                        <canvas id="teacherSummaryChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <canvas id="distributionChart"></canvas>
                    </div>
                </div>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Actions rapides</h2>
                </div>

                <div class="form-grid">
                    <div class="teacher-form-box">
                        <h3><i class='bx bx-calendar-plus' style="margin-right: 8px;"></i>Créer une évaluation</h3>
                        <form action="../../controllers/TeacherController.php" method="POST">
                            <input type="hidden" name="action" value="create_evaluation">
                            <div class="form-group">
                                <label><i class='bx bx-calendar'></i> Date de l'évaluation</label>
                                <input type="date" name="date_eval" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label><i class='bx bx-book'></i> Semestre</label>
                                <select name="semestre" required>
                                    <option value="1">Semestre 1</option>
                                    <option value="2">Semestre 2</option>
                                </select>
                            </div>
                            <button type="submit"><i class='bx bx-plus-circle' style="margin-right: 8px;"></i>Créer l'évaluation</button>
                        </form>
                    </div>

                    <div class="teacher-form-box">
                        <h3><i class='bx bx-user-plus' style="margin-right: 8px;"></i>Ajouter un étudiant</h3>
                        <form action="../../controllers/TeacherController.php" method="POST">
                            <input type="hidden" name="action" value="add_student">
                            <div class="form-group">
                                <label><i class='bx bx-user'></i> Nom</label>
                                <input type="text" name="nom" placeholder="Nom de l'étudiant" required>
                            </div>
                            <div class="form-group">
                                <label><i class='bx bx-user'></i> Prénom</label>
                                <input type="text" name="prenom" placeholder="Prénom de l'étudiant" required>
                            </div>
                            <div class="form-group">
                                <label><i class='bx bx-envelope'></i> Identifiant</label>
                                <input type="email" name="login" placeholder="Email de connexion" required>
                            </div>
                            <div class="form-group">
                                <label><i class='bx bx-lock'></i> Mot de passe</label>
                                <input type="password" name="password" placeholder="Mot de passe" required>
                            </div>
                            <div class="form-group">
                                <label><i class='bx bx-group'></i> Classe</label>
                                <select name="id_classe" required>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= $c['Id_Classe'] ?>"><?= htmlspecialchars($c['libelle'] . ' ' . $c['niveau']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit"><i class='bx bx-user-plus' style="margin-right: 8px;"></i>Créer l'étudiant</button>
                        </form>
                    </div>
                </div>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Dernières évaluations</h2>
                </div>
                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Semestre</th>
                                <th>Matière</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($evaluations) > 0): ?>
                                <?php foreach ($evaluations as $eval): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($eval['date_eval']) ?></td>
                                        <td><?= htmlspecialchars($eval['semestre']) ?></td>
                                        <td><?= htmlspecialchars($eval['matiere']) ?></td>
                                        <td>
                                            <a href="grades_entry.php?id_classe=<?= $selected_classe ?>&id_evaluation=<?= $eval['Id_Evaluation'] ?>" class="button-soft">Saisir notes</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; padding: 24px;">Aucune évaluation créée pour le moment.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        const labels = <?= json_encode($chartLabels) ?>;
        const averages = <?= json_encode($chartAverages) ?>;
        const success = <?= json_encode($chartSuccess) ?>;
        const distribution = <?= json_encode(array_values($distribution)) ?>;
        const distributionLabels = <?= json_encode(array_keys($distribution)) ?>;

        const summaryCtx = document.getElementById('teacherSummaryChart').getContext('2d');
        new Chart(summaryCtx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Moyenne de classe',
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59,130,246,0.2)',
                        data: averages,
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Taux de réussite (%)',
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16,185,129,0.2)',
                        data: success,
                        tension: 0.35,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true, max: 100 }
                }
            }
        });

        const distCtx = document.getElementById('distributionChart').getContext('2d');
        new Chart(distCtx, {
            type: 'doughnut',
            data: {
                labels: distributionLabels,
                datasets: [{
                    data: distribution,
                    backgroundColor: ['#F87171', '#FBBF24', '#60A5FA', '#34D399', '#8B5CF6']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>

</html>
