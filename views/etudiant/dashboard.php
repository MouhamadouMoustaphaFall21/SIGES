<?php

/**
 * Dashboard Étudiant - SIGES
 * Affiche les notes, la moyenne pondérée et le rang
 */
session_start();

// 1. Sécurité : Vérifier si l'utilisateur est connecté et est bien un Étudiant
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Etudiant') {
    header("Location: ../../index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../models/Student.php';
require_once '../../models/Grade.php';
require_once '../../models/Teacher.php';

// 2. Initialisation des objets
$database = new Database();
$db = $database->getConnection();

$studentModel = new Student($db);
$gradeModel = new Grade($db);
$teacherModel = new Teacher($db);

// 3. Récupération des données du profil de l'élève connecté
$studentData = $studentModel->getProfileByLogin($_SESSION['user_login']);
$id_etudiant = $studentData['id_Etudiant'];
$id_classe = $studentData['Id_Classe'];
$initials = strtoupper(substr($studentData['prenom'], 0, 1) . substr($studentData['nom'], 0, 1));

// 4. Calculs des performances (Logique métier)
$grades = $gradeModel->getStudentGrades($id_etudiant);
$moyenneGenerale = $gradeModel->calculateAverage($id_etudiant);
$classement = $gradeModel->getRankingByClasse($id_classe);

// Trouver le rang de l'élève dans la liste triée
$rang = 0;
foreach ($classement as $index => $row) {
    if ($row['id_Etudiant'] == $id_etudiant) {
        $rang = $index + 1;
        break;
    }
}

// 5. Récupérer les camarades de classe
$classmates = $studentModel->getByClasse($id_classe)->fetchAll(PDO::FETCH_ASSOC);

// 6. Récupérer les professeurs de la classe
$professors = $teacherModel->getProfessorsByClasse($id_classe)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Mon Dashboard - SIGES</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="student-shell">
        <aside class="student-sidebar">
            <div class="sidebar-brand">
                <img src="../../assets/img/logo_simple-SAP.png" alt="SIGES logo">
                <div class="brand-title">
                    <strong>SIGES</strong>
                    <span>Espace Étudiant</span>
                </div>
            </div>

            <div class="profile-box">
                <div class="profile-avatar"><i class='bx bxs-user'></i></div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($studentData['prenom'] . ' ' . $studentData['nom']) ?></h2>
                    <p><?= htmlspecialchars($studentData['nom_classe']) ?> • <?= htmlspecialchars($studentData['niveau']) ?></p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="active"><i class='bx bx-grid-alt'></i>Dashboard</a>
                <a href="performances.php"><i class='bx bx-bar-chart-alt-2'></i>Mes performances</a>
                <a href="schedule.php"><i class='bx bx-calendar'></i>Emploi du Temps</a>
                <a href="reclamation.php"><i class='bx bx-message-square-detail'></i>Réclamation</a>
                <a href="bulletin.php"><i class='bx bx-file'></i>Bulletin</a>
            </nav>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">

            <section class="page-header page-header-dashboard">

                <div>
                    <p class="eyebrow">Dashboard étudiant</p>
                    <h1>Mes notes et ressources</h1>
                    <p>Consultez votre moyenne, votre rang, vos professeurs, vos camarades et les ressources vidéo recommandées dans un seul espace.</p>
                    <div class="room-banner">
                        <span class="badge room-badge" style="color: #F29100; background: rgba(242, 145, 0, 0.12);">Salle fixe</span>
                        <strong>Salle S-12 • Bâtiment principal</strong>
                    </div>
                </div>

                <div class="header-user-card">
                    <strong> Bonjour, <?= htmlspecialchars($studentData['prenom']) ?></strong>
                    <span>Bienvenue sur votre espace</span>
                </div>

            </section>

            <section class="stats-grid">
                <article class="stat-card">
                    <h3  style="color: white;">Moyenne Générale</h3>
                    <p class="stat-value"  style="color: white;"><?= $moyenneGenerale ?> / 20</p>
                    <span class="video-meta badge <?= ($moyenneGenerale >= 10) ? 'tag-success' : 'tag-danger' ?>"  style="color: white;">
                        <?= ($moyenneGenerale >= 10) ? 'ADMIS' : 'AJOURNÉ' ?>
                    </span>
                </article>
                <article class="stat-card">
                    <h3  style="color: white;">Rang</h3>
                    <p class="stat-value"  style="color: white;"><?= $rang ?> / <?= count($classement) ?></p>
                    <span class="video-meta badge"  style="color: white;">Classe</span>
                </article>
                <article class="stat-card">
                    <h3  style="color: white;">Professeurs</h3>
                    <p class="stat-value"  style="color: white;"><?= count($professors) ?></p>
                    <span class="video-meta badge"  style="color: white;">Actifs</span>
                </article>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Ressources vidéo</h2>
                </div>
                <div class="video-grid">
                    <a href="https://www.youtube.com/playlist?list=PLkHw7J3J2iaoSgOn1zyHkY_6NwdrtVtfI" class="video-card-link" target="_blank" rel="noopener noreferrer">
                        <article class="video-card">
                            <div class="video-thumb">
                                <div class="play-icon">▶</div>
                            </div>
                            <div class="video-card-content">
                                <h3>PHP - Playlist complète</h3>
                                <p>Suivez une série de vidéos essentielles pour maîtriser PHP rapidement.</p>
                                <div class="video-meta">
                                    <span class="badge">YouTube</span>
                                    <span>Intro & Avancé</span>
                                </div>
                                <div class="video-cta">Voir la playlist</div>
                            </div>
                        </article>
                    </a>
                    <a href="https://www.youtube.com/playlist?list=PLXJw8DkEYeSMkE08RRwdw3JVjGM-pGDec" class="video-card-link" target="_blank" rel="noopener noreferrer">
                        <article class="video-card">
                            <div class="video-thumb">
                                <div class="play-icon">▶</div>
                            </div>
                            <div class="video-card-content">
                                <h3>Java - Playlist</h3>
                                <p>Approfondissez vos bases Java grâce à des vidéos pédagogiques structurées.</p>
                                <div class="video-meta">
                                    <span class="badge">YouTube</span>
                                    <span>Orienté objet</span>
                                </div>
                                <div class="video-cta">Voir la playlist</div>
                            </div>
                        </article>
                    </a>
                    <a href="https://www.youtube.com/playlist?list=PLZpzLuUp9qXx7LSoWwACqRwYmstUbxHKP" class="video-card-link" target="_blank" rel="noopener noreferrer">
                        <article class="video-card">
                            <div class="video-thumb">
                                <div class="play-icon">▶</div>
                            </div>
                            <div class="video-card-content">
                                <h3>C - Playlist</h3>
                                <p>Renforcez vos compétences en programmation système avec des tutoriels C.</p>
                                <div class="video-meta">
                                    <span class="badge">YouTube</span>
                                    <span>Bas niveau</span>
                                </div>
                                <div class="video-cta">Voir la playlist</div>
                            </div>
                        </article>
                    </a>
                </div>
            </section>

          <section class="section-block">
    <h2>Mes Cours</h2>

    <div class="courses-grid">

        <div class="course-card">
            <div class="course-icon">
                <i class="fas fa-square-root-alt"></i>
            </div>
            <h3>Mathématiques</h3>
            <p>Algèbre & Géométrie</p>
        </div>

        <div class="course-card">
            <div class="course-icon">
                <i class="fas fa-code"></i>
            </div>
            <h3>Programmation Web</h3>
            <p>HTML, CSS, PHP</p>
        </div>

        <div class="course-card">
            <div class="course-icon">
                <i class="fas fa-database"></i>
            </div>
            <h3>Base de Données</h3>
            <p>SQL & Requêtes</p>
        </div>

        <div class="course-card">
            <div class="course-icon">
                <i class="fas fa-globe"></i>
            </div>
            <h3>Anglais Technique</h3>
            <p>Vocabulaire IT</p>
        </div>

    </div>
</section>
            <section class="data-grid">
                <div class="section-block">
                    <h2>Notes par matière</h2>
                    <div class="table-card">
                        <table>
                            <thead>
                                <tr>
                                    <th><i class="fas fa-book" style="margin-right: 8px;"></i>Matière</th>
                                    <th><i class="fas fa-calendar-alt" style="margin-right: 8px;"></i>Semestre</th>
                                    <th><i class="fas fa-weight-hanging" style="margin-right: 8px;"></i>Coefficient</th>
                                    <th><i class="fas fa-star" style="margin-right: 8px;"></i>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($grades) > 0): ?>
                                    <?php foreach ($grades as $g): ?>
                                        <?php
                                        $note = floatval($g['note']);
                                        $status = '';
                                        $badgeClass = '';
                                        if ($note >= 16) {
                                            $status = 'Excellent';
                                            $badgeClass = 'badge-success';
                                        } elseif ($note >= 14) {
                                            $status = 'Très Bien';
                                            $badgeClass = 'badge-success';
                                        } elseif ($note >= 12) {
                                            $status = 'Bien';
                                            $badgeClass = 'badge-warning';
                                        } elseif ($note >= 10) {
                                            $status = 'Passable';
                                            $badgeClass = 'badge-warning';
                                        } else {
                                            $status = 'Insuffisant';
                                            $badgeClass = 'badge-danger';
                                        }
                                        ?>
                                        <tr>
                                            <td><i class="fas fa-book-open" style="margin-right: 8px; color: var(--secondary);"></i><?= htmlspecialchars($g['matiere']) ?></td>
                                            <td><i class="fas fa-calendar-alt" style="margin-right: 8px; color: var(--text-light);"></i>Semestre <?= $g['semestre'] ?></td>
                                            <td><i class="fas fa-weight-hanging" style="margin-right: 8px; color: var(--text-light);"></i><?= $g['coefficient'] ?></td>
                                            <td>
                                                <strong style="font-size: 1.1em;"><?= $g['note'] ?>/20</strong>
                                                <span class="badge <?= $badgeClass ?>" style="margin-left: 10px;"><?= $status ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center; padding: 40px;">
                                            <i class="fas fa-inbox" style="font-size: 2em; color: var(--text-light); margin-bottom: 10px;"></i><br>
                                            Aucune note n'a été saisie pour le moment.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="section-block">
                    <h2>Professeurs & Camarades</h2>
                    <div class="table-card" style="margin-bottom: 20px;">
                        <table>
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user-graduate" style="margin-right: 8px;"></i>Professeur</th>
                                    <th><i class="fas fa-chalkboard-teacher" style="margin-right: 8px;"></i>Matière</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($professors) > 0): ?>
                                    <?php foreach ($professors as $p): ?>
                                        <tr>
                                            <td><i class="fas fa-user-graduate" style="margin-right: 8px; color: var(--secondary);"></i><?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?></td>
                                            <td><i class="fas fa-chalkboard-teacher" style="margin-right: 8px; color: var(--text-light);"></i><?= htmlspecialchars($p['matiere_nom']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" style="text-align:center; padding: 40px;">
                                            <i class="fas fa-users" style="font-size: 2em; color: var(--text-light); margin-bottom: 10px;"></i><br>
                                            Aucun professeur affecté.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-card">
                        <table>
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user" style="margin-right: 8px;"></i>Nom</th>
                                    <th><i class="fas fa-user-friends" style="margin-right: 8px;"></i>Prénom</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($classmates) > 0): ?>
                                    <?php foreach ($classmates as $c): ?>
                                        <tr>
                                            <td><i class="fas fa-user" style="margin-right: 8px; color: var(--secondary);"></i><?= htmlspecialchars($c['nom']) ?></td>
                                            <td><i class="fas fa-user-friends" style="margin-right: 8px; color: var(--text-light);"></i><?= htmlspecialchars($c['prenom']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" style="text-align:center; padding: 40px;">
                                            <i class="fas fa-user-friends" style="font-size: 2em; color: var(--text-light); margin-bottom: 10px;"></i><br>
                                            Aucun camarade.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>

</html>
