<?php
/**
 * Saisie des notes - SIGES
 */
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Professeur') {
    header("Location: ../../index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../models/Teacher.php';
require_once '../../models/Student.php';
require_once '../../models/Grade.php';

$database     = new Database();
$db           = $database->getConnection();
$teacherModel = new Teacher($db);
$studentModel = new Student($db);
$gradeModel   = new Grade($db);

$profData       = $teacherModel->getProfileByLogin($_SESSION['user_login']);
$assignedClasses = $teacherModel->getAssignedClasses($profData['Id_Professeur'])->fetchAll(PDO::FETCH_ASSOC);
$selected_classe = isset($_GET['id_classe']) ? intval($_GET['id_classe']) : 0;
if (!$selected_classe && count($assignedClasses) > 0) {
    $selected_classe = $assignedClasses[0]['Id_Classe'];
}

$allowed = false;
$selectedClassLabel = 'Classe';
foreach ($assignedClasses as $c) {
    if ($c['Id_Classe'] == $selected_classe) {
        $allowed = true;
        $selectedClassLabel = $c['libelle'] . ' ' . $c['niveau'];
        break;
    }
}
if (!$allowed) {
    header('Location: ' . (count($assignedClasses) ? 'grades_entry.php?id_classe='.$assignedClasses[0]['Id_Classe'] : 'dashboard.php'));
    exit();
}

$allEvaluations = $gradeModel->getTeacherEvaluations($profData['Id_Professeur']);
$id_evaluation  = isset($_GET['id_evaluation']) ? intval($_GET['id_evaluation']) : null;
if (!$id_evaluation && count($allEvaluations) > 0) {
    $id_evaluation = $allEvaluations[0]['Id_Evaluation'];
}

if ($id_evaluation) {
    $stmtStudents = $db->prepare(
        "SELECT e.id_Etudiant, e.nom, e.prenom, eff.note
         FROM etudiant e
         LEFT JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant AND eff.Id_Evaluation = :id_ev
         WHERE e.Id_Classe = :id_c
         ORDER BY e.nom ASC"
    );
    $stmtStudents->execute(['id_ev' => $id_evaluation, 'id_c' => $selected_classe]);
    $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);
} else {
    $students = [];
}

// Message après enregistrement
$statusMessage = '';
$statusType    = 'info';
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $statusMessage = 'Notes enregistrées avec succès pour ' . htmlspecialchars($selectedClassLabel) . '.';
        $statusType    = 'success';
    } elseif ($_GET['status'] === 'error') {
        $statusMessage = 'Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.';
        $statusType    = 'error';
    }
}

$active_page = 'grades_entry';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Saisie des notes - SIGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        .toast{display:flex;align-items:center;gap:12px;padding:14px 20px;border-radius:12px;font-size:.95rem;font-weight:500;margin-top:16px;animation:slideIn .35s ease}
        .toast-success{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7}
        .toast-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
        @keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
        .grade-input-row input[type="number"]{width:120px;padding:10px 14px;border-radius:10px;border:1px solid #d1d5db;font-size:1rem;text-align:center;transition:border-color .2s,box-shadow .2s}
        .grade-input-row input[type="number"]:focus{outline:none;border-color:#2E86AB;box-shadow:0 0 0 4px rgba(46,134,171,.12)}
        .grade-input-row input.note-valid{border-color:#10B981;background:#f0fdf4}
        .grade-input-row input.note-warn{border-color:#F59E0B;background:#fffbeb}
        .grade-input-row input.note-fail{border-color:#EF4444;background:#fef2f2}
        .save-bar{position:sticky;bottom:24px;display:flex;align-items:center;justify-content:space-between;background:#1A3C5A;color:white;padding:16px 24px;border-radius:16px;gap:20px;box-shadow:0 8px 32px rgba(26,60,90,.25);z-index:10}
        .save-bar span{font-size:.95rem;opacity:.85}
        .save-bar button{background:#F29100;color:white;border:none;padding:12px 28px;border-radius:10px;font-weight:700;font-size:1rem;cursor:pointer;transition:background .2s}
        .save-bar button:hover{background:#d97d00}
        .note-count{background:rgba(255,255,255,.15);border-radius:8px;padding:4px 12px;font-size:.9rem;font-weight:600}
    </style>
</head>
<body>
<div class="student-shell">
    <?php include '_sidebar.php'; ?>

    <main class="student-main">
        <section class="page-header page-header-reclamation">
            <div>
                <p class="eyebrow">Saisie des notes</p>
                <h1><?= htmlspecialchars($selectedClassLabel) ?></h1>
                <p>Choisissez l'évaluation et saisissez les notes des étudiants.</p>
                <?php if ($statusMessage): ?>
                    <div class="toast toast-<?= $statusType ?>">
                        <i class='bx <?= $statusType==='success'?'bx-check-circle':'bx-x-circle' ?>'></i>
                        <?= $statusMessage ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="header-user-card">
                <strong><?= htmlspecialchars($profData['prenom'].' '.$profData['nom']) ?></strong>
                <span><?= htmlspecialchars($profData['nom_matiere']) ?></span>
            </div>
        </section>

        <!-- Sélection -->
        <section class="section-block">
            <div class="section-title-row"><h2>Sélection de l'évaluation</h2></div>
            <div class="form-box" style="max-width:640px;">
                <form method="GET">
                    <div class="form-group">
                        <label>Classe</label>
                        <select name="id_classe" onchange="this.form.submit()" style="max-width:none;">
                            <?php foreach ($assignedClasses as $c): ?>
                                <option value="<?= $c['Id_Classe'] ?>" <?= $selected_classe==$c['Id_Classe']?'selected':'' ?>>
                                    <?= htmlspecialchars($c['libelle'].' '.$c['niveau']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Évaluation à saisir</label>
                        <select name="id_evaluation" onchange="this.form.submit()" style="max-width:none;">
                            <option value="">-- Choisir une évaluation --</option>
                            <?php foreach ($allEvaluations as $evalItem): ?>
                                <option value="<?= $evalItem['Id_Evaluation'] ?>" <?= $id_evaluation==$evalItem['Id_Evaluation']?'selected':'' ?>>
                                    <?= htmlspecialchars($evalItem['matiere']) ?> · S<?= $evalItem['semestre'] ?> · <?= $evalItem['date_eval'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (isset($_GET['id_evaluation'])): ?>
                        <input type="hidden" name="id_evaluation" value="<?= intval($_GET['id_evaluation']) ?>">
                    <?php endif; ?>
                </form>
                <?php if (!$id_evaluation): ?>
                    <div class="form-hint" style="margin-top:16px;">
                        Aucune évaluation disponible. <a href="dashboard.php?id_classe=<?= $selected_classe ?>" style="color:#2E86AB;font-weight:600;">Créer une évaluation →</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Grille de notes -->
        <?php if ($id_evaluation): ?>
        <section class="section-block">
            <div class="section-title-row">
                <h2>Grille de saisie <span id="countBadge" style="font-size:.85rem;font-weight:500;color:#64748b;margin-left:8px;"></span></h2>
                <span style="font-size:.85rem;color:#64748b;">Pas : 0.25 · Plage : 0 – 20</span>
            </div>

            <form action="../../controllers/GradeController.php" method="POST" id="gradesForm">
                <input type="hidden" name="id_evaluation" value="<?= $id_evaluation ?>">
                <input type="hidden" name="id_classe" value="<?= $selected_classe ?>">

                <div class="table-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Rang</th>
                                <th>Étudiant</th>
                                <th style="text-align:center;">Note / 20</th>
                                <th style="text-align:center;">Mention</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $idx = 1; foreach ($students as $s): ?>
                                <tr class="grade-input-row">
                                    <td style="color:#94a3b8;font-weight:600;"><?= $idx++ ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($s['nom']) ?></strong>
                                        <?= htmlspecialchars($s['prenom']) ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <input type="number"
                                               name="notes[<?= $s['id_Etudiant'] ?>]"
                                               value="<?= $s['note'] !== null ? $s['note'] : '' ?>"
                                               step="0.25" min="0" max="20"
                                               placeholder="—"
                                               class="<?= $s['note']!==null?($s['note']>=10?'note-valid':($s['note']>=8?'note-warn':'note-fail')):'' ?>"
                                               oninput="colorNote(this); updateCount()">
                                    </td>
                                    <td id="mention_<?= $s['id_Etudiant'] ?>" style="text-align:center;font-size:.85rem;">
                                        <?php
                                        if ($s['note'] !== null) {
                                            $n = floatval($s['note']);
                                            if ($n >= 16) echo '<span style="color:#059669;font-weight:700;">Très bien</span>';
                                            elseif ($n >= 14) echo '<span style="color:#10B981;font-weight:700;">Bien</span>';
                                            elseif ($n >= 12) echo '<span style="color:#F59E0B;font-weight:700;">Assez bien</span>';
                                            elseif ($n >= 10) echo '<span style="color:#64748b;font-weight:700;">Passable</span>';
                                            else echo '<span style="color:#EF4444;font-weight:700;">Ajourné</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="save-bar">
                    <div>
                        <span>Évaluation prête à être enregistrée</span><br>
                        <span class="note-count" id="filledCount">0 / <?= count($students) ?> notes saisies</span>
                    </div>
                    <div style="display:flex;gap:12px;align-items:center;">
                        <a href="dashboard.php?id_classe=<?= $selected_classe ?>" style="color:rgba(255,255,255,.7);font-size:.9rem;">Annuler</a>
                        <button type="submit">
                            <i class='bx bx-save' style="margin-right:6px;"></i>Enregistrer les notes
                        </button>
                    </div>
                </div>
            </form>
        <!-- Bouton export PDF -->
        <?php if ($id_evaluation): ?>
        <section class="section-block">
            <div class="section-title-row"><h2>Exporter</h2></div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="export_notes.php?id_classe=<?= $selected_classe ?>&id_evaluation=<?= $id_evaluation ?>"
                   target="_blank"
                   style="display:inline-flex;align-items:center;gap:8px;padding:12px 22px;background:#1A3C5A;color:white;border-radius:10px;text-decoration:none;font-weight:700;font-size:.93rem;">
                    <i class='bx bx-download'></i>Aperçu PDF de cette évaluation
                </a>
                <a href="export_notes.php?id_classe=<?= $selected_classe ?>&id_evaluation=<?= $id_evaluation ?>&auto=1"
                   target="_blank"
                   style="display:inline-flex;align-items:center;gap:8px;padding:12px 22px;background:#f1f5f9;color:#1A3C5A;border:1px solid #e2e8f0;border-radius:10px;text-decoration:none;font-weight:700;font-size:.93rem;">
                    <i class='bx bx-printer'></i>Imprimer directement
                </a>
            </div>
        </section>
        <?php endif; ?>
        <?php else: ?>
        <section class="section-block">
            <div class="form-hint">Sélectionnez une évaluation pour afficher la liste des étudiants.</div>
        </section>
        <?php endif; ?>
    </main>
</div>

<script>
function colorNote(input) {
    input.classList.remove('note-valid','note-warn','note-fail');
    const v = parseFloat(input.value);
    if (!isNaN(v)) {
        if (v >= 10) input.classList.add('note-valid');
        else if (v >= 8) input.classList.add('note-warn');
        else input.classList.add('note-fail');
    }
    // Mise à jour mention
    const row = input.closest('tr');
    const tdId = row ? row.querySelector('td:last-child') : null;
    if (tdId && !isNaN(v)) {
        let label = '';
        if (v >= 16) label = '<span style="color:#059669;font-weight:700;">Très bien</span>';
        else if (v >= 14) label = '<span style="color:#10B981;font-weight:700;">Bien</span>';
        else if (v >= 12) label = '<span style="color:#F59E0B;font-weight:700;">Assez bien</span>';
        else if (v >= 10) label = '<span style="color:#64748b;font-weight:700;">Passable</span>';
        else label = '<span style="color:#EF4444;font-weight:700;">Ajourné</span>';
        tdId.innerHTML = label;
    }
}

function updateCount() {
    const inputs = document.querySelectorAll('input[type="number"][name^="notes"]');
    let filled = 0;
    inputs.forEach(i => { if (i.value !== '') filled++; });
    document.getElementById('filledCount').textContent = filled + ' / ' + inputs.length + ' notes saisies';
    document.getElementById('countBadge').textContent = '(' + inputs.length + ' élèves)';
}

updateCount();

const t = document.querySelector('.toast');
if (t) setTimeout(()=>{t.style.opacity='0';t.style.transition='opacity .5s';setTimeout(()=>t.remove(),500)},6000);
</script>
</body>
</html>
