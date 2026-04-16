<?php

/**
 * Mes Performances - SIGES Étudiant
 * Affiche les statistiques de performance par matière
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

// 2. Initialisation des objets
$database = new Database();
$db = $database->getConnection();

$studentModel = new Student($db);
$gradeModel = new Grade($db);

// 3. Récupération des données du profil de l'élève connecté
$studentData = $studentModel->getProfileByLogin($_SESSION['user_login']);
$id_etudiant = $studentData['id_Etudiant'];
$initials = strtoupper(substr($studentData['prenom'], 0, 1) . substr($studentData['nom'], 0, 1));

// 4. Récupérer les notes détaillées par matière
$grades = $gradeModel->getStudentGrades($id_etudiant);

// Grouper les notes par matière
$performanceBySubject = [];
foreach ($grades as $grade) {
    $matiere = $grade['matiere'];
    if (!isset($performanceBySubject[$matiere])) {
        $performanceBySubject[$matiere] = [
            'notes' => [],
            'coefficients' => [],
            'moyenne' => 0,
            'total_points' => 0,
            'total_coeffs' => 0
        ];
    }
    $performanceBySubject[$matiere]['notes'][] = $grade['note'];
    $performanceBySubject[$matiere]['coefficients'][] = $grade['coefficient'];
    $performanceBySubject[$matiere]['total_points'] += $grade['note'] * $grade['coefficient'];
    $performanceBySubject[$matiere]['total_coeffs'] += $grade['coefficient'];
}

// Calculer les moyennes par matière
foreach ($performanceBySubject as $matiere => &$data) {
    if ($data['total_coeffs'] > 0) {
        $data['moyenne'] = round($data['total_points'] / $data['total_coeffs'], 2);
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Mes Performances - SIGES</title>
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
                <a href="performances.php" class="active"><i class='bx bx-bar-chart-alt-2'></i>Mes performances</a>
                <a href="schedule.php"><i class='bx bx-calendar'></i>Emploi du Temps</a>
                <a href="reclamation.php"><i class='bx bx-message-square-detail'></i>Réclamation</a>
                <a href="bulletin.php"><i class='bx bx-file'></i>Bulletin</a>
            </nav>

            <a href="../../controllers/Logout.php" class="logout-btn"><i class='bx bx-log-out'></i>Déconnexion</a>
        </aside>

        <main class="student-main">
            <section class="page-header page-header-dashboard">
                <div>
                    <p class="eyebrow">Mes performances</p>
                    <h1>Statistiques par matière</h1>
                    <p>Suivez vos résultats détaillés dans chaque matière enseignée.</p>
                </div>
                <div class="header-user-card">
                    <strong><?= htmlspecialchars($studentData['prenom'] . ' ' . $studentData['nom']) ?></strong>
                    <span>Performance scolaire</span>
                </div>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Résultats par matière</h2>
                </div>

                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Matière</th>
                                <th>Notes obtenues</th>
                                <th>Coefficients</th>
                                <th>Moyenne pondérée</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($performanceBySubject) > 0): ?>
                                <?php foreach ($performanceBySubject as $matiere => $data): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($matiere) ?></strong></td>
                                        <td>
                                            <?php
                                            $notesStr = implode(', ', array_map(function($note, $coeff) {
                                                return $note . '/' . $coeff;
                                            }, $data['notes'], $data['coefficients']));
                                            echo htmlspecialchars($notesStr);
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $coeffsStr = implode(', ', $data['coefficients']);
                                            echo htmlspecialchars($coeffsStr);
                                            ?>
                                        </td>
                                        <td><strong><?= $data['moyenne'] ?>/20</strong></td>
                                        <td>
                                            <?php if ($data['moyenne'] >= 10): ?>
                                                <span class="badge badge-success">Réussi</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">À améliorer</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding: 24px;">Aucune note disponible pour le moment.</td>
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