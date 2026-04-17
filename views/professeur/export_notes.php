<?php
/**
 * Export PDF des notes - SIGES
 * Génère une page imprimable (Ctrl+P → Enregistrer en PDF)
 */
require_once '../../config/auth.php';
requireRole('Professeur');
require_once '../../config/database.php';
require_once '../../models/Teacher.php';
require_once '../../models/Grade.php';

$database     = new Database();
$db           = $database->getConnection();
$teacherModel = new Teacher($db);
$gradeModel   = new Grade($db);

$profData        = $teacherModel->getProfileByLogin($_SESSION['user_login']);
$id_prof         = $profData['Id_Professeur'];
$assignedClasses = $teacherModel->getAssignedClasses($id_prof)->fetchAll(PDO::FETCH_ASSOC);

$selected_classe = isset($_GET['id_classe']) ? intval($_GET['id_classe']) : ($assignedClasses[0]['Id_Classe'] ?? 0);
$id_evaluation   = isset($_GET['id_evaluation']) ? intval($_GET['id_evaluation']) : null;
$semestre        = isset($_GET['semestre']) ? intval($_GET['semestre']) : null;

// Label classe
$classeLabel = '';
foreach ($assignedClasses as $c) {
    if ($c['Id_Classe'] == $selected_classe) {
        $classeLabel = $c['libelle'] . ' — ' . $c['niveau'];
        break;
    }
}

// Évaluations du prof
$allEvals = $gradeModel->getTeacherEvaluations($id_prof);

// Mode 1 : export d'une évaluation spécifique
if ($id_evaluation) {
    $stmtS = $db->prepare(
        "SELECT e.id_Etudiant, e.nom, e.prenom, COALESCE(eff.note,'') as note
         FROM etudiant e
         LEFT JOIN effectue eff ON e.id_Etudiant = eff.id_Etudiant AND eff.Id_Evaluation = :id_ev
         WHERE e.Id_Classe = :id_c ORDER BY e.nom ASC"
    );
    $stmtS->execute(['id_ev' => $id_evaluation, 'id_c' => $selected_classe]);
    $students = $stmtS->fetchAll(PDO::FETCH_ASSOC);

    // Info éval
    $evalInfo = null;
    foreach ($allEvals as $ev) {
        if ($ev['Id_Evaluation'] == $id_evaluation) { $evalInfo = $ev; break; }
    }
    $exportTitle = 'Notes — ' . ($evalInfo ? $evalInfo['matiere'].' · S'.$evalInfo['semestre'].' · '.$evalInfo['date_eval'] : 'Évaluation');
    $mode = 'eval';

// Mode 2 : bulletin complet (moyennes par matière)
} else {
    $students = $gradeModel->getRankingByClasse($selected_classe);
    $subjectStats = $gradeModel->getSubjectAveragesForClass($selected_classe);
    $exportTitle  = 'Bulletin de notes' . ($semestre ? ' — Semestre '.$semestre : '');
    $mode = 'bulletin';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($exportTitle) ?> · SIGES</title>
    <style>
        /* ── Reset print ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            color: #1a1a2e;
            background: #f5f7fa;
        }

        /* ── Barre d'actions (masquée à l'impression) ── */
        .no-print {
            background: #1A3C5A;
            color: white;
            padding: 14px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .no-print h1 { font-size: 1rem; font-weight: 600; }
        .no-print .actions { display: flex; gap: 10px; }
        .btn-back {
            background: rgba(255,255,255,.15);
            color: white;
            border: 1px solid rgba(255,255,255,.3);
            padding: 8px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: .88rem;
            cursor: pointer;
        }
        .btn-print {
            background: #F29100;
            color: white;
            border: none;
            padding: 9px 22px;
            border-radius: 8px;
            font-size: .9rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .btn-print:hover { background: #d97d00; }

        /* ── Document ── */
        .doc {
            max-width: 900px;
            margin: 28px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,.1);
            padding: 40px 48px 48px;
        }

        /* En-tête */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #1A3C5A;
            padding-bottom: 20px;
            margin-bottom: 28px;
        }
        .doc-header .left h2 {
            font-size: 1.4rem;
            color: #1A3C5A;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .doc-header .left p { color: #64748b; font-size: .92rem; margin-top: 3px; }
        .doc-header .right { text-align: right; }
        .doc-header .right .badge {
            background: #1A3C5A;
            color: white;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .doc-header .right p { color: #94a3b8; font-size: .82rem; margin-top: 8px; }

        /* Infos bloc */
        .info-row {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }
        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 18px;
            flex: 1;
            min-width: 160px;
        }
        .info-box label { font-size: .75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; display: block; margin-bottom: 4px; }
        .info-box span  { font-size: .95rem; font-weight: 700; color: #1A3C5A; }

        /* Tableau */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: .9rem;
        }
        thead tr { background: #1A3C5A; color: white; }
        thead th { padding: 11px 14px; text-align: left; font-weight: 600; font-size: .82rem; text-transform: uppercase; letter-spacing: .06em; }
        thead th:last-child { text-align: center; }
        tbody tr { border-bottom: 1px solid #f1f5f9; }
        tbody tr:nth-child(even) { background: #fafbfc; }
        tbody td { padding: 10px 14px; color: #334155; }
        tbody td:last-child { text-align: center; font-weight: 700; }

        .note-pass  { color: #059669; }
        .note-warn  { color: #d97706; }
        .note-fail  { color: #dc2626; }
        .note-empty { color: #94a3b8; }

        /* Pied de page */
        .doc-footer {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .doc-footer p { font-size: .8rem; color: #94a3b8; }
        .signature-box { border-top: 1px solid #334155; width: 180px; padding-top: 6px; font-size: .8rem; color: #64748b; text-align: center; }

        /* Stats globales */
        .stats-row { display: flex; gap: 16px; margin-bottom: 24px; }
        .stat-mini { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; text-align: center; }
        .stat-mini .val { font-size: 1.4rem; font-weight: 800; color: #1A3C5A; }
        .stat-mini .lbl { font-size: .75rem; color: #94a3b8; margin-top: 2px; }

        /* ── Impression ── */
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .doc { max-width: 100%; margin: 0; box-shadow: none; border-radius: 0; padding: 20mm 16mm; }
            @page { size: A4 portrait; margin: 0; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<!-- Barre d'actions -->
<div class="no-print">
    <h1>📄 Aperçu avant export — <?= htmlspecialchars($exportTitle) ?></h1>
    <div class="actions">
        <a href="javascript:history.back()" class="btn-back">← Retour</a>
        <button class="btn-print" onclick="window.print()">
            🖨️ Exporter / Imprimer PDF
        </button>
    </div>
</div>

<!-- Document -->
<div class="doc">
    <!-- En-tête -->
    <div class="doc-header">
        <div class="left">
            <h2>SIGES — Système de Gestion des Étudiants</h2>
            <p><?= htmlspecialchars($exportTitle) ?></p>
            <p>Classe : <strong><?= htmlspecialchars($classeLabel) ?></strong></p>
        </div>
        <div class="right">
            <span class="badge">SIGES <?= date('Y') ?></span>
            <p>Généré le <?= date('d/m/Y à H:i') ?></p>
            <p>Prof. <?= htmlspecialchars($profData['prenom'].' '.$profData['nom']) ?></p>
        </div>
    </div>

    <?php if ($mode === 'eval' && $evalInfo): ?>
    <!-- Infos évaluation -->
    <div class="info-row">
        <div class="info-box"><label>Matière</label><span><?= htmlspecialchars($evalInfo['matiere']) ?></span></div>
        <div class="info-box"><label>Date</label><span><?= htmlspecialchars($evalInfo['date_eval']) ?></span></div>
        <div class="info-box"><label>Semestre</label><span>Semestre <?= $evalInfo['semestre'] ?></span></div>
        <div class="info-box"><label>Coefficient</label><span><?= $evalInfo['coefficient'] ?></span></div>
        <div class="info-box"><label>Effectif</label><span><?= count($students) ?> étudiants</span></div>
    </div>

    <?php
        // Calcul stats rapides
        $notesFiltered = array_filter($students, fn($s) => $s['note'] !== '');
        $noteValues    = array_map(fn($s) => floatval($s['note']), $notesFiltered);
        $avg  = count($noteValues) ? round(array_sum($noteValues)/count($noteValues), 2) : '—';
        $max  = count($noteValues) ? max($noteValues) : '—';
        $min  = count($noteValues) ? min($noteValues) : '—';
        $pass = count(array_filter($noteValues, fn($n) => $n >= 10));
    ?>
    <div class="stats-row">
        <div class="stat-mini"><div class="val"><?= $avg ?></div><div class="lbl">Moyenne</div></div>
        <div class="stat-mini"><div class="val" style="color:#059669;"><?= $max ?></div><div class="lbl">Meilleure note</div></div>
        <div class="stat-mini"><div class="val" style="color:#dc2626;"><?= $min ?></div><div class="lbl">Note la plus basse</div></div>
        <div class="stat-mini"><div class="val"><?= $pass ?>/<?= count($noteValues) ?></div><div class="lbl">Admis (≥10)</div></div>
    </div>

    <!-- Tableau notes éval -->
    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Note / 20</th>
                <th>Mention</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($students as $s):
                $n = $s['note'] !== '' ? floatval($s['note']) : null;
                $cls = $n === null ? 'note-empty' : ($n >= 10 ? 'note-pass' : ($n >= 8 ? 'note-warn' : 'note-fail'));
                $mention = $n === null ? '—' : ($n >= 16 ? 'Très bien' : ($n >= 14 ? 'Bien' : ($n >= 12 ? 'Assez bien' : ($n >= 10 ? 'Passable' : 'Ajourné'))));
            ?>
            <tr>
                <td style="color:#94a3b8;"><?= $i++ ?></td>
                <td><?= htmlspecialchars($s['nom']) ?></td>
                <td><?= htmlspecialchars($s['prenom']) ?></td>
                <td class="<?= $cls ?>"><?= $n !== null ? number_format($n, 2) : '—' ?></td>
                <td class="<?= $cls ?>"><?= $mention ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php elseif ($mode === 'bulletin'): ?>
    <!-- Classement général -->
    <?php
        $withNotes = array_filter($students, fn($s) => $s['moyenne_gen'] > 0);
        $avgGen    = count($withNotes) ? round(array_sum(array_column((array)$withNotes,'moyenne_gen'))/count($withNotes),2) : '—';
        $admis     = count(array_filter($students, fn($s) => $s['moyenne_gen'] >= 10));
    ?>
    <div class="stats-row">
        <div class="stat-mini"><div class="val"><?= count($students) ?></div><div class="lbl">Étudiants</div></div>
        <div class="stat-mini"><div class="val"><?= $avgGen ?>/20</div><div class="lbl">Moyenne générale</div></div>
        <div class="stat-mini"><div class="val" style="color:#059669;"><?= $admis ?></div><div class="lbl">Admis (≥10)</div></div>
        <div class="stat-mini"><div class="val" style="color:#dc2626;"><?= count($students)-$admis ?></div><div class="lbl">Ajournés</div></div>
    </div>

    <table>
        <thead>
            <tr><th>Rang</th><th>Nom</th><th>Prénom</th><th>Moyenne / 20</th><th>Résultat</th></tr>
        </thead>
        <tbody>
            <?php $rank=1; foreach ($students as $s):
                $mg  = floatval($s['moyenne_gen']);
                $cls = $mg >= 10 ? 'note-pass' : 'note-fail';
                $res = $mg >= 10 ? 'Admis' : 'Ajourné';
            ?>
            <tr>
                <td style="font-weight:700;color:<?= $rank<=3?'#1A3C5A':'#94a3b8' ?>;"><?= $rank++ ?></td>
                <td><?= htmlspecialchars($s['nom']) ?></td>
                <td><?= htmlspecialchars($s['prenom']) ?></td>
                <td class="<?= $cls ?>"><?= number_format($mg,2) ?></td>
                <td class="<?= $cls ?>"><?= $res ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (!empty($subjectStats)): ?>
    <h3 style="font-size:.95rem;color:#1A3C5A;margin-bottom:12px;margin-top:8px;">Moyennes par matière</h3>
    <table>
        <thead>
            <tr><th>Matière</th><th>Coefficient</th><th>Moyenne classe</th><th>Admis</th><th>Ajournés</th></tr>
        </thead>
        <tbody>
            <?php foreach ($subjectStats as $sub): ?>
            <tr>
                <td><?= htmlspecialchars($sub['matiere']) ?></td>
                <td style="text-align:center;"><?= $sub['coefficient'] ?></td>
                <td class="<?= floatval($sub['moyenne_mat'])>=10?'note-pass':'note-fail' ?>"><?= number_format(floatval($sub['moyenne_mat']),2) ?></td>
                <td style="text-align:center;color:#059669;font-weight:700;"><?= $sub['admis'] ?></td>
                <td style="text-align:center;color:#dc2626;font-weight:700;"><?= $sub['ajourne'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Pied de page -->
    <div class="doc-footer">
        <div>
            <p>Document généré automatiquement par SIGES</p>
            <p style="margin-top:4px;">© <?= date('Y') ?> — Confidentiel</p>
        </div>
        <div style="text-align:right;">
            <div class="signature-box">Signature &amp; cachet</div>
        </div>
    </div>
</div>

<script>
// Ouverture automatique de la boîte d'impression si le paramètre auto=1 est présent
<?php if (isset($_GET['auto'])): ?>
window.addEventListener('load', () => setTimeout(() => window.print(), 600));
<?php endif; ?>
</script>
</body>
</html>
