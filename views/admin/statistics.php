<?php

/**
 * Page de statistiques avancées - SIGES
 */
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../../index.php");
    exit();
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$stats = [];
$stats['etudiants'] = $db->query("SELECT COUNT(*) FROM etudiant")->fetchColumn();
$stats['professeurs'] = $db->query("SELECT COUNT(*) FROM professeur")->fetchColumn();
$stats['classes'] = $db->query("SELECT COUNT(*) FROM classe")->fetchColumn();
$stats['matieres'] = $db->query("SELECT COUNT(*) FROM matiere")->fetchColumn();
$stats['evaluations'] = $db->query("SELECT COUNT(*) FROM evaluation")->fetchColumn();
$stats['notes_saisies'] = $db->query("SELECT COUNT(*) FROM effectue WHERE note IS NOT NULL")->fetchColumn();
$stats['affectations'] = $db->query("SELECT COUNT(*) FROM affecter")->fetchColumn();
$stats['classes_sans_notes'] = $db->query("SELECT COUNT(*) FROM classe c WHERE NOT EXISTS (SELECT 1 FROM effectue eff JOIN etudiant e ON eff.id_Etudiant = e.id_Etudiant WHERE e.Id_Classe = c.Id_Classe AND eff.note IS NOT NULL)")->fetchColumn();

$noteDistributionRows = $db->query("SELECT CASE
    WHEN note < 8 THEN '0-7'
    WHEN note < 10 THEN '8-9.99'
    WHEN note < 13 THEN '10-12.99'
    WHEN note < 16 THEN '13-15.99'
    ELSE '16-20'
  END AS bucket,
  COUNT(*) AS count
  FROM effectue
  WHERE note IS NOT NULL
  GROUP BY bucket")->fetchAll(PDO::FETCH_ASSOC);
$noteBuckets = ['0-7' => 0, '8-9.99' => 0, '10-12.99' => 0, '13-15.99' => 0, '16-20' => 0];
foreach ($noteDistributionRows as $row) {
    $noteBuckets[$row['bucket']] = $row['count'];
}

$studentsByClass = $db->query("SELECT c.libelle, c.niveau, COUNT(e.id_Etudiant) AS student_count FROM classe c LEFT JOIN etudiant e ON e.Id_Classe = c.Id_Classe GROUP BY c.Id_Classe ORDER BY student_count DESC")->fetchAll(PDO::FETCH_ASSOC);
$evaluationsBySemester = $db->query("SELECT semestre, COUNT(*) AS total FROM evaluation GROUP BY semestre ORDER BY semestre")->fetchAll(PDO::FETCH_ASSOC);
$topClasses = $db->query("SELECT c.libelle, c.niveau, AVG(eff.note) AS moyenne FROM classe c JOIN etudiant e ON e.Id_Classe = c.Id_Classe JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant WHERE eff.note IS NOT NULL GROUP BY c.Id_Classe ORDER BY moyenne DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$teacherAssignments = $db->query("SELECT p.nom, p.prenom, COUNT(a.Id_Classe) AS classes FROM professeur p JOIN affecter a ON p.Id_Professeur = a.Id_Professeur GROUP BY p.Id_Professeur ORDER BY classes DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Statistiques - SIGES</title>
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
                    <span>Espace Admin</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php"><i class='bx bx-home'></i>Dashboard</a>
                <a href="users.php"><i class='bx bx-user-circle'></i>Gestion utilisateurs</a>
                <a href="grades_view.php"><i class='bx bx-book'></i>PV Délibération</a>
                <a href="schedule.php"><i class='bx bx-calendar'></i>Emploi du temps</a>
                <a href="reclamations.php"><i class='bx bx-message-square-detail'></i>Réclamations</a>
            </nav>

            <div class="sidebar-section">
                <h3>Statistiques</h3>
                <div class="course-list">
                    <a href="statistics.php" class="active">Graphiques</a>
                </div>
            </div>
            <div class="sidebar-section">
                <h3>Créateur</h3>
                <div class="course-list">
                    <a href="creators.php">Crédits</a>
                </div>
            </div>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">
            <section class="page-header page-header-dashboard">
                <div>
                    <p class="eyebrow">Statistiques</p>
                    <h1>Vue analytique</h1>
                    <p>Une page dédiée aux graphiques et aux données clés de l’application.</p>
                </div>
                <div class="header-user-card">
                    <strong>Administrateur</strong>
                    <span>Analyse avancée</span>
                </div>
            </section>

            <section class="stats-grid">
                <article class="stat-card">
                    <h3><i class='bx bx-user'></i> Étudiants</h3>
                    <p class="stat-value"><?= $stats['etudiants'] ?></p>
                    <span class="badge badge-soft">Total</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-chalkboard'></i> Enseignants</h3>
                    <p class="stat-value"><?= $stats['professeurs'] ?></p>
                    <span class="badge badge-soft">Total</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-building-house'></i> Classes</h3>
                    <p class="stat-value"><?= $stats['classes'] ?></p>
                    <span class="badge badge-soft">Actives</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-book'></i> Matières</h3>
                    <p class="stat-value"><?= $stats['matieres'] ?></p>
                    <span class="badge badge-soft">Discipline</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-edit-alt'></i> Notes saisies</h3>
                    <p class="stat-value"><?= $stats['notes_saisies'] ?></p>
                    <span class="badge badge-soft">Validées</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-chalkboard'></i> Affectations</h3>
                    <p class="stat-value"><?= $stats['affectations'] ?></p>
                    <span class="badge badge-soft">Professeur/Classe</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-block'></i> Classes sans notes</h3>
                    <p class="stat-value"><?= $stats['classes_sans_notes'] ?></p>
                    <span class="badge badge-warning">À suivre</span>
                </article>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Visualisations</h2>
                </div>
                <div class="chart-grid">
                    <div class="chart-card">
                        <h3>Répartition des notes</h3>
                        <canvas id="notesChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3>Effectifs par classe</h3>
                        <canvas id="classeEffectifChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3>Évaluations par semestre</h3>
                        <canvas id="evaluationSemesterChart"></canvas>
                    </div>
                </div>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Top classes par moyenne</h2>
                </div>
                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Classe</th>
                                <th>Niveau</th>
                                <th>Moyenne</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topClasses as $item): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($item['libelle']) ?></strong></td>
                                    <td><?= htmlspecialchars($item['niveau']) ?></td>
                                    <td><?= $item['moyenne'] ? round($item['moyenne'], 2) : 'N/A' ?> / 20</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Chargement des professeurs</h2>
                </div>
                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Professeur</th>
                                <th>Nombre de classes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teacherAssignments as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['prenom'] . ' ' . $item['nom']) ?></td>
                                    <td><?= $item['classes'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        const noteLabels = <?= json_encode(array_keys($noteBuckets)) ?>;
        const noteData = <?= json_encode(array_values($noteBuckets)) ?>;
        const classLabels = <?= json_encode(array_map(function ($item) { return $item['libelle'] . ' ' . $item['niveau']; }, $studentsByClass)) ?>;
        const classData = <?= json_encode(array_map(function ($item) { return intval($item['student_count']); }, $studentsByClass)) ?>;
        const semesterLabels = <?= json_encode(array_map(function ($item) { return 'Sem ' . $item['semestre']; }, $evaluationsBySemester)) ?>;
        const semesterData = <?= json_encode(array_map(function ($item) { return intval($item['total']); }, $evaluationsBySemester)) ?>;

        new Chart(document.getElementById('notesChart'), {
            type: 'doughnut',
            data: {
                labels: noteLabels,
                datasets: [{
                    data: noteData,
                    backgroundColor: ['#60a5fa', '#34d399', '#fbbf24', '#fb7185', '#a78bfa'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        new Chart(document.getElementById('classeEffectifChart'), {
            type: 'bar',
            data: {
                labels: classLabels,
                datasets: [{
                    label: 'Élèves',
                    data: classData,
                    backgroundColor: '#60a5fa'
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { display: false } }
            }
        });

        new Chart(document.getElementById('evaluationSemesterChart'), {
            type: 'pie',
            data: {
                labels: semesterLabels,
                datasets: [{
                    data: semesterData,
                    backgroundColor: ['#34d399', '#60a5fa', '#fbbf24', '#fb7185']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    </script>
</body>

</html>
