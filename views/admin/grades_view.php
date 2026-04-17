<?php
require_once '../../config/auth.php';
requireRole('Admin');
require_once '../../config/database.php';
require_once '../../models/Grade.php';

$database = new Database();
$db = $database->getConnection();
$gradeModel = new Grade($db);

$selected_classe = isset($_GET['id_classe']) ? $_GET['id_classe'] : null;
$selected_matiere = isset($_GET['id_matiere']) ? $_GET['id_matiere'] : null;
$classes = $db->query("SELECT * FROM classe")->fetchAll(PDO::FETCH_ASSOC);

$subjects = [];
$students = [];
$evaluations = [];
$notes = [];
$noteMap = [];
$sheetInfo = ['classe' => '', 'matiere' => '', 'semestre' => '', 'professeur' => ''];
$className = '';
foreach ($classes as $c) {
    if ($selected_classe && $c['Id_Classe'] == $selected_classe) {
        $className = $c['libelle'] . ' ' . $c['niveau'];
        break;
    }
}

if ($selected_classe) {
    $subjects = $gradeModel->getSubjectsByClass($selected_classe);
}

if ($selected_classe && $selected_matiere) {
    $students = $gradeModel->getStudentsByClass($selected_classe);
    $evaluations = $gradeModel->getEvaluationsByClassAndSubject($selected_classe, $selected_matiere);
    $notes = $gradeModel->getNotesByClassAndSubject($selected_classe, $selected_matiere);

    foreach ($notes as $row) {
        if ($row['Id_Evaluation']) {
            $noteMap[$row['id_Etudiant']][$row['Id_Evaluation']] = $row['note'];
        }
    }

    if (!empty($evaluations)) {
        $sheetInfo['matiere'] = $evaluations[0]['matiere'];
        $sheetInfo['semestre'] = $evaluations[0]['semestre'];
        $sheetInfo['professeur'] = $evaluations[0]['professeur'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Fiche de notes - SIGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        .pv-sheet {
            width: 100%;
            color: #1f2937;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .pv-sheet .pv-header {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
        }

        .pv-sheet .pv-header img {
            width: 110px;
            border-radius: 10px;
        }

        .pv-sheet .pv-document-title {
            text-align: center;
        }

        .pv-sheet .pv-document-title h1 {
            margin: 0;
            font-size: 1.35rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .pv-sheet .pv-document-title p {
            margin: 5px 0 0;
            color: #555;
            font-size: 0.95rem;
        }

        .pv-sheet .pv-meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .pv-sheet .pv-meta .meta-item {
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 12px 14px;
            background: #f8fafc;
            font-size: 0.95rem;
        }

        .pv-sheet .pv-meta .meta-item strong {
            display: block;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .pv-sheet .pv-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .pv-sheet .pv-table th,
        .pv-sheet .pv-table td {
            border: 1px solid #d1d5db;
            padding: 10px 8px;
            text-align: center;
            font-size: 0.95rem;
        }

        .pv-sheet .pv-table th {
            background: #1A3C5A;
            color: #ffffff;
            font-weight: 700;
        }

        .pv-sheet .pv-table th.small {
            background: #2E86AB;
            color: #ffffff;
            font-size: 0.8rem;
        }

        .pv-sheet .pv-summary {
            margin-top: 14px;
            display: flex;
            justify-content: flex-end;
            gap: 18px;
            font-size: 0.95rem;
        }

        .pv-sheet .pv-summary strong {
            font-weight: 700;
        }

        @media print {
            body { background: #fff; margin: 0; }
            .student-shell { padding: 0; }
            .student-sidebar, .page-header, .filter-section, .button-primary, .logout-btn, .sidebar-nav { display: none !important; }
            .student-main { margin-left: 0; padding: 0; }
            .section-block { border: none; box-shadow: none; padding: 0; }
            .table-card { box-shadow: none; border: none; }
            .pv-sheet .pv-table th,
            .pv-sheet .pv-table td { border-color: #000; }
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
                    <span>Espace Admin</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php"><i class='bx bx-home'></i>Dashboard</a>
                <a href="users.php"><i class='bx bx-user-circle'></i>Gestion utilisateurs</a>
                <a href="grades_view.php" class="active"><i class='bx bx-book'></i>PV Délibération</a>
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
            <section class="page-header page-header-reclamation">
                <div>
                    <p class="eyebrow">Fiche de notes</p>
                    <h1>Fiche de notes imprimable</h1>
                    <p>Recherchez la fiche de notes pour la classe et la matière sélectionnées, puis imprimez-la.</p>
                </div>
                <div class="header-user-card">
                    <strong>Administrateur</strong>
                    <span>Vision globale</span>
                </div>
            </section>

            <section class="section-block">
                <div class="section-title-row">
                    <h2>Classe sélectionnée</h2>
                </div>
                <div class="filter-section" style="background: rgba(226, 232, 240, 0.8);">
                    <form method="GET" style="display: flex; gap: 10px; align-items: center; width: 100%; flex-wrap: wrap;">
                        <label>Choisir une classe :</label>
                        <select name="id_classe" onchange="this.form.submit()" style="flex:1; min-width:180px;">
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['Id_Classe'] ?>" <?= $selected_classe == $c['Id_Classe'] ? 'selected' : '' ?> >
                                    <?= htmlspecialchars($c['libelle']) ?> (<?= htmlspecialchars($c['niveau']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if ($selected_classe): ?>
                            <label>Choisir une matière :</label>
                            <select name="id_matiere" onchange="this.form.submit()" style="flex:1; min-width:180px;">
                                <option value="">-- Sélectionner la matière --</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s['Id_Matiere'] ?>" <?= $selected_matiere == $s['Id_Matiere'] ? 'selected' : '' ?> >
                                        <?= htmlspecialchars($s['libelle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </form>
                    <button class="button-primary" onclick="window.print()">Imprimer le PV</button>
                </div>

                <?php if ($selected_classe && $selected_matiere && !empty($evaluations)): ?>
                    <div class="pv-sheet">
                        <div class="pv-header">
                            <div>
                                <img src="../../assets/img/logo_simple-SAP.png" alt="SIGES logo">
                            </div>
                            <div class="pv-document-title">
                                <h1>Fiche de notes de la classe de : <?= htmlspecialchars($className) ?></h1>
                                <p>Année scolaire : <?= date('Y') - 1 ?> / <?= date('Y') ?></p>
                            </div>
                            <div></div>
                        </div>

                        <div class="pv-meta">
                            <div class="meta-item">
                                <strong>Matière</strong>
                                <?= htmlspecialchars($sheetInfo['matiere']) ?>
                            </div>
                            <div class="meta-item">
                                <strong>Semestre</strong>
                                <?= htmlspecialchars($sheetInfo['semestre']) ?>
                            </div>
                            <div class="meta-item">
                                <strong>Professeur</strong>
                                <?= htmlspecialchars($sheetInfo['professeur']) ?>
                            </div>
                        </div>

                        <div class="table-card pv-table-card">
                            <table class="pv-table">
                                <thead>
                                    <tr>
                                        <th>Matricule</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <?php foreach ($evaluations as $index => $evaluation): ?>
                                            <th><?= $index === 3 ? 'COMP' : 'D' . ($index + 1) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                    <tr>
                                        <th colspan="3"></th>
                                        <?php foreach ($evaluations as $evaluation): ?>
                                            <th class="small"><?= date('d/m', strtotime($evaluation['date_eval'])) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(str_pad($student['id_Etudiant'], 5, '0', STR_PAD_LEFT)) ?></td>
                                            <td><?= htmlspecialchars($student['nom']) ?></td>
                                            <td><?= htmlspecialchars($student['prenom']) ?></td>
                                            <?php foreach ($evaluations as $evaluation): ?>
                                                <td>
                                                    <?= isset($noteMap[$student['id_Etudiant']][$evaluation['Id_Evaluation']])
                                                        ? htmlspecialchars(number_format($noteMap[$student['id_Etudiant']][$evaluation['Id_Evaluation']], 2))
                                                        : '' ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="pv-summary">
                            <div><strong>Effectif de la classe :</strong> <?= count($students) ?></div>
                        </div>
                    </div>
                <?php elseif ($selected_classe && $selected_matiere): ?>
                    <p>Aucune fiche de notes disponible pour cette matière dans cette classe.</p>
                <?php elseif ($selected_classe): ?>
                    <p>Veuillez sélectionner une matière pour afficher le PV.</p>
                <?php else: ?>
                    <p>Veuillez sélectionner une classe pour afficher le PV.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>