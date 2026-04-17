<?php

/**
 * Dashboard Administrateur - SIGES
 * Vue d'ensemble, statistiques et gestion globale
 */
session_start();

// 1. Sécurité : Vérifier le rôle Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../../index.php");
    exit();
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// 2. Récupération des statistiques globales (Comptage simple)
$stats = [];
$stats['admis'] = $db->query("SELECT COUNT(*) FROM (
    SELECT id_Etudiant, AVG(note) AS moyenne
    FROM effectue
    WHERE note IS NOT NULL
    GROUP BY id_Etudiant
    HAVING AVG(note) >= 10
) AS sub")->fetchColumn();
$stats['moyenne_generale'] = $db->query("SELECT AVG(note) FROM effectue WHERE note IS NOT NULL")->fetchColumn();
$stats['taux_reussite'] = $db->query("SELECT IFNULL(ROUND((SUM(CASE WHEN note >= 10 THEN 1 ELSE 0 END) * 100.0) / COUNT(*), 1), 0) FROM effectue WHERE note IS NOT NULL")->fetchColumn();
$stats['taux_licence'] = $db->query("SELECT IFNULL(ROUND((SUM(CASE WHEN avg_note >= 10 THEN 1 ELSE 0 END) * 100.0) / COUNT(*), 1), 0)
    FROM (
        SELECT e.id_Etudiant, AVG(eff.note) AS avg_note
        FROM etudiant e
        JOIN classe c ON e.Id_Classe = c.Id_Classe
        JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant
        WHERE eff.note IS NOT NULL AND c.niveau LIKE 'Licence %'
        GROUP BY e.id_Etudiant
    ) AS t")->fetchColumn();
$stats['taux_master'] = $db->query("SELECT IFNULL(ROUND((SUM(CASE WHEN avg_note >= 10 THEN 1 ELSE 0 END) * 100.0) / COUNT(*), 1), 0)
    FROM (
        SELECT e.id_Etudiant, AVG(eff.note) AS avg_note
        FROM etudiant e
        JOIN classe c ON e.Id_Classe = c.Id_Classe
        JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant
        WHERE eff.note IS NOT NULL AND c.niveau LIKE 'Master %'
        GROUP BY e.id_Etudiant
    ) AS t")->fetchColumn();

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

// 3. Calcul de la moyenne par classe (Performance globale)
$queryClasses = "SELECT c.Id_Classe, c.libelle, c.niveau,
                 (SELECT AVG(eff.note) 
                  FROM effectue eff 
                  JOIN etudiant e ON eff.id_Etudiant = e.id_Etudiant 
                  WHERE e.Id_Classe = c.Id_Classe AND eff.note IS NOT NULL) as moyenne_classe,
                 (SELECT COUNT(DISTINCT e.id_Etudiant)
                  FROM etudiant e
                  WHERE e.Id_Classe = c.Id_Classe) as nb_etudiants
                 FROM classe c";
$stmtClasses = $db->prepare($queryClasses);
$stmtClasses->execute();
$classesPerformance = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Administration - SIGES</title>
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
                <a href="dashboard.php" class="active"><i class='bx bx-home'></i>Dashboard</a>
                <a href="users.php"><i class='bx bx-user-circle'></i>Gestion utilisateurs</a>
                <a href="grades_view.php"><i class='bx bx-book'></i>PV Délibération</a>
                <a href="schedule.php"><i class='bx bx-calendar'></i>Emploi du temps</a>
            </nav>

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
                    <p class="eyebrow">Administration</p>
                    <h1>Tableau de bord</h1>
                    <p>Vue globale des étudiants, des enseignants, des classes et des performances scolaires.</p>
                </div>
                <div class="header-user-card">
                    <strong>Administrateur</strong>
                    <span>Gestion centrale</span>
                </div>
            </section>

            <section class="stats-grid">
                <article class="stat-card">
                    <h3><i class='bx bx-check-circle'></i> Étudiants admis</h3>
                    <p class="stat-value"><?= $stats['admis'] ?></p>
                    <span class="badge badge-success">Moyenne ≥ 10</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-bar-chart-alt-2'></i> Moyenne générale</h3>
                    <p class="stat-value"><?= $stats['moyenne_generale'] ? round($stats['moyenne_generale'], 1) : 'N/A' ?>/20</p>
                    <span class="badge badge-success">Établissement</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-trophy'></i> Taux de réussite</h3>
                    <p class="stat-value"><?= round($stats['taux_reussite'], 1) ?>%</p>
                    <span class="badge badge-success">Global</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-graduation'></i> Taux réussite Licence</h3>
                    <p class="stat-value"><?= round($stats['taux_licence'], 1) ?>%</p>
                    <span class="badge badge-soft">Licence 1-3</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-graduation'></i> Taux réussite Master</h3>
                    <p class="stat-value"><?= round($stats['taux_master'], 1) ?>%</p>
                    <span class="badge badge-soft">Master 1-2</span>
                </article>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Graphiques rapides</h2>
                </div>
                <div class="chart-grid">
                    <div class="chart-card">
                        <h3>Répartition des notes</h3>
                        <canvas id="noteDistributionChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3>Moyenne générale par classe</h3>
                        <canvas id="classAverageChart"></canvas>
                    </div>
                </div>
            </section>

            <section class="section-block">
                        <div class="section-title-row">
                    <h2>Performance par classe</h2>
                </div>

                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Classe</th>
                                <th>Niveau</th>
                                <th>Étudiants</th>
                                <th>Moyenne globale</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classesPerformance as $cp): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($cp['libelle']) ?></strong></td>
                                    <td><?= htmlspecialchars($cp['niveau']) ?></td>
                                    <td><?= $cp['nb_etudiants'] ?? 0 ?></td>
                                    <td><?= $cp['moyenne_classe'] ? round($cp['moyenne_classe'], 2) : 'N/A' ?> / 20</td>
                                    <td>
                                        <?php if (!$cp['moyenne_classe']): ?>
                                            <span class="badge badge-soft">Pas de notes</span>
                                        <?php elseif ($cp['moyenne_classe'] >= 10): ?>
                                            <span class="badge badge-success">Satisfaisant</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">À surveiller</span>
                                        <?php endif; ?>
                                    </td>
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
        const classLabels = <?= json_encode(array_map(function ($item) { return htmlspecialchars($item['libelle'] . ' ' . $item['niveau']); }, $classesPerformance)) ?>;
        const classAverages = <?= json_encode(array_map(function ($item) { return $item['moyenne_classe'] !== null ? round($item['moyenne_classe'], 2) : 0; }, $classesPerformance)) ?>;

        const noteCtx = document.getElementById('noteDistributionChart');
        if (noteCtx) {
            new Chart(noteCtx, {
                type: 'doughnut',
                data: {
                    labels: noteLabels,
                    datasets: [{
                        label: 'Répartition des notes',
                        data: noteData,
                        backgroundColor: ['#60a5fa', '#34d399', '#fbbf24', '#fb7185', '#a78bfa'],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }

        const classCtx = document.getElementById('classAverageChart');
        if (classCtx) {
            new Chart(classCtx, {
                type: 'bar',
                data: {
                    labels: classLabels,
                    datasets: [{
                        label: 'Moyenne générale par classe',
                        data: classAverages,
                        backgroundColor: '#34d399',
                        borderRadius: 12,
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: { ticks: { color: '#334155' } },
                        y: { beginAtZero: true, suggestedMax: 20, ticks: { color: '#334155' } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    </script>
</body>

</html>