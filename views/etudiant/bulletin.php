<?php
session_start();

require_once '../../config/auth.php';
require_once '../../config/database.php';
require_once '../../models/Student.php';
require_once '../../models/Grade.php';

requireRole('Etudiant');

$database = new Database();
$db = $database->getConnection();

$studentModel = new Student($db);
$gradeModel = new Grade($db);

$studentData = $studentModel->getProfileByLogin($_SESSION['user_login']);
$id_etudiant = $studentData['id_Etudiant'];
$id_classe = $studentData['Id_Classe'];
$grades = $gradeModel->getStudentGrades($id_etudiant);
$moyenneGenerale = $gradeModel->calculateAverage($id_etudiant);
$classement = $gradeModel->getRankingByClasse($id_classe);
$rang = 0;
foreach ($classement as $index => $row) {
    if ($row['id_Etudiant'] == $id_etudiant) {
        $rang = $index + 1;
        break;
    }
}
$totalPoints = 0;
$totalCoeffs = 0;
foreach ($grades as $g) {
    if ($g['note'] !== null && $g['note'] !== '') {
        $totalPoints += $g['note'] * $g['coefficient'];
        $totalCoeffs += $g['coefficient'];
    }
}
$averageCalculated = $totalCoeffs > 0 ? round($totalPoints / $totalCoeffs, 2) : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin - SIGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; margin: 0; }
            .student-main { padding: 0; }
            .table-card table { width: 100%; }
        }
    </style>
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
                <a href="dashboard.php"><i class='bx bx-grid-alt'></i>Dashboard</a>
                <a href="performances.php"><i class='bx bx-bar-chart-alt-2'></i>Mes performances</a>
                <a href="schedule.php"><i class='bx bx-calendar'></i>Emploi du Temps</a>
                <a href="reclamation.php"><i class='bx bx-message-square-detail'></i>Réclamation</a>
                <a href="bulletin.php" class="active"><i class='bx bx-file'></i>Bulletin</a>
            </nav>
            </nav>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">
            <section class="page-header page-header-dashboard">
                <div>
                    <p class="eyebrow">Bulletin</p>
                    <h1>Mes résultats</h1>
                    <p>Retrouvez toutes vos notes, coefficients et moyennes dans un format prêt à imprimer.</p>
                </div>
                <div class="header-user-card">
                    <strong>Bulletin officiel</strong>
                    <span><?= htmlspecialchars($studentData['nom_classe']) ?></span>
                </div>
            </section>

            <section class="section-block no-print">
                <button class="button-primary" onclick="window.print()">Télécharger / Imprimer le bulletin</button>
            </section>

            <section class="section-block">
                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Matière</th>
                                <th>Semestre</th>
                                <th>Coefficient</th>
                                <th>Note</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($grades) > 0): ?>
                                <?php foreach ($grades as $grade): ?>
                                    <?php
                                        $note = is_numeric($grade['note']) ? floatval($grade['note']) : 0;
                                        $status = $note >= 10 ? 'Admis' : 'Ajourne';
                                        $statusClass = $note >= 10 ? 'badge-success' : 'badge-danger';
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($grade['matiere']) ?></td>
                                        <td><?= htmlspecialchars($grade['semestre']) ?></td>
                                        <td><?= htmlspecialchars($grade['coefficient']) ?></td>
                                        <td><?= htmlspecialchars($grade['note']) ?></td>
                                        <td><span class="badge <?= $statusClass ?>"><?= $status ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding: 40px;">Aucune note disponible pour le moment.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="stats-grid" style="margin-top: 24px;">
                    <article class="stat-card">
                        <h3>Moyenne pondérée</h3>
                        <p class="stat-value"><?= $averageCalculated ?> / 20</p>
                    </article>
                    <article class="stat-card">
                        <h3>Rang dans la classe</h3>
                        <p class="stat-value"><?= $rang ?> / <?= count($classement) ?></p>
                    </article>
                    <article class="stat-card">
                        <h3>Total coefficients</h3>
                        <p class="stat-value"><?= $totalCoeffs ?></p>
                    </article>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
