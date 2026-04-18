²<?php
/**
 * Export PDF de l'emploi du temps - SIGES
 */
require_once '../../config/auth.php';
requireRole('Professeur');
require_once '../../config/database.php';
require_once '../../models/Teacher.php';
require_once '../../models/Schedule.php';

$database      = new Database();
$db            = $database->getConnection();
$teacherModel  = new Teacher($db);
$scheduleModel = new Schedule($db);

$profData   = $teacherModel->getProfileByLogin($_SESSION['user_login']);
$mySchedule = $scheduleModel->getByProfessor($profData['Id_Professeur'])->fetchAll(PDO::FETCH_ASSOC);

$days = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
$palette = [
    ['bg'=>'#dbeafe','border'=>'#3b82f6','text'=>'#1e3a8a'],
    ['bg'=>'#dcfce7','border'=>'#22c55e','text'=>'#14532d'],
    ['bg'=>'#fef3c7','border'=>'#f59e0b','text'=>'#78350f'],
    ['bg'=>'#ffe4e6','border'=>'#f43f5e','text'=>'#881337'],
    ['bg'=>'#f3e8ff','border'=>'#a855f7','text'=>'#581c87'],
    ['bg'=>'#e0f2fe','border'=>'#0ea5e9','text'=>'#0c4a6e'],
];

// Construire la grille
$grid      = [];
$timeSlots = [];
foreach ($mySchedule as $slot) {
    $tk = substr($slot['heure_debut'],0,5).' – '.substr($slot['heure_fin'],0,5);
    if (!in_array($tk, $timeSlots, true)) $timeSlots[] = $tk;
    $grid[$tk][$slot['jour']][] = $slot;
}
usort($timeSlots, fn($a,$b) => strtotime(explode(' – ',$a)[0]) - strtotime(explode(' – ',$b)[0]));

function getColor($id) {
    global $palette;
    return $palette[intval($id) % count($palette)];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Emploi du temps — <?= htmlspecialchars($profData['prenom'].' '.$profData['nom']) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; background: #f5f7fa; color: #1a1a2e; }

        /* ── Barre d'actions ── */
        .no-print {
            background: #1A3C5A; color: white;
            padding: 14px 32px;
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            position: sticky; top: 0; z-index: 100;
        }
        .no-print h1 { font-size: 1rem; font-weight: 600; }
        .btn-back  { background:rgba(255,255,255,.15);color:white;border:1px solid rgba(255,255,255,.3);padding:8px 18px;border-radius:8px;text-decoration:none;font-size:.88rem; }
        .btn-print { background:#F29100;color:white;border:none;padding:9px 22px;border-radius:8px;font-size:.9rem;font-weight:700;cursor:pointer; }

        /* ── Document ── */
        .doc { max-width: 1000px; margin: 28px auto; background: white; border-radius: 12px; padding: 36px 44px 44px; box-shadow: 0 4px 24px rgba(0,0,0,.1); }

        .doc-header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid #1A3C5A; padding-bottom:18px; margin-bottom:24px; }
        .doc-header .left h2 { font-size:1.3rem; color:#1A3C5A; font-weight:700; margin-bottom:4px; }
        .doc-header .left p  { color:#64748b; font-size:.88rem; margin-top:3px; }
        .doc-header .right   { text-align:right; }
        .badge { background:#1A3C5A; color:white; padding:4px 14px; border-radius:20px; font-size:.78rem; font-weight:700; letter-spacing:.05em; }
        .doc-header .right p { color:#94a3b8; font-size:.8rem; margin-top:8px; }

        /* ── Grille EDT ── */
        .sched-table { width:100%; border-collapse: collapse; table-layout: fixed; }
        .sched-table th, .sched-table td { border: 1px solid #e2e8f0; padding: 0; vertical-align: top; }
        .sched-table thead th {
            background: #1A3C5A; color: white;
            padding: 10px 8px; font-size: .82rem; font-weight: 700;
            text-align: center; text-transform: uppercase; letter-spacing: .06em;
        }
        .sched-table thead th:first-child { width: 80px; background: #122b40; }
        .time-cell {
            background: #f8fafc; text-align: center; font-size: .78rem;
            font-weight: 700; color: #64748b; padding: 10px 4px;
            border-right: 2px solid #e2e8f0;
        }
        .day-cell { padding: 4px; min-height: 70px; }
        .day-highlight { background: rgba(56, 189, 248, 0.08); }
        .sched-table th.day-highlight { background: #dbeafe; color: #1A3C5A; }
        .slot-card {
            border-radius: 7px; border-left: 4px solid;
            padding: 8px 10px; margin-bottom: 3px;
            font-size: .82rem; line-height: 1.4;
        }
        .slot-card .s-subject { font-weight: 700; margin-bottom: 2px; }
        .slot-card .s-class   { font-size: .76rem; opacity: .8; }
        .empty-cell { color: #cbd5e1; text-align: center; font-size: .78rem; padding: 20px 4px; }

        /* Légende */
        .legend { display:flex; flex-wrap:wrap; gap:10px; margin-top:20px; }
        .legend-item { display:flex; align-items:center; gap:7px; font-size:.8rem; color:#475569; }
        .legend-dot  { width:10px; height:10px; border-radius:3px; }

        /* Pied de page */
        .doc-footer { margin-top:28px; padding-top:14px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-end; }
        .doc-footer p { font-size:.78rem; color:#94a3b8; }
        .signature-box { border-top:1px solid #334155; width:160px; padding-top:6px; font-size:.78rem; color:#64748b; text-align:center; }

        /* ── Impression ── */
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .doc { max-width:100%; margin:0; box-shadow:none; border-radius:0; padding:14mm 12mm; }
            @page { size: A4 landscape; margin: 0; }
            .sched-table { font-size: 9px; }
            .slot-card { padding: 5px 7px; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <h1>📄 Emploi du temps — <?= htmlspecialchars($profData['prenom'].' '.$profData['nom']) ?></h1>
    <div style="display:flex;gap:10px;">
        <a href="javascript:history.back()" class="btn-back">← Retour</a>
    </div>
</div>

<div class="doc">
    <div class="doc-header">
        <div class="left">
            <h2>SIGES — Emploi du Temps</h2>
            <p>Prof. <strong><?= htmlspecialchars($profData['prenom'].' '.$profData['nom']) ?></strong></p>
            <p>Matière : <?= htmlspecialchars($profData['nom_matiere']) ?></p>
        </div>
        <div class="right">
            <span class="badge">Année <?= date('Y') ?></span>
            <p>Généré le <?= date('d/m/Y à H:i') ?></p>
        </div>
    </div>

    <?php if (count($mySchedule) > 0): ?>
    <table class="sched-table">
        <thead>
            <tr>
                <th>Horaire</th>
                <?php foreach ($days as $d): ?>
                    <th class="<?= in_array($d, ['Lundi','Mardi','Mercredi','Jeudi','Samedi']) ? 'day-highlight' : '' ?>"><?= $d ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($timeSlots as $time): ?>
            <tr>
                <td class="time-cell"><?= htmlspecialchars($time) ?></td>
                <?php foreach ($days as $day):
                    $slots = $grid[$time][$day] ?? [];
                    $dayClass = in_array($day, ['Lundi','Mardi','Mercredi','Jeudi','Samedi']) ? 'day-highlight' : '';
                ?>
                <td class="day-cell <?= $dayClass ?>">
                    <?php if ($slots): ?>
                        <?php foreach ($slots as $slot):
                            $col = getColor($slot['Id_Matiere']);
                        ?>
                            <div class="slot-card"
                                 style="background:<?= $col['bg'] ?>;border-color:<?= $col['border'] ?>;color:<?= $col['text'] ?>;">
                                <div class="s-subject"><?= htmlspecialchars($slot['matiere_nom']) ?></div>
                                <div class="s-class"><?= htmlspecialchars($slot['classe_nom']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-cell">—</div>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php
    $legendItems = [];
    foreach ($mySchedule as $s) {
        if (!isset($legendItems[$s['Id_Matiere']])) {
            $col = getColor($s['Id_Matiere']);
            $legendItems[$s['Id_Matiere']] = ['label'=>$s['matiere_nom'],'color'=>$col];
        }
    }
    ?>
    <div class="legend">
        <?php foreach ($legendItems as $item): ?>
            <div class="legend-item">
                <span class="legend-dot" style="background:<?= $item['color']['border'] ?>;"></span>
                <?= htmlspecialchars($item['label']) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
        <p style="text-align:center;color:#94a3b8;padding:40px;">Aucun créneau disponible.</p>
    <?php endif; ?>

    <div class="doc-footer">
        <div>
            <p>Document généré par SIGES — Confidentiel</p>
            <p style="margin-top:3px;">© <?= date('Y') ?> — Tous droits réservés</p>
        </div>
        <div class="signature-box">Signature &amp; cachet</div>
    </div>
</div>

<script>
window.addEventListener('load', () => setTimeout(() => window.print(), 600));
</script>
</body>
</html>
