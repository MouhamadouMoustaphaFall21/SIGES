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
                    <h2><?= htmlspecialchars($studentData['prenom'] . ' ' . $studentData['nom']) ?></h2>
                    <p><?= htmlspecialchars($studentData['nom_classe']) ?> • <?= htmlspecialchars($studentData['niveau']) ?></p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="schedule.php">Emploi du Temps</a>
                <a href="reclamation.php">Réclamation</a>
            </nav>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">
            <section class="page-header">
                <div>
                    <p class="eyebrow">Dashboard étudiant</p>
                    <h1>Mes notes et ressources</h1>
                    <p>Consultez votre moyenne, votre rang, vos professeurs, vos camarades et les ressources vidéo recommandées dans un seul espace.</p>
                    <div class="room-banner">
                        <span class="badge room-badge">Salle fixe</span>
                        <strong>Salle S-12 • Bâtiment principal</strong>
                    </div>
                </div>
                <div class="header-user-card">
                    <p>Bonjour</p>
                    <strong><?= htmlspecialchars($studentData['prenom']) ?></strong>
                    <span>Bienvenue sur votre espace</span>
                </div>
            </section>

            <section class="tools-bar">
                <div class="search-box">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Rechercher un cours, une note ou un professeur">
                    <button type="button"><i class='bx bx-search'></i>Rechercher</button>
                </div>
                <div class="filter-chips">
                    <span class="filter-chip active">Tous (24)</span>
                    <span class="filter-chip">Vidéos</span>
                    <span class="filter-chip">Planning</span>
                    <span class="filter-chip">Réclamation</span>
                </div>
            </section>

            <section class="stats-grid">
                <article class="stat-card">
                    <h3>Moyenne Générale</h3>
                    <p class="stat-value"><?= $moyenneGenerale ?> / 20</p>
                    <span class="video-meta badge <?= ($moyenneGenerale >= 10) ? 'tag-success' : 'tag-danger' ?>">
                        <?= ($moyenneGenerale >= 10) ? 'ADMIS' : 'AJOURNÉ' ?>
                    </span>
                </article>
                <article class="stat-card">
                    <h3>Rang</h3>
                    <p class="stat-value"><?= $rang ?> / <?= count($classement) ?></p>
                    <span class="video-meta badge">Classe</span>
                </article>
                <article class="stat-card">
                    <h3>Professeurs</h3>
                    <p class="stat-value"><?= count($professors) ?></p>
                    <span class="video-meta badge">Actifs</span>
                </article>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Ressources vidéo</h2>
                </div>
                <div class="video-grid">
                    <a href="https://www.youtube.com/playlist?list=PLS3QePTb8JS33pr11i6FOjU6kho6qi14j" class="video-card-link" target="_blank" rel="noopener noreferrer">
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
                    <a href="https://www.youtube.com/playlist?list=PLgcyz9E0ZvhdCw0S5AiRy-dOh1jEBSIYQ" class="video-card-link" target="_blank" rel="noopener noreferrer">
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
                    <a href="https://www.youtube.com/playlist?list=PLu0W_9lII9aiL0kysYlfSOUgY5rNlOhUd" class="video-card-link" target="_blank" rel="noopener noreferrer">
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
                        <div class="course-icon">📐</div>
                        <h3>Mathématiques</h3>
                        <p>Algèbre & Géométrie</p>
                    </div>
                    <div class="course-card">
                        <div class="course-icon">💻</div>
                        <h3>Programmation Web</h3>
                        <p>HTML, CSS, PHP</p>
                    </div>
                    <div class="course-card">
                        <div class="course-icon">🗄️</div>
                        <h3>Base de Données</h3>
                        <p>SQL & Requêtes</p>
                    </div>
                    <div class="course-card">
                        <div class="course-icon">🌍</div>
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
                                    <th>Matière</th>
                                    <th>Semestre</th>
                                    <th>Coefficient</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($grades) > 0): ?>
                                    <?php foreach ($grades as $g): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($g['matiere']) ?></td>
                                            <td>Semestre <?= $g['semestre'] ?></td>
                                            <td><?= $g['coefficient'] ?></td>
                                            <td><strong><?= $g['note'] ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center;">Aucune note n'a été saisie pour le moment.</td>
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
                                    <th>Professeur</th>
                                    <th>Matière</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($professors) > 0): ?>
                                    <?php foreach ($professors as $p): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?></td>
                                            <td><?= htmlspecialchars($p['matiere_nom']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" style="text-align:center;">Aucun professeur affecté.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-card">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($classmates) > 0): ?>
                                    <?php foreach ($classmates as $c): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($c['nom']) ?></td>
                                            <td><?= htmlspecialchars($c['prenom']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" style="text-align:center;">Aucun camarade.</td>
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
