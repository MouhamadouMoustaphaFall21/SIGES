<?php
require_once '../../config/auth.php';
requireRole('Etudiant');
require_once '../../config/database.php';
require_once '../../models/Student.php';
require_once '../../models/Grade.php';

$database = new Database();
$db = $database->getConnection();

$studentModel = new Student($db);
$gradeModel = new Grade($db);

$profile = $studentModel->getProfileByLogin($_SESSION['user_login']);
$grades = $gradeModel->getStudentGrades($profile['id_Etudiant']);
$initials = strtoupper(substr($profile['prenom'], 0, 1) . substr($profile['nom'], 0, 1));
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Réclamation - SIGES</title>
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
                <a href="schedule.php">Emploi du Temps</a>
                <a href="reclamation.php" class="active">Réclamation</a>
            </nav>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">
            <section class="page-header">
                <div>
                    <p class="eyebrow">Réclamation</p>
                    <h1>Signaler une note</h1>
                    <p>Soumettez une demande de vérification directement depuis votre espace étudiant.</p>
                    <div class="room-banner">
                        <span class="badge room-badge">Salle fixe</span>
                        <strong>Salle S-12 • Bâtiment principal</strong>
                    </div>
                </div>
                <div class="header-user-card">
                    <p>Bonjour</p>
                    <strong><?= htmlspecialchars($profile['prenom']) ?></strong>
                    <span>En attente de réclamation</span>
                </div>
            </section>

            <section class="tools-bar">
                <div class="search-box">
                    <input type="text" placeholder="Rechercher une note ou un cours">
                    <button type="button">Rechercher</button>
                </div>
                <div class="filter-chips">
                    <span class="filter-chip active">Toutes les réclamations</span>
                    <span class="filter-chip">Dernières notes</span>
                    <span class="filter-chip">Prioritaires</span>
                </div>
            </section>

            <section class="section-block form-card">
                <div class="section-title-row">
                    <div>
                        <h2><i class='bx bx-message-square-edit'></i>Envoyer une réclamation</h2>
                        <p style="margin: 8px 0 0; color: #7a8a9e; font-size: 0.95rem;">Demandez une vérification d'une note auprès de vos professeurs</p>
                    </div>
                </div>
                <?php if (count($grades) > 0): ?>
                    <form class="reclamation-form" action="../../controllers/GradeController.php" method="POST">
                        <input type="hidden" name="action" value="submit_reclamation">
                        
                        <div class="form-group">
                            <label for="evaluation"><i class='bx bx-book-open'></i>Matière / Évaluation</label>
                            <select id="evaluation" name="id_evaluation" required>
                                <option value="">-- Sélectionnez une évaluation --</option>
                                <?php foreach ($grades as $grade): ?>
                                    <option value="<?= $grade['Id_Evaluation'] ?>"><?= htmlspecialchars($grade['matiere']) ?> — Semestre <?= $grade['semestre'] ?> (Note : <?= $grade['note'] ?>/20)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="motif"><i class='bx bx-notepad'></i>Motif de la réclamation</label>
                            <textarea id="motif" name="motif" placeholder="Expliquez en détail pourquoi vous demandez une vérification..." required></textarea>
                        </div>

                        <button type="submit" class="button-success"><i class='bx bx-send'></i>Envoyer ma demande</button>
                    </form>
                <?php else: ?>
                    <div class="empty-state">
                        <i class='bx bx-inbox'></i>
                        <p>Aucune note disponible pour soumettre une réclamation.</p>
                    </div>
                <?php endif; ?>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2><i class='bx bx-list-check'></i>Vos notes récentes</h2>
                </div>
                <div class="table-card">
                    <table class="grades-table">
                        <thead>
                            <tr>
                                <th><i class='bx bx-book'></i>Matière</th>
                                <th><i class='bx bx-calendar'></i>Semestre</th>
                                <th><i class='bx bx-star'></i>Note</th>
                                <th><i class='bx bx-info-circle'></i>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($grades) > 0): ?>
                                <?php foreach ($grades as $grade): ?>
                                    <tr>
                                        <td class="subject-cell">
                                            <div class="subject-info"><?= htmlspecialchars($grade['matiere']) ?></div>
                                        </td>
                                        <td>
                                            <span class="semester-badge">S<?= $grade['semestre'] ?></span>
                                        </td>
                                        <td>
                                            <span class="note-badge note-<?= ($grade['note'] >= 12) ? 'excellent' : (($grade['note'] >= 10) ? 'good' : (($grade['note'] >= 8) ? 'medium' : 'low')) ?>">
                                                <?= $grade['note'] ?><span class="note-max">/20</span>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($grade['note'] >= 10): ?>
                                                <span class="status-badge status-pass">
                                                    <i class='bx bx-check'></i>Admis
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-fail">
                                                    <i class='bx bx-x'></i>Ajourné
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-cell"><i class='bx bx-inbox'></i>Aucune note enregistrée.</td>
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
