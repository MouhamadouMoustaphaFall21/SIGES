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
$stats['etudiants'] = $db->query("SELECT COUNT(*) FROM etudiant")->fetchColumn();
$stats['professeurs'] = $db->query("SELECT COUNT(*) FROM professeur")->fetchColumn();
$stats['classes'] = $db->query("SELECT COUNT(*) FROM classe")->fetchColumn();
$stats['matieres'] = $db->query("SELECT COUNT(*) FROM matiere")->fetchColumn();
$stats['evaluations'] = $db->query("SELECT COUNT(*) FROM evaluation")->fetchColumn();
$stats['notes'] = $db->query("SELECT COUNT(*) FROM effectue")->fetchColumn();

// Statistiques avancées
$stats['moyenne_generale'] = $db->query("SELECT AVG(note) FROM effectue WHERE note IS NOT NULL")->fetchColumn();
$stats['taux_reussite'] = $db->query("SELECT (COUNT(CASE WHEN note >= 10 THEN 1 END) * 100.0 / COUNT(*)) FROM effectue WHERE note IS NOT NULL")->fetchColumn();
$stats['notes_saisies'] = $db->query("SELECT COUNT(*) FROM effectue WHERE note IS NOT NULL")->fetchColumn();
$stats['affectations'] = $db->query("SELECT COUNT(*) FROM affecter")->fetchColumn();

// 3. Calcul de la moyenne par classe (Performance globale)
$queryClasses = "SELECT c.Id_Classe, c.libelle, c.niveau,
                 (SELECT AVG(eff.note) 
                  FROM effectue eff 
                  JOIN etudiant e ON eff.id_Etudiant = e.id_Etudiant 
                  WHERE e.Id_Classe = c.Id_Classe) as moyenne_classe,
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
                    <h3><i class='bx bx-user'></i> Étudiants inscrits</h3>
                    <p class="stat-value"><?= $stats['etudiants'] ?></p>
                    <span class="badge badge-soft">Total</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-chalkboard'></i> Enseignants</h3>
                    <p class="stat-value"><?= $stats['professeurs'] ?></p>
                    <span class="badge badge-soft">Actifs</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-building-house'></i> Classes actives</h3>
                    <p class="stat-value"><?= $stats['classes'] ?></p>
                    <span class="badge badge-soft">Promotions</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-book'></i> Matières</h3>
                    <p class="stat-value"><?= $stats['matieres'] ?></p>
                    <span class="badge badge-soft">Enseignées</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-bar-chart-alt-2'></i> Moyenne générale</h3>
                    <p class="stat-value"><?= $stats['moyenne_generale'] ? round($stats['moyenne_generale'], 1) : 'N/A' ?>/20</p>
                    <span class="badge badge-success">Établissement</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-trophy'></i> Taux de réussite</h3>
                    <p class="stat-value"><?= $stats['taux_reussite'] ? round($stats['taux_reussite'], 1) : 0 ?>%</p>
                    <span class="badge badge-success">Global</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-calendar-check'></i> Évaluations créées</h3>
                    <p class="stat-value"><?= $stats['evaluations'] ?></p>
                    <span class="badge badge-soft">Total</span>
                </article>
                <article class="stat-card">
                    <h3><i class='bx bx-edit-alt'></i> Notes saisies</h3>
                    <p class="stat-value"><?= $stats['notes_saisies'] ?></p>
                    <span class="badge badge-soft">Validées</span>
                </article>
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
</body>

</html>