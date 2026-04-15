<?php
require_once '../../config/auth.php';
requireRole('Etudiant');
require_once '../../config/database.php';
require_once '../../models/Student.php';
require_once '../../models/Schedule.php';

$database = new Database();
$db = $database->getConnection();

$studentModel = new Student($db);
$scheduleModel = new Schedule($db);

$profile = $studentModel->getProfileByLogin($_SESSION['user_login']);
$mySchedule = $scheduleModel->getByClasse($profile['Id_Classe'])->fetchAll(PDO::FETCH_ASSOC);
$initials = strtoupper(substr($profile['prenom'], 0, 1) . substr($profile['nom'], 0, 1));
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Emploi du Temps - SIGES</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <div class="student-shell">
        <aside class="student-sidebar">
            <div class="sidebar-brand">
                <img src="../../assets/img/logo_simple-SAP.png" alt="SIGES logo">
                <div class="brand-title">
                    <strong>SIGES</strong>
                    <span>Système Étudiant</span>
                </div>
            </div>

            <div class="profile-box">
                <div class="profile-avatar"><i class='bx bxs-user'></i></div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($profile['prenom'] . ' ' . $profile['nom']) ?></h2>
                    <p><?= htmlspecialchars($profile['nom_classe']) ?> • <?= htmlspecialchars($profile['niveau']) ?></p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="schedule.php" class="active">Emploi du Temps</a>
                <a href="reclamation.php">Réclamation</a>
            </nav>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">
            <section class="page-header">
                <div>
                    <p class="eyebrow">Emploi du Temps</p>
                    <h1>Planning de la semaine</h1>
                    <p>Retrouvez tous vos créneaux de cours, les heures et les professeurs de votre classe dans un seul espace.</p>
                    <div class="room-banner">
                        <span class="badge room-badge">Salle fixe</span>
                        <strong>Salle S-12 • Bâtiment principal</strong>
                    </div>
                </div>
                <div class="header-user-card">
                    <p>Bonjour</p>
                    <strong><?= htmlspecialchars($profile['prenom']) ?></strong>
                    <span>Bonne consultation</span>
                </div>
            </section>

            <section class="tools-bar">
                <div class="search-box">
                    <input type="text" placeholder="Rechercher un cours, une matière ou un professeur">
                    <button type="button">Rechercher</button>
                </div>
                <div class="filter-chips">
                    <span class="filter-chip active">Toutes les semaines</span>
                    <span class="filter-chip">Cours</span>
                    <span class="filter-chip">Examens</span>
                    <span class="filter-chip">Réservations</span>
                </div>
            </section>

            <section class="section-block">
                <div class="table-card">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Jour</th>
                                <th>Horaire</th>
                                <th>Matière</th>
                                <th>Professeur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($mySchedule) > 0): ?>
                                <?php foreach ($mySchedule as $slot): ?>
                                    <tr>
                                        <td><span class="schedule-chip"><?= htmlspecialchars($slot['jour']) ?></span></td>
                                        <td><?= substr($slot['heure_debut'], 0, 5) ?> - <?= substr($slot['heure_fin'], 0, 5) ?></td>
                                        <td><?= htmlspecialchars($slot['matiere_nom']) ?></td>
                                        <td>Prof. <?= htmlspecialchars($slot['prof_nom']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; padding: 28px;">Aucun créneau trouvé pour votre classe.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </main>
    </div>
</body>

</html>
