<?php
require_once '../../config/auth.php';
requireRole('Etudiant');
require_once '../../config/database.php';
require_once '../../models/Student.php';
require_once '../../models/Schedule.php';

$database = new Database();
$db = $database->getConnection();
$studentModel = new Student($db);
$scheduleModel = new Schedule($db);

$profile = $studentModel->getProfileByLogin($_SESSION['user_login']);
$mySchedule = $scheduleModel->getByClasse($profile['Id_Classe'])->fetchAll(PDO::FETCH_ASSOC);

$days = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
$palette = [
    ['bg'=>'#dbeafe','border'=>'#3b82f6','text'=>'#1e3a8a'],
    ['bg'=>'#dcfce7','border'=>'#22c55e','text'=>'#14532d'],
    ['bg'=>'#fef3c7','border'=>'#f59e0b','text'=>'#78350f'],
    ['bg'=>'#ffe4e6','border'=>'#f43f5e','text'=>'#881337'],
    ['bg'=>'#f3e8ff','border'=>'#a855f7','text'=>'#581c87'],
    ['bg'=>'#e0f2fe','border'=>'#0ea5e9','text'=>'#0c4a6e'],
];

$grid = [];
$timeSlots = [];
foreach ($mySchedule as $slot) {
    $timeKey = substr($slot['heure_debut'], 0, 5) . ' – ' . substr($slot['heure_fin'], 0, 5);
    if (!in_array($timeKey, $timeSlots, true)) {
        $timeSlots[] = $timeKey;
    }
    $grid[$timeKey][$slot['jour']][] = $slot;
}

usort($timeSlots, fn($a, $b) => strtotime(explode(' – ', $a)[0]) - strtotime(explode(' – ', $b)[0]));

function getColor($id) {
    global $palette;
    return $palette[intval($id) % count($palette)];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Export emploi du temps — SIGES</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f7fa; color: #1a1a2e; }
        .no-print { background: #1A3C5A; color: white; padding: 14px 28px; display: flex; justify-content: space-between; gap: 16px; position: sticky; top: 0; z-index: 100; }
        .no-print h1 { font-size: 1rem; font-weight: 700; }
        .btn-back { background: rgba(255,255,255,.16); color: white; border: 1px solid rgba(255,255,255,.3); padding: 9px 18px; border-radius: 10px; text-decoration: none; font-size: .88rem; }
        .btn-print { background: #F29100; color: white; border: none; padding: 9px 22px; border-radius: 8px; font-size: .9rem; font-weight: 700; cursor: pointer; }
        .doc { max-width: 1080px; margin: 28px auto; background: white; border-radius: 14px; padding: 34px 40px 40px; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
        .doc-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #1A3C5A; padding-bottom: 18px; margin-bottom: 24px; }
        .doc-header .left h2 { font-size: 1.4rem; color: #1A3C5A; margin-bottom: 5px; }
        .doc-header .left p { color: #64748b; font-size: .9rem; margin-top: 4px; }
        .doc-header .right { text-align: right; }
        .badge { display: inline-block; background: #1A3C5A; color: white; padding: 5px 16px; border-radius: 999px; font-size: .8rem; font-weight: 700; letter-spacing: .04em; }
        .doc-header .right p { color: #94a3b8; font-size: .82rem; margin-top: 8px; }
        .sched-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .sched-table th, .sched-table td { border: 1px solid #e2e8f0; vertical-align: top; padding: 0; }
        .sched-table thead th { background: #1A3C5A; color: white; padding: 12px 8px; font-size: .82rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; text-align: center; }
        .sched-table thead th:first-child { width: 90px; background: #122b40; }
        .time-cell { background: #f8fafc; text-align: center; font-size: .78rem; font-weight: 700; color: #64748b; padding: 12px 6px; border-right: 2px solid #e2e8f0; }
        .day-cell { min-height: 78px; padding: 8px; }
        .slot-card { border-radius: 9px; border-left: 4px solid; padding: 10px 12px; margin-bottom: 6px; font-size: .82rem; line-height: 1.4; }
        .slot-card .s-subject { font-weight: 700; margin-bottom: 4px; }
        .day-highlight { background: rgba(56, 189, 248, 0.08); }
        .sched-table th.day-highlight { background: #dbeafe; color: #1A3C5A; }
        .slot-card .s-meta { opacity: .78; font-size: .78rem; }
        .empty-cell { color: #94a3b8; text-align: center; padding: 18px 4px; }
        .legend { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 22px; }
        .legend-item { display: flex; align-items: center; gap: 8px; background: #f8fafc; padding: 8px 12px; border-radius: 10px; font-size: .82rem; color: #475569; }
        .legend-dot { width: 12px; height: 12px; border-radius: 3px; flex-shrink: 0; }
        .doc-footer { margin-top: 28px; padding-top: 16px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: .78rem; color: #94a3b8; }
        .doc-footer .signature-box { width: 180px; text-align: center; border-top: 1px solid #334155; padding-top: 8px; color: #64748b; }
        @media print { body { background: white; } .no-print { display: none !important; } .doc { max-width: 100%; margin: 0; box-shadow: none; border-radius: 0; padding: 12mm 10mm; } @page { size: A4 landscape; margin: 0; } .slot-card { padding: 8px 10px; } .sched-table th, .sched-table td { border-color: #d1d5db; } }
    </style>
</head>
<body>
<div class="no-print">
    <h1>📄 Export PDF — Emploi du temps</h1>
    <div style="display:flex;gap:10px;align-items:center;">
        <button onclick="window.print()" class="btn-print">🖨️ Imprimer / Télécharger</button>
    </div>
</div>
<div class="doc">
    <div class="doc-header">
        <div class="left">
            <h2>Emploi du temps — <?= htmlspecialchars($profile['prenom'].' '.$profile['nom']) ?></h2>
            <p>Classe <?= htmlspecialchars($profile['nom_classe']) ?> · <?= htmlspecialchars($profile['niveau']) ?></p>
        </div>
        <div class="right">
            <span class="badge">Étudiant</span>
            <p>Généré le <?= date('d/m/Y à H:i') ?></p>
        </div>
    </div>
    <?php if (count($mySchedule) > 0): ?>
        <table class="sched-table">
            <thead>
                <tr>
                    <th>Horaire</th>
                    <?php foreach ($days as $day): ?>
                        <th class="<?= in_array($day, ['Lundi','Mardi','Mercredi','Jeudi','Samedi']) ? 'day-highlight' : '' ?>"><?= $day ?></th>
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
                                        <div class="slot-card" style="background:<?= $col['bg'] ?>;border-color:<?= $col['border'] ?>;color:<?= $col['text'] ?>;">
                                            <div class="s-subject"><?= htmlspecialchars($slot['matiere_nom']) ?></div>
                                            <div class="s-meta">Prof. <?= htmlspecialchars($slot['prof_nom']) ?></div>
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
        foreach ($mySchedule as $slot) {
            if (!isset($legendItems[$slot['Id_Matiere']])) {
                $legendItems[$slot['Id_Matiere']] = [
                    'label' => $slot['matiere_nom'],
                    'color' => getColor($slot['Id_Matiere'])
                ];
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
        <p style="text-align:center;color:#94a3b8;padding:40px;">Aucun créneau trouvé pour votre classe.</p>
    <?php endif; ?>
    <div class="doc-footer">
        <div>
            <p>Document généré par SIGES — Confidentiel</p>
            <p>© <?= date('Y') ?> SIGES</p>
        </div>
        <div class="signature-box">Signature & cachet</div>
    </div>
</div>
</body>
</html>
