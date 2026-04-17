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
$studentSubjectGrades = $gradeModel->getStudentSubjectGrades($id_etudiant);
$classSubjectStatsList = $gradeModel->getClassSubjectStats($id_classe);
$classement = $gradeModel->getRankingByClasse($id_classe);
$rang = 0;
foreach ($classement as $index => $row) {
    if ($row['id_Etudiant'] == $id_etudiant) {
        $rang = $index + 1;
        break;
    }
}
$classSubjectStats = [];
foreach ($classSubjectStatsList as $stat) {
    $classSubjectStats[$stat['matiere']] = $stat;
}
$moyenneGenerale = $gradeModel->calculateAverage($id_etudiant);
$mention = 'Passable';
if ($moyenneGenerale >= 17) {
    $mention = 'Excellent';
} elseif ($moyenneGenerale >= 14) {
    $mention = 'Très Bien';
} elseif ($moyenneGenerale >= 12) {
    $mention = 'Bien';
} elseif ($moyenneGenerale >= 10) {
    $mention = 'Assez Bien';
}
$absences = 0;
$commentaireConseil = 'Très bon trimestre pour l’élève. À confirmer par un travail régulier et assidu.';
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
            .no-print,
            .student-sidebar,
            .sidebar-nav,
            .profile-box,
            .sidebar-brand,
            .page-header,
            .logout-btn,
            .header-user-card,
            .button-primary {
                display: none !important;
            }

            body {
                background: #fff;
                margin: 0;
                color: #000;
            }

            main.student-main {
                display: block !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .section-block,
            .table-card,
            .bulletin-top,
            .bulletin-info-grid,
            .bulletin-table-card {
                border: none !important;
                box-shadow: none !important;
                background: transparent !important;
            }

            .bulletin-top,
            .bulletin-info-grid,
            .bulletin-table-card,
            .bulletin-comment {
                page-break-inside: avoid !important;
            }

            .bulletin-table,
            .bulletin-table th,
            .bulletin-table td {
                width: 100% !important;
                border: 1px solid #000 !important;
            }

            img {
                max-width: 100% !important;
                height: auto !important;
            }
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
                <div class="bulletin-top">
                    <div class="bulletin-school-info">
                        <h2>SIGES</h2>
                        <p>Adresse • Thies/Senegal</p>
                        <p>Tél : 77 245 15 15 • Site : www.siges.sn</p>
                    </div>
                    <div class="bulletin-title">
                        <h1>BULLETIN DE NOTES DU 1er TRIMESTRE</h1>
                        <p>Année scolaire : <?= date('Y') - 1 ?> - <?= date('Y') ?></p>
                    </div>
                    <div class="bulletin-avatar">
                        <img src="../../assets/img/logo_simple-SAP.png" alt="Logo établissement">
                    </div>
                </div>

                <div class="bulletin-info-grid">
                    <div class="info-row"><span>Nom :</span> <?= htmlspecialchars($studentData['nom'] . ' ' . $studentData['prenom']) ?></div>
                    <div class="info-row"><span>Classe :</span> <?= htmlspecialchars($studentData['nom_classe']) ?> (<?= htmlspecialchars($studentData['niveau']) ?>)</div>
                    <div class="info-row"><span>Section :</span> <?= htmlspecialchars($studentData['niveau']) ?> (<?= count($classement) ?> élèves)</div>
                    <div class="info-row"><span>Rang :</span> <?= $rang ?> / <?= count($classement) ?></div>
                </div>

                <div class="table-card bulletin-table-card">
                    <table class="bulletin-table">
                        <thead>
                            <tr>
                                <th style="background: #001a2c;">Matières</th>
                                <th style="background: #001a2c;">Coef</th>
                                <th style="background: #001a2c;">Note /20</th>
                                <th style="background: #001a2c;">Min</th>
                                <th style="background: #001a2c;">Max</th>
                                <th style="background: #001a2c;">Moy Classe</th>
                                <th style="background: #001a2c;">Appréciations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($studentSubjectGrades) > 0): ?>
                                <?php foreach ($studentSubjectGrades as $grade): ?>
                                    <?php
                                        $stat = $classSubjectStats[$grade['matiere']] ?? null;
                                        $note = is_numeric($grade['note']) ? number_format($grade['note'], 2) : 'N/A';
                                        $numericNote = is_numeric($grade['note']) ? floatval($grade['note']) : null;
                                        $min = $stat ? number_format($stat['note_min'], 2) : 'N/A';
                                        $max = $stat ? number_format($stat['note_max'], 2) : 'N/A';
                                        $moyClasse = $stat ? number_format($stat['moyenne_classe'], 2) : 'N/A';
                                        if ($numericNote === null) {
                                            $appreciation = 'Note non saisie';
                                        } elseif ($numericNote >= 16) {
                                            $appreciation = 'Excellent travail';
                                        } elseif ($numericNote >= 12) {
                                            $appreciation = 'Bon travail';
                                        } elseif ($numericNote >= 10) {
                                            $appreciation = 'Satisfaisant';
                                        } else {
                                            $appreciation = 'Doit s’améliorer';
                                        }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($grade['matiere']) ?></td>
                                        <td><?= htmlspecialchars($grade['coefficient']) ?></td>
                                        <td><?= $note ?></td>
                                        <td><?= $min ?></td>
                                        <td><?= $max ?></td>
                                        <td><?= $moyClasse ?></td>
                                        <td><?= htmlspecialchars($appreciation) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding: 40px;">Aucune note disponible pour le moment.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2"><strong>Moyenne générale</strong></td>
                                <td><strong><?= $moyenneGenerale > 0 ? number_format($moyenneGenerale, 2) . ' / 20' : 'N/A' ?></strong></td>
                                <td colspan="2"><strong>Absences</strong></td>
                                <td colspan="2"><strong><?= $absences ?> demi-journées</strong></td>
                            </tr>
                            <tr>
                                <td colspan="4"><strong>Mention</strong></td>
                                <td colspan="3"><strong><?= htmlspecialchars($mention) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="bulletin-comment">
                    <h3>Appréciations du conseil de classe</h3>
                    <p><?= htmlspecialchars($commentaireConseil) ?></p>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
