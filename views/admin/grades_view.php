<?php
require_once '../../config/auth.php';
requireRole('Admin');
require_once '../../config/database.php';
require_once '../../models/Grade.php';

$database = new Database();
$db = $database->getConnection();

$pendingReclamations = 0;
try {
    $hasReclamTable = (int) $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'reclamation'")->fetchColumn();
    if ($hasReclamTable) {
        $pendingReclamations = (int) $db->query("SELECT COUNT(*) FROM reclamation WHERE statut = 'En attente'")->fetchColumn();
    }
} catch (PDOException $e) {
    $pendingReclamations = 0;
}

$gradeModel = new Grade($db);

$selected_classe = isset($_GET['id_classe']) ? $_GET['id_classe'] : null;
$classes = $db->query("SELECT * FROM classe")->fetchAll(PDO::FETCH_ASSOC);

$subjects = [];
$students = [];
$className = '';
foreach ($classes as $c) {
    if ($selected_classe && $c['Id_Classe'] == $selected_classe) {
        $className = $c['libelle'] . ' ' . $c['niveau'];
        break;
    }
}

if ($selected_classe) {
    $detailedGrades = $gradeModel->getDetailedGradesByClass($selected_classe);
    $subjects = $gradeModel->getSubjectsByClass($selected_classe);

    // Grouper les notes par étudiant
    $students = [];
    $subjectList = array_column($subjects, 'libelle');
    $subjectCoeffs = array_column($subjects, 'coefficient', 'libelle');

    foreach ($detailedGrades as $grade) {
        $id = $grade['id_Etudiant'];
        if (!isset($students[$id])) {
            $students[$id] = [
                'id_Etudiant' => $grade['id_Etudiant'],
                'nom' => $grade['nom'],
                'prenom' => $grade['prenom'],
                'grades' => [],
                'moyenne_generale' => null
            ];
        }
        $students[$id]['grades'][$grade['matiere']] = $grade['note'];
    }

    // Calculer la moyenne générale pour chaque étudiant
    foreach ($students as &$student) {
        $sum_note_coeff = 0;
        $sum_coeff = 0;
        foreach ($student['grades'] as $matiere => $note) {
            if ($note !== null) {
                $coeff = $subjectCoeffs[$matiere] ?? 1;
                $sum_note_coeff += $note * $coeff;
                $sum_coeff += $coeff;
            }
        }
        if ($sum_coeff > 0) {
            $student['moyenne_generale'] = round($sum_note_coeff / $sum_coeff, 2);
        }
    }

    // Trier les étudiants par moyenne décroissante, NULL en dernier
    $students = array_values($students);
    usort($students, function($a, $b) {
        if ($a['moyenne_generale'] === null && $b['moyenne_generale'] === null) return 0;
        if ($a['moyenne_generale'] === null) return 1;
        if ($b['moyenne_generale'] === null) return -1;
        return $b['moyenne_generale'] <=> $a['moyenne_generale'];
    });
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Fiche de notes - SIGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <script>
        window.downloadSchedulePDF = function(filename = 'emploi-du-temps.pdf') {
            window.print();
        };

        window.downloadNotesPDF = function(filename = 'notes.pdf') {
            window.print();
        };
    </script>
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
            body { background: #fff; margin: 0; color: #0f172a; font-family: Arial, sans-serif; }
            .student-shell { padding: 0; margin: 0; }
            .student-sidebar, .page-header, .filter-section, .button-primary, .logout-btn, .sidebar-nav, .header-user-card { display: none !important; }
            .student-main { margin-left: 0; padding: 0; }
            .section-block { border: none; box-shadow: none; padding: 0; margin: 0; }
            .table-card { box-shadow: none; border: none; background: transparent; }

            .pv-sheet { margin: 0; padding: 0; }
            .pv-header { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #1A3C5A; }
            .pv-header img { width: 52px; height: 52px; }
            .pv-document-title { flex: 1; }
            .pv-document-title h1 { font-size: 1.35rem; margin: 0 0 6px; color: #1A3C5A; line-height: 1.1; }
            .pv-document-title p { margin: 0; font-size: .88rem; color: #475569; }

            .pv-meta { display: flex; flex-wrap: wrap; gap: 16px; margin: 14px 0; padding: 12px 0; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
            .pv-meta .meta-item { flex: 1 1 180px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; }
            .pv-meta .meta-item strong { display: block; font-weight: 700; margin-bottom: 4px; color: #1A3C5A; }

            .pv-sheet .pv-table { margin: 12px 0; border-collapse: collapse; width: 100%; }
            .pv-sheet .pv-table th { background: #1A3C5A !important; color: #fff !important; border: 1px solid #1A3C5A !important; padding: 10px 8px !important; font-weight: 700 !important; font-size: .85rem !important; text-align: center !important; }
            .pv-sheet .pv-table td { border: 1px solid #d1d5db !important; padding: 8px !important; text-align: center !important; font-size: .85rem !important; }
            .pv-sheet .pv-table tr:nth-child(even) { background: #f8fafc; }
            .pv-sheet .pv-summary { margin-top: 14px; justify-content: flex-end; }
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
                        <label for="id_classe">Choisir une classe :</label>
                        <select name="id_classe" id="id_classe" style="flex:1; min-width:180px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                            <option value="">-- Sélectionner une classe --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['Id_Classe'] ?>" <?= $selected_classe == $c['Id_Classe'] ? 'selected' : '' ?> >
                                    <?= htmlspecialchars($c['libelle']) ?> (<?= htmlspecialchars($c['niveau']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="button-primary" style="padding: 8px 16px;">Afficher</button>
                    </form>
                    <?php if ($selected_classe): ?>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-left:auto;">
                            <button onclick="downloadNotesPDF('pv-deliberation-<?= htmlspecialchars($selected_classe) ?>.pdf')" class="button-primary" style="padding: 8px 16px;border:none;cursor:pointer;">Télécharger PDF</button>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($selected_classe && !empty($students)): ?>
                    <div class="pv-sheet">
                        <div class="pv-header">
                            <div>
                                <img src="../../assets/img/logo_simple-SAP.png" alt="SIGES logo">
                            </div>
                            <div class="pv-document-title">
                                <h1>PV de délibération — <?= htmlspecialchars($className) ?></h1>
                                <p>Année scolaire : <?= date('Y') - 1 ?> / <?= date('Y') ?></p>
                            </div>
                            <div></div>
                        </div>

                        <div class="pv-meta">
                            <div class="meta-item">
                                <strong>Matières</strong>
                                <?= !empty($subjects) ? htmlspecialchars(implode(', ', array_column($subjects, 'libelle'))) : 'Toutes les matières' ?>
                            </div>
                            <div class="meta-item">
                                <strong>Classe</strong>
                                <?= htmlspecialchars($className) ?>
                            </div>
                            <div class="meta-item">
                                <strong>Effectif</strong>
                                <?= count($students) ?> étudiants
                            </div>
                        </div>

                        <div class="table-card pv-table-card">
                            <table class="pv-table">
                                <thead>
                                    <tr>
                                        <th>Rang</th>
                                        <th>Matricule</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <?php foreach ($subjectList as $subj): ?>
                                            <th><?= htmlspecialchars($subj) ?></th>
                                        <?php endforeach; ?>
                                        <th>Moyenne générale</th>
                                        <th>Résultat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rank = 1; foreach ($students as $student): ?>
                                        <?php
                                            $moyenne = $student['moyenne_generale'] !== null ? number_format($student['moyenne_generale'], 2) : '—';
                                            $isAdmis = $student['moyenne_generale'] !== null && $student['moyenne_generale'] >= 10;
                                        ?>
                                        <tr>
                                            <td><?= $rank++ ?></td>
                                            <td><?= htmlspecialchars(str_pad($student['id_Etudiant'], 5, '0', STR_PAD_LEFT)) ?></td>
                                            <td><?= htmlspecialchars($student['nom']) ?></td>
                                            <td><?= htmlspecialchars($student['prenom']) ?></td>
                                            <?php foreach ($subjectList as $subj): ?>
                                                <?php $note = $student['grades'][$subj] ?? null; ?>
                                                <td class="<?= $note !== null && $note >= 10 ? 'note-pass' : ($note !== null ? 'note-fail' : '') ?>"><?= $note !== null ? number_format($note, 2) : '—' ?></td>
                                            <?php endforeach; ?>
                                            <td class="<?= $isAdmis ? 'note-pass' : 'note-fail' ?>"><?= $moyenne ?></td>
                                            <td class="<?= $isAdmis ? 'note-pass' : 'note-fail' ?>"><?= $isAdmis ? 'Admis' : 'Ajourné' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="pv-summary">
                            <div><strong>Effectif de la classe :</strong> <?= count($students) ?></div>
                        </div>
                    </div>
                <?php elseif ($selected_classe): ?>
                    <p>Aucune fiche de notes disponible pour cette classe.</p>
                <?php else: ?>
                    <p>Veuillez sélectionner une classe pour afficher le PV.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>