<?php
require_once '../../config/auth.php';
requireRole('Admin');
require_once '../../config/database.php';
require_once '../../models/Schedule.php';

$database = new Database();
$db = $database->getConnection();

$scheduleModel = new Schedule($db);
$allSchedules = $scheduleModel->getAllSchedules()->fetchAll(PDO::FETCH_ASSOC);

// Organiser les horaires
$times = [];
$days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$grid = [];

foreach ($allSchedules as $schedule) {
    $times[] = $schedule['heure_debut'] . ' - ' . $schedule['heure_fin'];
    if (!isset($grid[$schedule['heure_debut'] . ' - ' . $schedule['heure_fin']])) {
        $grid[$schedule['heure_debut'] . ' - ' . $schedule['heure_fin']] = array_fill_keys($days, []);
    }
    $grid[$schedule['heure_debut'] . ' - ' . $schedule['heure_fin']][$schedule['jour']][] = $schedule;
}

$times = array_unique($times);
usort($times, function($a, $b) {
    return strtotime(explode(' - ', $a)[0]) - strtotime(explode(' - ', $b)[0]);
});

$subjectPalette = [
    ['bg' => 'rgba(56, 189, 248, 0.18)', 'border' => 'rgba(56, 189, 248, 0.34)', 'text' => '#0f172a'],
    ['bg' => 'rgba(34, 197, 94, 0.16)', 'border' => 'rgba(34, 197, 94, 0.32)', 'text' => '#0f172a'],
    ['bg' => 'rgba(251, 191, 36, 0.16)', 'border' => 'rgba(251, 191, 36, 0.30)', 'text' => '#0f172a'],
    ['bg' => 'rgba(249, 115, 22, 0.14)', 'border' => 'rgba(249, 115, 22, 0.28)', 'text' => '#0f172a'],
    ['bg' => 'rgba(168, 85, 247, 0.15)', 'border' => 'rgba(168, 85, 247, 0.28)', 'text' => '#0f172a'],
    ['bg' => 'rgba(129, 140, 248, 0.14)', 'border' => 'rgba(129, 140, 248, 0.28)', 'text' => '#0f172a']
];

function getScheduleColor($id) {
    global $subjectPalette;
    $key = intval($id) % count($subjectPalette);
    return $subjectPalette[$key];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Emploi du temps - SIGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f9fafb;
            padding: 24px;
        }
        .export-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .export-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
        }
        .export-title h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .export-title p {
            font-size: 14px;
            color: #6b7280;
        }
        .export-actions {
            display: flex;
            gap: 12px;
        }
        .btn-print {
            padding: 10px 24px;
            background: #1A3C5A;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        .btn-print:hover {
            background: #0f2a42;
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }
        .schedule-table th {
            background: #f3f4f6;
            color: #1f2937;
            padding: 16px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }
        .schedule-table th.day-highlight {
            background: rgba(56,189,248,0.12);
            color: #1A3C5A;
        }
        .schedule-table td {
            padding: 16px;
            border: 1px solid #d1d5db;
            vertical-align: top;
            font-size: 14px;
        }
        .schedule-table td.day-highlight {
            background: rgba(56,189,248,0.08);
        }
        .time-cell {
            background: #f9fafb;
            font-weight: 600;
            color: #1f2937;
            min-width: 120px;
        }
        .schedule-slot {
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 8px;
            font-size: 13px;
            border-left: 3px solid;
            background-color: #f3f4f6;
        }
        .schedule-slot:last-child {
            margin-bottom: 0;
        }
        .schedule-slot strong {
            display: block;
            margin-bottom: 2px;
        }
        .schedule-slot small {
            color: #6b7280;
            font-size: 12px;
        }
        @media print {
            body { background: white; padding: 0; }
            .export-container { box-shadow: none; padding: 20px; }
            .export-actions { display: none; }
            .export-header { margin-bottom: 20px; padding-bottom: 16px; }
        }
    </style>
</head>
<body>
    <div class="export-container">
        <div class="export-header">
            <div class="export-title">
                <h1>📅 Emploi du temps complet</h1>
                <p>Aperçu de tous les créneaux - <?= date('d/m/Y') ?></p>
            </div>
            <div class="export-actions">
                <button class="btn-print" onclick="window.print()">🖨️ Imprimer / Télécharger</button>
            </div>
        </div>

        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Horaire</th>
                    <?php foreach ($days as $day): ?>
                        <th class="<?= in_array($day, ['Lundi','Mardi','Mercredi','Jeudi','Samedi']) ? 'day-highlight' : '' ?>">
                            <?= $day ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($times as $time): ?>
                    <tr>
                        <td class="time-cell"><?= $time ?></td>
                        <?php foreach ($days as $day):
                            $slots = $grid[$time][$day] ?? [];
                            $dayClass = in_array($day, ['Lundi','Mardi','Mercredi','Jeudi','Samedi']) ? 'day-highlight' : '';
                        ?>
                            <td class="<?= $dayClass ?>">
                                <?php foreach ($slots as $slot):
                                    $color = getScheduleColor($slot['Id_Creneau']);
                                ?>
                                    <div class="schedule-slot" style="background-color: <?= $color['bg'] ?>; border-left-color: <?= $color['border'] ?>; color: <?= $color['text'] ?>;">
                                        <strong><?= htmlspecialchars($slot['matiere_nom']) ?></strong>
                                        <small><?= htmlspecialchars($slot['classe_nom']) ?> (<?= htmlspecialchars($slot['classe_niveau']) ?>)</small>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Auto-print si paramètre auto=1
        if (new URLSearchParams(window.location.search).get('auto') === '1') {
            window.addEventListener('load', function() {
                setTimeout(() => window.print(), 500);
            });
        }
    </script>
</body>
</html>
